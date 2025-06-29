package cmd

import (
	"fmt"
	"os"
	"strconv"

	"github.com/floriscornel/piak/internal/config"
	"github.com/floriscornel/piak/internal/generator"
	"github.com/spf13/cobra"
	"github.com/spf13/pflag"
	"github.com/spf13/viper"
)

var (
	// Only keep command-specific flags that aren't in config
	configFile string
)

// generateCmd represents the generate command.
var generateCmd = &cobra.Command{
	Use:   "generate",
	Short: "Generate PHP code from OpenAPI specification",
	Long: `Generate PHP classes, models, and optionally client code from an OpenAPI 3.0+ specification.

This command reads an OpenAPI specification file and generates corresponding PHP code
including models, DTOs, and optionally HTTP client classes.

Configuration can be provided through:
1. Command line flags (highest priority)
2. Environment variables with PIAK_ prefix  
3. Configuration file (piak.yaml)
4. Default values (lowest priority)

Examples:
  piak generate -i api.yaml -o ./generated
  piak generate --input api.yaml --namespace "MyApp\\Models"
  piak generate --config ./custom-config.yaml --dry-run`,
	RunE: runGenerate,
}

func init() {
	// First add the config file flag manually
	generateCmd.Flags().StringVarP(&configFile, "config", "c", "", "Configuration file path")

	// Create a temporary viper instance for auto-flag generation (only for init)
	tempViper := viper.New()

	// Create a temporary config instance to generate flags from
	tempConfig := &config.GenerateConfig{
		Config: &config.Config{},
	}

	// Use auto-flags to generate CLI flags from struct tags
	autoFlags := config.NewAutoFlags(generateCmd, tempViper)
	if err := autoFlags.BindFlags(tempConfig); err != nil {
		// Handle the error gracefully during init
		fmt.Printf("Warning: Failed to auto-bind flags: %v\n", err)
	}
}

// runGenerate executes the generate command with auto-generated flags.
func runGenerate(cmd *cobra.Command, _ []string) error {
	// Get global flags from root command
	globalConfigFile, _ := GetGlobalFlags()

	// Use global config file if local one isn't specified
	actualConfigFile := configFile
	if actualConfigFile == "" {
		actualConfigFile = globalConfigFile
	}

	// Create config loader
	loader := config.NewLoader()

	// Bind the cobra flags to viper (this bridges auto-generated flags with the config system)
	if err := bindCobraFlagsToViper(cmd, loader); err != nil {
		return fmt.Errorf("failed to bind flags: %w", err)
	}

	// Load configuration normally (flags are now bound)
	cfg, err := loader.LoadGenerateConfig(actualConfigFile)
	if err != nil {
		return fmt.Errorf("failed to load configuration: %w", err)
	}

	// Display configuration summary if verbose
	if cfg.Verbose || cfg.DryRun {
		displayConfigSummary(cfg, loader.GetConfigFileUsed())
	}

	// Handle dry run
	if cfg.DryRun {
		return handleDryRun(cfg)
	}

	// Execute generation
	return executeGeneration(cfg)
}

// bindCobraFlagsToViper binds all cobra flags to the viper instance
func bindCobraFlagsToViper(cmd *cobra.Command, loader *config.Loader) error {
	flagMap := make(map[string]interface{})

	// Map flag names to config keys - this must match the mapstructure tags
	flagToConfigMap := map[string]string{
		"input":               "input",
		"output":              "output",
		"verbose":             "verbose",
		"namespace":           "php.namespace",
		"base-path":           "php.base_path",
		"generate-docs":       "php.generate_docblocks",
		"file-extension":      "php.file_extension",
		"php-strict-types":    "php.use_strict_types",
		"generate-from-array": "php.generate_from_array",
		"psr-compliant":       "php.psr_compliant",
		"use-enums":           "php.use_enums",
		"use-readonly-props":  "php.use_readonly_props",
		"validate-spec":       "openapi.validate_spec",
		"resolve-refs":        "openapi.resolve_refs",
		"overwrite":           "output_config.overwrite",
		"create-directories":  "output_config.create_directories",
		"http-client":         "http_client",
		"strict-types":        "strict_types",
		"generate-client":     "generate_client",
		"generate-tests":      "generate_tests",
		"dry-run":             "dry_run",
	}

	// Iterate through all flags and add their values to the map
	cmd.Flags().VisitAll(func(flag *pflag.Flag) {
		if flag.Changed {
			configKey, exists := flagToConfigMap[flag.Name]
			if !exists {
				// Skip unknown flags (like config, help)
				return
			}

			// Handle different flag types
			switch flag.Value.Type() {
			case "bool":
				boolVal, _ := strconv.ParseBool(flag.Value.String())
				flagMap[configKey] = boolVal
			case "int":
				intVal, _ := strconv.Atoi(flag.Value.String())
				flagMap[configKey] = intVal
			case "float32", "float64":
				floatVal, _ := strconv.ParseFloat(flag.Value.String(), 64)
				flagMap[configKey] = floatVal
			default:
				// String and other types
				flagMap[configKey] = flag.Value.String()
			}
		}
	})

	// Use the existing BindFlags method
	return loader.BindFlags(flagMap)
}

// displayConfigSummary shows the current configuration.
func displayConfigSummary(cfg *config.GenerateConfig, configFile string) {
	fmt.Printf("🔧 Configuration Summary\n")
	fmt.Printf("========================\n")
	if configFile != "" {
		fmt.Printf("📋 Config file: %s\n", configFile)
	}
	fmt.Printf("📥 Input file: %s\n", cfg.Input)
	fmt.Printf("📁 Output directory: %s\n", cfg.Output)
	fmt.Printf("🏷️  PHP namespace: %s\n", cfg.PHP.Namespace)
	fmt.Printf("🌐 HTTP client: %s\n", cfg.HTTPClient)
	fmt.Printf("🔒 Strict validation: %t\n", cfg.StrictTypes)
	fmt.Printf("📝 Generate docs: %t\n", cfg.PHP.GenerateDocblocks)
	fmt.Printf("⚡ PHP strict types: %t\n", cfg.PHP.UseStrictTypes)
	fmt.Printf("🔄 Generate fromArray(): %t\n", cfg.PHP.GenerateFromArray)
	fmt.Printf("🏛️  Use enums: %t\n", cfg.PHP.UseEnums)
	fmt.Printf("🔧 Generate client: %t\n", cfg.GenerateClient)
	fmt.Printf("🧪 Generate tests: %t\n", cfg.GenerateTests)

	if cfg.DryRun {
		fmt.Printf("🔍 Mode: DRY RUN (no files will be created)\n")
	}

	fmt.Printf("\n")
}

// handleDryRun simulates the generation process without creating files.
func handleDryRun(cfg *config.GenerateConfig) error {
	fmt.Printf("🔍 DRY RUN: Simulating generation process...\n\n")

	genConfig := cfg.ToGeneratorConfig()

	// Create generator instance
	gen, err := generator.NewGenerator(genConfig)
	if err != nil {
		return fmt.Errorf("failed to create generator: %w", err)
	}

	// TODO: Implement DryRun method in generator
	fmt.Printf("📁 Would create output directory: %s\n", cfg.Output)
	fmt.Printf("🔄 Would process OpenAPI file: %s\n", cfg.Input)
	fmt.Printf("🏗️  Would generate PHP classes with namespace: %s\n", cfg.PHP.Namespace)

	if cfg.GenerateClient {
		fmt.Printf("🌐 Would generate HTTP client using: %s\n", cfg.HTTPClient)
	}

	if cfg.GenerateTests {
		fmt.Printf("🧪 Would generate test files\n")
	}

	fmt.Printf("\n✅ Dry run completed. Use --verbose to see full configuration.\n")
	fmt.Printf("💡 Remove --dry-run flag to execute actual generation.\n")

	// Validate that we can actually do the work by creating the generator
	_ = gen

	return nil
}

// executeGeneration performs the actual code generation.
func executeGeneration(cfg *config.GenerateConfig) error {
	genConfig := cfg.ToGeneratorConfig()

	// Create generator instance
	gen, err := generator.NewGenerator(genConfig)
	if err != nil {
		return fmt.Errorf("failed to create generator: %w", err)
	}

	// Check if output directory exists
	if _, err := os.Stat(cfg.Output); os.IsNotExist(err) {
		if cfg.OutputConfig.CreateDirectories {
			if mkdirErr := os.MkdirAll(cfg.Output, 0755); mkdirErr != nil {
				return fmt.Errorf("failed to create output directory: %w", mkdirErr)
			}
			fmt.Printf("📁 Created output directory: %s\n", cfg.Output)
		} else {
			return fmt.Errorf("output directory does not exist: %s", cfg.Output)
		}
	}

	// Generate code
	fmt.Printf("🚀 Starting code generation...\n")
	if err := gen.Generate(); err != nil {
		return fmt.Errorf("code generation failed: %w", err)
	}

	fmt.Printf("✅ Code generation completed successfully!\n")
	return nil
}
