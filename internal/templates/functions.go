package templates

import (
	"fmt"
	"regexp"
	"sort"
	"strings"

	"github.com/floriscornel/piak/internal/config"
	"github.com/iancoleman/strcase"
)

// PHP-specific template helper functions for MVP

// formatPHPType formats a PHP type with proper nullable syntax (simplified for MVP).
func formatPHPType(phpType config.PHPType) string {
	var typeStr string

	// Simplified for MVP - handle basic types and arrays
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

// formatPHPDocType formats a PHP type for PHPDoc comments (simplified for MVP).
func formatPHPDocType(phpType config.PHPType) string {
	var typeStr string

	// Simplified for MVP - handle basic array types
	if phpType.IsArray {
		// For MVP, we'll just use array<string, mixed> for simplicity
		typeStr = "array<string, mixed>"
	} else {
		typeStr = phpType.Name
	}

	if phpType.IsNullable && !strings.Contains(typeStr, "null") {
		typeStr += "|null"
	}

	return typeStr
}

// generateUseStatements generates sorted use statements from import list.
func generateUseStatements(imports []string) string {
	if len(imports) == 0 {
		return ""
	}

	// Remove duplicates and sort
	uniqueImports := make(map[string]bool)
	for _, imp := range imports {
		if imp != "" {
			uniqueImports[imp] = true
		}
	}

	var sortedImports []string
	for imp := range uniqueImports {
		sortedImports = append(sortedImports, imp)
	}
	sort.Strings(sortedImports)

	var result strings.Builder
	for _, imp := range sortedImports {
		result.WriteString(fmt.Sprintf("use %s;\n", imp))
	}

	return result.String()
}

// formatConstructorParam formats a constructor parameter with proper type and default.
func formatConstructorParam(prop *config.Property) string {
	paramType := prop.PHPType.Name
	paramName := strcase.ToSnake(prop.Name)

	if !prop.Required {
		paramType = "?" + paramType
		return fmt.Sprintf("%s $%s = null", paramType, paramName)
	}

	return fmt.Sprintf("%s $%s", paramType, paramName)
}

// formatDefaultValue formats a default value for PHP.
func formatDefaultValue(value interface{}) string {
	if value == nil {
		return "null"
	}

	switch v := value.(type) {
	case string:
		return fmt.Sprintf("'%s'", strings.ReplaceAll(v, "'", "\\'"))
	case bool:
		if v {
			return "true"
		}
		return "false"
	case int, int32, int64, float32, float64:
		return fmt.Sprintf("%v", v)
	default:
		return "null"
	}
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
			result.WriteString(fmt.Sprintf("        throw new \\InvalidArgumentException('Missing required field: %s');\n", prop.Name))
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
	allProps := append(requiredProps, optionalProps...)

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

// isValidPHPIdentifier checks if a string is a valid PHP identifier.
func isValidPHPIdentifier(name string) bool {
	// PHP identifier regex: starts with letter or underscore, followed by letters, numbers, or underscores
	matched, _ := regexp.MatchString(`^[a-zA-Z_][a-zA-Z0-9_]*$`, name)
	return matched
}

// sanitizePHPIdentifier converts a string to a valid PHP identifier.
func sanitizePHPIdentifier(name string) string {
	if name == "" {
		return "property"
	}

	// Replace invalid characters with underscores
	reg := regexp.MustCompile(`[^a-zA-Z0-9_]`)
	sanitized := reg.ReplaceAllString(name, "_")

	// Ensure it starts with a letter or underscore
	if matched, _ := regexp.MatchString(`^[0-9]`, sanitized); matched {
		sanitized = "_" + sanitized
	}

	// Handle empty result
	if sanitized == "" {
		return "property"
	}

	// Convert to camelCase for properties
	return strcase.ToCamel(sanitized)
}

// renderArrayType handles array type rendering for MVP
func renderArrayType(phpType config.PHPType) string {
	if phpType.IsArray {
		return "array"
	}
	return phpType.Name
}

// getHTTPClientImports returns imports for HTTP client (simplified for MVP)
func getHTTPClientImports(clientType interface{}) []string {
	// For MVP, return empty array - no special imports needed
	return []string{}
}

// indent adds indentation to text.
func indent(text string, spaces int) string {
	if text == "" {
		return text
	}

	indentStr := strings.Repeat(" ", spaces)
	lines := strings.Split(text, "\n")
	var result strings.Builder

	for i, line := range lines {
		if line != "" {
			result.WriteString(indentStr + line)
		}
		if i < len(lines)-1 {
			result.WriteString("\n")
		}
	}

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

// generateTestData creates sample test data for a schema
func generateTestData(schema *config.SchemaModel) string {
	var properties []string

	for _, prop := range schema.Properties {
		value := generatePropertyTestValue(prop)
		properties = append(properties, fmt.Sprintf("'%s' => %s", prop.Name, value))
	}

	return fmt.Sprintf("[\n        %s\n    ]", strings.Join(properties, ",\n        "))
}

// generatePropertyTestValue creates a test value for a property
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

// generateAssertions creates assertions for testing property values
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

// generateSerializationAssertions creates assertions for testing serialization
func generateSerializationAssertions(schema *config.SchemaModel) string {
	var assertions []string

	for _, prop := range schema.Properties {
		assertions = append(assertions,
			fmt.Sprintf("$this->assertArrayHasKey('%s', $result);", prop.Name))
	}

	return strings.Join(assertions, "\n        ")
}

// generateMinimalTestData creates minimal test data with only required fields
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

// Composer.json template helpers

// generatePackageName generates a package name from the namespace
func generatePackageName(namespace string) string {
	// Convert namespace to lowercase with hyphens
	namespaceParts := strings.Split(namespace, "\\")
	vendor := strings.ToLower(namespaceParts[0])
	var packageName string
	if len(namespaceParts) > 1 {
		packageName = fmt.Sprintf("%s/%s", vendor, strings.ToLower(strings.Join(namespaceParts[1:], "-")))
	} else {
		packageName = fmt.Sprintf("%s/sdk", vendor)
	}
	return packageName
}

// prepareJSONNamespace escapes backslashes for JSON
func prepareJSONNamespace(namespace string) string {
	return strings.ReplaceAll(namespace, "\\", "\\\\")
}

// cleanDescription cleans and escapes description for JSON
func cleanDescription(description string) string {
	cleaned := strings.ReplaceAll(description, "\n", " ")
	cleaned = strings.ReplaceAll(cleaned, "\r", " ")
	cleaned = strings.ReplaceAll(cleaned, "\"", "\\\"")
	cleaned = strings.TrimSpace(cleaned)
	if cleaned == "" {
		cleaned = "Generated API client"
	}
	return cleaned
}
