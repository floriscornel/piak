#!/usr/bin/env bun
import chalk from "chalk";
import { Command } from "commander";
import { z } from "zod";
import { ConfigSchema } from "./config";
import { processOpenApi } from "./generator";

const program = new Command();

program
	.name("piak")
	.description("OpenAPI to PHP class generator")
	.version("1.0.0");

program
	.command("generate")
	.description("Generate PHP classes from OpenAPI specification")
	.requiredOption(
		"-i, --input <path>",
		"Path to the OpenAPI specification file",
	)
	.requiredOption(
		"-o, --output <path>",
		"Output directory for generated PHP files",
	)
	.option(
		"-n, --namespace <namespace>",
		"PHP namespace for generated classes",
		"App\\Api\\V1",
	)
	.option("-t, --template <path>", "Path to custom Handlebars template file")
	.action(async (options) => {
		try {
			const config = ConfigSchema.parse(options);
			await processOpenApi(config);
		} catch (error) {
			if (error instanceof z.ZodError) {
				console.error(chalk.red("Configuration error:"));
				for (const err of error.errors) {
					console.error(
						chalk.yellow(`- ${err.path.join(".")}: ${err.message}`),
					);
				}
			} else {
				console.error(chalk.red("Error:"), error);
			}
			process.exit(1);
		}
	});

// Check if this is being run directly
const isRunningDirectly =
	process.argv.length > 1 &&
	process.argv[1].includes("cli.ts") &&
	!process.argv[0].includes("vitest");

if (isRunningDirectly) {
	program.parse(process.argv);
}

export { program };
