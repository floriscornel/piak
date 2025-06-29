package templates

import (
	"strings"

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

func pluralize(s string) string {
	return inflection.Plural(s)
}

func singularize(s string) string {
	return inflection.Singular(s)
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

func trimSpace(s string) string {
	return strings.TrimSpace(s)
}

func sub(a, b int) int {
	return a - b
}

func add(a, b int) int {
	return a + b
}
