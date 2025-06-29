package interfaces

import (
	"github.com/floriscornel/piak/internal/types"
	"github.com/getkin/kin-openapi/openapi3"
)

// Parser handles OpenAPI specification parsing.
type Parser interface {
	Parse(specPath string) (*openapi3.T, error)
	ParseBytes(data []byte) (*openapi3.T, error)
}

// Analyzer analyzes OpenAPI specifications and builds internal models.
type Analyzer interface {
	Analyze(spec *openapi3.T) (*types.InternalModel, error)
	AnalyzeSchemas(spec *openapi3.T) (map[string]*types.SchemaModel, error)
	AnalyzeEndpoints(spec *openapi3.T) ([]*types.EndpointModel, error)
	DetectSpecialCases(schema *openapi3.Schema) []types.SpecialCase
	BuildTemplateContext(model *types.SchemaModel) map[string]interface{}
}

// Generator generates code from the internal model.
type Generator interface {
	Generate(model *types.InternalModel) error
}

// TypeMapper maps OpenAPI types to target language types.
type TypeMapper interface {
	MapType(schema *openapi3.Schema) types.PHPType
	MapStringType(schema *openapi3.Schema) types.PHPType
	MapArrayType(schema *openapi3.Schema) types.PHPType
	MapObjectType(schema *openapi3.Schema) types.PHPType
	MapUnionType(schemas []*openapi3.Schema, discriminator *types.DiscriminatorInfo) types.PHPType
	MapEnumType(schema *openapi3.Schema) types.PHPType
	ResolveImports(phpType *types.PHPType) []string
}

// TemplateRenderer renders templates with data for different patterns.
type TemplateRenderer interface {
	// Core rendering methods
	RenderModel(model *types.SchemaModel) (string, error)
	RenderClient(model *types.InternalModel) (string, error)
	RenderException() (string, error)

	// Pattern-specific rendering methods
	RenderUnionType(context *types.UnionTypeContext) (string, error)
	RenderDiscriminatedUnion(model *types.SchemaModel, discriminator *types.DiscriminatorInfo) (string, error)
	RenderDynamicProperties(context *types.DynamicPropertiesContext) (string, error)
	RenderCircularReference(context *types.CircularReferenceContext) (string, error)
	RenderConditionalSchema(context *types.ConditionalSchemaContext) (string, error)
	RenderArrayReference(context *types.ArrayReferenceContext) (string, error)
	RenderEnum(model *types.SchemaModel) (string, error)

	// Method-specific rendering
	RenderFromArrayMethod(model *types.SchemaModel) (string, error)
	RenderConstructor(model *types.SchemaModel) (string, error)
	RenderAccessors(property *types.Property) (string, error)
	RenderValidation(rules []*types.ValidationRule) (string, error)

	// Partial template rendering
	RenderPHPHeader(config *types.PHPConfig) (string, error)
	RenderUseStatements(imports []string) (string, error)
	RenderClassDocblock(model *types.SchemaModel) (string, error)
	RenderPropertyDocblock(property *types.Property) (string, error)

	// Template utilities
	GetTemplateContext(model *types.SchemaModel) map[string]interface{}
	ValidateTemplate(templateName string) error
	ReloadTemplates() error
}

// TemplateContextBuilder builds template contexts for complex patterns.
type TemplateContextBuilder interface {
	BuildUnionTypeContext(property *types.Property, schemas []*openapi3.Schema) *types.UnionTypeContext
	BuildDynamicPropertiesContext(
		model *types.SchemaModel,
		additionalProps *openapi3.Schema,
	) *types.DynamicPropertiesContext
	BuildCircularReferenceContext(model *types.SchemaModel, refs []*types.CircularRef) *types.CircularReferenceContext
	BuildConditionalSchemaContext(
		model *types.SchemaModel,
		conditions []*types.Condition,
	) *types.ConditionalSchemaContext
	BuildArrayReferenceContext(property *types.Property, itemSchema *openapi3.Schema) *types.ArrayReferenceContext
	BuildDiscriminatorInfo(schema *openapi3.Schema) *types.DiscriminatorInfo
}

// PatternDetector detects OpenAPI patterns that require special handling.
type PatternDetector interface {
	DetectUnionTypes(schema *openapi3.Schema) bool
	DetectDiscriminatedUnion(schema *openapi3.Schema) bool
	DetectDynamicProperties(schema *openapi3.Schema) bool
	DetectCircularReferences(schemas map[string]*openapi3.Schema, currentSchema string, visited map[string]bool) bool
	DetectConditionalSchemas(schema *openapi3.Schema) bool
	DetectPolymorphicArrays(schema *openapi3.Schema) bool
	DetectRecursiveSchemas(schema *openapi3.Schema) bool
	DetectMultipleInheritance(schema *openapi3.Schema) bool
	AnalyzeComplexity(schema *openapi3.Schema) []types.SpecialCase
}

// FileWriter handles file output operations.
type FileWriter interface {
	WriteFile(path string, content []byte) error
	MkdirAll(path string) error
	Exists(path string) bool
	BackupFile(path string) error
	ValidateOutput(path string, content []byte) error
}

// TemplateValidator validates template content and structure.
type TemplateValidator interface {
	ValidateTemplateContent(templateName string, content string) error
	ValidateTemplateContext(context map[string]interface{}) error
	ValidateGeneratedCode(content string, language string) error
	CheckTemplateSyntax(templatePath string) error
}

// CodeFormatter formats generated code according to language standards.
type CodeFormatter interface {
	FormatPHPCode(content string) (string, error)
	ValidatePHPSyntax(content string) error
	ApplyPSRStandards(content string) (string, error)
	OptimizeImports(content string) (string, error)
}
