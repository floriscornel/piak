package interfaces

import (
	"github.com/floriscornel/piak/internal/types"
	"github.com/getkin/kin-openapi/openapi3"
)

// Parser handles OpenAPI specification parsing
type Parser interface {
	Parse(specPath string) (*openapi3.T, error)
	ParseBytes(data []byte) (*openapi3.T, error)
}

// Analyzer analyzes OpenAPI specifications and builds internal models
type Analyzer interface {
	Analyze(spec *openapi3.T) (*types.InternalModel, error)
	AnalyzeSchemas(spec *openapi3.T) (map[string]*types.SchemaModel, error)
	AnalyzeEndpoints(spec *openapi3.T) ([]*types.EndpointModel, error)
}

// Generator generates code from the internal model
type Generator interface {
	Generate(model *types.InternalModel) error
}

// TypeMapper maps OpenAPI types to target language types
type TypeMapper interface {
	MapType(schema *openapi3.Schema) types.PHPType
	MapStringType(schema *openapi3.Schema) types.PHPType
	MapArrayType(schema *openapi3.Schema) types.PHPType
	MapObjectType(schema *openapi3.Schema) types.PHPType
}

// TemplateRenderer renders templates with data
type TemplateRenderer interface {
	RenderModel(model *types.SchemaModel) (string, error)
	RenderClient(model *types.InternalModel) (string, error)
	RenderException() (string, error)
}

// FileWriter handles file output operations
type FileWriter interface {
	WriteFile(path string, content []byte) error
	MkdirAll(path string) error
	Exists(path string) bool
}
