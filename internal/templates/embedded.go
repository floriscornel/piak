package templates

import (
	"embed"
	"strings"
	"text/template"

	"github.com/iancoleman/strcase"
	"github.com/jinzhu/inflection"
)

//go:embed *.tmpl partials/**/*.tmpl
var templateFS embed.FS

// GetTemplates returns all embedded templates with custom functions.
func GetTemplates() (*template.Template, error) {
	funcMap := template.FuncMap{
		// Basic string functions
		"toCamel":          strcase.ToCamel,
		"toSnake":          strcase.ToSnake,
		"toLower":          strings.ToLower,
		"toUpper":          strings.ToUpper,
		"toScreamingSnake": strcase.ToScreamingSnake,

		"pluralize":   inflection.Plural,
		"singularize": inflection.Singular,
		"join":        strings.Join,
		"hasPrefix":   strings.HasPrefix,
		"hasSuffix":   strings.HasSuffix,
		"trimSpace":   strings.TrimSpace,
		"sub":         func(a, b int) int { return a - b },
		"add":         func(a, b int) int { return a + b },

		// PHP-specific type formatting
		"formatPHPType":         formatPHPType,
		"renderFromArrayMethod": renderFromArrayMethod,
		"renderToArrayMethod":   renderToArrayMethod,

		// Test data generation helpers
		"generateTestData":                generateTestData,
		"generatePropertyTestValue":       generatePropertyTestValue,
		"generateAssertions":              generateAssertions,
		"generateSerializationAssertions": generateSerializationAssertions,
		"generateMinimalTestData":         generateMinimalTestData,
	}

	tmpl := template.New("").Funcs(funcMap)

	// Try to parse templates, return empty template if none exist
	parsed, err := tmpl.ParseFS(templateFS, "*.tmpl", "partials/**/*.tmpl")
	if err != nil {
		// Return just the base template with functions if no .tmpl files exist
		return tmpl, err
	}
	return parsed, nil
}
