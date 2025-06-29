package parser

import (
	"context"
	"fmt"
	"os"

	"github.com/getkin/kin-openapi/openapi3"
)

// OpenAPIParser handles parsing of OpenAPI specifications
type OpenAPIParser struct {
	validateSpec bool
	resolveRefs  bool
}

// New creates a new OpenAPIParser instance
func New(validateSpec, resolveRefs bool) *OpenAPIParser {
	return &OpenAPIParser{
		validateSpec: validateSpec,
		resolveRefs:  resolveRefs,
	}
}

// ParseFile parses an OpenAPI specification from a file
func (p *OpenAPIParser) ParseFile(filePath string) (*openapi3.T, error) {
	// Check if file exists
	if _, err := os.Stat(filePath); os.IsNotExist(err) {
		return nil, fmt.Errorf("input file does not exist: %s", filePath)
	}

	// Load the OpenAPI specification
	loader := openapi3.NewLoader()
	spec, err := loader.LoadFromFile(filePath)
	if err != nil {
		return nil, fmt.Errorf("failed to load OpenAPI specification: %w", err)
	}

	// Validate the specification if requested
	if p.validateSpec {
		ctx := context.Background()
		if err := spec.Validate(ctx); err != nil {
			return nil, fmt.Errorf("OpenAPI specification validation failed: %w", err)
		}
	}

	return spec, nil
}

// ParseBytes parses an OpenAPI specification from bytes
func (p *OpenAPIParser) ParseBytes(data []byte) (*openapi3.T, error) {
	loader := openapi3.NewLoader()
	spec, err := loader.LoadFromData(data)
	if err != nil {
		return nil, fmt.Errorf("failed to load OpenAPI specification from data: %w", err)
	}

	// Validate the specification if requested
	if p.validateSpec {
		ctx := context.Background()
		if err := spec.Validate(ctx); err != nil {
			return nil, fmt.Errorf("OpenAPI specification validation failed: %w", err)
		}
	}

	return spec, nil
}
