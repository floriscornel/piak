package config

import "github.com/getkin/kin-openapi/openapi3"

// PHPType represents a PHP type with additional metadata.
type PHPType struct {
	Name       string
	IsNullable bool
	IsArray    bool
	DocComment string
}

// Property represents a schema property.
type Property struct {
	Name        string
	PHPType     PHPType
	OpenAPIType *openapi3.Schema
	Required    bool
	Description string
}

// SchemaModel represents an analyzed schema ready for code generation.
type SchemaModel struct {
	Name         string
	PHPType      string
	OriginalName string
	Properties   []*Property
	IsEnum       bool
	EnumValues   []interface{}
	Description  string
}

// InternalModel represents the complete analyzed OpenAPI specification.
type InternalModel struct {
	Info    *InfoModel
	Schemas map[string]*SchemaModel
	Config  *GeneratorConfig
}

// InfoModel represents OpenAPI info section.
type InfoModel struct {
	Title       string
	Version     string
	Description string
}

// GeneratorConfig holds the essential settings for code generation.
type GeneratorConfig struct {
	InputFile      string `yaml:"input_file"`
	Namespace      string `yaml:"namespace"       validate:"required"`
	OutputDir      string `yaml:"output_dir"      validate:"required"`
	GenerateTests  bool   `yaml:"generate_tests"`
	GenerateClient bool   `yaml:"generate_client"`
}
