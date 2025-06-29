package generator

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"text/template"

	"github.com/floriscornel/piak/internal/config"
	"github.com/floriscornel/piak/internal/templates"
	"github.com/iancoleman/strcase"
)

// PHPGenerator generates PHP code from OpenAPI specifications.
type PHPGenerator struct {
	config    *config.GeneratorConfig
	templates *template.Template
}

// NewPHPGenerator creates a new PHPGenerator instance.
func NewPHPGenerator(cfg *config.GeneratorConfig) (*PHPGenerator, error) {
	tmpl, err := templates.GetTemplates()
	if err != nil {
		return nil, fmt.Errorf("failed to load templates: %w", err)
	}

	return &PHPGenerator{
		config:    cfg,
		templates: tmpl,
	}, nil
}

// GenerateFromModel generates PHP code from the internal model.
func (g *PHPGenerator) GenerateFromModel(model *config.InternalModel) error {
	// Create output directory
	if err := os.MkdirAll(g.config.OutputDir, 0755); err != nil {
		return fmt.Errorf("failed to create output directory: %w", err)
	}

	// Generate classes for each schema
	for name, schema := range model.Schemas {
		if genErr := g.generateClass(name, schema); genErr != nil {
			return fmt.Errorf("failed to generate class %s: %w", name, genErr)
		}
	}

	// Generate client if requested
	if g.config.GenerateClient {
		if clientErr := g.generateClient(model); clientErr != nil {
			return fmt.Errorf("failed to generate client: %w", clientErr)
		}
	}

	return nil
}

// generateClass generates a single PHP class.
func (g *PHPGenerator) generateClass(name string, schema *config.SchemaModel) error {
	// Generate class content
	content, err := g.generateClassContent(name, schema)
	if err != nil {
		return fmt.Errorf("failed to generate class content: %w", err)
	}

	// Write to file
	filename := fmt.Sprintf("%s%s", name, g.config.PHP.FileExtension)
	filePath := filepath.Join(g.config.OutputDir, filename)

	if writeErr := os.WriteFile(filePath, []byte(content), 0644); writeErr != nil {
		return fmt.Errorf("failed to write file %s: %w", filePath, writeErr)
	}

	fmt.Printf("✅ Generated: %s\n", filename)
	return nil
}

// generateClient generates the API client.
func (g *PHPGenerator) generateClient(model *config.InternalModel) error {
	content, err := g.generateClientContent(model)
	if err != nil {
		return fmt.Errorf("failed to generate client content: %w", err)
	}

	filename := fmt.Sprintf("ApiClient%s", g.config.PHP.FileExtension)
	filePath := filepath.Join(g.config.OutputDir, filename)

	if writeErr := os.WriteFile(filePath, []byte(content), 0644); writeErr != nil {
		return fmt.Errorf("failed to write client file: %w", writeErr)
	}

	fmt.Printf("✅ Generated API Client: %s\n", filename)
	return nil
}

func (g *PHPGenerator) generateClassContent(className string, schema *config.SchemaModel) (string, error) {
	// Prepare template context
	templateData := struct {
		*config.SchemaModel
		Config *config.GeneratorConfig
	}{
		SchemaModel: schema,
		Config:      g.config,
	}

	// Use template to generate content
	var content strings.Builder
	err := g.templates.ExecuteTemplate(&content, "model.php.tmpl", templateData)
	if err != nil {
		return "", fmt.Errorf("failed to execute model template: %w", err)
	}

	return content.String(), nil
}

func (g *PHPGenerator) generateClientContent(model *config.InternalModel) (string, error) {
	// Prepare template context
	templateData := struct {
		*config.InternalModel
		Config *config.GeneratorConfig
	}{
		InternalModel: model,
		Config:        g.config,
	}

	// Use template to generate content
	var content strings.Builder
	err := g.templates.ExecuteTemplate(&content, "client.php.tmpl", templateData)
	if err != nil {
		return "", fmt.Errorf("failed to execute client template: %w", err)
	}

	return content.String(), nil
}

// Legacy methods for backwards compatibility (can be removed later)

func (g *PHPGenerator) generateProperty(content *strings.Builder, prop *config.Property) {
	// This method is now unused - keeping for backwards compatibility
	if g.config.PHP.GenerateDocblocks {
		content.WriteString("\n    /**\n")
		if prop.Description != "" {
			content.WriteString(fmt.Sprintf("     * %s\n", prop.Description))
		}
		content.WriteString(fmt.Sprintf("     * @var %s\n", prop.PHPType.DocComment))
		content.WriteString("     */\n")
	}

	nullable := ""
	if !prop.Required {
		nullable = "?"
	}

	propName := strcase.ToSnake(prop.Name)
	content.WriteString(fmt.Sprintf("    private %s%s $%s;\n", nullable, prop.PHPType.Name, propName))
}

func (g *PHPGenerator) generateConstructor(content *strings.Builder, schema *config.SchemaModel) {
	// This method is now unused - keeping for backwards compatibility
	content.WriteString("\n    public function __construct(\n")

	for i, prop := range schema.Properties {
		propType := prop.PHPType.Name
		if !prop.Required {
			propType = "?" + propType
		}

		propName := strcase.ToSnake(prop.Name)
		content.WriteString(fmt.Sprintf("        %s $%s", propType, propName))

		if !prop.Required {
			content.WriteString(" = null")
		}

		if i < len(schema.Properties)-1 {
			content.WriteString(",")
		}
		content.WriteString("\n")
	}

	content.WriteString("    ) {\n")

	for _, prop := range schema.Properties {
		propName := strcase.ToSnake(prop.Name)
		content.WriteString(fmt.Sprintf("        $this->%s = $%s;\n", propName, propName))
	}

	content.WriteString("    }\n")
}

func (g *PHPGenerator) generateAccessors(content *strings.Builder, prop *config.Property) {
	// This method is now unused - keeping for backwards compatibility
	propName := strcase.ToSnake(prop.Name)
	methodName := strcase.ToCamel(prop.Name)

	// Getter
	content.WriteString(fmt.Sprintf("\n    public function get%s(): %s\n", methodName, prop.PHPType.Name))
	content.WriteString("    {\n")
	content.WriteString(fmt.Sprintf("        return $this->%s;\n", propName))
	content.WriteString("    }\n")

	// Setter
	content.WriteString(fmt.Sprintf("\n    public function set%s(%s $%s): void\n", methodName, prop.PHPType.Name, propName))
	content.WriteString("    {\n")
	content.WriteString(fmt.Sprintf("        $this->%s = $%s;\n", propName, propName))
	content.WriteString("    }\n")
}
