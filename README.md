# piak

[![CI](https://github.com/floriscornel/piak/actions/workflows/ci.yml/badge.svg)](https://github.com/floriscornel/piak/actions/workflows/ci.yml)

To install dependencies:

```bash
bun install
```

To run for petstore example:

```bash
bun run src/cli/cli.ts generate -i examples/petstore/openapi.yaml -o examples/petstore/output
diff examples/petstore/output examples/petstore/expected
```

This project was created using `bun init` in bun v1.2.0. [Bun](https://bun.sh) is a fast all-in-one JavaScript runtime.
