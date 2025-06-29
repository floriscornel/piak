package generator

import (
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"text/template"

	"github.com/floriscornel/piak/internal/config"
	"github.com/floriscornel/piak/internal/templates"
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
	// Create output directory structure
	if err := g.createDirectoryStructure(); err != nil {
		return fmt.Errorf("failed to create directory structure: %w", err)
	}

	// Copy OpenAPI spec to output directory
	if err := g.copyOpenAPISpec(); err != nil {
		return fmt.Errorf("failed to copy OpenAPI spec: %w", err)
	}

	// Generate composer.json
	if err := g.generateComposerJSON(model); err != nil {
		return fmt.Errorf("failed to generate composer.json: %w", err)
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

	// Generate tests if requested
	if g.config.GenerateTests {
		if testErr := g.generateTests(model); testErr != nil {
			return fmt.Errorf("failed to generate tests: %w", testErr)
		}
	}

	// Generate README
	if err := g.generateReadme(model); err != nil {
		return fmt.Errorf("failed to generate README: %w", err)
	}

	return nil
}

// createDirectoryStructure creates the src/ and tests/ directories.
func (g *PHPGenerator) createDirectoryStructure() error {
	dirs := []string{
		g.config.OutputDir,
		filepath.Join(g.config.OutputDir, "src"),
		filepath.Join(g.config.OutputDir, "tests"),
	}

	for _, dir := range dirs {
		if err := os.MkdirAll(dir, 0755); err != nil {
			return fmt.Errorf("failed to create directory %s: %w", dir, err)
		}
	}

	return nil
}

// copyOpenAPISpec copies the OpenAPI specification file to the output directory.
func (g *PHPGenerator) copyOpenAPISpec() error {
	// Read the source OpenAPI file
	sourceData, err := os.ReadFile(g.config.InputFile)
	if err != nil {
		return fmt.Errorf("failed to read OpenAPI spec: %w", err)
	}

	// Determine the output filename
	sourcePath := filepath.Base(g.config.InputFile)
	destPath := filepath.Join(g.config.OutputDir, sourcePath)

	// Write to destination
	if writeErr := os.WriteFile(destPath, sourceData, 0644); writeErr != nil {
		return fmt.Errorf("failed to write OpenAPI spec: %w", writeErr)
	}

	return nil
}

// generateComposerJSON creates a composer.json file for the package.
func (g *PHPGenerator) generateComposerJSON(model *config.InternalModel) error {
	// Prepare template context
	templateData := struct {
		PackageName   string
		Description   string
		JSONNamespace string
	}{
		PackageName:   g.generatePackageName(),
		Description:   g.cleanDescription(model.Info.Description),
		JSONNamespace: g.prepareJSONNamespace(),
	}

	// Use template to generate content
	var content strings.Builder
	err := g.templates.ExecuteTemplate(&content, "composer.json.tmpl", templateData)
	if err != nil {
		return fmt.Errorf("failed to execute composer.json template: %w", err)
	}

	filePath := filepath.Join(g.config.OutputDir, "composer.json")
	return os.WriteFile(filePath, []byte(content.String()), 0644)
}

// Helper methods for composer.json generation.
func (g *PHPGenerator) generatePackageName() string {
	namespaceParts := strings.Split(g.config.Namespace, "\\")
	vendor := strings.ToLower(namespaceParts[0])
	var packageName string
	if len(namespaceParts) > 1 {
		packageName = fmt.Sprintf("%s/%s", vendor, strings.ToLower(strings.Join(namespaceParts[1:], "-")))
	} else {
		packageName = fmt.Sprintf("%s/sdk", vendor)
	}
	return packageName
}

func (g *PHPGenerator) prepareJSONNamespace() string {
	return strings.ReplaceAll(g.config.Namespace, "\\", "\\\\")
}

func (g *PHPGenerator) cleanDescription(description string) string {
	cleaned := strings.ReplaceAll(description, "\n", " ")
	cleaned = strings.ReplaceAll(cleaned, "\r", " ")
	cleaned = strings.ReplaceAll(cleaned, "\"", "\\\"")
	cleaned = strings.TrimSpace(cleaned)
	if cleaned == "" {
		cleaned = "Generated API client"
	}
	return cleaned
}

// generateClass generates a single PHP class in the src/ directory.
func (g *PHPGenerator) generateClass(name string, schema *config.SchemaModel) error {
	// Generate class content
	content, err := g.generateClassContent(name, schema)
	if err != nil {
		return fmt.Errorf("failed to generate class content: %w", err)
	}

	// Write to src/ directory
	filename := fmt.Sprintf("%s.php", name)
	filePath := filepath.Join(g.config.OutputDir, "src", filename)

	if writeErr := os.WriteFile(filePath, []byte(content), 0644); writeErr != nil {
		return fmt.Errorf("failed to write file %s: %w", filePath, writeErr)
	}

	return nil
}

// generateClient generates the API client in the src/ directory.
func (g *PHPGenerator) generateClient(model *config.InternalModel) error {
	content, err := g.generateClientContent(model)
	if err != nil {
		return fmt.Errorf("failed to generate client content: %w", err)
	}

	filename := "ApiClient.php"
	filePath := filepath.Join(g.config.OutputDir, "src", filename)

	if writeErr := os.WriteFile(filePath, []byte(content), 0644); writeErr != nil {
		return fmt.Errorf("failed to write client file: %w", writeErr)
	}

	return nil
}

// generateTests generates test files in the tests/ directory.
func (g *PHPGenerator) generateTests(model *config.InternalModel) error {
	// Generate model tests
	for name, schema := range model.Schemas {
		if err := g.generateModelTest(name, schema); err != nil {
			return fmt.Errorf("failed to generate test for %s: %w", name, err)
		}
	}

	// Generate API client tests if client is generated
	if g.config.GenerateClient {
		if err := g.generateAPIClientTest(model); err != nil {
			return fmt.Errorf("failed to generate API client tests: %w", err)
		}
	}

	// Generate PHPUnit configuration
	if err := g.generatePhpUnitConfig(); err != nil {
		return fmt.Errorf("failed to generate PHPUnit config: %w", err)
	}

	return nil
}

// generateModelTest generates a test for a model class.
func (g *PHPGenerator) generateModelTest(name string, schema *config.SchemaModel) error {
	testContent := g.generateModelTestContent(name, schema)
	filename := fmt.Sprintf("%sTest.php", name)
	filePath := filepath.Join(g.config.OutputDir, "tests", filename)

	return os.WriteFile(filePath, []byte(testContent), 0644)
}

// generateAPIClientTest generates tests for the API client.
func (g *PHPGenerator) generateAPIClientTest(model *config.InternalModel) error {
	testContent := g.generateAPIClientTestContent(model)
	filename := "ApiClientTest.php"
	filePath := filepath.Join(g.config.OutputDir, "tests", filename)

	return os.WriteFile(filePath, []byte(testContent), 0644)
}

// generatePHPUnitConfig generates phpunit.xml configuration.
func (g *PHPGenerator) generatePhpUnitConfig() error {
	// Use template to generate content
	var content strings.Builder
	err := g.templates.ExecuteTemplate(&content, "phpunit.xml.tmpl", nil)
	if err != nil {
		return fmt.Errorf("failed to execute phpunit.xml template: %w", err)
	}

	filePath := filepath.Join(g.config.OutputDir, "phpunit.xml")
	return os.WriteFile(filePath, []byte(content.String()), 0644)
}

func (g *PHPGenerator) generateClassContent(_ string, schema *config.SchemaModel) (string, error) {
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

// generateModelTestContent creates test content for a model class.
func (g *PHPGenerator) generateModelTestContent(name string, schema *config.SchemaModel) string {
	// Prepare template context
	templateData := struct {
		ClassName     string
		VarName       string
		TestNamespace string
		UseNamespace  string
		SpecFilename  string
		Schema        *config.SchemaModel
	}{
		ClassName:     name,
		VarName:       strings.ToLower(name),
		TestNamespace: g.config.Namespace + "\\Tests",
		UseNamespace:  g.config.Namespace,
		SpecFilename:  filepath.Base(g.config.InputFile),
		Schema:        schema,
	}

	// Use template to generate content
	var content strings.Builder
	err := g.templates.ExecuteTemplate(&content, "model-test.php.tmpl", templateData)
	if err != nil {
		// Fall back to error message if template fails
		return fmt.Sprintf("// Template error: %v", err)
	}

	return content.String()
}

// generateAPIClientTestContent creates test content for the API client.
func (g *PHPGenerator) generateAPIClientTestContent(_ *config.InternalModel) string {
	// Prepare template context
	templateData := struct {
		TestNamespace string
		UseNamespace  string
		SpecFilename  string
	}{
		TestNamespace: g.config.Namespace + "\\Tests",
		UseNamespace:  g.config.Namespace,
		SpecFilename:  filepath.Base(g.config.InputFile),
	}

	// Use template to generate content
	var content strings.Builder
	err := g.templates.ExecuteTemplate(&content, "client-test.php.tmpl", templateData)
	if err != nil {
		// Fall back to error message if template fails
		return fmt.Sprintf("// Template error: %v", err)
	}

	return content.String()
}

// generateReadme creates a README.md file for the generated package.
func (g *PHPGenerator) generateReadme(_ *config.InternalModel) error {
	// Prepare template context
	templateData := struct {
		PackageName    string
		Namespace      string
		SpecFilename   string
		GenerateClient bool
	}{
		PackageName:    g.generatePackageName(),
		Namespace:      g.config.Namespace,
		SpecFilename:   filepath.Base(g.config.InputFile),
		GenerateClient: g.config.GenerateClient,
	}

	// Use template to generate content
	var content strings.Builder
	err := g.templates.ExecuteTemplate(&content, "README.md.tmpl", templateData)
	if err != nil {
		return fmt.Errorf("failed to execute README template: %w", err)
	}

	return os.WriteFile(filepath.Join(g.config.OutputDir, "README.md"), []byte(content.String()), 0644)
}
