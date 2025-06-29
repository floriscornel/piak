package config

import (
	"errors"
	"fmt"
	"os"

	"github.com/spf13/viper"
)

// Config holds the application configuration.
type Config struct {
	Input        string        `mapstructure:"input"`
	Output       string        `mapstructure:"output"`
	Verbose      bool          `mapstructure:"verbose"`
	PHP          PHPConfig     `mapstructure:"php"`
	OpenAPI      OpenAPIConfig `mapstructure:"openapi"`
	OutputConfig OutputConfig  `mapstructure:"output_config"`
}

// PHPConfig holds PHP-specific generation settings.
type PHPConfig struct {
	Namespace         string `mapstructure:"namespace"`
	BasePath          string `mapstructure:"base_path"`
	UseStrictTypes    bool   `mapstructure:"use_strict_types"`
	GenerateDocblocks bool   `mapstructure:"generate_docblocks"`
}

// OpenAPIConfig holds OpenAPI processing settings.
type OpenAPIConfig struct {
	ValidateSpec bool `mapstructure:"validate_spec"`
	ResolveRefs  bool `mapstructure:"resolve_refs"`
}

// OutputConfig holds output-specific settings.
type OutputConfig struct {
	Overwrite         bool   `mapstructure:"overwrite"`
	CreateDirectories bool   `mapstructure:"create_directories"`
	FileExtension     string `mapstructure:"file_extension"`
}

// Load initializes and loads the configuration.
func Load(configFile string) (*Config, error) {
	if configFile != "" {
		viper.SetConfigFile(configFile)
	} else {
		// Search for config in common locations
		viper.SetConfigName("piak")
		viper.SetConfigType("yaml")
		viper.AddConfigPath(".")
		viper.AddConfigPath("$HOME/.piak")
		viper.AddConfigPath("/etc/piak")
	}

	// Set environment variable prefix
	viper.SetEnvPrefix("PIAK")
	viper.AutomaticEnv()

	// Set defaults
	setDefaults()

	// Read config file if it exists
	if err := viper.ReadInConfig(); err != nil {
		var configFileNotFoundError viper.ConfigFileNotFoundError
		if errors.As(err, &configFileNotFoundError) {
			return nil, fmt.Errorf("failed to read config file: %w", err)
		}
	}

	var cfg Config
	if err := viper.Unmarshal(&cfg); err != nil {
		return nil, fmt.Errorf("failed to unmarshal config: %w", err)
	}

	return &cfg, nil
}

// LoadWithGlobalConfig initializes the global config (for root command).
func LoadWithGlobalConfig(configFile string) error {
	if configFile != "" {
		viper.SetConfigFile(configFile)
	} else {
		// Find home directory
		home, err := os.UserHomeDir()
		if err != nil {
			return err
		}

		// Search config in home directory with name ".piak" (without extension)
		viper.AddConfigPath(home)
		viper.AddConfigPath(".")
		viper.SetConfigType("yaml")
		viper.SetConfigName(".piak")
	}

	viper.SetEnvPrefix("PIAK")
	viper.AutomaticEnv()

	// Set defaults
	setDefaults()

	// If a config file is found, read it in
	if err := viper.ReadInConfig(); err != nil {
		var configFileNotFoundError viper.ConfigFileNotFoundError
		if errors.As(err, &configFileNotFoundError) {
			return fmt.Errorf("failed to read config file: %w", err)
		}
	}

	return nil
}

func setDefaults() {
	viper.SetDefault("output", "./generated")
	viper.SetDefault("verbose", false)
	viper.SetDefault("php.namespace", "Generated")
	viper.SetDefault("php.base_path", "src")
	viper.SetDefault("php.use_strict_types", true)
	viper.SetDefault("php.generate_docblocks", true)
	viper.SetDefault("openapi.validate_spec", true)
	viper.SetDefault("openapi.resolve_refs", true)
	viper.SetDefault("output_config.overwrite", false)
	viper.SetDefault("output_config.create_directories", true)
	viper.SetDefault("output_config.file_extension", ".php")
}

// GetConfigFileUsed returns the config file that was used.
func GetConfigFileUsed() string {
	return viper.ConfigFileUsed()
}
