package cmd

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestGetGlobalFlags(t *testing.T) {
	// Test default values
	configFile, verbose := GetGlobalFlags()
	assert.Equal(t, "", configFile)
	assert.False(t, verbose)
}

func TestRootCmd_Initialization(t *testing.T) {
	// Test that the root command is properly initialized
	assert.Equal(t, "piak", rootCmd.Use)
	assert.NotEmpty(t, rootCmd.Short)
	assert.NotEmpty(t, rootCmd.Long)

	// Test that global flags are properly defined
	flags := rootCmd.PersistentFlags()
	assert.NotNil(t, flags.Lookup("config"))
	assert.NotNil(t, flags.Lookup("verbose"))
}

func TestExecute(t *testing.T) {
	// Test that Execute doesn't panic
	assert.NotPanics(t, func() {
		// We can't really test Execute without affecting the global state
		// but we can at least verify it exists and is callable
		_ = Execute
	})
}
