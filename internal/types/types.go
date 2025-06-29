package types

import "github.com/getkin/kin-openapi/openapi3"

// SpecialCase represents different edge cases in OpenAPI specs.
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

// HTTPClientType represents different HTTP client implementations.
type HTTPClientType string

const (
	GuzzleClient  HTTPClientType = "guzzle"
	CurlClient    HTTPClientType = "curl"
	LaravelClient HTTPClientType = "laravel"
)

// PHPType represents a PHP type with additional metadata for complex patterns.
type PHPType struct {
	Name             string
	IsNullable       bool
	IsUnion          bool
	UnionTypes       []string
	IsArray          bool
	ArrayItemType    *PHPType
	DocComment       string
	ImportStatements []string // Required use statements
	IsEnum           bool
	EnumValues       []interface{}
	Format           string // OpenAPI format (date, uuid, etc.)
}

// Property represents a schema property with enhanced pattern support.
type Property struct {
	Name            string
	PHPType         PHPType
	OpenAPIType     *openapi3.Schema
	Required        bool
	Description     string
	SpecialCases    []SpecialCase
	ValidationRules []*ValidationRule
	DefaultValue    interface{}
	Example         interface{}
}

// ValidationRule represents a property validation constraint.
type ValidationRule struct {
	Type         string // enum, pattern, range, etc.
	Value        interface{}
	ErrorMessage string
}

// InheritanceStrategy defines how inheritance should be implemented.
type InheritanceStrategy struct {
	Type        InheritanceType
	BaseClass   string
	Interface   string
	Composition []*SchemaModel // For multiple inheritance via composition
}

type InheritanceType int

const (
	NoInheritance InheritanceType = iota
	AbstractClass
	InterfaceImpl
	Composition
	AllOfFlattening // For multiple inheritance patterns
)

// SchemaModel represents a analyzed schema ready for code generation.
type SchemaModel struct {
	Name            string
	PHPType         string
	OriginalName    string
	Properties      []*Property
	Inheritance     *InheritanceStrategy
	SpecialCases    []SpecialCase
	Dependencies    []*SchemaModel
	IsEnum          bool
	EnumValues      []interface{}
	Description     string
	TemplateContext map[string]interface{} // Pattern-specific template data
	Methods         []*MethodModel         // Custom methods (fromArray, etc.)
}

// MethodModel represents a generated method.
type MethodModel struct {
	Name       string
	Visibility string // public, private, protected
	IsStatic   bool
	Parameters []*ParameterModel
	ReturnType string
	Body       string // Template-generated method body
	DocComment string
}

// DiscriminatorInfo represents discriminated union metadata.
type DiscriminatorInfo struct {
	PropertyName string
	ValueMapping map[string]string // discriminator value -> class name
	Required     bool
}

// UnionTypeContext provides template context for union types.
type UnionTypeContext struct {
	Property          *Property
	UnionMembers      []*PHPType
	Discriminator     *DiscriminatorInfo
	DetectionStrategy string // property-based, try-catch, heuristic
	IsOneOf           bool   // true for oneOf, false for anyOf
}

// DynamicPropertiesContext provides template context for additionalProperties.
type DynamicPropertiesContext struct {
	BaseProperties       []*Property
	AdditionalProperties *Property // Type constraint for additional props
	AllowsAdditional     bool
	AdditionalStrategy   string // strict, typed, mixed
}

// CircularReferenceContext provides template context for circular references.
type CircularReferenceContext struct {
	MainClass          *SchemaModel
	CircularReferences []*CircularRef
	BreakingStrategy   string // ID-based, depth-limited, factory-methods
	MaxDepth           int
}

// CircularRef represents a circular reference relationship.
type CircularRef struct {
	PropertyName   string
	TargetSchema   string
	ReferenceType  string // direct, array, nested
	BreakingMethod string // use-id, omit-property, factory-method
}

// ConditionalSchemaContext provides template context for conditional schemas.
type ConditionalSchemaContext struct {
	BaseSchema      *SchemaModel
	Conditions      []*Condition
	ValidationRules []*ValidationRule
}

// Condition represents an if/then/else condition in OpenAPI.
type Condition struct {
	IfProperty   string
	IfValue      interface{}
	ThenRequired []string
	ThenSchema   *SchemaModel
	ElseRequired []string
	ElseSchema   *SchemaModel
}

// ArrayReferenceContext provides template context for complex array patterns.
type ArrayReferenceContext struct {
	BaseProperty       *Property
	ItemType           *PHPType
	IsPolymorphic      bool // Different types in same array
	PolymorphicTypes   []*PHPType
	CircularPrevention bool
}

// EndpointModel represents an API endpoint.
type EndpointModel struct {
	Path        string
	Method      string
	OperationID string
	Summary     string
	Description string
	Parameters  []*ParameterModel
	RequestBody *RequestBodyModel
	Responses   []*ResponseModel
	HTTPClient  HTTPClientType
	MethodName  string // Generated method name
}

// ParameterModel represents an endpoint parameter.
type ParameterModel struct {
	Name        string
	In          string // query, path, header
	Required    bool
	Schema      *SchemaModel
	Description string
	Example     interface{}
}

// RequestBodyModel represents a request body.
type RequestBodyModel struct {
	Required    bool
	ContentType string
	Schema      *SchemaModel
	Description string
}

// ResponseModel represents an endpoint response.
type ResponseModel struct {
	StatusCode  string
	ContentType string
	Schema      *SchemaModel
	Description string
	IsError     bool
}

// InternalModel represents the complete analyzed OpenAPI specification.
type InternalModel struct {
	Info      *InfoModel
	Schemas   map[string]*SchemaModel
	Endpoints []*EndpointModel
	Config    *GeneratorConfig
}

// InfoModel represents API information.
type InfoModel struct {
	Title       string
	Version     string
	Description string
	Contact     *ContactInfo
	License     *LicenseInfo
}

// ContactInfo represents API contact information.
type ContactInfo struct {
	Name  string
	Email string
	URL   string
}

// LicenseInfo represents API license information.
type LicenseInfo struct {
	Name string
	URL  string
}

// GeneratorConfig holds all configuration for code generation.
type GeneratorConfig struct {
	InputFile      string         `yaml:"inputFile"`
	HTTPClient     HTTPClientType `yaml:"httpClient"     validate:"oneof=guzzle curl laravel"`
	Namespace      string         `yaml:"namespace"      validate:"required"`
	OutputDir      string         `yaml:"outputDir"      validate:"required"`
	StrictTypes    bool           `yaml:"strictTypes"`
	GenerateTests  bool           `yaml:"generateTests"`
	GenerateClient bool           `yaml:"generateClient"`
	Overwrite      bool           `yaml:"overwrite"`

	// PHP specific settings
	PHP PHPConfig `yaml:"php"`

	// OpenAPI processing settings
	OpenAPI OpenAPIConfig `yaml:"openapi"`
}

// PHPConfig holds PHP-specific generation settings.
type PHPConfig struct {
	Namespace         string `yaml:"namespace"`
	BasePath          string `yaml:"basePath"`
	UseStrictTypes    bool   `yaml:"useStrictTypes"`
	GenerateDocblocks bool   `yaml:"generateDocblocks"`
	FileExtension     string `yaml:"fileExtension"`
	PSRCompliant      bool   `yaml:"psrCompliant"`      // Follow PSR standards
	GenerateFromArray bool   `yaml:"generateFromArray"` // Generate fromArray() methods
	UseReadonlyProps  bool   `yaml:"useReadonlyProps"`  // Use readonly properties (PHP 8.1+)
	UseEnums          bool   `yaml:"useEnums"`          // Use PHP 8.1+ enums
}

// OpenAPIConfig holds OpenAPI processing settings.
type OpenAPIConfig struct {
	ValidateSpec bool `yaml:"validateSpec"`
	ResolveRefs  bool `yaml:"resolveRefs"`
}
