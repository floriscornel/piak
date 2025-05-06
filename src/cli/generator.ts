import { existsSync, mkdirSync, readFileSync, writeFileSync } from "node:fs";
import { dirname, join } from "node:path";
import { flatten } from "@/converter/flattenProperties";
import { generatePhp } from "@/generators/php/generator";
import { parse } from "@/parser/parser";
import chalk from "chalk";
import type { Config } from "./config";
import { defaultTemplate } from "./config";

export async function processOpenApi(config: Config): Promise<void> {
	try {
		// Validate input
		if (!config.input || !config.output || !config.namespace) {
			throw new Error(
				"Invalid configuration: input, output, and namespace are required",
			);
		}

		console.log(chalk.blue("Reading OpenAPI specification..."));
		const specification = readFileSync(config.input, "utf-8");

		if (!specification) {
			throw new Error("Empty OpenAPI specification");
		}

		console.log(chalk.blue("Parsing OpenAPI specification..."));
		const parsed = await parse(specification);

		console.log(chalk.blue("Flattening properties..."));
		const flattened = flatten(parsed);

		console.log(chalk.blue("Generating PHP classes..."));
		let template: string;
		try {
			template = config.template
				? readFileSync(config.template, "utf-8")
				: defaultTemplate;
		} catch (error: unknown) {
			if (error instanceof Error) {
				throw new Error(`Failed to read template: ${error.message}`);
			}
			throw new Error("Failed to read template: Unknown error");
		}

		const generated = generatePhp(flattened, template);

		// Ensure output directory exists
		if (!existsSync(config.output)) {
			try {
				mkdirSync(config.output, { recursive: true });
			} catch (error: unknown) {
				if (error instanceof Error) {
					throw new Error(
						`Failed to create output directory: ${error.message}`,
					);
				}
				throw new Error("Failed to create output directory: Unknown error");
			}
		}

		// Write generated files
		for (const [filename, content] of Object.entries(generated)) {
			const filePath = join(config.output, filename);
			const fileDir = dirname(filePath);

			if (!existsSync(fileDir)) {
				try {
					mkdirSync(fileDir, { recursive: true });
				} catch (error: unknown) {
					if (error instanceof Error) {
						throw new Error(
							`Failed to create directory ${fileDir}: ${error.message}`,
						);
					}
					throw new Error(
						`Failed to create directory ${fileDir}: Unknown error`,
					);
				}
			}

			try {
				writeFileSync(filePath, content);
				console.log(chalk.green(`Generated: ${filename}`));
			} catch (error: unknown) {
				if (error instanceof Error) {
					throw new Error(`Failed to write file ${filename}: ${error.message}`);
				}
				throw new Error(`Failed to write file ${filename}: Unknown error`);
			}
		}

		console.log(chalk.green("Generation completed successfully!"));
	} catch (error) {
		console.error(chalk.red("Error:"), error);
		throw error;
	}
}
