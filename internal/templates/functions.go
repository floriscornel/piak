package templates

import (
	"fmt"
	"regexp"
	"sort"
	"strings"

	"github.com/floriscornel/piak/internal/config"
	"github.com/iancoleman/strcase"
)

// PHP-specific template helper functions

// formatPHPType formats a PHP type with proper nullable and union syntax.
func formatPHPType(phpType config.PHPType) string {
	var typeStr string

	switch {
	case phpType.IsUnion && len(phpType.UnionTypes) > 0:
		typeStr = strings.Join(phpType.UnionTypes, "|")
	case phpType.IsArray && phpType.ArrayItemType != nil:
		typeStr = "array" // We'll use PHPDoc for array types
	default:
		typeStr = phpType.Name
	}

	if phpType.IsNullable && !strings.Contains(typeStr, "null") {
		if phpType.IsUnion {
			typeStr += "|null"
		} else {
			typeStr = "?" + typeStr
		}
	}

	return typeStr
}

// formatPHPDocType formats a PHP type for PHPDoc comments.
func formatPHPDocType(phpType config.PHPType) string {
	var typeStr string

	switch {
	case phpType.IsArray && phpType.ArrayItemType != nil:
		itemType := formatPHPDocType(*phpType.ArrayItemType)
		if phpType.IsUnion && len(phpType.UnionTypes) > 0 {
			// For union array types: array<Type1|Type2>
			typeStr = fmt.Sprintf("array<%s>", strings.Join(phpType.UnionTypes, "|"))
		} else {
			// For simple array types: Type[]
			typeStr = itemType + "[]"
		}
	case phpType.IsUnion && len(phpType.UnionTypes) > 0:
		typeStr = strings.Join(phpType.UnionTypes, "|")
	default:
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

// renderUnionTypeDetection generates detection logic for union types.
func renderUnionTypeDetection(context *config.UnionTypeContext) string {
	if context.Discriminator != nil {
		return renderDiscriminatorDetection(context)
	}
	return renderHeuristicDetection(context)
}

// renderDiscriminatorDetection generates discriminator-based detection.
func renderDiscriminatorDetection(context *config.UnionTypeContext) string {
	var result strings.Builder
	result.WriteString(fmt.Sprintf("if (!isset($data['%s'])) {\n", context.Discriminator.PropertyName))
	result.WriteString("    throw new \\InvalidArgumentException('Missing discriminator property');\n")
	result.WriteString("}\n\n")

	result.WriteString(fmt.Sprintf("switch ($data['%s']) {\n", context.Discriminator.PropertyName))
	for value, className := range context.Discriminator.ValueMapping {
		result.WriteString(fmt.Sprintf("    case '%s':\n", value))
		result.WriteString(fmt.Sprintf("        return %s::fromArray($data);\n", className))
	}
	result.WriteString("    default:\n")
	result.WriteString("        throw new \\InvalidArgumentException('Unknown discriminator value');\n")
	result.WriteString("}")

	return result.String()
}

// renderHeuristicDetection generates heuristic-based detection.
func renderHeuristicDetection(context *config.UnionTypeContext) string {
	var result strings.Builder
	result.WriteString("// Try each type in order\n")

	for i, member := range context.UnionMembers {
		result.WriteString("try {\n")
		result.WriteString(fmt.Sprintf("    return %s::fromArray($data);\n", member.Name))
		result.WriteString("} catch (\\Throwable $e) {\n")
		if i == len(context.UnionMembers)-1 {
			result.WriteString("    throw new \\InvalidArgumentException('Data matches no union type');\n")
		}
		result.WriteString("}\n\n")
	}

	return result.String()
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

	// Generate validation and assignment logic
	for _, prop := range model.Properties {
		if prop.Required {
			result.WriteString(fmt.Sprintf("    if (!isset($data['%s'])) {\n", prop.Name))
			result.WriteString(fmt.Sprintf("        throw new \\InvalidArgumentException('Missing required field: %s');\n", prop.Name))
			result.WriteString("    }\n")
		}
	}

	result.WriteString("\n    return new self(\n")
	for i, prop := range model.Properties {
		propAccess := fmt.Sprintf("$data['%s'] ?? null", prop.Name)
		if i < len(model.Properties)-1 {
			result.WriteString(fmt.Sprintf("        %s,\n", propAccess))
		} else {
			result.WriteString(fmt.Sprintf("        %s\n", propAccess))
		}
	}
	result.WriteString("    );\n")
	result.WriteString("}")

	return result.String()
}

// renderPropertyValidation generates validation rules for properties.
func renderPropertyValidation(rules []*config.ValidationRule) string {
	if len(rules) == 0 {
		return ""
	}

	var result strings.Builder
	for _, rule := range rules {
		switch rule.Type {
		case "pattern":
			result.WriteString(fmt.Sprintf("if (!preg_match('/%s/', $value)) {\n", rule.Value))
			result.WriteString(fmt.Sprintf("    throw new \\InvalidArgumentException('%s');\n", rule.ErrorMessage))
			result.WriteString("}\n")
		case "range":
			// Handle range validation
		}
	}

	return result.String()
}

// isValidPHPIdentifier checks if a string is a valid PHP identifier.
func isValidPHPIdentifier(name string) bool {
	if name == "" {
		return false
	}

	// Check first character
	first := name[0]
	if !((first >= 'a' && first <= 'z') || (first >= 'A' && first <= 'Z') || first == '_') {
		return false
	}

	// Check remaining characters
	for _, char := range name[1:] {
		if !((char >= 'a' && char <= 'z') || (char >= 'A' && char <= 'Z') ||
			(char >= '0' && char <= '9') || char == '_') {
			return false
		}
	}

	return true
}

func sanitizePHPIdentifier(name string) string {
	if name == "" {
		return "unnamed"
	}

	// Replace invalid characters with underscores
	reg := regexp.MustCompile(`[^a-zA-Z0-9_]`)
	sanitized := reg.ReplaceAllString(name, "_")

	// Ensure it starts with a letter or underscore
	if match, _ := regexp.MatchString(`^[0-9]`, sanitized); match {
		sanitized = "_" + sanitized
	}

	// Handle reserved words
	reserved := map[string]string{
		"class":      "class_",
		"function":   "function_",
		"const":      "const_",
		"public":     "public_",
		"private":    "private_",
		"protected":  "protected_",
		"static":     "static_",
		"final":      "final_",
		"abstract":   "abstract_",
		"interface":  "interface_",
		"trait":      "trait_",
		"namespace":  "namespace_",
		"use":        "use_",
		"extends":    "extends_",
		"implements": "implements_",
	}

	if replacement, exists := reserved[strings.ToLower(sanitized)]; exists {
		return replacement
	}

	return sanitized
}

// renderArrayType generates PHP array type handling.
func renderArrayType(phpType config.PHPType) string {
	if !phpType.IsArray || phpType.ArrayItemType == nil {
		return phpType.Name
	}

	itemType := formatPHPType(*phpType.ArrayItemType)
	return fmt.Sprintf("array<%s>", itemType) // For PHPDoc
}

// hasSpecialCase checks if a model has a specific special case.
func hasSpecialCase(data interface{}, specialCase config.SpecialCase) bool {
	var model *config.SchemaModel

	// Handle both direct SchemaModel and wrapped struct
	switch v := data.(type) {
	case *config.SchemaModel:
		model = v
	case struct {
		*config.SchemaModel
		Config *config.GeneratorConfig
	}:
		model = v.SchemaModel
	default:
		return false
	}

	for _, sc := range model.SpecialCases {
		if sc == specialCase {
			return true
		}
	}
	return false
}

// getHTTPClientImports returns import statements for the specified HTTP client.
func getHTTPClientImports(clientType config.HTTPClientType) []string {
	switch clientType {
	case config.GuzzleClient:
		return []string{
			"GuzzleHttp\\Client",
			"GuzzleHttp\\Exception\\GuzzleException",
			"GuzzleHttp\\RequestOptions",
		}
	case config.LaravelClient:
		return []string{
			"Illuminate\\Http\\Client\\Factory as HttpFactory",
			"Illuminate\\Http\\Client\\Response",
		}
	case config.CurlClient:
		return []string{} // cURL doesn't need imports
	default:
		return []string{}
	}
}

// indent adds indentation to multiline strings.
func indent(text string, spaces int) string {
	if text == "" {
		return text
	}

	indentStr := strings.Repeat(" ", spaces)
	lines := strings.Split(text, "\n")

	var result []string
	for _, line := range lines {
		if strings.TrimSpace(line) == "" {
			result = append(result, "")
		} else {
			result = append(result, indentStr+line)
		}
	}

	return strings.Join(result, "\n")
}
