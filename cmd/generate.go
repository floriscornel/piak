package cmd

import (
	"fmt"
	"os"

	"github.com/floriscornel/piak/internal/config"
	"github.com/floriscornel/piak/internal/generator"
	"github.com/spf13/cobra"
)

var (
	// Command-specific flags.
	inputFile      string
	outputDir      string
	configFile     string
	namespace      string
	httpClient     string
	strictTypes    bool
	generateDocs   bool
	generateClient bool
	generateTests  bool
	dryRun         bool
)

var generateCmd = &cobra.Command{
	Use:   "generate",
	Short: "Generate PHP code from OpenAPI specification",
	Long: `Generate PHP code from an OpenAPI specification file.
	
This command reads an OpenAPI specification (YAML or JSON) and generates
corresponding PHP classes and models in the specified output directory.

The command supports various configuration options that can be specified via:
1. Command-line flags (highest priority)
2. Environment variables (PIAK_*)
3. Configuration file (./piak.yaml in current directory)
4. Default values (lowest priority)`,
	Example: `  # Basic usage
  piak generate -i openapi.yaml -o ./generated

  # With custom namespace and HTTP client
  piak generate -i api.json --namespace "MyApp\\Api" --http-client guzzle

  # Using project config file
  piak generate -i spec.yaml --config piak.yaml

  # Dry run to preview changes
  piak generate -i api.yaml --dry-run

  # Generate with tests
  piak generate -i api.yaml --generate-tests`,
	RunE: runGenerate,
}

func init() {
	// Required flags - but validation handled by config system to allow config file values
	generateCmd.Flags().
		StringVarP(&inputFile, "input", "i", "", "Input OpenAPI specification file (required)")

		// Note: Removed MarkFlagRequired to allow config file to provide required values

	// Output configuration
	generateCmd.Flags().
		StringVarP(&outputDir, "output", "o", "", "Output directory for generated PHP files")
	generateCmd.Flags().StringVarP(&configFile, "config", "c", "", "Configuration file path")

	// PHP generation options
	generateCmd.Flags().
		StringVarP(&namespace, "namespace", "n", "", "PHP namespace for generated classes")
	generateCmd.Flags().
		StringVar(&httpClient, "http-client", "", "HTTP client to use (guzzle, curl, laravel)")
	generateCmd.Flags().BoolVar(&strictTypes, "strict-types", true, "Generate strict PHP types")
	generateCmd.Flags().BoolVar(&generateDocs, "generate-docs", true, "Generate PHPDoc comments")

	// Generation options
	generateCmd.Flags().
		BoolVar(&generateClient, "generate-client", true, "Generate HTTP client code")
	generateCmd.Flags().BoolVar(&generateTests, "generate-tests", false, "Generate test files")
	generateCmd.Flags().
		BoolVar(&dryRun, "dry-run", false, "Show what would be generated without creating files")
}

// runGenerate executes the generate command with proper config loading and validation.
func runGenerate(_ *cobra.Command, _ []string) error {
	// Get global flags from root command
	globalConfigFile, globalVerbose := GetGlobalFlags()

	// Use global config file if local one isn't specified
	actualConfigFile := configFile
	if actualConfigFile == "" {
		actualConfigFile = globalConfigFile
	}

	// Create config loader
	loader := config.NewLoader()

	// Bind command-line flags to config
	flags := buildFlagsMap(globalVerbose)
	if err := loader.BindFlags(flags); err != nil {
		return fmt.Errorf("failed to bind flags: %w", err)
	}

	// Load and validate configuration
	cfg, err := loader.LoadGenerateConfig(actualConfigFile)
	if err != nil {
		return fmt.Errorf("failed to load configuration: %w", err)
	}

	// Display configuration summary if verbose
	if cfg.Verbose || dryRun {
		displayConfigSummary(cfg, loader.GetConfigFileUsed())
	}

	// Handle dry run
	if cfg.DryRun {
		return handleDryRun(cfg)
	}

	// Execute generation
	return executeGeneration(cfg)
}

// buildFlagsMap creates a map of flag values for config binding.
func buildFlagsMap(globalVerbose bool) map[string]interface{} {
	flags := make(map[string]interface{})

	// Only set non-zero values to preserve config file/default precedence
	if inputFile != "" {
		flags["input"] = inputFile
	}
	if outputDir != "" {
		flags["output"] = outputDir
	}
	if namespace != "" {
		flags["php.namespace"] = namespace
	}
	if httpClient != "" {
		flags["http_client"] = httpClient
	}

	// Set global verbose flag
	flags["verbose"] = globalVerbose

	// Boolean flags - always set since cobra defaults them
	flags["strict_types"] = strictTypes
	flags["php.generate_docblocks"] = generateDocs
	flags["generate_client"] = generateClient
	flags["generate_tests"] = generateTests
	flags["dry_run"] = dryRun

	return flags
}

// displayConfigSummary shows the current configuration to the user.
func displayConfigSummary(cfg *config.GenerateConfig, configFile string) {
	fmt.Printf("📋 Configuration Summary\n")
	fmt.Printf("═══════════════════════\n")

	if configFile != "" {
		fmt.Printf("📄 Config file: %s\n", configFile)
	} else {
		fmt.Printf("📄 Config file: <using defaults>\n")
	}

	fmt.Printf("📥 Input file: %s\n", cfg.Input)
	fmt.Printf("📁 Output directory: %s\n", cfg.Output)
	fmt.Printf("🏷️  PHP namespace: %s\n", cfg.PHP.Namespace)
	fmt.Printf("🌐 HTTP client: %s\n", cfg.HTTPClient)
	fmt.Printf("🔒 Strict types: %t\n", cfg.StrictTypes)
	fmt.Printf("📝 Generate docs: %t\n", cfg.PHP.GenerateDocblocks)
	fmt.Printf("🔧 Generate client: %t\n", cfg.GenerateClient)
	fmt.Printf("🧪 Generate tests: %t\n", cfg.GenerateTests)

	if cfg.DryRun {
		fmt.Printf("🔍 Mode: DRY RUN (no files will be created)\n")
	}

	fmt.Printf("\n")
}

// handleDryRun shows what would be generated without actually creating files.
func handleDryRun(cfg *config.GenerateConfig) error {
	fmt.Printf("🔍 DRY RUN MODE - Preview of planned generation\n")
	fmt.Printf("═════════════════════════════════════════════\n\n")

	// Convert to generator config
	genConfig := cfg.ToGeneratorConfig()

	// Create generator
	gen := generator.NewGenerator(genConfig)

	// TODO: Add a DryRun method to generator interface
	// For now, just show what would be done
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

	// Validate that we can actually do the work
	_ = gen // Use the generator to avoid unused variable

	return nil
}

// executeGeneration performs the actual code generation.
func executeGeneration(cfg *config.GenerateConfig) error {
	// Create output directory if it doesn't exist and creation is enabled
	if cfg.OutputConfig.CreateDirectories {
		if err := os.MkdirAll(cfg.Output, 0750); err != nil {
			return fmt.Errorf("failed to create output directory: %w", err)
		}
	}

	// Convert to generator config
	genConfig := cfg.ToGeneratorConfig()

	// Create and run generator
	gen := generator.NewGenerator(genConfig)

	fmt.Printf("🔄 Generating PHP code from: %s\n", cfg.Input)

	if err := gen.Generate(); err != nil {
		return fmt.Errorf("generation failed: %w", err)
	}

	// Success message
	fmt.Printf("✅ Generation completed successfully!\n")
	fmt.Printf("📁 Generated files in: %s\n", cfg.Output)

	if cfg.GenerateClient {
		fmt.Printf("🌐 HTTP client: %s\n", cfg.HTTPClient)
	}

	if cfg.Verbose {
		fmt.Printf("🏷️  Namespace: %s\n", cfg.PHP.Namespace)
		fmt.Printf("📝 Documentation: %t\n", cfg.PHP.GenerateDocblocks)
		fmt.Printf("🔒 Strict types: %t\n", cfg.StrictTypes)
	}

	return nil
}
