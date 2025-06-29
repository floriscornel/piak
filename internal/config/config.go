package config

import (
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"strings"

	"github.com/spf13/viper"
)

// MVP: Comment out complex HTTP client validation for now
// ValidHTTPClients are the valid HTTP client types.
// var ValidHTTPClients = []string{
// 	string(GuzzleClient),
// 	string(CurlClient),
// 	string(LaravelClient),
// }

// MVP: Simplified config with only essential options
// Config holds the application configuration.
type Config struct {
	Input     string `mapstructure:"input"     validate:"required" flag:"input,i" usage:"Input OpenAPI specification file"`
	Output    string `mapstructure:"output"    validate:"required" flag:"output,o" usage:"Output directory for generated PHP files"`
	Namespace string `mapstructure:"namespace" validate:"required" flag:"namespace,n" usage:"PHP namespace for generated classes"`
	// MVP: Comment out verbose for now - only output on errors
	// Verbose      bool          `mapstructure:"verbose"                           flag:"verbose,v" usage:"Enable verbose output"`
	// MVP: Comment out complex nested configs
	// PHP          PHPConfig     `mapstructure:"php"`
	// OpenAPI      OpenAPIConfig `mapstructure:"openapi"`
	// OutputConfig OutputConfig  `mapstructure:"output_config"`
}

// MVP: Simplified generate config with only essential options
// GenerateConfig holds generation-specific configuration.
type GenerateConfig struct {
	*Config
	// MVP: Only keep essential generation options
	GenerateClient bool `mapstructure:"generate_client" flag:"generate-client" usage:"Generate HTTP client code" default:"true"`
	GenerateTests  bool `mapstructure:"generate_tests"  flag:"generate-tests" usage:"Generate test files" default:"false"`

	// MVP: Comment out complex options
	// HTTPClient     HTTPClientType `mapstructure:"http_client"     flag:"http-client" usage:"HTTP client to use (guzzle, curl, laravel)" default:"guzzle"`
	// StrictTypes    bool           `mapstructure:"strict_types"    flag:"strict-types" usage:"Generate strict PHP types and validation" default:"true"`
	// DryRun         bool           `mapstructure:"dry_run"         flag:"dry-run" usage:"Show what would be generated without creating files" default:"false"`
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

	if err := l.validateConfig(&cfg); err != nil {
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

// MVP: Comment out complex auto-flag generation
// LoadGenerateConfigWithAutoFlags loads configuration and automatically generates CLI flags.
// func (l *Loader) LoadGenerateConfigWithAutoFlags(cmd *cobra.Command, configFile string) (*GenerateConfig, error) {
// 	// Setup viper first
// 	if err := l.setupViper(configFile); err != nil {
// 		return nil, fmt.Errorf("failed to setup configuration: %w", err)
// 	}

// 	// Create a config instance for auto-flag generation
// 	cfg := &GenerateConfig{
// 		Config: &Config{},
// 	}

// 	// Use auto-flags to generate CLI flags from struct tags
// 	autoFlags := NewAutoFlags(cmd, l.v)
// 	if err := autoFlags.BindFlags(cfg); err != nil {
// 		return nil, fmt.Errorf("failed to auto-bind flags: %w", err)
// 	}

// 	// Load configuration after flags are bound
// 	baseConfig, err := l.LoadConfig(configFile)
// 	if err != nil {
// 		return nil, err
// 	}

// 	// Set base config
// 	cfg.Config = baseConfig

// 	// Unmarshal generate-specific settings
// 	if unmarshalErr := l.v.Unmarshal(cfg); unmarshalErr != nil {
// 		return nil, fmt.Errorf("failed to unmarshal generate config: %w", unmarshalErr)
// 	}

// 	if validationErr := l.validateGenerateConfig(cfg); validationErr != nil {
// 		return nil, fmt.Errorf("generate config validation failed: %w", validationErr)
// 	}

// 	return cfg, nil
// }

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

// MVP: Simplified validation - only check essential fields
// validateConfig validates the base configuration.
func (l *Loader) validateConfig(cfg *Config) error {
	var errs []string

	// Validate input file
	if cfg.Input == "" {
		errs = append(errs, "input file is required")
	} else if _, err := os.Stat(cfg.Input); os.IsNotExist(err) {
		errs = append(errs, fmt.Sprintf("input file does not exist: %s", cfg.Input))
	}

	// Validate output directory
	if cfg.Output == "" {
		errs = append(errs, "output directory cannot be empty")
	}

	// Validate PHP namespace
	if cfg.Namespace == "" {
		errs = append(errs, "PHP namespace cannot be empty")
	} else if !isValidPHPNamespace(cfg.Namespace) {
		errs = append(errs, fmt.Sprintf("invalid PHP namespace: %s", cfg.Namespace))
	}

	// MVP: Comment out complex validation
	// // Validate file extension
	// if cfg.PHP.FileExtension != "" &&
	// 	!strings.HasPrefix(cfg.PHP.FileExtension, ".") {
	// 	errs = append(errs, "file extension must start with a dot")
	// }

	if len(errs) > 0 {
		return fmt.Errorf("validation errors:\n  - %s", strings.Join(errs, "\n  - "))
	}

	return nil
}

// MVP: Simplified validation
// validateGenerateConfig validates the generate command configuration.
func (l *Loader) validateGenerateConfig(cfg *GenerateConfig) error {
	// MVP: For now, just rely on base config validation
	// var errs []string

	// MVP: Comment out HTTP client validation
	// // Validate HTTP client
	// if !isValidHTTPClient(string(cfg.HTTPClient)) {
	// 	errs = append(errs, fmt.Sprintf("invalid HTTP client '%s', valid options: %s",
	// 		cfg.HTTPClient, strings.Join(ValidHTTPClients, ", ")))
	// }

	// if len(errs) > 0 {
	// 	return fmt.Errorf("validation errors:\n  - %s", strings.Join(errs, "\n  - "))
	// }

	return nil
}

// MVP: Simplified defaults
// setDefaults sets default configuration values.
func setDefaults(v *viper.Viper) {
	// MVP: Only essential defaults
	v.SetDefault("output", "./generated")
	v.SetDefault("namespace", "Generated")
	v.SetDefault("generate_client", true)
	v.SetDefault("generate_tests", false)

	// MVP: Comment out complex defaults
	// // Base defaults
	// v.SetDefault("verbose", false)

	// // PHP defaults
	// v.SetDefault("php.namespace", "Generated")
	// v.SetDefault("php.base_path", "src")
	// v.SetDefault("php.use_strict_types", true)
	// v.SetDefault("php.generate_docblocks", true)
	// v.SetDefault("php.file_extension", ".php")
	// v.SetDefault("php.psr_compliant", true)
	// v.SetDefault("php.generate_from_array", true)
	// v.SetDefault("php.use_readonly_props", true)
	// v.SetDefault("php.use_enums", true)

	// // OpenAPI defaults
	// v.SetDefault("openapi.validate_spec", true)
	// v.SetDefault("openapi.resolve_refs", true)

	// // Output defaults
	// v.SetDefault("output_config.overwrite", false)
	// v.SetDefault("output_config.create_directories", true)

	// // Generate defaults
	// v.SetDefault("http_client", string(GuzzleClient))
	// v.SetDefault("strict_types", true)
	// v.SetDefault("dry_run", false)
}

// MVP: Comment out HTTP client validation
// isValidHTTPClient checks if the HTTP client type is valid.
// func isValidHTTPClient(client string) bool {
// 	for _, valid := range ValidHTTPClients {
// 		if client == valid {
// 			return true
// 		}
// 	}
// 	return false
// }

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

// GetConfigFileUsed returns the config file that was used.
func (l *Loader) GetConfigFileUsed() string {
	return l.v.ConfigFileUsed()
}

// MVP: Simplified generator config conversion
// ToGeneratorConfig converts the generate config to a generator config.
func (cfg *GenerateConfig) ToGeneratorConfig() *GeneratorConfig {
	return &GeneratorConfig{
		InputFile:      cfg.Input,
		OutputDir:      cfg.Output,
		Namespace:      cfg.Namespace,
		GenerateTests:  cfg.GenerateTests,
		GenerateClient: cfg.GenerateClient,
		// MVP: Use hardcoded sensible defaults instead of complex configuration
		// HTTPClient:     cfg.HTTPClient,
		// StrictTypes:    cfg.StrictTypes,
		// Overwrite:      cfg.OutputConfig.Overwrite,
		// PHP:            cfg.PHP,     // Direct assignment - no conversion needed!
		// OpenAPI:        cfg.OpenAPI, // Direct assignment - no conversion needed!
	}
}
