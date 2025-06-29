# Piak - OpenAPI to PHP Code Generator

A command-line tool that generates PHP client libraries and models from OpenAPI 3.x specifications with comprehensive testing capabilities.

## Features

- Generate PHP models from OpenAPI schemas
- Generate HTTP client for API endpoints
- Generate comprehensive PHPUnit tests with OpenAPI validation
- Support for modern PHP 8.1+ features (readonly properties, type hints)
- Test-driven development approach for new features
- Comprehensive CI/CD pipeline with coverage reporting

## Installation

### Prerequisites

- Go 1.21+
- PHP 8.1+ (for testing generated code)
- Composer (for PHP dependency management)

### Build from source

```bash
git clone https://github.com/floriscornel/piak.git
cd piak
make build
```

## Usage

### Basic Usage

```bash
# Generate PHP code from OpenAPI spec
./piak generate \
  --input-file examples/petstore/petstore.yaml \
  --output-dir output \
  --namespace MyApi \
  --generate-client \
  --generate-tests
```

### Generated Structure

```
output/
├── src/
│   ├── Pet.php              # Model classes
│   ├── User.php
│   └── ApiClient.php        # HTTP client
├── tests/
│   ├── PetTest.php          # Model tests
│   ├── UserTest.php
│   └── ApiClientTest.php    # Client tests  
├── composer.json            # Dependencies with testing libs
├── openapi.yaml            # Original spec (for validation)
└── README.md               # Usage documentation
```

## Testing

This project uses a comprehensive testing approach that ensures both the Go code generator and the generated PHP code work correctly.

### Test Types

1. **Unit Tests** - Test individual Go components
2. **Integration Tests** - Test complete generation pipeline
3. **Generated Code Tests** - Validate generated PHP code works
4. **End-to-End Tests** - Full pipeline from spec to working PHP

### Running Tests

```bash
# Run all tests
make test-all

# Run only unit tests (fast)
make test-unit

# Run integration tests (requires PHP/Composer)
make test-integration

# Generate and test PHP code end-to-end
make e2e-test

# Generate test coverage
make coverage
```

### Test-Driven Development

New features should be added using a TDD approach:

1. **Add test case** in `tests/integration/generation_test.go`
2. **Create OpenAPI spec** in `tests/integration/testdata/`
3. **Set `ShouldPass: false`** initially
4. **Run tests** - they should fail
5. **Implement feature** in generator
6. **Update test** to `ShouldPass: true`
7. **Verify tests pass**

Example for adding inheritance support:

```go
{
    Name:           "inheritance-example",
    InputSpec:      "testdata/inheritance.yaml", 
    Namespace:      "InheritanceTest",
    GenerateTests:  true,
    ExpectedFiles:  []string{"src/Animal.php", "src/Dog.php"},
    ShouldPass:     false, // Will fail until implemented
}
```

### OpenAPI Testing Features

Generated PHP code includes:

- **OpenAPI Validation** - Tests validate against original spec
- **Request/Response Testing** - HTTP message validation
- **Schema Compliance** - Model structure validation  
- **Realistic Test Data** - Schema-compliant test data generation

Dependencies automatically included:
- `osteel/openapi-httpfoundation-testing` - OpenAPI validation
- `phpunit/phpunit` - Testing framework

## Development

### Setup Development Environment

```bash
make dev-setup
```

### Available Make Targets

```bash
make help                  # Show all available targets
make build                 # Build the binary
make test                  # Run unit tests
make test-all             # Run all tests
make coverage             # Generate coverage report
make run-example          # Generate petstore example
make test-generated-php   # Test generated PHP
make e2e-test            # Full end-to-end test
make lint                # Lint Go code
make check-php-syntax    # Validate PHP syntax
make ci                  # Full CI pipeline
```

### CI/CD Pipeline

The GitHub Actions workflow runs:

1. **Linting** - Code quality checks
2. **Unit Tests** - Fast Go component tests  
3. **Integration Tests** - Full generation pipeline
4. **Coverage** - Code coverage reporting
5. **End-to-End** - Generated PHP validation
6. **Syntax Check** - PHP code validation

Coverage reports are uploaded to Codecov and artifacts are preserved for debugging.

## Configuration

### Command Line Options

- `--input-file` - Path to OpenAPI specification file
- `--output-dir` - Directory for generated code
- `--namespace` - PHP namespace for generated classes
- `--generate-client` - Generate HTTP client class
- `--generate-tests` - Generate PHPUnit tests

### Configuration File

Create `piak.yaml`:

```yaml
input: api-spec.yaml
output: generated/
namespace: MyApi
generate_client: true
generate_tests: true
```

## Examples

### Petstore Example

The repository includes a complete petstore example:

```bash
# Generate code
make run-example

# Test generated code  
make test-generated-php

# Check generated files
ls examples/petstore/output/
```

### Generated PHP Usage

```php
use Generated\Pet;
use Generated\ApiClient;

// Create model from data
$pet = Pet::fromArray([
    'id' => 123,
    'name' => 'Fluffy', 
    'status' => 'available'
]);

// Use API client
$client = new ApiClient('https://api.example.com');
$response = $client->request('GET', '/pets');
```

## Contributing

1. Fork the repository
2. Create feature branch
3. Add tests (TDD approach)
4. Implement feature
5. Ensure all tests pass: `make ci`
6. Submit pull request

## License

MIT License - see LICENSE file for details. 