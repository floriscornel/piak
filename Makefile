# Build variables
BINARY_NAME=piak
VERSION?=dev
GIT_COMMIT?=$(shell git rev-parse --short HEAD)
BUILD_DATE?=$(shell date -u +"%Y-%m-%dT%H:%M:%SZ")

# Go variables
GOCMD=go
GOBUILD=$(GOCMD) build
GOCLEAN=$(GOCMD) clean
GOTEST=$(GOCMD) test
GOGET=$(GOCMD) get
GOMOD=$(GOCMD) mod
GOFMT=$(GOCMD) fmt

# Tools
GOLANGCI_LINT=golangci-lint

# Build flags
LDFLAGS=-ldflags "-X github.com/floriscornel/piak/cmd.version=$(VERSION) -X github.com/floriscornel/piak/cmd.commit=$(GIT_COMMIT) -X github.com/floriscornel/piak/cmd.date=$(BUILD_DATE)"

.PHONY: all build clean test deps lint fmt fmt-check lint-fix check install-tools help

all: check test build

## build: Build the binary
build:
	$(GOBUILD) $(LDFLAGS) -o $(BINARY_NAME) -v ./

## clean: Clean build files
clean:
	$(GOCLEAN)
	rm -f $(BINARY_NAME)

## test: Run tests
test:
	$(GOTEST) -v ./...

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

## run-example: Run with example
run-example:
	./$(BINARY_NAME) generate -i examples/petstore/openapi.yaml -o /tmp/piak-test

## help: Show this help message
help: Makefile
	@echo "Available commands:"
	@sed -n 's/^##//p' $< | column -t -s ':' | sed -e 's/^/ /' 