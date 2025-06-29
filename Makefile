# Build variables
BINARY_NAME=piak
BUILD_DIR=./build
VERSION?=dev
GIT_COMMIT?=$(shell git rev-parse --short HEAD)
BUILD_DATE?=$(shell date -u +"%Y-%m-%dT%H:%M:%SZ")

# Go variables
GOCMD=go
GOBUILD=$(GOCMD) build
GORUN=$(GOCMD) run
GOCLEAN=$(GOCMD) clean
GOTEST=$(GOCMD) test
GOGET=$(GOCMD) get
GOMOD=$(GOCMD) mod
GOFMT=$(GOCMD) fmt

# Tools
GOLANGCI_LINT=golangci-lint

# Build flags
LDFLAGS=-ldflags "-X github.com/floriscornel/piak/cmd.version=$(VERSION) -X github.com/floriscornel/piak/cmd.commit=$(GIT_COMMIT) -X github.com/floriscornel/piak/cmd.date=$(BUILD_DATE)"

# Test variables
COVERAGE_FILE = coverage.out
COVERAGE_HTML = coverage.html

.PHONY: all build clean test deps lint fmt fmt-check lint-fix check install-tools help test test-unit test-integration test-all coverage build-example run-example test-generated-php e2e-test check-php-syntax dev-setup ci

all: check test build

## build: Build the binary
build:
	@mkdir -p $(BUILD_DIR)
	$(GOBUILD) $(LDFLAGS) -o $(BUILD_DIR)/$(BINARY_NAME) -v ./

## clean: Clean build files
clean:
	$(GOCLEAN)
	rm -rf $(BUILD_DIR)
	rm -f $(COVERAGE_FILE)
	rm -f $(COVERAGE_HTML)
	rm -rf examples/*/output/

## test: Run tests
test: test-unit test-integration test-all

## deps: Download dependencies
deps:
	$(GOMOD) download
	$(GOMOD) tidy

## lint: Run golangci-lint
lint:
	$(GOLANGCI_LINT) run

## lint-fix: Run golangci-lint with --fix flag
lint-fix:
	$(GOLANGCI_LINT) run --fix

## fmt: Format Go code
fmt:
	$(GOFMT) ./...

## fmt-check: Check if Go code is formatted
fmt-check:
	@if [ -n "$$(gofmt -l .)" ]; then \
		echo "The following files are not formatted:"; \
		gofmt -l .; \
		echo "Run 'make fmt' to format them."; \
		exit 1; \
	fi

## check: Run formatting and linting checks
check: fmt-check lint

## install-tools: Install development tools
install-tools:
	@echo "Installing golangci-lint..."
	@which golangci-lint > /dev/null || curl -sSfL https://raw.githubusercontent.com/golangci/golangci-lint/HEAD/install.sh | sh -s -- -b $(shell go env GOPATH)/bin

## install: Install the binary
install:
	$(GOBUILD) $(LDFLAGS) -o $(GOPATH)/bin/$(BINARY_NAME) ./

## help: Show this help message
help: Makefile
	@echo "Available commands:"
	@sed -n 's/^##//p' $< | column -t -s ':' | sed -e 's/^/ /'

# Run unit tests only
test-unit:
	$(GOTEST) -v -race ./internal/... ./cmd/...

# Run integration tests only (requires PHP and Composer)
test-integration:
	$(GOTEST) -v -race -tags=integration ./tests/integration/...

# Run all tests
test-all: test-unit test-integration

# Default test target (unit tests only for CI compatibility)
test: test-unit

# Generate test coverage (includes integration tests with cross-package coverage)
coverage:
	$(GOTEST) -v -race -coverprofile=$(COVERAGE_FILE) -covermode=atomic -tags=integration -coverpkg=./internal/...,./cmd/... ./...
	$(GOCMD) tool cover -html=$(COVERAGE_FILE) -o $(COVERAGE_HTML)
	@echo "Coverage report generated: $(COVERAGE_HTML)"

# Generate test coverage for CI
coverage-ci:
	$(GOTEST) -v -race -coverprofile=$(COVERAGE_FILE) -covermode=atomic -tags=integration -coverpkg=./internal/...,./cmd/... ./...
	$(GOCMD) tool cover -func=$(COVERAGE_FILE)

# Run the tool on the petstore example
run-example: build
	$(GORUN) ./main.go generate \
		--input examples/petstore/openapi.yaml \
		--output examples/petstore/output \
		--namespace Generated \
		--generate-client \
		--generate-tests

# Run generated PHP tests for the petstore example
test-generated-php:
	@if [ -d "examples/petstore/output" ]; then \
		echo "Testing generated PHP code..."; \
		cd examples/petstore/output && \
		composer install --no-interaction --prefer-dist && \
		vendor/bin/phpunit tests/; \
	else \
		echo "No generated output found. Run 'make run-example' first."; \
		exit 1; \
	fi

# Full end-to-end test: generate code and test it
e2e-test: run-example test-generated-php

# Check if generated PHP code has valid syntax
check-php-syntax:
	@if [ -d "examples/petstore/output/src" ]; then \
		echo "Checking PHP syntax..."; \
		find examples/petstore/output -name "*.php" -exec php -l {} \; | grep -v "No syntax errors detected" || true; \
	else \
		echo "No PHP files found. Run 'make run-example' first."; \
	fi

# Development helpers
dev-setup:
	@echo "Setting up development environment..."
	$(GOMOD) download
	@echo "Checking for required tools..."
	@command -v php >/dev/null 2>&1 || (echo "PHP is required for integration tests" && exit 1)
	@command -v composer >/dev/null 2>&1 || (echo "Composer is required for PHP dependency management" && exit 1)
	@echo "Development environment ready!"

# CI target that runs everything
ci: deps lint test-all coverage-ci e2e-test 