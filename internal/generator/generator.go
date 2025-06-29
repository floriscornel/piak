package generator

import (
	"fmt"
	"strings"

	"github.com/floriscornel/piak/internal/analyzer"
	"github.com/floriscornel/piak/internal/config"
	"github.com/floriscornel/piak/internal/parser"
	"github.com/getkin/kin-openapi/openapi3"
)

const arrayType = "array"

// Generator coordinates the entire generation process.
type Generator struct {
	config *config.GeneratorConfig
	parser *parser.OpenAPIParser
	phpGen *PHPGenerator
}

// NewGenerator creates a new Generator instance.
func NewGenerator(cfg *config.GeneratorConfig) (*Generator, error) {
	phpGen, err := NewPHPGenerator(cfg)
	if err != nil {
		return nil, fmt.Errorf("failed to create PHP generator: %w", err)
	}

	return &Generator{
		config: cfg,
		parser: parser.New(cfg.OpenAPI.ValidateSpec, cfg.OpenAPI.ResolveRefs),
		phpGen: phpGen,
	}, nil
}

// Generate performs the complete generation process.
func (g *Generator) Generate() error {
	// Parse the OpenAPI specification
	if g.config.PHP.GenerateDocblocks {
		fmt.Printf("🔍 Parsing OpenAPI specification: %s\n", g.config.InputFile)
	}

	spec, err := g.parser.ParseFile(g.config.InputFile)
	if err != nil {
		return fmt.Errorf("failed to parse OpenAPI specification: %w", err)
	}

	// Analyze the specification
	if g.config.PHP.GenerateDocblocks {
		fmt.Printf("🔬 Analyzing OpenAPI specification...\n")
	}

	analyzer := analyzer.New(spec)
	schemas, err := analyzer.AnalyzeSchemas()
	if err != nil {
		return fmt.Errorf("failed to analyze OpenAPI specification: %w", err)
	}

	if g.config.PHP.GenerateDocblocks {
		info := analyzer.GetInfo()
		fmt.Printf("📊 Found %d schemas in API: %s v%s\n",
			len(schemas), info["title"], info["version"])
	}

	// Convert to new types format
	schemaModels := make(map[string]*config.SchemaModel)
	for name, schema := range schemas {
		schemaModel := &config.SchemaModel{
			Name:         name,
			PHPType:      name, // TODO: Apply proper PHP naming conventions
			OriginalName: name,
			Properties:   convertProperties(schema.Properties, schema.Required),
			Description:  schema.Description,
			IsEnum:       schema.IsEnum,
			EnumValues:   schema.EnumValues,
		}
		schemaModels[name] = schemaModel
	}

	// Create internal model
	internalModel := &config.InternalModel{
		Info: &config.InfoModel{
			Title:       spec.Info.Title,
			Version:     spec.Info.Version,
			Description: spec.Info.Description,
		},
		Schemas: schemaModels,
		Config:  g.config,
	}

	// Generate PHP code
	if g.config.PHP.GenerateDocblocks {
		fmt.Printf("🏗️  Generating PHP code to: %s\n", g.config.OutputDir)
	}

	if genErr := g.phpGen.GenerateFromModel(internalModel); genErr != nil {
		return fmt.Errorf("failed to generate PHP code: %w", genErr)
	}

	if g.config.PHP.GenerateDocblocks {
		fmt.Printf("✅ Successfully generated %d PHP classes\n", len(schemas))
	}

	return nil
}

// Helper function to convert old properties to new format.
func convertProperties(oldProps map[string]*openapi3.SchemaRef, required []string) []*config.Property {
	var properties []*config.Property

	// Create a map for quick required field lookup
	requiredMap := make(map[string]bool)
	for _, reqField := range required {
		requiredMap[reqField] = true
	}

	for name, propRef := range oldProps {
		if propRef.Value != nil {
			isRequired := requiredMap[name]

			// Handle schema references first
			var typeName string
			if propRef.Ref != "" {
				// Extract schema name from reference
				parts := strings.Split(propRef.Ref, "/")
				if len(parts) > 0 {
					typeName = parts[len(parts)-1]
				} else {
					typeName = "mixed"
				}
			} else {
				typeName = mapOpenAPITypeToPHP(propRef.Value)
			}

			phpType := config.PHPType{
				Name:       typeName,
				IsNullable: !isRequired, // Required fields are not nullable
				DocComment: typeName,
			}

			// Handle array types with proper item type detection
			if phpType.Name == "array" && propRef.Value.Items != nil {
				itemType := resolveArrayItemType(propRef.Value.Items)
				phpType.IsArray = true
				phpType.ArrayItemType = &config.PHPType{
					Name:       itemType,
					IsNullable: false,
					DocComment: itemType,
				}
			}

			prop := &config.Property{
				Name:        name,
				PHPType:     phpType,
				OpenAPIType: propRef.Value,
				Required:    isRequired,
				Description: propRef.Value.Description,
			}
			properties = append(properties, prop)
		}
	}

	return properties
}

// Helper function to map OpenAPI types to PHP types.
func mapOpenAPITypeToPHP(schema *openapi3.Schema) string {
	if len(schema.Type.Slice()) == 0 {
		return "mixed"
	}

	schemaType := schema.Type.Slice()[0]
	switch schemaType {
	case "string":
		return "string"
	case "integer":
		return "int"
	case "number":
		return "float"
	case "boolean":
		return "bool"
	case arrayType:
		return arrayType
	case "object":
		return arrayType
	default:
		return "mixed"
	}
}

// Helper function to resolve array item types, including schema references.
func resolveArrayItemType(itemsRef *openapi3.SchemaRef) string {
	if itemsRef == nil {
		return "mixed"
	}

	// Check if it's a reference to a schema
	if itemsRef.Ref != "" {
		// Extract the schema name from the reference
		// Example: "#/components/schemas/Tag" -> "Tag"
		parts := strings.Split(itemsRef.Ref, "/")
		if len(parts) > 0 {
			return parts[len(parts)-1]
		}
	}

	// If not a reference, use the regular type mapping
	if itemsRef.Value != nil {
		return mapOpenAPITypeToPHP(itemsRef.Value)
	}

	return "mixed"
}
