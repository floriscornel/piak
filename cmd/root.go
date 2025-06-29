package cmd

import (
	"fmt"
	"os"

	"github.com/spf13/cobra"
)

const banner = `       .__        __    
______ |__|____  |  | __
\____ \|  \__  \ |  |/ /
|  |_> >  |/ __ \|    < 
|   __/|__(____  /__|_ \
|__|           \/     \/`

var (
	cfgFile string
	verbose bool
)

var rootCmd = &cobra.Command{
	Use:   "piak",
	Short: "A tool to convert OpenAPI specifications to PHP code",
	Long:  banner + "\n\npiak is a tool to convert OpenAPI specifications to PHP code.",
	PersistentPreRunE: func(_ *cobra.Command, _ []string) error {
		// Initialize global configuration for all subcommands
		if err := initGlobalConfig(); err != nil {
			return fmt.Errorf("failed to initialize configuration: %w", err)
		}
		return nil
	},
}

func init() {
	// Global flags available to all commands
	rootCmd.PersistentFlags().
		StringVar(&cfgFile, "config", "", "config file (default is $HOME/.piak.yaml)")
	rootCmd.PersistentFlags().BoolVarP(&verbose, "verbose", "v", false, "verbose output")

	// Add commands
	rootCmd.AddCommand(generateCmd)
	rootCmd.AddCommand(versionCmd)
}

// initGlobalConfig initializes the global configuration that's shared across commands.
func initGlobalConfig() error {
	// Set verbose flag in a way that subcommands can access it
	if verbose {
		fmt.Fprintf(os.Stderr, "🔧 Verbose mode enabled\n")
	}

	// Validate config file exists if specified
	if cfgFile != "" {
		if _, err := os.Stat(cfgFile); os.IsNotExist(err) {
			return fmt.Errorf("config file does not exist: %s", cfgFile)
		}

		if verbose {
			fmt.Fprintf(os.Stderr, "📄 Using config file: %s\n", cfgFile)
		}
	}

	return nil
}

// Execute is the main entry point for the CLI application.
func Execute() {
	if err := rootCmd.Execute(); err != nil {
		fmt.Fprintf(os.Stderr, "Error: %v\n", err)
		os.Exit(1)
	}
}

// GetGlobalFlags returns the global flag values for use by subcommands.
func GetGlobalFlags() (string, bool) {
	return cfgFile, verbose
}
