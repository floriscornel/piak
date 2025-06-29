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

# Build flags
LDFLAGS=-ldflags "-X github.com/floriscornel/piak/cmd.version=$(VERSION) -X github.com/floriscornel/piak/cmd.commit=$(GIT_COMMIT) -X github.com/floriscornel/piak/cmd.date=$(BUILD_DATE)"

.PHONY: all build clean test deps help

all: test build

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