package cmd

import (
	"testing"

	"github.com/stretchr/testify/assert"
)

func TestVersionCmd_Initialization(t *testing.T) {
	// Test that the version command is properly initialized
	assert.Equal(t, "version", versionCmd.Use)
	assert.NotEmpty(t, versionCmd.Short)
	assert.NotNil(t, versionCmd.Run)
}

func TestVersionVariables(t *testing.T) {
	// Test that version variables exist and have default values
	assert.NotEmpty(t, version)
	assert.NotEmpty(t, commit)
	assert.NotEmpty(t, date)

	// Test default values
	assert.Equal(t, "dev", version)
	assert.Equal(t, "none", commit)
	assert.Equal(t, "unknown", date)
}
