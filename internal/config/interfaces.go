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

// Complex interfaces commented out for MVP - these reference types we removed

// TemplateRenderer renders templates with data for different patterns.
// type TemplateRenderer interface {
// 	// Core rendering methods
// 	RenderModel(model *SchemaModel) (string, error)
// 	RenderClient(model *InternalModel) (string, error)
// 	RenderException() (string, error)
//
// 	// Pattern-specific rendering methods
// 	RenderUnionType(context *UnionTypeContext) (string, error)
// 	RenderDiscriminatedUnion(model *SchemaModel, discriminator *DiscriminatorInfo) (string, error)
// 	RenderDynamicProperties(context *DynamicPropertiesContext) (string, error)
// 	RenderCircularReference(context *CircularReferenceContext) (string, error)
// 	RenderConditionalSchema(context *ConditionalSchemaContext) (string, error)
// 	RenderArrayReference(context *ArrayReferenceContext) (string, error)
// 	RenderEnum(model *SchemaModel) (string, error)
//
// 	// Method-specific rendering
// 	RenderFromArrayMethod(model *SchemaModel) (string, error)
// 	RenderConstructor(model *SchemaModel) (string, error)
// 	RenderAccessors(property *Property) (string, error)
// 	RenderValidation(rules []*ValidationRule) (string, error)
//
// 	// Partial template rendering
// 	RenderPHPHeader(config *PHPConfig) (string, error)
// 	RenderUseStatements(imports []string) (string, error)
// 	RenderClassDocblock(model *SchemaModel) (string, error)
// 	RenderPropertyDocblock(property *Property) (string, error)
//
// 	// Template utilities
// 	GetTemplateContext(model *SchemaModel) map[string]interface{}
// 	ValidateTemplate(templateName string) error
// 	ReloadTemplates() error
// }

// TemplateContextBuilder builds template contexts for complex patterns.
// type TemplateContextBuilder interface {
// 	BuildUnionTypeContext(property *Property, schemas []*openapi3.Schema) *UnionTypeContext
// 	BuildDynamicPropertiesContext(
// 		model *SchemaModel,
// 		additionalProps *openapi3.Schema,
// 	) *DynamicPropertiesContext
// 	BuildCircularReferenceContext(model *SchemaModel, refs []*CircularRef) *CircularReferenceContext
// 	BuildConditionalSchemaContext(
// 		model *SchemaModel,
// 		conditions []*Condition,
// 	) *ConditionalSchemaContext
// 	BuildArrayReferenceContext(property *Property, itemSchema *openapi3.Schema) *ArrayReferenceContext
// 	BuildDiscriminatorInfo(schema *openapi3.Schema) *DiscriminatorInfo
// }

// PatternDetector detects OpenAPI patterns that require special handling.
// type PatternDetector interface {
// 	DetectUnionTypes(schema *openapi3.Schema) bool
// 	DetectDiscriminatedUnion(schema *openapi3.Schema) bool
// 	DetectDynamicProperties(schema *openapi3.Schema) bool
// 	DetectCircularReferences(schemas map[string]*openapi3.Schema, currentSchema string, visited map[string]bool) bool
// 	DetectConditionalSchemas(schema *openapi3.Schema) bool
// 	DetectPolymorphicArrays(schema *openapi3.Schema) bool
// 	DetectRecursiveSchemas(schema *openapi3.Schema) bool
// 	DetectMultipleInheritance(schema *openapi3.Schema) bool
// 	AnalyzeComplexity(schema *openapi3.Schema) []SpecialCase
// }

// TemplateValidator validates template content and structure.
// type TemplateValidator interface {
// 	ValidateTemplateContent(templateName string, content string) error
// 	ValidateTemplateContext(context map[string]interface{}) error
// 	ValidateGeneratedCode(content string, language string) error
// 	CheckTemplateSyntax(templatePath string) error
// }

// CodeFormatter formats generated code according to language standards.
// type CodeFormatter interface {
// 	FormatPHPCode(content string) (string, error)
// 	ValidatePHPSyntax(content string) error
// 	ApplyPSRStandards(content string) (string, error)
// 	OptimizeImports(content string) (string, error)
// }
