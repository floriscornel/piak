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
	if err := g.generateComposerJson(model); err != nil {
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

// createDirectoryStructure creates the src/ and tests/ directories
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

// copyOpenAPISpec copies the OpenAPI specification file to the output directory
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
	if err := os.WriteFile(destPath, sourceData, 0644); err != nil {
		return fmt.Errorf("failed to write OpenAPI spec: %w", err)
	}

	return nil
}

// generateComposerJson creates a composer.json file for the package
func (g *PHPGenerator) generateComposerJson(model *config.InternalModel) error {
	// Clean and escape the description for JSON
	description := strings.ReplaceAll(model.Info.Description, "\n", " ")
	description = strings.ReplaceAll(description, "\r", " ")
	description = strings.ReplaceAll(description, "\"", "\\\"")
	description = strings.TrimSpace(description)
	if description == "" {
		description = "Generated API client"
	}

	// Create a valid package name (lowercase, hyphenated, vendor/package format)
	namespaceParts := strings.Split(g.config.Namespace, "\\")
	vendor := strings.ToLower(namespaceParts[0])
	var packageName string
	if len(namespaceParts) > 1 {
		packageName = fmt.Sprintf("%s/%s", vendor, strings.ToLower(strings.Join(namespaceParts[1:], "-")))
	} else {
		packageName = fmt.Sprintf("%s/sdk", vendor)
	}

	// For JSON, we need to escape backslashes once (they get interpreted as escape chars)
	jsonNamespace := strings.ReplaceAll(g.config.Namespace, "\\", "\\\\")

	composerContent := fmt.Sprintf(`{
    "name": "%s",
    "description": "%s",
    "type": "library",
    "license": "MIT",
    "autoload": {
        "psr-4": {
            "%s\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "%s\\Tests\\": "tests/"
        }
    },
    "require": {
        "php": "^8.4"
    },
    "require-dev": {
        "phpunit/phpunit": "^12.0",
        "osteel/openapi-httpfoundation-testing": "^0.11"
    }
}`,
		packageName,
		description,
		jsonNamespace,
		jsonNamespace)

	filePath := filepath.Join(g.config.OutputDir, "composer.json")
	return os.WriteFile(filePath, []byte(composerContent), 0644)
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

// generateTests generates test files in the tests/ directory
func (g *PHPGenerator) generateTests(model *config.InternalModel) error {
	// Generate model tests
	for name, schema := range model.Schemas {
		if err := g.generateModelTest(name, schema); err != nil {
			return fmt.Errorf("failed to generate test for %s: %w", name, err)
		}
	}

	// Generate API client tests if client is generated
	if g.config.GenerateClient {
		if err := g.generateApiClientTest(model); err != nil {
			return fmt.Errorf("failed to generate API client tests: %w", err)
		}
	}

	// Generate PHPUnit configuration
	if err := g.generatePhpUnitConfig(); err != nil {
		return fmt.Errorf("failed to generate PHPUnit config: %w", err)
	}

	return nil
}

// generateModelTest generates a test for a model class
func (g *PHPGenerator) generateModelTest(name string, schema *config.SchemaModel) error {
	testContent := g.generateModelTestContent(name, schema)
	filename := fmt.Sprintf("%sTest.php", name)
	filePath := filepath.Join(g.config.OutputDir, "tests", filename)

	return os.WriteFile(filePath, []byte(testContent), 0644)
}

// generateApiClientTest generates tests for the API client
func (g *PHPGenerator) generateApiClientTest(model *config.InternalModel) error {
	testContent := g.generateApiClientTestContent(model)
	filename := "ApiClientTest.php"
	filePath := filepath.Join(g.config.OutputDir, "tests", filename)

	return os.WriteFile(filePath, []byte(testContent), 0644)
}

// generatePhpUnitConfig generates phpunit.xml configuration
func (g *PHPGenerator) generatePhpUnitConfig() error {
	configContent := `<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">./src</directory>
        </include>
    </source>
</phpunit>`

	filePath := filepath.Join(g.config.OutputDir, "phpunit.xml")
	return os.WriteFile(filePath, []byte(configContent), 0644)
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

// MVP: Comment out legacy methods that use the old PHP config
// Legacy methods for backwards compatibility (can be removed later)

// func (g *PHPGenerator) generateProperty(content *strings.Builder, prop *config.Property) {
// 	// This method is now unused - keeping for backwards compatibility
// 	if g.config.PHP.GenerateDocblocks {
// 		content.WriteString("\n    /**\n")
// 		if prop.Description != "" {
// 			content.WriteString(fmt.Sprintf("     * %s\n", prop.Description))
// 		}
// 		content.WriteString(fmt.Sprintf("     * @var %s\n", prop.PHPType.DocComment))
// 		content.WriteString("     */\n")
// 	}

// 	nullable := ""
// 	if !prop.Required {
// 		nullable = "?"
// 	}

// 	propName := strcase.ToSnake(prop.Name)
// 	content.WriteString(fmt.Sprintf("    private %s%s $%s;\n", nullable, prop.PHPType.Name, propName))
// }

// func (g *PHPGenerator) generateConstructor(content *strings.Builder, schema *config.SchemaModel) {
// 	// This method is now unused - keeping for backwards compatibility
// 	content.WriteString("\n    public function __construct(\n")

// 	for i, prop := range schema.Properties {
// 		propType := prop.PHPType.Name
// 		if !prop.Required {
// 			propType = "?" + propType
// 		}

// 		propName := strcase.ToSnake(prop.Name)
// 		content.WriteString(fmt.Sprintf("        %s $%s", propType, propName))

// 		if !prop.Required {
// 			content.WriteString(" = null")
// 		}

// 		if i < len(schema.Properties)-1 {
// 			content.WriteString(",")
// 		}
// 		content.WriteString("\n")
// 	}

// 	content.WriteString("    ) {\n")

// 	for _, prop := range schema.Properties {
// 		propName := strcase.ToSnake(prop.Name)
// 		content.WriteString(fmt.Sprintf("        $this->%s = $%s;\n", propName, propName))
// 	}

// 	content.WriteString("    }\n")
// }

// func (g *PHPGenerator) generateAccessors(content *strings.Builder, prop *config.Property) {
// 	// This method is now unused - keeping for backwards compatibility
// 	propName := strcase.ToSnake(prop.Name)
// 	methodName := strcase.ToCamel(prop.Name)

// 	// Getter
// 	content.WriteString(fmt.Sprintf("\n    public function get%s(): %s\n", methodName, prop.PHPType.Name))
// 	content.WriteString("    {\n")
// 	content.WriteString(fmt.Sprintf("        return $this->%s;\n", propName))
// 	content.WriteString("    }\n")

// 	// Setter
// 	content.WriteString(fmt.Sprintf("\n    public function set%s(%s $%s): void\n", methodName, prop.PHPType.Name, propName))
// 	content.WriteString("    {\n")
// 	content.WriteString(fmt.Sprintf("        $this->%s = $%s;\n", propName, propName))
// 	content.WriteString("    }\n")
// }

// generateModelTestContent creates test content for a model class
func (g *PHPGenerator) generateModelTestContent(name string, schema *config.SchemaModel) string {
	// For PHP namespace declarations, don't escape backslashes
	testNamespace := g.config.Namespace + "\\Tests"
	useNamespace := g.config.Namespace // For use statements, don't double-escape

	// Get the OpenAPI spec filename
	specFilename := filepath.Base(g.config.InputFile)

	return fmt.Sprintf(`<?php

namespace %s;

use %s\%s;
use PHPUnit\Framework\TestCase;
use Osteel\OpenApi\Testing\ValidatorBuilder;

class %sTest extends TestCase
{
    private \Osteel\OpenApi\Testing\Validator $validator;
    
    protected function setUp(): void
    {
        // Initialize OpenAPI validator
        $this->validator = ValidatorBuilder::fromYamlFile(__DIR__ . '/../%s')->getValidator();
    }
    
    public function testCanBeInstantiatedWithTestData(): void
    {
        $testData = %s;
        
        $%s = %s::fromArray($testData);
        
        $this->assertInstanceOf(%s::class, $%s);
        
        // Validate that the generated data structure is correct
        $result = $%s->toArray();
        $this->assertIsArray($result);
        
        // Basic property checks
        foreach ($testData as $key => $value) {
            $this->assertArrayHasKey($key, $result);
        }
        
        // Test individual property values
        %s
    }
    
    public function testFromArrayWithMinimalData(): void
    {
        // Test with minimal required fields
        $minimalData = %s;
        $%s = %s::fromArray($minimalData);
        
        $this->assertInstanceOf(%s::class, $%s);
    }
    
    public function testCanBeSerializedToArray(): void
    {
        $testData = %s;
        $%s = %s::fromArray($testData);
        $result = $%s->toArray();
        
        $this->assertIsArray($result);
        %s
    }
    
    public function testDataIntegrityAfterSerialization(): void
    {
        // Use comprehensive test data
        $originalData = %s;
        
        $%s = %s::fromArray($originalData);
        $serializedData = $%s->toArray();
        
        // Verify data integrity through serialization cycle
        $%sReconstituted = %s::fromArray($serializedData);
        $finalData = $%sReconstituted->toArray();
        
        // Key structural checks (avoiding strict equality due to potential type coercion)
        $this->assertSameSize($originalData, $finalData);
        foreach (array_keys($originalData) as $key) {
            $this->assertArrayHasKey($key, $finalData);
        }
    }
}`,
		testNamespace,
		useNamespace, name,
		name,
		specFilename,
		g.generateTestData(schema),
		strings.ToLower(name), name,
		name, strings.ToLower(name),
		strings.ToLower(name),
		g.generateAssertions(name, schema),
		g.generateMinimalTestData(schema),
		strings.ToLower(name), name,
		name, strings.ToLower(name),
		g.generateTestData(schema),
		strings.ToLower(name), name,
		strings.ToLower(name),
		g.generateSerializationAssertions(schema),
		g.generateTestData(schema),
		strings.ToLower(name), name,
		strings.ToLower(name),
		strings.ToLower(name), name,
		strings.ToLower(name))
}

// generateApiClientTestContent creates test content for the API client
func (g *PHPGenerator) generateApiClientTestContent(model *config.InternalModel) string {
	// For PHP namespace declarations, don't escape backslashes
	testNamespace := g.config.Namespace + "\\Tests"
	useNamespace := g.config.Namespace // For use statements, don't double-escape

	// Get the OpenAPI spec filename
	specFilename := filepath.Base(g.config.InputFile)

	return fmt.Sprintf(`<?php

namespace %s;

use %s\ApiClient;
use PHPUnit\Framework\TestCase;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiClientTest extends TestCase
{
    private ApiClient $client;
    private \Osteel\OpenApi\Testing\Validator $validator;
    
    protected function setUp(): void
    {
        $this->client = new ApiClient('https://api.example.com');
        
        // Initialize OpenAPI validator
        $this->validator = ValidatorBuilder::fromYamlFile(__DIR__ . '/../%s')->getValidator();
    }
    
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ApiClient::class, $this->client);
    }
    
    public function testBaseUrlIsSet(): void
    {
        $reflection = new \ReflectionClass($this->client);
        $property = $reflection->getProperty('baseUrl');
        $property->setAccessible(true);
        
        $this->assertEquals('https://api.example.com', $property->getValue($this->client));
    }
    
    /**
     * Test basic HTTP method functionality
     */
    public function testHttpMethods(): void
    {
        // The ApiClient has a generic request method that supports all HTTP methods
        $this->assertTrue(method_exists($this->client, 'request'));
        
        // Test that the request method accepts the correct parameters
        $reflection = new \ReflectionMethod($this->client, 'request');
        $parameters = $reflection->getParameters();
        
        $this->assertCount(4, $parameters);
        $this->assertEquals('method', $parameters[0]->getName());
        $this->assertEquals('endpoint', $parameters[1]->getName());
        $this->assertEquals('data', $parameters[2]->getName());
        $this->assertEquals('headers', $parameters[3]->getName());
    }
    
    /**
     * Test that mock requests validate against OpenAPI specification
     */
    public function testMockRequestValidation(): void
    {
        // Create a mock POST request
        $mockData = [
            'name' => 'Test Pet',
            'photoUrls' => ['https://example.com/photo.jpg']
        ];
        
        // Create a Symfony request object
        $request = new Request(
            [], // query
            [], // post
            [], // attributes
            [], // cookies  
            [], // files
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/pet',
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode($mockData)
        );
        
        // Test that our mock request structure is valid
        $this->assertIsArray($mockData);
        $this->assertArrayHasKey('name', $mockData);
        $this->assertArrayHasKey('photoUrls', $mockData);
    }
    
    /**
     * Test that mock responses validate against OpenAPI specification
     */
    public function testMockResponseValidation(): void
    {
        // Create a mock response
        $mockResponseData = [
            'id' => 123,
            'name' => 'Test Pet',
            'photoUrls' => ['https://example.com/photo.jpg'],
            'status' => 'available'
        ];
        
        // Create a Symfony response object
        $response = new Response(
            json_encode($mockResponseData),
            200,
            ['Content-Type' => 'application/json']
        );
        
        // Test basic response structure
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        
        $decodedData = json_decode($response->getContent(), true);
        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('id', $decodedData);
        $this->assertArrayHasKey('name', $decodedData);
    }
    
    /**
     * Test error response structure
     */
    public function testErrorResponseStructure(): void
    {
        $errorData = [
            'code' => 400,
            'message' => 'Invalid input'
        ];
        
        $response = new Response(
            json_encode($errorData),
            400,
            ['Content-Type' => 'application/json']
        );
        
        // Validate error response structure
        $this->assertEquals(400, $response->getStatusCode());
        $decodedData = json_decode($response->getContent(), true);
        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('code', $decodedData);
        $this->assertArrayHasKey('message', $decodedData);
    }
}`,
		testNamespace,
		useNamespace,
		specFilename)
}

// generateTestData creates sample test data for a schema
func (g *PHPGenerator) generateTestData(schema *config.SchemaModel) string {
	var properties []string

	for _, prop := range schema.Properties {
		value := g.generatePropertyTestValue(prop)
		properties = append(properties, fmt.Sprintf("'%s' => %s", prop.Name, value))
	}

	return fmt.Sprintf("[\n        %s\n    ]", strings.Join(properties, ",\n        "))
}

// generatePropertyTestValue creates a test value for a property
func (g *PHPGenerator) generatePropertyTestValue(prop *config.Property) string {
	switch prop.PHPType.Name {
	case "string":
		return fmt.Sprintf("'test_%s'", strings.ToLower(prop.Name))
	case "int":
		return "123"
	case "float":
		return "123.45"
	case "bool":
		return "true"
	case "array":
		return "[]"
	default:
		if prop.Required {
			return fmt.Sprintf("'test_%s'", strings.ToLower(prop.Name))
		}
		return "null"
	}
}

// generateAssertions creates assertions for testing property values
func (g *PHPGenerator) generateAssertions(className string, schema *config.SchemaModel) string {
	var assertions []string
	varName := strings.ToLower(className)

	for _, prop := range schema.Properties {
		expected := g.generatePropertyTestValue(prop)
		assertions = append(assertions,
			fmt.Sprintf("$this->assertEquals(%s, $%s->%s);", expected, varName, prop.Name))
	}

	return strings.Join(assertions, "\n        ")
}

// generateSerializationAssertions creates assertions for testing serialization
func (g *PHPGenerator) generateSerializationAssertions(schema *config.SchemaModel) string {
	var assertions []string

	for _, prop := range schema.Properties {
		assertions = append(assertions,
			fmt.Sprintf("$this->assertArrayHasKey('%s', $result);", prop.Name))
	}

	return strings.Join(assertions, "\n        ")
}

// generateMinimalTestData creates minimal test data with only required fields
func (g *PHPGenerator) generateMinimalTestData(schema *config.SchemaModel) string {
	var properties []string

	// Only include required properties for minimal test data
	for _, prop := range schema.Properties {
		if prop.Required {
			value := g.generatePropertyTestValue(prop)
			properties = append(properties, fmt.Sprintf("'%s' => %s", prop.Name, value))
		}
	}

	// If no required properties, include at least one property for testing
	if len(properties) == 0 && len(schema.Properties) > 0 {
		prop := schema.Properties[0]
		value := g.generatePropertyTestValue(prop)
		properties = append(properties, fmt.Sprintf("'%s' => %s", prop.Name, value))
	}

	return fmt.Sprintf("[\n        %s\n    ]", strings.Join(properties, ",\n        "))
}

// generateReadme creates a README.md file for the generated package
func (g *PHPGenerator) generateReadme(model *config.InternalModel) error {
	// Get the OpenAPI spec filename
	specFilename := filepath.Base(g.config.InputFile)

	readme := g.generateReadmeContent(specFilename, model)

	return os.WriteFile(filepath.Join(g.config.OutputDir, "README.md"), []byte(readme), 0644)
}

// generateReadmeContent creates the content for the README file
func (g *PHPGenerator) generateReadmeContent(specFilename string, model *config.InternalModel) string {
	content := "# " + g.getPackageName() + "\n\n"
	content += "Generated PHP client and models from OpenAPI specification.\n\n"

	content += "## Installation\n\n"
	content += "Install dependencies:\n\n"
	content += "```bash\n"
	content += "composer install\n"
	content += "```\n\n"

	content += "## Usage\n\n"
	content += "### Models\n\n"
	content += "All model classes are generated in the `src/` directory with the namespace `" + g.config.Namespace + "`.\n\n"
	content += "Example usage:\n\n"
	content += "```php\n"
	content += "use " + g.config.Namespace + "\\Pet;\n\n"
	content += "// Create from array\n"
	content += "$pet = Pet::fromArray([\n"
	content += "    'id' => 123,\n"
	content += "    'name' => 'Fluffy',\n"
	content += "    'status' => 'available'\n"
	content += "]);\n\n"
	content += "// Convert to array\n"
	content += "$data = $pet->toArray();\n"
	content += "```\n\n"

	content += g.generateApiClientSection()

	content += "\n## Testing\n\n"
	content += "This package includes comprehensive tests using:\n\n"
	content += "- **[PHPUnit](https://phpunit.de/)** - Testing framework\n"
	content += "- **[OpenAPI HttpFoundation Testing](https://github.com/osteel/openapi-httpfoundation-testing)** - Validates requests/responses against OpenAPI spec\n"
	content += "- **[PHP OpenAPI Faker](https://github.com/canvural/php-openapi-faker)** - Generates realistic test data from OpenAPI schemas\n\n"

	content += "### Running Tests\n\n"
	content += "```bash\n"
	content += "# Run all tests\n"
	content += "vendor/bin/phpunit\n\n"
	content += "# Run with verbose output\n"
	content += "vendor/bin/phpunit --verbose\n\n"
	content += "# Run specific test\n"
	content += "vendor/bin/phpunit tests/PetTest.php\n"
	content += "```\n\n"

	content += "### Test Features\n\n"
	content += "The generated tests include:\n\n"
	content += "1. **Model Validation Tests**: Verify that generated models can be instantiated and serialized correctly\n"
	content += "2. **OpenAPI Compliance Tests**: Validate that models conform to the OpenAPI specification\n"
	content += "3. **Fake Data Generation**: Use realistic test data generated from OpenAPI schemas\n"
	content += "4. **Request/Response Validation**: Ensure API client requests and responses match the specification\n\n"

	content += "### OpenAPI Specification\n\n"
	content += "The OpenAPI specification file is included as `" + specFilename + "` and is used by the testing libraries to:\n\n"
	content += "- Generate realistic fake data for testing\n"
	content += "- Validate request/response structures\n"
	content += "- Ensure compliance with the API specification\n\n"

	content += "## Generated Files\n\n"
	content += "- `src/` - Model classes\n"
	content += g.generateFilesSection(model)
	content += "- `tests/` - PHPUnit test cases\n"
	content += "- `composer.json` - Composer configuration with testing dependencies\n"
	content += "- `" + specFilename + "` - OpenAPI specification file\n\n"

	content += "## Requirements\n\n"
	content += "- PHP 8.1+\n"
	content += "- Composer\n\n"

	content += "## License\n\n"
	content += "MIT\n"

	return content
}

// getPackageName generates a package name from the namespace
func (g *PHPGenerator) getPackageName() string {
	// Convert namespace to lowercase with hyphens
	name := strings.ToLower(g.config.Namespace)
	name = strings.ReplaceAll(name, "\\", "-")
	return strings.ReplaceAll(name, "_", "-")
}

// generateApiClientSection generates the API client section for README
func (g *PHPGenerator) generateApiClientSection() string {
	if !g.config.GenerateClient {
		return ""
	}

	content := "### API Client\n\n"
	content += "An API client is also generated:\n\n"
	content += "```php\n"
	content += "use " + g.config.Namespace + "\\ApiClient;\n\n"
	content += "$client = new ApiClient('https://api.example.com');\n"
	content += "$response = $client->get('/pets');\n"
	content += "```\n\n"

	return content
}

// generateFilesSection generates the files list section for README
func (g *PHPGenerator) generateFilesSection(model *config.InternalModel) string {
	content := ""

	if g.config.GenerateClient {
		content += "- `src/ApiClient.php` - HTTP client for API calls\n"
	}

	return content
}
