package cmd

import (
	"fmt"
	"os"

	"github.com/spf13/cobra"
	"github.com/spf13/viper"
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
}

func init() {
	cobra.OnInitialize(initRootConfig)
	cobra.EnableCommandSorting = false

	// Global flags
	rootCmd.PersistentFlags().StringVar(&cfgFile, "config", "", "config file (default is $HOME/.piak.yaml)")
	rootCmd.PersistentFlags().BoolVarP(&verbose, "verbose", "v", false, "verbose output")

	// Bind global flags to viper
	viper.BindPFlag("verbose", rootCmd.PersistentFlags().Lookup("verbose"))

	// Add commands
	rootCmd.AddCommand(generateCmd)
	rootCmd.AddCommand(versionCmd)
}

func initRootConfig() {
	if cfgFile != "" {
		// Use config file from the flag
		viper.SetConfigFile(cfgFile)
	} else {
		// Find home directory
		home, err := os.UserHomeDir()
		cobra.CheckErr(err)

		// Search config in home directory with name ".piak" (without extension)
		viper.AddConfigPath(home)
		viper.AddConfigPath(".")
		viper.SetConfigType("yaml")
		viper.SetConfigName(".piak")
	}

	viper.SetEnvPrefix("PIAK")
	viper.AutomaticEnv() // read in environment variables that match

	// If a config file is found, read it in
	if err := viper.ReadInConfig(); err == nil && verbose {
		fmt.Fprintln(os.Stderr, "Using config file:", viper.ConfigFileUsed())
	}
}

func Execute() {
	if err := rootCmd.Execute(); err != nil {
		fmt.Println(err)
		os.Exit(1)
	}
}
