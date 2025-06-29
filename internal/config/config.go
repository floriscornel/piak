package config

import (
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"strings"

	"github.com/spf13/viper"
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

// Loader handles configuration loading and validation.
type Loader struct {
	v *viper.Viper
}

// NewLoader creates a new configuration loader.
func NewLoader() *Loader {
	v := viper.New()
	setDefaults(v)
	return &Loader{v: v}
}

// LoadConfig loads and validates the main application config.
func (l *Loader) LoadConfig(configFile string) (*Config, error) {
	if err := l.setupViper(configFile); err != nil {
		return nil, fmt.Errorf("failed to setup configuration: %w", err)
	}

	var cfg Config
	if err := l.v.Unmarshal(&cfg); err != nil {
		return nil, fmt.Errorf("failed to unmarshal config: %w", err)
	}

	if err := l.ValidateConfig(&cfg); err != nil {
		return nil, fmt.Errorf("config validation failed: %w", err)
	}

	return &cfg, nil
}

// LoadGenerateConfig loads and validates the generate command config.
func (l *Loader) LoadGenerateConfig(configFile string) (*GenerateConfig, error) {
	baseConfig, err := l.LoadConfig(configFile)
	if err != nil {
		return nil, err
	}

	var genConfig GenerateConfig
	genConfig.Config = baseConfig

	// Unmarshal generate-specific settings
	if unmarshalErr := l.v.Unmarshal(&genConfig); unmarshalErr != nil {
		return nil, fmt.Errorf("failed to unmarshal generate config: %w", unmarshalErr)
	}

	if validationErr := l.validateGenerateConfig(&genConfig); validationErr != nil {
		return nil, fmt.Errorf("generate config validation failed: %w", validationErr)
	}

	return &genConfig, nil
}

// BindFlags binds command-line flags to the viper instance.
func (l *Loader) BindFlags(flags map[string]interface{}) error {
	for key, value := range flags {
		l.v.Set(key, value)
	}
	return nil
}

// setupViper configures the viper instance for loading config files.
func (l *Loader) setupViper(configFile string) error {
	if configFile != "" {
		if err := l.setupSpecificConfigFile(configFile); err != nil {
			return err
		}
	} else {
		if err := l.setupDefaultConfigPaths(); err != nil {
			return err
		}
	}

	// Set environment variable prefix
	l.v.SetEnvPrefix("PIAK")
	l.v.AutomaticEnv()

	// Replace dots with underscores in env vars
	l.v.SetEnvKeyReplacer(strings.NewReplacer(".", "_"))

	// Read config file if it exists
	if err := l.v.ReadInConfig(); err != nil {
		var configFileNotFoundError viper.ConfigFileNotFoundError
		if !errors.As(err, &configFileNotFoundError) {
			return fmt.Errorf("failed to read config file: %w", err)
		}
		// Config file not found is OK, we'll use defaults
	}

	return nil
}

// setupSpecificConfigFile configures viper for a specific config file.
func (l *Loader) setupSpecificConfigFile(configFile string) error {
	// Use specific config file
	if !filepath.IsAbs(configFile) {
		abs, err := filepath.Abs(configFile)
		if err != nil {
			return fmt.Errorf("failed to resolve config file path: %w", err)
		}
		configFile = abs
	}
	l.v.SetConfigFile(configFile)
	return nil
}

// setupDefaultConfigPaths configures viper to search in default locations.
func (l *Loader) setupDefaultConfigPaths() error {
	// For a code generation tool, config should be project-specific
	// Search for config only in current directory
	l.v.SetConfigName("piak")
	l.v.SetConfigType("yaml")
	l.v.AddConfigPath(".") // Only current directory

	return nil
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

// validateGenerateConfig validates the generate command configuration.
func (l *Loader) validateGenerateConfig(_ *GenerateConfig) error {
	// For now, just rely on base config validation
	return nil
}

// setDefaults sets default configuration values.
func setDefaults(v *viper.Viper) {
	v.SetDefault("output", "./generated")
	v.SetDefault("namespace", "Generated")
	v.SetDefault("generate_client", true)
	v.SetDefault("generate_tests", false)
}

// isValidPHPNamespace checks if the PHP namespace is valid.
func isValidPHPNamespace(namespace string) bool {
	if namespace == "" {
		return false
	}

	// Basic validation - could be more comprehensive
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
	}

	return true
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
