package config

import "github.com/getkin/kin-openapi/openapi3"

// MVP: Comment out edge cases for now - focus on basic schema generation
// SpecialCase represents different edge cases in OpenAPI specs.
// type SpecialCase int

// const (
// 	CircularReference SpecialCase = iota
// 	DiscriminatedUnion
// 	PolymorphicArray
// 	DynamicProperties
// 	AmbiguousUnion
// 	DeepReferenceChains
// 	ArrayReferences
// 	MixedContentTypes
// 	RecursiveSchemas
// 	MultipleInheritance
// 	UnionTypes
// 	ConditionalSchemas
// 	NullableOptional
// )

// MVP: Comment out HTTP client types for now - just use default
// HTTPClientType represents different HTTP client implementations.
// type HTTPClientType string

// const (
// 	GuzzleClient  HTTPClientType = "guzzle"
// 	CurlClient    HTTPClientType = "curl"
// 	LaravelClient HTTPClientType = "laravel"
// )

// MVP: Simplified PHPType for basic type mapping
// PHPType represents a PHP type with additional metadata for complex patterns.
type PHPType struct {
	Name       string
	IsNullable bool
	IsArray    bool
	DocComment string
	// MVP: Comment out complex features
	// IsUnion          bool
	// UnionTypes       []string
	// ArrayItemType    *PHPType
	// ImportStatements []string // Required use statements
	// IsEnum           bool
	// EnumValues       []interface{}
	// Format           string // OpenAPI format (date, uuid, etc.)
}

// MVP: Simplified Property for basic property handling
// Property represents a schema property with enhanced pattern support.
type Property struct {
	Name        string
	PHPType     PHPType
	OpenAPIType *openapi3.Schema
	Required    bool
	Description string
	// MVP: Comment out complex features
	// SpecialCases    []SpecialCase
	// ValidationRules []*ValidationRule
	// DefaultValue    interface{}
	// Example         interface{}
}

// MVP: Comment out validation rules for now
// ValidationRule represents a property validation constraint.
// type ValidationRule struct {
// 	Type         string // enum, pattern, range, etc.
// 	Value        interface{}
// 	ErrorMessage string
// }

// MVP: Comment out inheritance for now
// InheritanceStrategy defines how inheritance should be implemented.
// type InheritanceStrategy struct {
// 	Type        InheritanceType
// 	BaseClass   string
// 	Interface   string
// 	Composition []*SchemaModel // For multiple inheritance via composition
// }

// type InheritanceType int

// const (
// 	NoInheritance InheritanceType = iota
// 	AbstractClass
// 	InterfaceImpl
// 	Composition
// 	AllOfFlattening // For multiple inheritance patterns
// )

// MVP: Simplified SchemaModel for basic code generation
// SchemaModel represents a analyzed schema ready for code generation.
type SchemaModel struct {
	Name         string
	PHPType      string
	OriginalName string
	Properties   []*Property
	IsEnum       bool
	EnumValues   []interface{}
	Description  string
	// MVP: Comment out complex features
	// Inheritance     *InheritanceStrategy
	// SpecialCases    []SpecialCase
	// Dependencies    []*SchemaModel
	// TemplateContext map[string]interface{} // Pattern-specific template data
	// Methods         []*MethodModel         // Custom methods (fromArray, etc.)
}

// MVP: Comment out method generation for now
// MethodModel represents a generated method.
// type MethodModel struct {
// 	Name       string
// 	Visibility string // public, private, protected
// 	IsStatic   bool
// 	Parameters []*ParameterModel
// 	ReturnType string
// 	Body       string // Template-generated method body
// 	DocComment string
// }

// MVP: Comment out discriminator support for now
// DiscriminatorInfo represents discriminated union metadata.
// type DiscriminatorInfo struct {
// 	PropertyName string
// 	ValueMapping map[string]string // discriminator value -> class name
// 	Required     bool
// }

// MVP: Comment out union types for now
// UnionTypeContext provides template context for union types.
// type UnionTypeContext struct {
// 	Property          *Property
// 	UnionMembers      []*PHPType
// 	Discriminator     *DiscriminatorInfo
// 	DetectionStrategy string // property-based, try-catch, heuristic
// 	IsOneOf           bool   // true for oneOf, false for anyOf
// }

// MVP: Comment out dynamic properties for now
// DynamicPropertiesContext provides template context for additionalProperties.
// type DynamicPropertiesContext struct {
// 	BaseProperties       []*Property
// 	AdditionalProperties *Property // Type constraint for additional props
// 	AllowsAdditional     bool
// 	AdditionalStrategy   string // strict, typed, mixed
// }

// MVP: Comment out circular references for now
// CircularReferenceContext provides template context for circular references.
// type CircularReferenceContext struct {
// 	MainClass          *SchemaModel
// 	CircularReferences []*CircularRef
// 	BreakingStrategy   string // ID-based, depth-limited, factory-methods
// 	MaxDepth           int
// }

// MVP: Comment out circular reference handling
// CircularRef represents a circular reference relationship.
// type CircularRef struct {
// 	PropertyName   string
// 	TargetSchema   string
// 	ReferenceType  string // direct, array, nested
// 	BreakingMethod string // use-id, omit-property, factory-method
// }

// MVP: Comment out conditional schemas for now
// ConditionalSchemaContext provides template context for conditional schemas.
// type ConditionalSchemaContext struct {
// 	BaseSchema      *SchemaModel
// 	Conditions      []*Condition
// 	ValidationRules []*ValidationRule
// }

// MVP: Comment out conditions for now
// Condition represents an if/then/else condition in OpenAPI.
// type Condition struct {
// 	IfProperty   string
// 	IfValue      interface{}
// 	ThenRequired []string
// 	ThenSchema   *SchemaModel
// 	ElseRequired []string
// 	ElseSchema   *SchemaModel
// }

// MVP: Comment out array references for now
// ArrayReferenceContext provides template context for complex array patterns.
// type ArrayReferenceContext struct {
// 	BaseProperty       *Property
// 	ItemType           *PHPType
// 	IsPolymorphic      bool // Different types in same array
// 	PolymorphicTypes   []*PHPType
// 	CircularPrevention bool
// }

// MVP: Comment out endpoints for now - focus on models first
// EndpointModel represents an API endpoint.
// type EndpointModel struct {
// 	Path        string
// 	Method      string
// 	OperationID string
// 	Summary     string
// 	Description string
// 	Parameters  []*ParameterModel
// 	RequestBody *RequestBodyModel
// 	Responses   []*ResponseModel
// 	HTTPClient  HTTPClientType
// 	MethodName  string // Generated method name
// }

// MVP: Comment out parameters for now
// ParameterModel represents an endpoint parameter.
// type ParameterModel struct {
// 	Name        string
// 	In          string // query, path, header
// 	Required    bool
// 	Schema      *SchemaModel
// 	Description string
// 	Example     interface{}
// }

// MVP: Comment out request body for now
// RequestBodyModel represents a request body.
// type RequestBodyModel struct {
// 	Required    bool
// 	ContentType string
// 	Schema      *SchemaModel
// 	Description string
// }

// MVP: Comment out responses for now
// ResponseModel represents an endpoint response.
// type ResponseModel struct {
// 	StatusCode  string
// 	ContentType string
// 	Schema      *SchemaModel
// 	Description string
// 	IsError     bool
// }

// MVP: Simplified internal model for basic generation
// InternalModel represents the complete analyzed OpenAPI specification.
type InternalModel struct {
	Info    *InfoModel
	Schemas map[string]*SchemaModel
	Config  *GeneratorConfig
	// MVP: Comment out endpoints for now
	// Endpoints []*EndpointModel
}

// InfoModel represents API information.
type InfoModel struct {
	Title       string
	Version     string
	Description string
	// MVP: Comment out contact/license for now
	// Contact     *ContactInfo
	// License     *LicenseInfo
}

// MVP: Comment out contact info for now
// ContactInfo represents API contact information.
// type ContactInfo struct {
// 	Name  string
// 	Email string
// 	URL   string
// }

// MVP: Comment out license info for now
// LicenseInfo represents API license information.
// type LicenseInfo struct {
// 	Name string
// 	URL  string
// }

// MVP: Simplified generator config with only essential settings
// GeneratorConfig holds all configuration for code generation.
type GeneratorConfig struct {
	InputFile      string `yaml:"input_file"`
	Namespace      string `yaml:"namespace"      validate:"required"`
	OutputDir      string `yaml:"output_dir"     validate:"required"`
	GenerateTests  bool   `yaml:"generate_tests"`
	GenerateClient bool   `yaml:"generate_client"`

	// MVP: Comment out complex configurations
	// HTTPClient     HTTPClientType `yaml:"http_client"     validate:"oneof=guzzle curl laravel"`
	// StrictTypes    bool           `yaml:"strict_types"`
	// Overwrite      bool           `yaml:"overwrite"`
	// PHP specific settings
	// PHP PHPConfig `yaml:"php"`
	// OpenAPI processing settings
	// OpenAPI OpenAPIConfig `yaml:"openapi"`
}

// MVP: Comment out complex PHP config for now
// PHPConfig holds PHP-specific generation settings.
// type PHPConfig struct {
// 	Namespace         string `yaml:"namespace"           mapstructure:"namespace"           validate:"required" flag:"namespace,n" usage:"PHP namespace for generated classes"`
// 	BasePath          string `yaml:"base_path"           mapstructure:"base_path"                               flag:"base-path"    usage:"Base path for PHP files"`
// 	UseStrictTypes    bool   `yaml:"use_strict_types"    mapstructure:"use_strict_types"                        flag:"php-strict-types" usage:"Add declare(strict_types=1) to PHP files" default:"true"`
// 	GenerateDocblocks bool   `yaml:"generate_docblocks"  mapstructure:"generate_docblocks"                      flag:"generate-docs" usage:"Generate PHPDoc comments" default:"true"`
// 	FileExtension     string `yaml:"file_extension"      mapstructure:"file_extension"                          flag:"file-extension" usage:"File extension for generated files" default:".php"`
// 	PSRCompliant      bool   `yaml:"psr_compliant"       mapstructure:"psr_compliant"                           flag:"psr-compliant" usage:"Follow PSR standards" default:"true"`
// 	GenerateFromArray bool   `yaml:"generate_from_array" mapstructure:"generate_from_array"                     flag:"generate-from-array" usage:"Generate fromArray() methods for models" default:"true"`
// 	UseReadonlyProps  bool   `yaml:"use_readonly_props"  mapstructure:"use_readonly_props"                      flag:"use-readonly-props" usage:"Use readonly properties (PHP 8.1+)" default:"true"`
// 	UseEnums          bool   `yaml:"use_enums"           mapstructure:"use_enums"                               flag:"use-enums" usage:"Use PHP 8.1+ enums instead of constants" default:"true"`
// }

// MVP: Comment out OpenAPI config for now
// OpenAPIConfig holds OpenAPI processing settings.
// type OpenAPIConfig struct {
// 	ValidateSpec bool `yaml:"validate_spec" mapstructure:"validate_spec" flag:"validate-spec" usage:"Validate OpenAPI specification" default:"true"`
// 	ResolveRefs  bool `yaml:"resolve_refs"  mapstructure:"resolve_refs"  flag:"resolve-refs" usage:"Resolve OpenAPI references" default:"true"`
// }

// MVP: Comment out output config for now
// OutputConfig holds output-specific settings.
// type OutputConfig struct {
// 	Overwrite         bool `yaml:"overwrite"          mapstructure:"overwrite"          flag:"overwrite" usage:"Overwrite existing files" default:"false"`
// 	CreateDirectories bool `yaml:"create_directories" mapstructure:"create_directories" flag:"create-directories" usage:"Create output directories if they don't exist" default:"true"`
// }
