package generator

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"text/template"

	"github.com/floriscornel/piak/internal/templates"
	"github.com/floriscornel/piak/internal/types"
	"github.com/iancoleman/strcase"
)

// PHPGenerator generates PHP code from OpenAPI specifications.
type PHPGenerator struct {
	config    *types.GeneratorConfig
	templates *template.Template
}

// NewPHPGenerator creates a new PHPGenerator instance.
func NewPHPGenerator(cfg *types.GeneratorConfig) (*PHPGenerator, error) {
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
func (g *PHPGenerator) GenerateFromModel(model *types.InternalModel) error {
	// Create output directory if it doesn't exist
	if err := os.MkdirAll(g.config.OutputDir, 0750); err != nil {
		return fmt.Errorf("failed to create output directory: %w", err)
	}

	for name, schema := range model.Schemas {
		if err := g.generateClass(name, schema); err != nil {
			return fmt.Errorf("failed to generate class %s: %w", name, err)
		}
	}

	// Generate API client if requested
	if g.config.GenerateClient {
		if err := g.generateClient(model); err != nil {
			return fmt.Errorf("failed to generate API client: %w", err)
		}
	}

	return nil
}

func (g *PHPGenerator) generateClass(name string, schema *types.SchemaModel) error {
	className := strcase.ToCamel(name)
	fileName := className + g.config.PHP.FileExtension
	filePath := filepath.Join(g.config.OutputDir, fileName)

	// Check if file exists and overwrite is disabled
	if !g.config.Overwrite {
		if _, err := os.Stat(filePath); err == nil {
			fmt.Printf("⚠️  Skipping %s (file exists, overwrite disabled)\n", fileName)
			return nil
		}
	}

	content, err := g.generateClassContent(className, schema)
	if err != nil {
		return fmt.Errorf("failed to generate class content: %w", err)
	}

	if err := os.WriteFile(filePath, []byte(content), 0600); err != nil {
		return fmt.Errorf("failed to write file %s: %w", filePath, err)
	}

	fmt.Printf("✅ Generated: %s\n", fileName)
	return nil
}

func (g *PHPGenerator) generateClient(model *types.InternalModel) error {
	fileName := "ApiClient" + g.config.PHP.FileExtension
	filePath := filepath.Join(g.config.OutputDir, fileName)

	content, err := g.generateClientContent(model)
	if err != nil {
		return fmt.Errorf("failed to generate client content: %w", err)
	}

	if err := os.WriteFile(filePath, []byte(content), 0600); err != nil {
		return fmt.Errorf("failed to write client file: %w", err)
	}

	fmt.Printf("✅ Generated API Client: %s\n", fileName)
	return nil
}

func (g *PHPGenerator) generateClassContent(className string, schema *types.SchemaModel) (string, error) {
	// Set the class name
	schema.Name = className

	// Prepare template context with Config accessible
	templateData := struct {
		*types.SchemaModel
		Config *types.GeneratorConfig
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

func (g *PHPGenerator) generateClientContent(model *types.InternalModel) (string, error) {
	// Prepare template context
	templateData := struct {
		*types.InternalModel
		Config *types.GeneratorConfig
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

func (g *PHPGenerator) generateProperty(content *strings.Builder, prop *types.Property) {
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

func (g *PHPGenerator) generateConstructor(content *strings.Builder, schema *types.SchemaModel) {
	// This method is now unused - keeping for backwards compatibility
	content.WriteString("\n    public function __construct(\n")

	for i, prop := range schema.Properties {
		nullable := ""
		defaultValue := " = null"
		if !prop.Required {
			nullable = "?"
		} else {
			defaultValue = ""
		}

		propName := strcase.ToSnake(prop.Name)
		comma := ","
		if i == len(schema.Properties)-1 {
			comma = ""
		}

		content.WriteString(fmt.Sprintf("        %s%s $%s%s%s\n", nullable, prop.PHPType.Name, propName, defaultValue, comma))
	}

	content.WriteString("    ) {\n")

	for _, prop := range schema.Properties {
		propName := strcase.ToSnake(prop.Name)
		content.WriteString(fmt.Sprintf("        $this->%s = $%s;\n", propName, propName))
	}

	content.WriteString("    }\n")
}

func (g *PHPGenerator) generateAccessors(content *strings.Builder, prop *types.Property) {
	// This method is now unused - keeping for backwards compatibility
	propName := strcase.ToSnake(prop.Name)
	methodName := strcase.ToCamel(prop.Name)

	nullable := ""
	if !prop.Required {
		nullable = "?"
	}

	// Getter
	content.WriteString(fmt.Sprintf("\n    public function get%s(): %s%s\n", methodName, nullable, prop.PHPType.Name))
	content.WriteString("    {\n")
	content.WriteString(fmt.Sprintf("        return $this->%s;\n", propName))
	content.WriteString("    }\n")

	// Setter
	content.WriteString(fmt.Sprintf("\n    public function set%s(%s%s $%s): self\n", methodName, nullable, prop.PHPType.Name, propName))
	content.WriteString("    {\n")
	content.WriteString(fmt.Sprintf("        $this->%s = $%s;\n", propName, propName))
	content.WriteString("        return $this;\n")
	content.WriteString("    }\n")
}
