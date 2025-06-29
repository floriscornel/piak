package templates

import (
	"embed"
	"text/template"
)

//go:embed *.tmpl
var TemplateFS embed.FS

// GetTemplates returns all embedded templates with custom functions.
func GetTemplates() (*template.Template, error) {
	funcMap := template.FuncMap{
		// Basic string functions
		"toCamel":          toCamel,
		"toSnake":          toSnake,
		"toLower":          toLower,
		"toUpper":          toUpper,
		"toScreamingSnake": toScreamingSnake,
		"pluralize":        pluralize,
		"singularize":      singularize,
		"join":             join,
		"hasPrefix":        hasPrefix,
		"hasSuffix":        hasSuffix,
		"trimSpace":        trimSpace,
		"sub":              sub,
		"add":              add,

		// PHP-specific type formatting
		"formatPHPType":          formatPHPType,
		"formatPHPDocType":       formatPHPDocType,
		"formatConstructorParam": formatConstructorParam,
		"formatDefaultValue":     formatDefaultValue,
		"renderArrayType":        renderArrayType,

		// Code generation helpers
		"generateUseStatements":        generateUseStatements,
		"renderUnionTypeDetection":     renderUnionTypeDetection,
		"renderDiscriminatorDetection": renderDiscriminatorDetection,
		"renderHeuristicDetection":     renderHeuristicDetection,
		"renderFromArrayMethod":        renderFromArrayMethod,
		"renderPropertyValidation":     renderPropertyValidation,

		// Validation and sanitization
		"isValidPHPIdentifier":  isValidPHPIdentifier,
		"sanitizePHPIdentifier": sanitizePHPIdentifier,

		// Utility functions
		"hasSpecialCase":       hasSpecialCase,
		"getHTTPClientImports": getHTTPClientImports,
		"indent":               indent,
	}

	tmpl := template.New("").Funcs(funcMap)

	// Try to parse templates, return empty template if none exist
	parsed, err := tmpl.ParseFS(TemplateFS, "*.tmpl")
	if err != nil {
		// Return just the base template with functions if no .tmpl files exist
		return tmpl, err
	}
	return parsed, nil
}
