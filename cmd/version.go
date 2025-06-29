package cmd

import (
	"fmt"
	"runtime"

	"github.com/spf13/cobra"
)

var (
	// These will be set by build flags.
	version = "dev"
	commit  = "none"
	date    = "unknown"
)

var versionCmd = &cobra.Command{
	Use:   "version",
	Short: "Print the version information",
	Long:  "Print the version information for piak",
	Run: func(_ *cobra.Command, _ []string) {
		fmt.Printf("piak version: %s\n", version)
		fmt.Printf("Git commit: %s\n", commit)
		fmt.Printf("Built: %s\n", date)
		fmt.Printf("Go version: %s\n", runtime.Version())
		fmt.Printf("OS/Arch: %s/%s\n", runtime.GOOS, runtime.GOARCH)
	},
}
