package config_test

import (
	"os"
	"path/filepath"
	"testing"

	"github.com/floriscornel/piak/internal/config"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestNewLoader(t *testing.T) {
	loader := config.NewLoader()
	assert.NotNil(t, loader)
	assert.IsType(t, &config.Loader{}, loader)
}

func TestValidateConfig_ValidConfig(t *testing.T) {
	// Create a temporary input file
	tmpDir := t.TempDir()
	inputFile := filepath.Join(tmpDir, "test.yaml")
	err := os.WriteFile(inputFile, []byte("test content"), 0644)
	require.NoError(t, err)

	loader := config.NewLoader()
	cfg := &config.Config{
		Input:     inputFile,
		Output:    tmpDir,
		Namespace: "ValidNamespace",
	}

	err = loader.ValidateConfig(cfg)
	assert.NoError(t, err)
}

func TestValidateConfig_MissingInput(t *testing.T) {
	loader := config.NewLoader()
	cfg := &config.Config{
		Input:     "",
		Output:    "/tmp",
		Namespace: "ValidNamespace",
	}

	err := loader.ValidateConfig(cfg)
	require.Error(t, err)
	assert.Contains(t, err.Error(), "input file is required")
}

func TestValidateConfig_NonExistentInput(t *testing.T) {
	loader := config.NewLoader()
	cfg := &config.Config{
		Input:     "/path/that/does/not/exist.yaml",
		Output:    "/tmp",
		Namespace: "ValidNamespace",
	}

	err := loader.ValidateConfig(cfg)
	require.Error(t, err)
	assert.Contains(t, err.Error(), "input file does not exist")
}

func TestValidateConfig_MissingOutput(t *testing.T) {
	// Create a temporary input file
	tmpDir := t.TempDir()
	inputFile := filepath.Join(tmpDir, "test.yaml")
	err := os.WriteFile(inputFile, []byte("test content"), 0644)
	require.NoError(t, err)

	loader := config.NewLoader()
	cfg := &config.Config{
		Input:     inputFile,
		Output:    "",
		Namespace: "ValidNamespace",
	}

	err = loader.ValidateConfig(cfg)
	require.Error(t, err)
	assert.Contains(t, err.Error(), "output directory is required")
}

func TestValidateConfig_MissingNamespace(t *testing.T) {
	// Create a temporary input file
	tmpDir := t.TempDir()
	inputFile := filepath.Join(tmpDir, "test.yaml")
	err := os.WriteFile(inputFile, []byte("test content"), 0644)
	require.NoError(t, err)

	loader := config.NewLoader()
	cfg := &config.Config{
		Input:     inputFile,
		Output:    tmpDir,
		Namespace: "",
	}

	err = loader.ValidateConfig(cfg)
	require.Error(t, err)
	assert.Contains(t, err.Error(), "namespace is required")
}

func TestValidateConfig_InvalidNamespace(t *testing.T) {
	// Create a temporary input file
	tmpDir := t.TempDir()
	inputFile := filepath.Join(tmpDir, "test.yaml")
	err := os.WriteFile(inputFile, []byte("test content"), 0644)
	require.NoError(t, err)

	loader := config.NewLoader()
	cfg := &config.Config{
		Input:     inputFile,
		Output:    tmpDir,
		Namespace: "123InvalidNamespace", // starts with number
	}

	err = loader.ValidateConfig(cfg)
	require.Error(t, err)
	assert.Contains(t, err.Error(), "invalid PHP namespace")
}

func TestValidateConfig_MultipleErrors(t *testing.T) {
	loader := config.NewLoader()
	cfg := &config.Config{
		Input:     "",
		Output:    "",
		Namespace: "",
	}

	err := loader.ValidateConfig(cfg)
	require.Error(t, err)
	assert.Contains(t, err.Error(), "input file is required")
	assert.Contains(t, err.Error(), "output directory is required")
	assert.Contains(t, err.Error(), "namespace is required")
}

func TestValidateConfig_PHPNamespaceValidation(t *testing.T) {
	tests := []struct {
		name      string
		namespace string
		valid     bool
	}{
		{"empty namespace", "", false},
		{"simple valid namespace", "MyNamespace", true},
		{"valid with backslash", "My\\Namespace", true},
		{"valid with multiple parts", "Vendor\\Package\\Models", true},
		{"valid with underscore", "_MyNamespace", true},
		{"valid with underscore parts", "My_Vendor\\My_Package", true},
		{"invalid starts with number", "123Invalid", false},
		{"invalid part starts with number", "Valid\\123Invalid", false},
		{"invalid empty part", "Valid\\\\Models", false},
		{"invalid only backslashes", "\\\\", false},
		{"valid single underscore", "_", true},
		{"invalid with spaces", "My Namespace", false},
		{"valid complex", "VendorName\\PackageName\\SubPackage\\Models", true},
		{"valid with numbers after letters", "Test123\\Package456", true},
		{"invalid with special characters", "Test@Package", false},
		{"invalid with hyphen", "Test-Package", false},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			// Test namespace validation through ValidateConfig
			loader := config.NewLoader()
			tmpDir := t.TempDir()
			inputFile := filepath.Join(tmpDir, "test.yaml")
			err := os.WriteFile(inputFile, []byte("test content"), 0644)
			require.NoError(t, err)

			cfg := &config.Config{
				Input:     inputFile,
				Output:    tmpDir,
				Namespace: tt.namespace,
			}

			err = loader.ValidateConfig(cfg)
			if tt.valid {
				assert.NoError(t, err, "namespace: %q", tt.namespace)
			} else {
				assert.Error(t, err, "namespace: %q", tt.namespace)
				if tt.namespace != "" { // Only check for namespace error if namespace is not empty
					assert.Contains(t, err.Error(), "invalid PHP namespace", "namespace: %q", tt.namespace)
				}
			}
		})
	}
}

func TestToGeneratorConfig(t *testing.T) {
	cfg := &config.GenerateConfig{
		Config: &config.Config{
			Input:     "/path/to/input.yaml",
			Output:    "/path/to/output",
			Namespace: "My\\Namespace",
		},
		GenerateClient: true,
		GenerateTests:  false,
	}

	genConfig := cfg.ToGeneratorConfig()

	assert.NotNil(t, genConfig)
	assert.Equal(t, "/path/to/input.yaml", genConfig.InputFile)
	assert.Equal(t, "/path/to/output", genConfig.OutputDir)
	assert.Equal(t, "My\\Namespace", genConfig.Namespace)
	assert.True(t, genConfig.GenerateClient)
	assert.False(t, genConfig.GenerateTests)
}

func TestToGeneratorConfig_AllOptions(t *testing.T) {
	cfg := &config.GenerateConfig{
		Config: &config.Config{
			Input:     "input.yaml",
			Output:    "output",
			Namespace: "TestNS",
		},
		GenerateClient: false,
		GenerateTests:  true,
	}

	genConfig := cfg.ToGeneratorConfig()

	assert.Equal(t, "input.yaml", genConfig.InputFile)
	assert.Equal(t, "output", genConfig.OutputDir)
	assert.Equal(t, "TestNS", genConfig.Namespace)
	assert.False(t, genConfig.GenerateClient)
	assert.True(t, genConfig.GenerateTests)
}
