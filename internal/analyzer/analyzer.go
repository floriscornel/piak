package analyzer

import (
	"fmt"

	"github.com/getkin/kin-openapi/openapi3"
)

// Analyzer analyzes OpenAPI specifications and extracts information for code generation
type Analyzer struct {
	spec *openapi3.T
}

// New creates a new Analyzer instance
func New(spec *openapi3.T) *Analyzer {
	return &Analyzer{
		spec: spec,
	}
}

// SchemaInfo contains information about a schema for code generation
type SchemaInfo struct {
	Name        string
	Schema      *openapi3.Schema
	Required    []string
	Properties  map[string]*openapi3.SchemaRef
	IsEnum      bool
	EnumValues  []interface{}
	Description string
}

// AnalyzeSchemas extracts and analyzes all schemas from the OpenAPI specification
func (a *Analyzer) AnalyzeSchemas() (map[string]*SchemaInfo, error) {
	if a.spec.Components == nil || a.spec.Components.Schemas == nil {
		return nil, fmt.Errorf("no schemas found in OpenAPI specification")
	}

	schemas := make(map[string]*SchemaInfo)

	for name, schemaRef := range a.spec.Components.Schemas {
		if schemaRef.Value == nil {
			continue
		}

		schema := schemaRef.Value
		info := &SchemaInfo{
			Name:        name,
			Schema:      schema,
			Required:    schema.Required,
			Properties:  schema.Properties,
			Description: schema.Description,
		}

		// Check if it's an enum
		if len(schema.Enum) > 0 {
			info.IsEnum = true
			info.EnumValues = schema.Enum
		}

		schemas[name] = info
	}

	return schemas, nil
}

// GetInfo returns basic information about the OpenAPI specification
func (a *Analyzer) GetInfo() map[string]interface{} {
	info := make(map[string]interface{})

	if a.spec.Info != nil {
		info["title"] = a.spec.Info.Title
		info["version"] = a.spec.Info.Version
		info["description"] = a.spec.Info.Description
	}

	if a.spec.Components != nil && a.spec.Components.Schemas != nil {
		info["schema_count"] = len(a.spec.Components.Schemas)
	}

	if a.spec.Paths != nil {
		info["path_count"] = len(a.spec.Paths.Map())
	}

	return info
}
