package config

import (
	"fmt"
	"os"
	"strings"
)

// Config holds the application configuration.
type Config struct {
	Input     string `mapstructure:"input"     validate:"required" flag:"input,i"     usage:"Input OpenAPI spec file"`
	Output    string `mapstructure:"output"    validate:"required" flag:"output,o"    usage:"Output dir for PHP files"`
	Namespace string `mapstructure:"namespace" validate:"required" flag:"namespace,n" usage:"PHP namespace"`
}

// GenerateConfig holds generation-specific configuration.
type GenerateConfig struct {
	*Config
	GenerateClient bool `mapstructure:"generate_client" flag:"generate-client" usage:"Generate HTTP client code" default:"true"`
	GenerateTests  bool `mapstructure:"generate_tests"  flag:"generate-tests"  usage:"Generate test files"       default:"false"`
}

// Loader handles configuration validation.
type Loader struct{}

// NewLoader creates a new configuration loader.
func NewLoader() *Loader {
	return &Loader{}
}

// ValidateConfig validates the base configuration.
func (l *Loader) ValidateConfig(cfg *Config) error {
	var errs []string

	// Validate input file
	if cfg.Input == "" {
		errs = append(errs, "input file is required")
	} else if _, err := os.Stat(cfg.Input); os.IsNotExist(err) {
		errs = append(errs, fmt.Sprintf("input file does not exist: %s", cfg.Input))
	}

	// Validate output directory
	if cfg.Output == "" {
		errs = append(errs, "output directory is required")
	}

	// Validate PHP namespace
	if cfg.Namespace == "" {
		errs = append(errs, "namespace is required")
	} else if !isValidPHPNamespace(cfg.Namespace) {
		errs = append(errs, fmt.Sprintf("invalid PHP namespace: %s", cfg.Namespace))
	}

	if len(errs) > 0 {
		return fmt.Errorf("validation errors:\n  - %s", strings.Join(errs, "\n  - "))
	}

	return nil
}

// isValidPHPNamespace checks if the PHP namespace is valid.
func isValidPHPNamespace(namespace string) bool {
	if namespace == "" {
		return false
	}

	// Split by backslashes to check each part
	parts := strings.Split(namespace, "\\")
	for _, part := range parts {
		part = strings.TrimSpace(part)
		if part == "" {
			return false
		}

		// Check if part starts with a letter or underscore
		if len(part) == 0 || (!isLetter(rune(part[0])) && part[0] != '_') {
			return false
		}

		// Check that all characters are valid (letters, numbers, underscores)
		for _, char := range part {
			if !isLetter(char) && !isDigit(char) && char != '_' {
				return false
			}
		}
	}

	return true
}

// isDigit checks if a rune is a digit.
func isDigit(r rune) bool {
	return r >= '0' && r <= '9'
}

// isLetter checks if a rune is a letter.
func isLetter(r rune) bool {
	return (r >= 'a' && r <= 'z') || (r >= 'A' && r <= 'Z')
}

// ToGeneratorConfig converts the generate config to a generator config.
func (cfg *GenerateConfig) ToGeneratorConfig() *GeneratorConfig {
	return &GeneratorConfig{
		InputFile:      cfg.Input,
		OutputDir:      cfg.Output,
		Namespace:      cfg.Namespace,
		GenerateTests:  cfg.GenerateTests,
		GenerateClient: cfg.GenerateClient,
	}
}
