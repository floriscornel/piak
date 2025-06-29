package templates

import (
	"fmt"
	"regexp"
	"sort"
	"strings"

	"github.com/floriscornel/piak/internal/types"
	"github.com/iancoleman/strcase"
	"github.com/jinzhu/inflection"
)

// Template helper functions

func toCamel(s string) string {
	return strcase.ToCamel(s)
}

func toSnake(s string) string {
	return strcase.ToSnake(s)
}

func toLower(s string) string {
	return strings.ToLower(s)
}

func toUpper(s string) string {
	return strings.ToUpper(s)
}

func toScreamingSnake(s string) string {
	return strcase.ToScreamingSnake(s)
}

func toPascal(s string) string {
	return strcase.ToCamel(s)
}

func toKebab(s string) string {
	return strcase.ToKebab(s)
}

func title(s string) string {
	return strings.Title(s)
}

func capitalize(s string) string {
	if len(s) == 0 {
		return s
	}
	return strings.ToUpper(s[:1]) + s[1:]
}

func uncapitalize(s string) string {
	if len(s) == 0 {
		return s
	}
	return strings.ToLower(s[:1]) + s[1:]
}

func trimSpace(s string) string {
	return strings.TrimSpace(s)
}

func replace(old, new, s string) string {
	return strings.ReplaceAll(s, old, new)
}

func split(sep, s string) []string {
	return strings.Split(s, sep)
}

func singularize(s string) string {
	if strings.HasSuffix(s, "ies") {
		return strings.TrimSuffix(s, "ies") + "y"
	} else if strings.HasSuffix(s, "es") {
		return strings.TrimSuffix(s, "es")
	} else if strings.HasSuffix(s, "s") {
		return strings.TrimSuffix(s, "s")
	}
	return s
}

func pluralize(s string) string {
	return inflection.Plural(s)
}

func join(sep string, elems []string) string {
	return strings.Join(elems, sep)
}

func hasPrefix(s, prefix string) bool {
	return strings.HasPrefix(s, prefix)
}

func hasSuffix(s, suffix string) bool {
	return strings.HasSuffix(s, suffix)
}

func sub(a, b int) int {
	return a - b
}

func add(a, b int) int {
	return a + b
}

// PHP-specific template helper functions

// formatPHPType formats a PHP type with proper nullable and union syntax.
func formatPHPType(phpType types.PHPType) string {
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
func formatPHPDocType(phpType types.PHPType) string {
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
func formatConstructorParam(prop *types.Property, isLast bool) string {
	phpType := formatPHPType(prop.PHPType)
	paramName := toSnake(prop.Name)

	var param strings.Builder

	// Add type hint
	if phpType != "" {
		param.WriteString(phpType)
		param.WriteString(" ")
	}

	// Add parameter name
	param.WriteString("$")
	param.WriteString(paramName)

	// Add default value for optional parameters
	if !prop.Required {
		if prop.DefaultValue != nil {
			param.WriteString(" = ")
			param.WriteString(formatDefaultValue(prop.DefaultValue))
		} else {
			param.WriteString(" = null")
		}
	}

	// Add comma if not last parameter
	if !isLast {
		param.WriteString(",")
	}

	return param.String()
}

// formatDefaultValue formats a default value for PHP code.
func formatDefaultValue(value interface{}) string {
	switch v := value.(type) {
	case string:
		return fmt.Sprintf("'%s'", strings.ReplaceAll(v, "'", "\\'"))
	case bool:
		if v {
			return "true"
		}
		return "false"
	case int, int32, int64:
		return fmt.Sprintf("%d", v)
	case float32, float64:
		return fmt.Sprintf("%g", v)
	case nil:
		return "null"
	default:
		return "null"
	}
}

// renderUnionTypeDetection generates type detection logic for union types.
func renderUnionTypeDetection(context *types.UnionTypeContext) string {
	if context.Discriminator != nil {
		return renderDiscriminatorDetection(context.Discriminator)
	}

	return renderHeuristicDetection(context)
}

// renderDiscriminatorDetection generates discriminator-based type detection.
func renderDiscriminatorDetection(discriminator *types.DiscriminatorInfo) string {
	var result strings.Builder

	result.WriteString(fmt.Sprintf("match ($data['%s']) {\n", discriminator.PropertyName))

	for value, className := range discriminator.ValueMapping {
		result.WriteString(fmt.Sprintf("    '%s' => %s::fromArray($data),\n", value, className))
	}

	result.WriteString(fmt.Sprintf(
		"    default => throw new \\InvalidArgumentException(\"Unknown %s type: {$data['%s']}\")\n",
		discriminator.PropertyName,
		discriminator.PropertyName,
	))
	result.WriteString("}")

	return result.String()
}

// renderHeuristicDetection generates heuristic-based type detection.
func renderHeuristicDetection(context *types.UnionTypeContext) string {
	var result strings.Builder

	// Generate detection logic based on unique properties
	for i, member := range context.UnionMembers {
		if i > 0 {
			result.WriteString("} else ")
		}

		result.WriteString("if (")
		// Add unique property checks (this would need more context about unique properties)
		result.WriteString("isset($data['uniqueProperty'])) {\n")
		result.WriteString(fmt.Sprintf("    return %s::fromArray($data);\n", member.Name))
	}

	result.WriteString("} else {\n")
	result.WriteString("    throw new \\InvalidArgumentException('Unable to determine union type from data');\n")
	result.WriteString("}")

	return result.String()
}

// renderFromArrayMethod generates a fromArray factory method.
func renderFromArrayMethod(model *types.SchemaModel) string {
	var result strings.Builder

	result.WriteString("public static function fromArray(array $data): self\n{\n")

	// Add validation if needed
	for _, prop := range model.Properties {
		if prop.Required {
			result.WriteString(fmt.Sprintf(
				"    if (!isset($data['%s'])) {\n        throw new \\InvalidArgumentException('%s is required');\n    }\n\n",
				prop.Name,
				prop.Name,
			))
		}
	}

	// Generate constructor call
	result.WriteString("    return new self(\n")
	for i, prop := range model.Properties {
		paramName := toSnake(prop.Name)
		isLast := i == len(model.Properties)-1

		if prop.Required {
			result.WriteString(fmt.Sprintf("        %s: $data['%s']", paramName, prop.Name))
		} else {
			result.WriteString(fmt.Sprintf("        %s: $data['%s'] ?? null", paramName, prop.Name))
		}

		if !isLast {
			result.WriteString(",")
		}
		result.WriteString("\n")
	}
	result.WriteString("    );\n")

	result.WriteString("}")

	return result.String()
}

// renderPropertyValidation generates validation logic for a property.
func renderPropertyValidation(rules []*types.ValidationRule) string {
	if len(rules) == 0 {
		return ""
	}

	var result strings.Builder

	for _, rule := range rules {
		switch rule.Type {
		case "enum":
			if values, ok := rule.Value.([]interface{}); ok {
				result.WriteString("if (!in_array($value, [")
				for i, val := range values {
					if i > 0 {
						result.WriteString(", ")
					}
					result.WriteString(formatDefaultValue(val))
				}
				result.WriteString("])) {\n")
				result.WriteString(fmt.Sprintf("    throw new \\InvalidArgumentException('%s');\n", rule.ErrorMessage))
				result.WriteString("}\n")
			}
		case "pattern":
			if pattern, ok := rule.Value.(string); ok {
				result.WriteString(fmt.Sprintf("if (!preg_match('/%s/', $value)) {\n",
					strings.ReplaceAll(pattern, "/", "\\/")))
				result.WriteString(fmt.Sprintf("    throw new \\InvalidArgumentException('%s');\n", rule.ErrorMessage))
				result.WriteString("}\n")
			}
		case "range":
			// Add range validation logic
			result.WriteString("// Range validation would go here\n")
		}
	}

	return result.String()
}

// isValidPHPIdentifier checks if a string is a valid PHP identifier.
func isValidPHPIdentifier(name string) bool {
	if name == "" {
		return false
	}

	// PHP identifier regex: starts with letter or underscore, followed by letters, digits, or underscores
	match, _ := regexp.MatchString(`^[a-zA-Z_][a-zA-Z0-9_]*$`, name)
	return match
}

// sanitizePHPIdentifier converts a string to a valid PHP identifier.
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
func renderArrayType(phpType types.PHPType) string {
	if !phpType.IsArray || phpType.ArrayItemType == nil {
		return phpType.Name
	}

	itemType := formatPHPType(*phpType.ArrayItemType)
	return fmt.Sprintf("array<%s>", itemType) // For PHPDoc
}

// hasSpecialCase checks if a model has a specific special case.
func hasSpecialCase(data interface{}, specialCase types.SpecialCase) bool {
	var model *types.SchemaModel

	// Handle both direct SchemaModel and wrapped struct
	switch v := data.(type) {
	case *types.SchemaModel:
		model = v
	case struct {
		*types.SchemaModel
		Config *types.GeneratorConfig
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
func getHTTPClientImports(clientType types.HTTPClientType) []string {
	switch clientType {
	case types.GuzzleClient:
		return []string{
			"GuzzleHttp\\Client",
			"GuzzleHttp\\Exception\\GuzzleException",
			"GuzzleHttp\\RequestOptions",
		}
	case types.LaravelClient:
		return []string{
			"Illuminate\\Http\\Client\\Factory as HttpFactory",
			"Illuminate\\Http\\Client\\Response",
		}
	case types.CurlClient:
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
