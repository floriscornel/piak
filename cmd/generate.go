package cmd

import (
	"fmt"
	"os"

	"github.com/floriscornel/piak/internal/config"
	"github.com/floriscornel/piak/internal/generator"
	"github.com/spf13/cobra"
)

var (
	// MVP: Simple manual flags for essential options only
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
	// MVP: Simple manual flags
	generateCmd.Flags().StringVarP(&configFile, "config", "c", "", "Configuration file path")
	generateCmd.Flags().StringVarP(&inputFile, "input", "i", "", "Input OpenAPI specification file (required)")
	generateCmd.Flags().StringVarP(&outputDir, "output", "o", "", "Output directory for generated PHP files (required)")
	generateCmd.Flags().StringVarP(&namespace, "namespace", "n", "", "PHP namespace for generated classes (required)")
	generateCmd.Flags().BoolVar(&generateClient, "generate-client", true, "Generate HTTP client code")
	generateCmd.Flags().BoolVar(&generateTests, "generate-tests", false, "Generate test files")

	// MVP: Comment out complex auto-flag generation
	// // First add the config file flag manually
	// generateCmd.Flags().StringVarP(&configFile, "config", "c", "", "Configuration file path")

	// // Create a temporary viper instance for auto-flag generation (only for init)
	// tempViper := viper.New()

	// // Create a temporary config instance to generate flags from
	// tempConfig := &config.GenerateConfig{
	// 	Config: &config.Config{},
	// }

	// // Use auto-flags to generate CLI flags from struct tags
	// autoFlags := config.NewAutoFlags(generateCmd, tempViper)
	// if err := autoFlags.BindFlags(tempConfig); err != nil {
	// 	// Handle the error gracefully during init
	// 	fmt.Printf("Warning: Failed to auto-bind flags: %v\n", err)
	// }
}

// MVP: Simplified run function with manual flag handling
// runGenerate executes the generate command with auto-generated flags.
func runGenerate(cmd *cobra.Command, _ []string) error {
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

	// MVP: Only show errors, no verbose output
	// // Display configuration summary if verbose
	// if cfg.Verbose || cfg.DryRun {
	// 	displayConfigSummary(cfg, loader.GetConfigFileUsed())
	// }

	// MVP: No dry run for now
	// // Handle dry run
	// if cfg.DryRun {
	// 	return handleDryRun(cfg)
	// }

	// Execute generation
	return executeGeneration(cfg)
}

// MVP: Simple config loading from flags and file
func loadConfigFromFlagsAndFile(configFile string) (*config.GenerateConfig, error) {
	// Create config loader
	loader := config.NewLoader()

	// Load base config from file (if exists)
	baseConfig, err := loader.LoadConfig(configFile)
	if err != nil {
		return nil, err
	}

	// Override with command line flags
	if inputFile != "" {
		baseConfig.Input = inputFile
	}
	if outputDir != "" {
		baseConfig.Output = outputDir
	}
	if namespace != "" {
		baseConfig.Namespace = namespace
	}

	// Create generate config
	cfg := &config.GenerateConfig{
		Config:         baseConfig,
		GenerateClient: generateClient,
		GenerateTests:  generateTests,
	}

	// Validate required fields
	if cfg.Input == "" {
		return nil, fmt.Errorf("input file is required (use -i or --input)")
	}
	if cfg.Output == "" {
		return nil, fmt.Errorf("output directory is required (use -o or --output)")
	}
	if cfg.Namespace == "" {
		return nil, fmt.Errorf("namespace is required (use -n or --namespace)")
	}

	return cfg, nil
}

// MVP: Comment out complex flag binding
// bindCobraFlagsToViper binds all cobra flags to the viper instance
// func bindCobraFlagsToViper(cmd *cobra.Command, loader *config.Loader) error {
// 	flagMap := make(map[string]interface{})

// 	// Map flag names to config keys - this must match the mapstructure tags
// 	flagToConfigMap := map[string]string{
// 		"input":               "input",
// 		"output":              "output",
// 		"verbose":             "verbose",
// 		"namespace":           "php.namespace",
// 		"base-path":           "php.base_path",
// 		"generate-docs":       "php.generate_docblocks",
// 		"file-extension":      "php.file_extension",
// 		"php-strict-types":    "php.use_strict_types",
// 		"generate-from-array": "php.generate_from_array",
// 		"psr-compliant":       "php.psr_compliant",
// 		"use-enums":           "php.use_enums",
// 		"use-readonly-props":  "php.use_readonly_props",
// 		"validate-spec":       "openapi.validate_spec",
// 		"resolve-refs":        "openapi.resolve_refs",
// 		"overwrite":           "output_config.overwrite",
// 		"create-directories":  "output_config.create_directories",
// 		"http-client":         "http_client",
// 		"strict-types":        "strict_types",
// 		"generate-client":     "generate_client",
// 		"generate-tests":      "generate_tests",
// 		"dry-run":             "dry_run",
// 	}

// 	// Iterate through all flags and add their values to the map
// 	cmd.Flags().VisitAll(func(flag *pflag.Flag) {
// 		if flag.Changed {
// 			configKey, exists := flagToConfigMap[flag.Name]
// 			if !exists {
// 				// Skip unknown flags (like config, help)
// 				return
// 			}

// 			// Handle different flag types
// 			switch flag.Value.Type() {
// 			case "bool":
// 				boolVal, _ := strconv.ParseBool(flag.Value.String())
// 				flagMap[configKey] = boolVal
// 			case "int":
// 				intVal, _ := strconv.Atoi(flag.Value.String())
// 				flagMap[configKey] = intVal
// 			case "float32", "float64":
// 				floatVal, _ := strconv.ParseFloat(flag.Value.String(), 64)
// 				flagMap[configKey] = floatVal
// 			default:
// 				// String and other types
// 				flagMap[configKey] = flag.Value.String()
// 			}
// 		}
// 	})

// 	// Use the existing BindFlags method
// 	return loader.BindFlags(flagMap)
// }

// MVP: Comment out verbose config display
// displayConfigSummary shows the current configuration.
// func displayConfigSummary(cfg *config.GenerateConfig, configFile string) {
// 	fmt.Printf("🔧 Configuration Summary\n")
// 	fmt.Printf("========================\n")
// 	if configFile != "" {
// 		fmt.Printf("📋 Config file: %s\n", configFile)
// 	}
// 	fmt.Printf("📥 Input file: %s\n", cfg.Input)
// 	fmt.Printf("📁 Output directory: %s\n", cfg.Output)
// 	fmt.Printf("🏷️  PHP namespace: %s\n", cfg.PHP.Namespace)
// 	fmt.Printf("🌐 HTTP client: %s\n", cfg.HTTPClient)
// 	fmt.Printf("🔒 Strict validation: %t\n", cfg.StrictTypes)
// 	fmt.Printf("📝 Generate docs: %t\n", cfg.PHP.GenerateDocblocks)
// 	fmt.Printf("⚡ PHP strict types: %t\n", cfg.PHP.UseStrictTypes)
// 	fmt.Printf("🔄 Generate fromArray(): %t\n", cfg.PHP.GenerateFromArray)
// 	fmt.Printf("🏛️  Use enums: %t\n", cfg.PHP.UseEnums)
// 	fmt.Printf("🔧 Generate client: %t\n", cfg.GenerateClient)
// 	fmt.Printf("🧪 Generate tests: %t\n", cfg.GenerateTests)

// 	if cfg.DryRun {
// 		fmt.Printf("🔍 Mode: DRY RUN (no files will be created)\n")
// 	}

// 	fmt.Printf("\n")
// }

// MVP: Comment out dry run functionality
// handleDryRun simulates the generation process without creating files.
// func handleDryRun(cfg *config.GenerateConfig) error {
// 	fmt.Printf("🔍 DRY RUN: Simulating generation process...\n\n")

// 	genConfig := cfg.ToGeneratorConfig()

// 	// Create generator instance
// 	gen, err := generator.NewGenerator(genConfig)
// 	if err != nil {
// 		return fmt.Errorf("failed to create generator: %w", err)
// 	}

// 	// TODO: Implement DryRun method in generator
// 	fmt.Printf("📁 Would create output directory: %s\n", cfg.Output)
// 	fmt.Printf("🔄 Would process OpenAPI file: %s\n", cfg.Input)
// 	fmt.Printf("🏗️  Would generate PHP classes with namespace: %s\n", cfg.PHP.Namespace)

// 	if cfg.GenerateClient {
// 		fmt.Printf("🌐 Would generate HTTP client using: %s\n", cfg.HTTPClient)
// 	}

// 	if cfg.GenerateTests {
// 		fmt.Printf("🧪 Would generate test files\n")
// 	}

// 	fmt.Printf("\n✅ Dry run completed. Use --verbose to see full configuration.\n")
// 	fmt.Printf("💡 Remove --dry-run flag to execute actual generation.\n")

// 	// Validate that we can actually do the work by creating the generator
// 	_ = gen

// 	return nil
// }

// executeGeneration performs the actual code generation.
func executeGeneration(cfg *config.GenerateConfig) error {
	genConfig := cfg.ToGeneratorConfig()

	// Create generator instance
	gen, err := generator.NewGenerator(genConfig)
	if err != nil {
		return fmt.Errorf("failed to create generator: %w", err)
	}

	// Check if output directory exists and create it if needed
	if _, err := os.Stat(cfg.Output); os.IsNotExist(err) {
		if mkdirErr := os.MkdirAll(cfg.Output, 0755); mkdirErr != nil {
			return fmt.Errorf("failed to create output directory: %w", mkdirErr)
		}
	}

	// Generate code
	if err := gen.Generate(); err != nil {
		return fmt.Errorf("code generation failed: %w", err)
	}

	return nil
}
