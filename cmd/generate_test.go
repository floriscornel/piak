package cmd

import (
	"os"
	"path/filepath"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestRunGenerate_MissingRequiredFlags(t *testing.T) {
	// Create a temporary OpenAPI file for tests that need a valid input
	tmpDir := t.TempDir()
	validInputFile := filepath.Join(tmpDir, "test.yaml")
	err := os.WriteFile(validInputFile, []byte(`
openapi: 3.0.0
info:
  title: Test API
  version: 1.0.0
paths: {}
`), 0644)
	require.NoError(t, err)

	tests := []struct {
		name        string
		inputFile   string
		outputDir   string
		namespace   string
		expectedErr string
	}{
		{
			name:        "missing input file",
			inputFile:   "",
			outputDir:   "output",
			namespace:   "TestNS",
			expectedErr: "input file is required",
		},
		{
			name:        "missing output directory",
			inputFile:   validInputFile,
			outputDir:   "",
			namespace:   "TestNS",
			expectedErr: "output directory is required",
		},
		{
			name:        "missing namespace",
			inputFile:   validInputFile,
			outputDir:   "output",
			namespace:   "",
			expectedErr: "namespace is required",
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			// Save original values
			origInput := inputFile
			origOutput := outputDir
			origNamespace := namespace

			// Set test values
			inputFile = tt.inputFile
			outputDir = tt.outputDir
			namespace = tt.namespace

			// Restore original values after test
			defer func() {
				inputFile = origInput
				outputDir = origOutput
				namespace = origNamespace
			}()

			cfg, loadErr := loadConfigFromFlagsAndFile("")
			require.Error(t, loadErr)
			assert.Contains(t, loadErr.Error(), tt.expectedErr)
			assert.Nil(t, cfg)
		})
	}
}

func TestLoadConfigFromFlagsAndFile_ValidConfig(t *testing.T) {
	// Create a temporary OpenAPI file
	tmpDir := t.TempDir()
	inputFilePath := filepath.Join(tmpDir, "test.yaml")
	err := os.WriteFile(inputFilePath, []byte(`
openapi: 3.0.0
info:
  title: Test API
  version: 1.0.0
paths: {}
`), 0644)
	require.NoError(t, err)

	// Save original values
	origInput := inputFile
	origOutput := outputDir
	origNamespace := namespace

	// Set valid test values - need to provide all required flags
	inputFile = inputFilePath
	outputDir = tmpDir
	namespace = "TestNamespace"

	// Restore original values after test
	defer func() {
		inputFile = origInput
		outputDir = origOutput
		namespace = origNamespace
	}()

	cfg, err := loadConfigFromFlagsAndFile("")
	require.NoError(t, err)
	assert.NotNil(t, cfg)
	assert.Equal(t, inputFilePath, cfg.Input)
	assert.Equal(t, tmpDir, cfg.Output)
	assert.Equal(t, "TestNamespace", cfg.Namespace)
	assert.True(t, cfg.GenerateClient) // default value
	assert.False(t, cfg.GenerateTests) // default value
}

func TestLoadConfigFromFlagsAndFile_NonExistentInputFile(t *testing.T) {
	// Save original values
	origInput := inputFile
	origOutput := outputDir
	origNamespace := namespace

	// Set test values with non-existent input file
	inputFile = "/path/that/does/not/exist.yaml"
	outputDir = "output"
	namespace = "TestNS"

	// Restore original values after test
	defer func() {
		inputFile = origInput
		outputDir = origOutput
		namespace = origNamespace
	}()

	cfg, err := loadConfigFromFlagsAndFile("")
	require.Error(t, err)
	assert.Contains(t, err.Error(), "input file does not exist")
	assert.Nil(t, cfg)
}

func TestGenerateCmd_Initialization(t *testing.T) {
	// Test that the command is properly initialized
	assert.Equal(t, "generate", generateCmd.Use)
	assert.NotEmpty(t, generateCmd.Short)
	assert.NotEmpty(t, generateCmd.Long)
	assert.NotNil(t, generateCmd.RunE)

	// Test that flags are properly defined
	flags := generateCmd.Flags()
	assert.NotNil(t, flags.Lookup("config"))
	assert.NotNil(t, flags.Lookup("input"))
	assert.NotNil(t, flags.Lookup("output"))
	assert.NotNil(t, flags.Lookup("namespace"))
	assert.NotNil(t, flags.Lookup("generate-client"))
	assert.NotNil(t, flags.Lookup("generate-tests"))
}
