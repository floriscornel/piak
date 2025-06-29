package types

import "github.com/getkin/kin-openapi/openapi3"

// SpecialCase represents different edge cases in OpenAPI specs
type SpecialCase int

const (
	CircularReference SpecialCase = iota
	DiscriminatedUnion
	PolymorphicArray
	DynamicProperties
	AmbiguousUnion
	DeepReferenceChains
	ArrayReferences
	MixedContentTypes
	RecursiveSchemas
	MultipleInheritance
	UnionTypes
	ConditionalSchemas
	NullableOptional
)

// HTTPClientType represents different HTTP client implementations
type HTTPClientType string

const (
	GuzzleClient  HTTPClientType = "guzzle"
	CurlClient    HTTPClientType = "curl"
	LaravelClient HTTPClientType = "laravel"
)

// PHPType represents a PHP type with additional metadata
type PHPType struct {
	Name       string
	IsNullable bool
	IsUnion    bool
	UnionTypes []string
	DocComment string
}

// Property represents a schema property
type Property struct {
	Name        string
	PHPType     PHPType
	OpenAPIType *openapi3.Schema
	Required    bool
	Description string
}

// InheritanceStrategy defines how inheritance should be implemented
type InheritanceStrategy struct {
	Type      InheritanceType
	BaseClass string
	Interface string
}

type InheritanceType int

const (
	NoInheritance InheritanceType = iota
	AbstractClass
	InterfaceImpl
	Composition
)

// SchemaModel represents a analyzed schema ready for code generation
type SchemaModel struct {
	Name         string
	PHPType      string
	OriginalName string
	Properties   []*Property
	Inheritance  *InheritanceStrategy
	SpecialCases []SpecialCase
	Dependencies []*SchemaModel
	IsEnum       bool
	EnumValues   []interface{}
	Description  string
}

// EndpointModel represents an API endpoint
type EndpointModel struct {
	Path        string
	Method      string
	OperationID string
	Summary     string
	Description string
	Parameters  []*ParameterModel
	RequestBody *RequestBodyModel
	Responses   []*ResponseModel
}

// ParameterModel represents an endpoint parameter
type ParameterModel struct {
	Name        string
	In          string // query, path, header
	Required    bool
	Schema      *SchemaModel
	Description string
}

// RequestBodyModel represents a request body
type RequestBodyModel struct {
	Required    bool
	ContentType string
	Schema      *SchemaModel
	Description string
}

// ResponseModel represents an endpoint response
type ResponseModel struct {
	StatusCode  string
	ContentType string
	Schema      *SchemaModel
	Description string
}

// InternalModel represents the complete analyzed OpenAPI specification
type InternalModel struct {
	Info      *InfoModel
	Schemas   map[string]*SchemaModel
	Endpoints []*EndpointModel
	Config    *GeneratorConfig
}

// InfoModel represents API information
type InfoModel struct {
	Title       string
	Version     string
	Description string
}

// GeneratorConfig holds all configuration for code generation
type GeneratorConfig struct {
	InputFile      string         `yaml:"inputFile"`
	HTTPClient     HTTPClientType `yaml:"httpClient" validate:"oneof=guzzle curl laravel"`
	Namespace      string         `yaml:"namespace" validate:"required"`
	OutputDir      string         `yaml:"outputDir" validate:"required"`
	StrictTypes    bool           `yaml:"strictTypes"`
	GenerateTests  bool           `yaml:"generateTests"`
	GenerateClient bool           `yaml:"generateClient"`
	Overwrite      bool           `yaml:"overwrite"`

	// PHP specific settings
	PHP PHPConfig `yaml:"php"`

	// OpenAPI processing settings
	OpenAPI OpenAPIConfig `yaml:"openapi"`
}

// PHPConfig holds PHP-specific generation settings
type PHPConfig struct {
	Namespace         string `yaml:"namespace"`
	BasePath          string `yaml:"basePath"`
	UseStrictTypes    bool   `yaml:"useStrictTypes"`
	GenerateDocblocks bool   `yaml:"generateDocblocks"`
	FileExtension     string `yaml:"fileExtension"`
}

// OpenAPIConfig holds OpenAPI processing settings
type OpenAPIConfig struct {
	ValidateSpec bool `yaml:"validateSpec"`
	ResolveRefs  bool `yaml:"resolveRefs"`
}
