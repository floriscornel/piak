> [!WARNING]
> **Unstable / Work In Progress**: This project is under active development and the code generated in not stable yet. Use at your own risk!

# piak 🚀

[![Test](https://github.com/floriscornel/piak/actions/workflows/test.yml/badge.svg)](https://github.com/floriscornel/piak/actions/workflows/test.yml)
[![codecov](https://codecov.io/gh/floriscornel/piak/graph/badge.svg)](https://codecov.io/gh/floriscornel/piak)
[![Go Report Card](https://goreportcard.com/badge/github.com/floriscornel/piak)](https://goreportcard.com/report/github.com/floriscornel/piak)
[![Release](https://img.shields.io/github/release/floriscornel/piak.svg)](https://github.com/floriscornel/piak/releases/latest)

Opinionated OpenAPI 3.0 code generator for PHP.

## Quick Start

### Basic Usage

```bash
# Generate PHP code from OpenAPI spec
piak generate -i openapi.yaml -o ./generated -n "MyApp\\Generated"
```

### Generate with Client and Tests

```bash
piak generate \
  --input api.yaml \
  --output ./src/Generated \
  --namespace "MyApp\\Api" \
  --generate-client \
  --generate-tests
```

## Development

### Prerequisites

- Go 1.24+
- PHP 8.4+ (for testing generated code)
- Composer (for PHP dependency management)

### Building from Source

```bash
git clone https://github.com/floriscornel/piak.git
cd piak
make build
```
