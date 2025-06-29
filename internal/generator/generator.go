package generator

import (
	"fmt"

	"github.com/floriscornel/piak/internal/analyzer"
	"github.com/floriscornel/piak/internal/parser"
	"github.com/floriscornel/piak/internal/types"
	"github.com/getkin/kin-openapi/openapi3"
)

const arrayType = "array"

// Generator coordinates the entire generation process.
type Generator struct {
	config *types.GeneratorConfig
	parser *parser.OpenAPIParser
	phpGen *PHPGenerator
}

// NewGenerator creates a new Generator instance.
func NewGenerator(cfg *types.GeneratorConfig) *Generator {
	return &Generator{
		config: cfg,
		parser: parser.New(cfg.OpenAPI.ValidateSpec, cfg.OpenAPI.ResolveRefs),
		phpGen: NewPHPGenerator(cfg),
	}
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
	schemaModels := make(map[string]*types.SchemaModel)
	for name, schema := range schemas {
		schemaModel := &types.SchemaModel{
			Name:         name,
			PHPType:      name, // TODO: Apply proper PHP naming conventions
			OriginalName: name,
			Properties:   convertProperties(schema.Properties),
			Description:  schema.Description,
			IsEnum:       schema.IsEnum,
			EnumValues:   schema.EnumValues,
		}
		schemaModels[name] = schemaModel
	}

	// Create internal model
	internalModel := &types.InternalModel{
		Info: &types.InfoModel{
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
func convertProperties(oldProps map[string]*openapi3.SchemaRef) []*types.Property {
	var properties []*types.Property

	for name, propRef := range oldProps {
		if propRef.Value != nil {
			phpType := types.PHPType{
				Name:       mapOpenAPITypeToPHP(propRef.Value),
				IsNullable: false, // TODO: determine nullability
				DocComment: mapOpenAPITypeToPHP(propRef.Value),
			}

			prop := &types.Property{
				Name:        name,
				PHPType:     phpType,
				OpenAPIType: propRef.Value,
				Required:    false, // TODO: determine from required array
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
