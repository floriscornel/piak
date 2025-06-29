//go:build integration

package integration

import (
	"os"
	"os/exec"
	"path/filepath"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"

	"github.com/floriscornel/piak/internal/config"
	"github.com/floriscornel/piak/internal/generator"
)

// TestCase represents a code generation test case
type TestCase struct {
	Name           string
	InputSpec      string
	Namespace      string
	GenerateClient bool
	GenerateTests  bool
	ExpectedFiles  []string
	ShouldPass     bool // false for future features that should fail until implemented
}

// getTestCases returns all test cases for code generation
func getTestCases() []TestCase {
	return []TestCase{
		{
			Name:           "petstore-basic",
			InputSpec:      "testdata/petstore.yaml",
			Namespace:      "Generated",
			GenerateClient: true,
			GenerateTests:  true,
			ExpectedFiles: []string{
				"src/Pet.php",
				"src/User.php",
				"src/Category.php",
				"src/Order.php",
				"src/Tag.php",
				"src/ApiResponse.php",
				"src/Error.php",
				"src/ApiClient.php",
				"tests/PetTest.php",
				"tests/UserTest.php",
				"tests/ApiClientTest.php",
				"composer.json",
				"petstore.yaml",
				"README.md",
			},
			ShouldPass: true,
		},
	}
}

func TestCodeGeneration(t *testing.T) {
	for _, tc := range getTestCases() {
		t.Run(tc.Name, func(t *testing.T) {
			// Create temporary output directory
			outputDir := filepath.Join(os.TempDir(), "piak-test-"+tc.Name)
			defer os.RemoveAll(outputDir)

			// Skip test cases that should fail if we're not ready for them
			if !tc.ShouldPass {
				if _, err := os.Stat(tc.InputSpec); os.IsNotExist(err) {
					t.Skipf("Skipping %s: input spec doesn't exist yet (expected for unimplemented features)", tc.Name)
					return
				}
			}

			// Verify input spec exists
			require.FileExists(t, tc.InputSpec, "Input OpenAPI spec should exist")

			// Create config
			cfg := &config.GeneratorConfig{
				InputFile:      tc.InputSpec,
				OutputDir:      outputDir,
				Namespace:      tc.Namespace,
				GenerateClient: tc.GenerateClient,
				GenerateTests:  tc.GenerateTests,
			}

			// Run code generation
			gen, err := generator.NewGenerator(cfg)
			require.NoError(t, err, "Should create generator without error")

			err = gen.Generate()
			if !tc.ShouldPass {
				assert.Error(t, err, "Expected generation to fail for unimplemented feature %s", tc.Name)
				return
			}
			require.NoError(t, err, "Code generation should succeed")

			// Verify expected files were generated
			for _, expectedFile := range tc.ExpectedFiles {
				fullPath := filepath.Join(outputDir, expectedFile)
				assert.FileExists(t, fullPath, "Expected file %s should be generated", expectedFile)
			}

			// Run PHP-specific validations
			t.Run("php-validation", func(t *testing.T) {
				runPHPValidation(t, outputDir)
			})

			// Run generated tests if they were created
			if tc.GenerateTests {
				t.Run("php-tests", func(t *testing.T) {
					runGeneratedPHPTests(t, outputDir)
				})
			}
		})
	}
}

// runPHPValidation validates the generated PHP code
func runPHPValidation(t *testing.T, outputDir string) {
	// Check PHP syntax for all generated .php files
	err := filepath.Walk(outputDir, func(path string, info os.FileInfo, err error) error {
		if err != nil {
			return err
		}

		if filepath.Ext(path) == ".php" {
			// Check PHP syntax
			cmd := exec.Command("php", "-l", path)
			output, err := cmd.CombinedOutput()
			if err != nil {
				t.Errorf("PHP syntax error in %s: %s\nOutput: %s", path, err, string(output))
			}
		}
		return nil
	})
	require.NoError(t, err, "Should be able to walk output directory")
}

// runGeneratedPHPTests runs the generated PHP tests
func runGeneratedPHPTests(t *testing.T, outputDir string) {
	// Check if composer.json exists
	composerFile := filepath.Join(outputDir, "composer.json")
	require.FileExists(t, composerFile, "composer.json should exist")

	// Install PHP dependencies
	cmd := exec.Command("composer", "install", "--no-interaction", "--prefer-dist")
	cmd.Dir = outputDir
	output, err := cmd.CombinedOutput()
	if err != nil {
		t.Fatalf("Composer install failed: %s\nOutput: %s", err, string(output))
	}

	// Check if there are test files
	testsDir := filepath.Join(outputDir, "tests")
	if _, err := os.Stat(testsDir); os.IsNotExist(err) {
		t.Skip("No tests directory found, skipping PHP test execution")
		return
	}

	// Run PHPUnit tests
	phpunitPath := filepath.Join(outputDir, "vendor", "bin", "phpunit")
	if _, err := os.Stat(phpunitPath); os.IsNotExist(err) {
		t.Skip("PHPUnit not found, skipping test execution")
		return
	}

	cmd = exec.Command(phpunitPath, "tests/")
	cmd.Dir = outputDir
	output, err = cmd.CombinedOutput()

	// Log output for debugging
	t.Logf("PHPUnit output:\n%s", string(output))

	if err != nil {
		t.Errorf("PHP tests failed: %s\nOutput: %s", err, string(output))
	} else {
		t.Log("PHP tests passed successfully")
	}
}

// TestBenchmarkGeneration benchmarks the code generation performance
func TestBenchmarkGeneration(t *testing.T) {
	// Only run on petstore example for performance testing
	tc := getTestCases()[0] // petstore-basic

	outputDir := filepath.Join(os.TempDir(), "piak-benchmark")
	defer os.RemoveAll(outputDir)

	cfg := &config.GeneratorConfig{
		InputFile:      tc.InputSpec,
		OutputDir:      outputDir,
		Namespace:      tc.Namespace,
		GenerateClient: tc.GenerateClient,
		GenerateTests:  tc.GenerateTests,
	}

	// Benchmark the generation
	for i := 0; i < 5; i++ {
		start := time.Now()

		gen, err := generator.NewGenerator(cfg)
		require.NoError(t, err)

		err = gen.Generate()
		require.NoError(t, err)

		duration := time.Since(start)
		t.Logf("Generation #%d took: %v", i+1, duration)

		// Clean up for next iteration
		os.RemoveAll(outputDir)
	}
}
