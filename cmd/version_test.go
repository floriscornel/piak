package cmd

import (
	"bytes"
	"io"
	"os"
	"runtime"
	"strings"
	"testing"

	"github.com/spf13/cobra"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
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

func TestPrintVersion(t *testing.T) {
	// Capture stdout since the function uses fmt.Printf
	oldStdout := os.Stdout
	r, w, err := os.Pipe()
	require.NoError(t, err)
	os.Stdout = w

	// Channel to capture output
	outputChan := make(chan string)
	go func() {
		var buf bytes.Buffer
		_, _ = io.Copy(&buf, r) // Error ignored in test goroutine
		outputChan <- buf.String()
	}()

	// Call the printVersion function directly
	printVersion()

	// Restore stdout and close the pipe
	w.Close()
	os.Stdout = oldStdout
	output := <-outputChan

	// Verify output contains expected information
	assert.Contains(t, output, "piak version: dev")
	assert.Contains(t, output, "Git commit: none")
	assert.Contains(t, output, "Built: unknown")
	assert.Contains(t, output, "Go version: "+runtime.Version())
	assert.Contains(t, output, "OS/Arch: "+runtime.GOOS+"/"+runtime.GOARCH)

	// Verify output format
	lines := strings.Split(strings.TrimSpace(output), "\n")
	assert.Len(t, lines, 5)
}

func TestPrintVersion_WithBuildInfo(t *testing.T) {
	// Save original values
	origVersion := version
	origCommit := commit
	origDate := date

	// Set test values
	version = "1.2.3"
	commit = "abc123def"
	date = "2023-12-01T10:30:00Z"

	// Restore original values after test
	defer func() {
		version = origVersion
		commit = origCommit
		date = origDate
	}()

	// Capture stdout
	oldStdout := os.Stdout
	r, w, err := os.Pipe()
	require.NoError(t, err)
	os.Stdout = w

	outputChan := make(chan string)
	go func() {
		var buf bytes.Buffer
		_, _ = io.Copy(&buf, r) // Error ignored in test goroutine
		outputChan <- buf.String()
	}()

	// Call the printVersion function directly
	printVersion()

	// Restore stdout and get output
	w.Close()
	os.Stdout = oldStdout
	output := <-outputChan

	// Verify custom build info is displayed
	assert.Contains(t, output, "piak version: 1.2.3")
	assert.Contains(t, output, "Git commit: abc123def")
	assert.Contains(t, output, "Built: 2023-12-01T10:30:00Z")
}

func TestVersionCmd_RunFunction(t *testing.T) {
	// Capture stdout since the version command uses fmt.Printf
	oldStdout := os.Stdout
	r, w, err := os.Pipe()
	require.NoError(t, err)
	os.Stdout = w

	// Channel to capture output
	outputChan := make(chan string)
	go func() {
		var buf bytes.Buffer
		_, _ = io.Copy(&buf, r) // Error ignored in test goroutine
		outputChan <- buf.String()
	}()

	// Execute the actual Run function
	versionCmd.Run(versionCmd, []string{})

	// Restore stdout and close the pipe
	w.Close()
	os.Stdout = oldStdout
	output := <-outputChan

	// Verify output contains expected information
	assert.Contains(t, output, "piak version: dev")
	assert.Contains(t, output, "Git commit: none")
	assert.Contains(t, output, "Built: unknown")
	assert.Contains(t, output, "Go version: "+runtime.Version())
	assert.Contains(t, output, "OS/Arch: "+runtime.GOOS+"/"+runtime.GOARCH)

	// Verify output format
	lines := strings.Split(strings.TrimSpace(output), "\n")
	assert.Len(t, lines, 5)
}

func TestVersionCmd_RunFunctionDirectly(t *testing.T) {
	// Test calling the Run function directly without output capture
	assert.NotPanics(t, func() {
		versionCmd.Run(&cobra.Command{}, []string{})
	})
}

func TestVersionCmd_WithBuildInfo(t *testing.T) {
	// Save original values
	origVersion := version
	origCommit := commit
	origDate := date

	// Set test values
	version = "1.2.3"
	commit = "abc123def"
	date = "2023-12-01T10:30:00Z"

	// Restore original values after test
	defer func() {
		version = origVersion
		commit = origCommit
		date = origDate
	}()

	// Capture stdout
	oldStdout := os.Stdout
	r, w, err := os.Pipe()
	require.NoError(t, err)
	os.Stdout = w

	outputChan := make(chan string)
	go func() {
		var buf bytes.Buffer
		_, _ = io.Copy(&buf, r) // Error ignored in test goroutine
		outputChan <- buf.String()
	}()

	// Execute the actual Run function
	versionCmd.Run(versionCmd, []string{})

	// Restore stdout and get output
	w.Close()
	os.Stdout = oldStdout
	output := <-outputChan

	// Verify custom build info is displayed
	assert.Contains(t, output, "piak version: 1.2.3")
	assert.Contains(t, output, "Git commit: abc123def")
	assert.Contains(t, output, "Built: 2023-12-01T10:30:00Z")
}

func TestVersionCmd_ExecuteCommand(t *testing.T) {
	// Test executing the version command as a subcommand
	// Capture stdout
	oldStdout := os.Stdout
	r, w, err := os.Pipe()
	require.NoError(t, err)
	os.Stdout = w

	outputChan := make(chan string)
	go func() {
		var buf bytes.Buffer
		_, _ = io.Copy(&buf, r) // Error ignored in test goroutine
		outputChan <- buf.String()
	}()

	// Create a root command and add version as subcommand
	testRootCmd := &cobra.Command{Use: "test"}
	testRootCmd.AddCommand(versionCmd)
	testRootCmd.SetArgs([]string{"version"})

	// Execute
	execErr := testRootCmd.Execute()
	require.NoError(t, execErr)

	// Restore stdout and get output
	w.Close()
	os.Stdout = oldStdout
	output := <-outputChan

	// Verify we got version output
	assert.NotEmpty(t, output)
	assert.Contains(t, output, "piak version:")
}
