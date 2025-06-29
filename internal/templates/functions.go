package templates

import (
	"fmt"
	"strings"

	"github.com/floriscornel/piak/internal/config"
)

// PHP-specific template helper functions

// formatPHPType formats a PHP type with proper nullable syntax.
func formatPHPType(phpType config.PHPType) string {
	var typeStr string

	// Handle basic types and arrays
	if phpType.IsArray {
		typeStr = "array" // We'll use PHPDoc for array types
	} else {
		typeStr = phpType.Name
	}

	if phpType.IsNullable && !strings.Contains(typeStr, "null") {
		typeStr = "?" + typeStr
	}

	return typeStr
}

// renderFromArrayMethod generates a fromArray method for models.
func renderFromArrayMethod(model *config.SchemaModel) string {
	var result strings.Builder

	result.WriteString("/**\n")
	result.WriteString(" * Create instance from array data\n")
	result.WriteString(" * @param array<string, mixed> $data\n")
	result.WriteString(" * @return self\n")
	result.WriteString(" */\n")
	result.WriteString("public static function fromArray(array $data): self\n")
	result.WriteString("{\n")

	// Generate validation for required fields
	for _, prop := range model.Properties {
		if prop.Required {
			result.WriteString(fmt.Sprintf("    if (!isset($data['%s'])) {\n", prop.Name))
			errMsg := fmt.Sprintf(
				"        throw new \\InvalidArgumentException('Missing required field: %s');\n",
				prop.Name,
			)
			result.WriteString(errMsg)
			result.WriteString("    }\n")
		}
	}

	result.WriteString("\n    return new self(\n")

	// Order parameters to match constructor: required first, then optional
	var requiredProps []*config.Property
	var optionalProps []*config.Property

	for _, prop := range model.Properties {
		if prop.Required {
			requiredProps = append(requiredProps, prop)
		} else {
			optionalProps = append(optionalProps, prop)
		}
	}

	// Combine required and optional properties in correct order
	requiredProps = append(requiredProps, optionalProps...)
	allProps := requiredProps

	for i, prop := range allProps {
		propAccess := fmt.Sprintf("$data['%s'] ?? null", prop.Name)
		if i < len(allProps)-1 {
			result.WriteString(fmt.Sprintf("        %s,\n", propAccess))
		} else {
			result.WriteString(fmt.Sprintf("        %s\n", propAccess))
		}
	}
	result.WriteString("    );\n")
	result.WriteString("}\n")

	return result.String()
}

// renderToArrayMethod generates a toArray method for models.
func renderToArrayMethod(model *config.SchemaModel) string {
	var result strings.Builder

	result.WriteString("\n/**\n")
	result.WriteString(" * Convert instance to array\n")
	result.WriteString(" * @return array<string, mixed>\n")
	result.WriteString(" */\n")
	result.WriteString("public function toArray(): array\n")
	result.WriteString("{\n")
	result.WriteString("    return [\n")

	for _, prop := range model.Properties {
		result.WriteString(fmt.Sprintf("        '%s' => $this->%s,\n", prop.Name, prop.Name))
	}

	result.WriteString("    ];\n")
	result.WriteString("}\n")

	return result.String()
}

// Test data generation template helpers

// generateTestData creates sample test data for a schema.
func generateTestData(schema *config.SchemaModel) string {
	var properties []string

	for _, prop := range schema.Properties {
		value := generatePropertyTestValue(prop)
		properties = append(properties, fmt.Sprintf("'%s' => %s", prop.Name, value))
	}

	return fmt.Sprintf("[\n        %s\n    ]", strings.Join(properties, ",\n        "))
}

// generatePropertyTestValue creates a test value for a property.
func generatePropertyTestValue(prop *config.Property) string {
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

// generateAssertions creates assertions for testing property values.
func generateAssertions(className string, schema *config.SchemaModel) string {
	var assertions []string
	varName := strings.ToLower(className)

	for _, prop := range schema.Properties {
		expected := generatePropertyTestValue(prop)
		assertions = append(assertions,
			fmt.Sprintf("$this->assertEquals(%s, $%s->%s);", expected, varName, prop.Name))
	}

	return strings.Join(assertions, "\n        ")
}

// generateSerializationAssertions creates assertions for testing serialization.
func generateSerializationAssertions(schema *config.SchemaModel) string {
	var assertions []string

	for _, prop := range schema.Properties {
		assertions = append(assertions,
			fmt.Sprintf("$this->assertArrayHasKey('%s', $result);", prop.Name))
	}

	return strings.Join(assertions, "\n        ")
}

// generateMinimalTestData creates minimal test data with only required fields.
func generateMinimalTestData(schema *config.SchemaModel) string {
	var properties []string

	// Only include required properties for minimal test data
	for _, prop := range schema.Properties {
		if prop.Required {
			value := generatePropertyTestValue(prop)
			properties = append(properties, fmt.Sprintf("'%s' => %s", prop.Name, value))
		}
	}

	// If no required properties, include at least one property for testing
	if len(properties) == 0 && len(schema.Properties) > 0 {
		prop := schema.Properties[0]
		value := generatePropertyTestValue(prop)
		properties = append(properties, fmt.Sprintf("'%s' => %s", prop.Name, value))
	}

	return fmt.Sprintf("[\n        %s\n    ]", strings.Join(properties, ",\n        "))
}
