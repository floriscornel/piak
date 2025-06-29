package cmd

import (
	"fmt"
	"os"

	"github.com/floriscornel/piak/internal/config"
	"github.com/floriscornel/piak/internal/generator"
	"github.com/spf13/cobra"
)

var (
	// Simple manual flags for essential options.
	configFile     string
	inputFile      string
	outputDir      string
	namespace      string
	generateClient bool
	generateTests  bool
)

// generateCmd represents the generate command.
var generateCmd = &cobra.Command{
	Use:   "generate",
	Short: "Generate PHP code from OpenAPI specification",
	Long: `Generate PHP classes, models, and optionally client code from an OpenAPI 3.0+ specification.

This command reads an OpenAPI specification file and generates corresponding PHP code
including models, DTOs, and optionally HTTP client classes.

Examples:
  piak generate -i api.yaml -o ./generated
  piak generate --input api.yaml --namespace "MyApp\\Models"
  piak generate -i api.yaml -o ./generated --generate-client --generate-tests`,
	RunE: runGenerate,
}

func init() {
	// Simple manual flags
	generateCmd.Flags().StringVarP(&configFile, "config", "c", "", "Configuration file path")
	generateCmd.Flags().StringVarP(&inputFile, "input", "i", "", "Input OpenAPI specification file (required)")
	generateCmd.Flags().StringVarP(&outputDir, "output", "o", "", "Output directory for generated PHP files (required)")
	generateCmd.Flags().StringVarP(&namespace, "namespace", "n", "", "PHP namespace for generated classes (required)")
	generateCmd.Flags().BoolVar(&generateClient, "generate-client", true, "Generate HTTP client code")
	generateCmd.Flags().BoolVar(&generateTests, "generate-tests", false, "Generate test files")
}

// runGenerate executes the generate command.
func runGenerate(_ *cobra.Command, _ []string) error {
	// Get global flags from root command
	globalConfigFile, _ := GetGlobalFlags()

	// Use global config file if local one isn't specified
	actualConfigFile := configFile
	if actualConfigFile == "" {
		actualConfigFile = globalConfigFile
	}

	// Create config from flags and file
	cfg, err := loadConfigFromFlagsAndFile(actualConfigFile)
	if err != nil {
		return fmt.Errorf("failed to load configuration: %w", err)
	}

	// Execute generation
	return executeGeneration(cfg)
}

// loadConfigFromFlagsAndFile creates configuration from flags and file.
func loadConfigFromFlagsAndFile(_ string) (*config.GenerateConfig, error) {
	// Create a base config from flags
	baseConfig := &config.Config{
		Input:     inputFile,
		Output:    outputDir,
		Namespace: namespace,
	}

	// TODO: Add config file support later if needed
	// For now, we prioritize command-line flags

	// Create generate config
	cfg := &config.GenerateConfig{
		Config:         baseConfig,
		GenerateClient: generateClient,
		GenerateTests:  generateTests,
	}

	// Validate the final configuration
	loader := config.NewLoader()
	if err := loader.ValidateConfig(cfg.Config); err != nil {
		return nil, fmt.Errorf("config validation failed: %w", err)
	}

	return cfg, nil
}

// executeGeneration performs the actual code generation.
func executeGeneration(cfg *config.GenerateConfig) error {
	genConfig := cfg.ToGeneratorConfig()

	// Create generator instance
	gen, err := generator.NewGenerator(genConfig)
	if err != nil {
		return fmt.Errorf("failed to create generator: %w", err)
	}

	// Check if output directory exists and create it if needed
	if _, statErr := os.Stat(cfg.Output); os.IsNotExist(statErr) {
		if mkdirErr := os.MkdirAll(cfg.Output, 0755); mkdirErr != nil {
			return fmt.Errorf("failed to create output directory: %w", mkdirErr)
		}
	}

	// Generate code
	if genErr := gen.Generate(); genErr != nil {
		return fmt.Errorf("code generation failed: %w", genErr)
	}

	return nil
}
