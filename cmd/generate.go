package cmd

import (
	"fmt"
	"os"

	"github.com/floriscornel/piak/internal/generator"
	"github.com/floriscornel/piak/internal/types"
	"github.com/spf13/cobra"
	"github.com/spf13/viper"
)

var (
	inputFile    string
	outputDir    string
	configFile   string
	namespace    string
	httpClient   string
	strictTypes  bool
	generateDocs bool
)

var generateCmd = &cobra.Command{
	Use:   "generate",
	Short: "Generate PHP code from OpenAPI specification",
	Long: `Generate PHP code from an OpenAPI specification file.
	
This command reads an OpenAPI specification (YAML or JSON) and generates
corresponding PHP classes and models in the specified output directory.`,
	Example: `  piak generate -i openapi.yaml -o ./generated
  piak generate --input api.json --output ./src/models
  piak generate -i spec.yaml -o ./output --config config.yaml
  piak generate -i api.yaml --namespace "MyApp\\Api" --http-client guzzle`,
	RunE: runGenerate,
}

func init() {
	// Add flags to the generate command
	generateCmd.Flags().StringVarP(&inputFile, "input", "i", "", "Input OpenAPI specification file (required)")
	generateCmd.Flags().StringVarP(&outputDir, "output", "o", "./generated", "Output directory for generated PHP files")
	generateCmd.Flags().StringVarP(&configFile, "config", "c", "", "Configuration file path")
	generateCmd.Flags().StringVarP(&namespace, "namespace", "n", "Generated", "PHP namespace for generated classes")
	generateCmd.Flags().StringVar(&httpClient, "http-client", "guzzle", "HTTP client to use (guzzle, curl, laravel)")
	generateCmd.Flags().BoolVar(&strictTypes, "strict-types", true, "Generate strict PHP types")
	generateCmd.Flags().BoolVar(&generateDocs, "generate-docs", true, "Generate PHPDoc comments")

	// Mark input as required
	generateCmd.MarkFlagRequired("input")

	// Bind flags to viper
	viper.BindPFlag("input", generateCmd.Flags().Lookup("input"))
	viper.BindPFlag("output", generateCmd.Flags().Lookup("output"))
	viper.BindPFlag("config", generateCmd.Flags().Lookup("config"))
	viper.BindPFlag("namespace", generateCmd.Flags().Lookup("namespace"))
	viper.BindPFlag("httpClient", generateCmd.Flags().Lookup("http-client"))
	viper.BindPFlag("strictTypes", generateCmd.Flags().Lookup("strict-types"))
	viper.BindPFlag("generateDocs", generateCmd.Flags().Lookup("generate-docs"))
}

func runGenerate(cmd *cobra.Command, args []string) error {
	// Build configuration from flags and config file
	config := &types.GeneratorConfig{
		HTTPClient:     types.HTTPClientType(httpClient),
		Namespace:      namespace,
		OutputDir:      outputDir,
		StrictTypes:    strictTypes,
		GenerateTests:  false, // TODO: Add flag for this
		GenerateClient: true,  // TODO: Add flag for this
		Overwrite:      true,  // TODO: Add flag for this
		PHP: types.PHPConfig{
			Namespace:         namespace,
			BasePath:          "src",
			UseStrictTypes:    strictTypes,
			GenerateDocblocks: generateDocs,
			FileExtension:     ".php",
		},
		OpenAPI: types.OpenAPIConfig{
			ValidateSpec: true,
			ResolveRefs:  true,
		},
	}

	// Override with config file if provided
	if configFile != "" {
		// TODO: Load from config file using viper
		fmt.Printf("📄 Using config file: %s\n", configFile)
	}

	// Validate input file exists
	if _, err := os.Stat(inputFile); os.IsNotExist(err) {
		return fmt.Errorf("input file does not exist: %s", inputFile)
	}

	// Create output directory if it doesn't exist
	if err := os.MkdirAll(outputDir, 0755); err != nil {
		return fmt.Errorf("failed to create output directory: %w", err)
	}

	fmt.Printf("🔄 Generating PHP code from: %s\n", inputFile)
	fmt.Printf("📁 Output directory: %s\n", outputDir)
	fmt.Printf("🏷️  Namespace: %s\n", namespace)
	fmt.Printf("🌐 HTTP Client: %s\n", httpClient)

	// Create generator with config
	gen := generator.NewGenerator(config)

	// Set input file in config
	config.InputFile = inputFile

	// Run generation
	if err := gen.Generate(); err != nil {
		return fmt.Errorf("generation failed: %w", err)
	}

	fmt.Println("✅ Generation completed successfully!")

	return nil
}

func initConfig() error {
	if configFile != "" {
		// Use config file from the flag
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

	// Read config file if it exists
	if err := viper.ReadInConfig(); err != nil {
		if _, ok := err.(viper.ConfigFileNotFoundError); !ok {
			return err
		}
	}

	return nil
}
