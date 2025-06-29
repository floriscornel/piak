package config

import (
	"github.com/getkin/kin-openapi/openapi3"
)

// Parser handles OpenAPI specification parsing.
type Parser interface {
	Parse(specPath string) (*openapi3.T, error)
	ParseBytes(data []byte) (*openapi3.T, error)
}

// Analyzer analyzes OpenAPI specifications and builds internal models.
type Analyzer interface {
	Analyze(spec *openapi3.T) (*InternalModel, error)
	AnalyzeSchemas(spec *openapi3.T) (map[string]*SchemaModel, error)
	BuildTemplateContext(model *SchemaModel) map[string]interface{}
}

// Generator generates code from the internal model.
type Generator interface {
	Generate(model *InternalModel) error
}

// TypeMapper maps OpenAPI types to target language types.
type TypeMapper interface {
	MapType(schema *openapi3.Schema) PHPType
	MapStringType(schema *openapi3.Schema) PHPType
	MapArrayType(schema *openapi3.Schema) PHPType
	MapObjectType(schema *openapi3.Schema) PHPType
	MapEnumType(schema *openapi3.Schema) PHPType
	ResolveImports(phpType *PHPType) []string
}

// FileWriter handles file output operations.
type FileWriter interface {
	WriteFile(path string, content []byte) error
	MkdirAll(path string) error
	Exists(path string) bool
	BackupFile(path string) error
	ValidateOutput(path string, content []byte) error
}
