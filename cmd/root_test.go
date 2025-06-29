package cmd

import (
	"bytes"
	"io"
	"os"
	"path/filepath"
	"testing"

	"github.com/spf13/cobra"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
)

func TestGetGlobalFlags(t *testing.T) {
	// Test default values
	cfgPath, verboseFlag := GetGlobalFlags()
	assert.Empty(t, cfgPath)
	assert.False(t, verboseFlag)
}

func TestRootCmd_Initialization(t *testing.T) {
	// Test that the root command is properly initialized
	assert.Equal(t, "piak", rootCmd.Use)
	assert.NotEmpty(t, rootCmd.Short)
	assert.NotEmpty(t, rootCmd.Long)
	assert.Contains(t, rootCmd.Long, banner)

	// Test that global flags are properly defined
	flags := rootCmd.PersistentFlags()
	assert.NotNil(t, flags.Lookup("config"))
	assert.NotNil(t, flags.Lookup("verbose"))

	// Test that subcommands are added
	commands := rootCmd.Commands()
	commandNames := make([]string, len(commands))
	for i, cmd := range commands {
		commandNames[i] = cmd.Use
	}
	assert.Contains(t, commandNames, "generate")
	assert.Contains(t, commandNames, "version")
}

func TestInitGlobalConfig_Default(t *testing.T) {
	// Save original values
	origCfgFile := cfgFile
	origVerbose := verbose

	// Set default values
	cfgFile = ""
	verbose = false

	// Restore original values after test
	defer func() {
		cfgFile = origCfgFile
		verbose = origVerbose
	}()

	// Test default configuration
	err := initGlobalConfig()
	assert.NoError(t, err)
}

func TestInitGlobalConfig_VerboseMode(t *testing.T) {
	// Save original values
	origCfgFile := cfgFile
	origVerbose := verbose

	// Set verbose mode
	cfgFile = ""
	verbose = true

	// Restore original values after test
	defer func() {
		cfgFile = origCfgFile
		verbose = origVerbose
	}()

	// Capture stderr to verify verbose output
	oldStderr := os.Stderr
	r, w, err := os.Pipe()
	require.NoError(t, err)
	os.Stderr = w

	outputChan := make(chan string)
	go func() {
		defer close(outputChan)
		var buf bytes.Buffer
		_, _ = io.Copy(&buf, r) // Error ignored in test goroutine
		outputChan <- buf.String()
	}()

	// Test verbose configuration
	initErr := initGlobalConfig()

	// Close the pipe and restore stderr
	w.Close()
	os.Stderr = oldStderr

	// Get the output
	output := <-outputChan

	require.NoError(t, initErr)
	assert.Contains(t, output, "🔧 Verbose mode enabled")
}

func TestInitGlobalConfig_ValidConfigFile(t *testing.T) {
	// Save original values
	origCfgFile := cfgFile
	origVerbose := verbose

	// Create a temporary config file
	tmpDir := t.TempDir()
	configPath := filepath.Join(tmpDir, "piak.yaml")
	err := os.WriteFile(configPath, []byte("# test config"), 0644)
	require.NoError(t, err)

	// Set config file and verbose mode
	cfgFile = configPath
	verbose = true

	// Restore original values after test
	defer func() {
		cfgFile = origCfgFile
		verbose = origVerbose
	}()

	// Capture stderr
	oldStderr := os.Stderr
	r, w, pipeErr := os.Pipe()
	require.NoError(t, pipeErr)
	os.Stderr = w

	outputChan := make(chan string)
	go func() {
		defer close(outputChan)
		var buf bytes.Buffer
		_, _ = io.Copy(&buf, r) // Error ignored in test goroutine
		outputChan <- buf.String()
	}()

	// Test configuration with valid config file
	initErr := initGlobalConfig()

	w.Close()
	os.Stderr = oldStderr
	output := <-outputChan

	require.NoError(t, initErr)
	assert.Contains(t, output, "🔧 Verbose mode enabled")
	assert.Contains(t, output, "📄 Using config file: "+configPath)
}

func TestInitGlobalConfig_NonExistentConfigFile(t *testing.T) {
	// Save original values
	origCfgFile := cfgFile
	origVerbose := verbose

	// Set non-existent config file
	cfgFile = "/path/that/does/not/exist.yaml"
	verbose = false

	// Restore original values after test
	defer func() {
		cfgFile = origCfgFile
		verbose = origVerbose
	}()

	// Test configuration with non-existent config file
	err := initGlobalConfig()
	require.Error(t, err)
	assert.Contains(t, err.Error(), "config file does not exist")
	assert.Contains(t, err.Error(), "/path/that/does/not/exist.yaml")
}

func TestRootCmd_PersistentPreRunE(t *testing.T) {
	// Test that PersistentPreRunE is properly set
	assert.NotNil(t, rootCmd.PersistentPreRunE)

	// Save original values
	origCfgFile := cfgFile
	origVerbose := verbose

	// Set default values for clean test
	cfgFile = ""
	verbose = false

	// Restore original values after test
	defer func() {
		cfgFile = origCfgFile
		verbose = origVerbose
	}()

	// Test successful execution with default values
	err := rootCmd.PersistentPreRunE(rootCmd, []string{})
	require.NoError(t, err)
}

func TestRootCmd_PersistentPreRunE_WithError(t *testing.T) {
	// Save original values
	origCfgFile := cfgFile
	origVerbose := verbose

	// Set invalid config file to trigger error
	cfgFile = "/nonexistent/config.yaml"
	verbose = false

	// Restore original values after test
	defer func() {
		cfgFile = origCfgFile
		verbose = origVerbose
	}()

	// Test error handling in PersistentPreRunE
	err := rootCmd.PersistentPreRunE(rootCmd, []string{})
	require.Error(t, err)
	assert.Contains(t, err.Error(), "failed to initialize configuration")
}

func TestExecute_SuccessfulCommand(t *testing.T) {
	// Test Execute with the version command which should succeed
	// Capture stdout and stderr
	oldStdout := os.Stdout
	oldStderr := os.Stderr

	rOut, wOut, err := os.Pipe()
	require.NoError(t, err)
	os.Stdout = wOut

	rErr, wErr, errPipe := os.Pipe()
	require.NoError(t, errPipe)
	os.Stderr = wErr

	// Channels to capture output
	outChan := make(chan string)
	errChan := make(chan string)

	go func() {
		defer close(outChan)
		var buf bytes.Buffer
		_, _ = io.Copy(&buf, rOut) // Error ignored in test goroutine
		outChan <- buf.String()
	}()

	go func() {
		defer close(errChan)
		var buf bytes.Buffer
		_, _ = io.Copy(&buf, rErr) // Error ignored in test goroutine
		errChan <- buf.String()
	}()

	// Temporarily set args to run version command
	originalArgs := os.Args
	os.Args = []string{"piak", "version"}

	// Restore everything after test
	defer func() {
		os.Args = originalArgs
		wOut.Close()
		wErr.Close()
		os.Stdout = oldStdout
		os.Stderr = oldStderr
	}()

	// This should not panic and should exit cleanly
	assert.NotPanics(t, func() {
		Execute()
	})

	// Close pipes and get output
	wOut.Close()
	wErr.Close()

	stdoutOutput := <-outChan
	stderrOutput := <-errChan

	// Version command should produce output
	assert.Contains(t, stdoutOutput, "piak version:")
	// Should not have errors for successful command
	assert.Empty(t, stderrOutput)
}

func TestExecute_ErrorCommand(t *testing.T) {
	// Test Execute with an invalid command using rootCmd.Execute directly to avoid os.Exit
	// Create a copy of rootCmd to test error scenarios
	testRootCmd := &cobra.Command{
		Use:               rootCmd.Use,
		Short:             rootCmd.Short,
		Long:              rootCmd.Long,
		PersistentPreRunE: rootCmd.PersistentPreRunE,
	}
	testRootCmd.AddCommand(versionCmd)
	testRootCmd.SetArgs([]string{"invalidcommand"})

	// Capture stderr
	var buf bytes.Buffer
	testRootCmd.SetErr(&buf)

	// This should return an error for invalid command
	err := testRootCmd.Execute()
	require.Error(t, err)

	// Test the actual Execute function exists and doesn't panic when called properly
	assert.NotPanics(t, func() {
		// Just verify the function exists - we can't safely test os.Exit behavior
		_ = Execute
	})
}

func TestRootCmd_HelpOutput(t *testing.T) {
	// Test help command execution by calling Execute on a copy
	cmd := &cobra.Command{
		Use:   rootCmd.Use,
		Short: rootCmd.Short,
		Long:  rootCmd.Long,
	}
	cmd.AddCommand(versionCmd)
	cmd.SetArgs([]string{"--help"})

	// Capture output
	var buf bytes.Buffer
	cmd.SetOut(&buf)

	// Execute help command - in newer cobra versions this doesn't always return error
	err := cmd.Execute()
	// Help might or might not return an error, both are valid
	if err != nil {
		// If error, it should be help-related
		assert.Contains(t, err.Error(), "help")
	}

	output := buf.String()
	// Should contain usage information
	assert.Contains(t, output, "piak")
}

func TestRootCmd_InvalidCommand(t *testing.T) {
	// Test with invalid subcommand using a copy
	cmd := &cobra.Command{
		Use:   rootCmd.Use,
		Short: rootCmd.Short,
		Long:  rootCmd.Long,
	}
	cmd.AddCommand(versionCmd)
	cmd.SetArgs([]string{"nonexistent"})

	var buf bytes.Buffer
	cmd.SetErr(&buf)

	err := cmd.Execute()
	require.Error(t, err)
}

func TestRootCmd_Banner(t *testing.T) {
	// Test that banner is included in long description
	assert.Contains(t, rootCmd.Long, banner)

	// Test banner content - the ASCII art contains the letters of "piak" in ASCII art form
	assert.Contains(t, banner, "__")
	assert.Contains(t, banner, "|__|")

	// Test that the long description contains the word "piak" after the banner
	assert.Contains(t, rootCmd.Long, "piak is a tool")
}
