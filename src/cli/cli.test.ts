import { existsSync, mkdirSync, readFileSync, writeFileSync } from "node:fs";
import { join } from "node:path";
import { Command } from "commander";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import type { Mock } from "vitest";

// Mock the file system operations
vi.mock("node:fs", () => ({
	readFileSync: vi.fn(),
	writeFileSync: vi.fn(),
	mkdirSync: vi.fn(),
	existsSync: vi.fn(),
}));

// Mock the console
vi.mock("console", () => ({
	log: vi.fn(),
	error: vi.fn(),
}));

// Mock process.exit
const mockExit = vi
	.spyOn(process, "exit")
	.mockImplementation(() => undefined as never);

// Mock the parser, flattener, and generator
vi.mock("../parser/parser", () => ({
	parse: vi.fn(),
}));

vi.mock("../converter/flattenProperties", () => ({
	flatten: vi.fn(),
}));

vi.mock("../generators/php/generator", () => ({
	generatePhp: vi.fn(),
}));

// Import mocked modules after mocking
import { flatten } from "../converter/flattenProperties";
import { generatePhp } from "../generators/php/generator";
import { parse } from "../parser/parser";
import { program } from "./cli";
import { processOpenApi } from "./generator";

interface GenerateOptions {
	input: string;
	output: string;
	namespace?: string;
	template?: string;
}

describe("CLI", () => {
	const mockOpenApiSpec = {
		openapi: "3.0.0",
		paths: {},
		components: {
			schemas: {
				User: {
					type: "object",
					properties: {
						id: { type: "integer" },
						name: { type: "string" },
					},
				},
			},
		},
	};

	const mockParsed = {
		User: {
			type: "object",
			propertyID: 1,
			properties: {
				id: { type: "integer" },
				name: { type: "string" },
			},
		},
	};

	const mockFlattened = {
		User: {
			type: "object",
			propertyID: 1,
			properties: {
				id: { type: "integer" },
				name: { type: "string" },
			},
		},
	};

	const mockGenerated = {
		"User.php":
			"<?php\nnamespace App\\Api\\V1;\n\nclass User\n{\n    private int $id;\n    private string $name;\n}",
	};

	let testProgram: Command;
	let lastOptions: GenerateOptions | undefined;

	beforeEach(() => {
		testProgram = new Command()
			.name("piak")
			.description("OpenAPI to PHP class generator")
			.version("1.0.0");

		lastOptions = undefined;

		const generateCommand = testProgram
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
			.option(
				"-t, --template <path>",
				"Path to custom Handlebars template file",
			)
			.action(async (options: GenerateOptions) => {
				lastOptions = options;
			});

		// Reset all mocks
		vi.clearAllMocks();

		// Setup default mock implementations
		(readFileSync as Mock).mockImplementation((path: string) => {
			if (path.endsWith(".yaml") || path.endsWith(".json")) {
				return JSON.stringify(mockOpenApiSpec);
			}
			return "";
		});

		(existsSync as Mock).mockReturnValue(false);
		(parse as unknown as Mock).mockResolvedValue(mockParsed);
		(flatten as unknown as Mock).mockReturnValue(mockFlattened);
		(generatePhp as unknown as Mock).mockReturnValue(mockGenerated);

		// Override exit for test program
		testProgram.exitOverride();
		testProgram.configureOutput({
			writeErr: () => {},
			writeOut: () => {},
		});
		testProgram.showHelpAfterError(false);
	});

	afterEach(() => {
		vi.resetAllMocks();
	});

	describe("Command Line Arguments", () => {
		it("should require input and output options", () => {
			// Simply verify that the command was configured correctly with required options
			const command = testProgram.commands.find(
				(cmd) => cmd.name() === "generate",
			);
			expect(command).toBeDefined();

			const inputOption = command?.options.find((opt) =>
				opt.flags.includes("--input"),
			);
			const outputOption = command?.options.find((opt) =>
				opt.flags.includes("--output"),
			);

			expect(inputOption?.required).toBe(true);
			expect(outputOption?.required).toBe(true);
		});

		it("should accept valid input and output options", async () => {
			const args = ["generate", "-i", "spec.yaml", "-o", "output"];
			await testProgram.parseAsync(args, { from: "user" });
			expect(lastOptions).toEqual({
				input: "spec.yaml",
				output: "output",
				namespace: "App\\Api\\V1",
			});
		});

		it("should use default namespace if not specified", async () => {
			const args = ["generate", "-i", "spec.yaml", "-o", "output"];
			await testProgram.parseAsync(args, { from: "user" });
			expect(lastOptions?.namespace).toBe("App\\Api\\V1");
		});

		it("should accept custom namespace", async () => {
			const args = [
				"generate",
				"-i",
				"spec.yaml",
				"-o",
				"output",
				"-n",
				"Custom\\Namespace",
			];
			await testProgram.parseAsync(args, { from: "user" });
			expect(lastOptions?.namespace).toBe("Custom\\Namespace");
		});
	});

	describe("File Operations", () => {
		it("should create output directory if it does not exist", async () => {
			await processOpenApi({
				input: "spec.yaml",
				output: "output",
				namespace: "App\\Api\\V1",
			});

			expect(mkdirSync).toHaveBeenCalledWith("output", { recursive: true });
		});

		it("should read the OpenAPI specification file", async () => {
			await processOpenApi({
				input: "spec.yaml",
				output: "output",
				namespace: "App\\Api\\V1",
			});

			expect(readFileSync).toHaveBeenCalledWith("spec.yaml", "utf-8");
		});

		it("should write generated files to output directory", async () => {
			await processOpenApi({
				input: "spec.yaml",
				output: "output",
				namespace: "App\\Api\\V1",
			});

			expect(writeFileSync).toHaveBeenCalledWith(
				join("output", "User.php"),
				mockGenerated["User.php"],
			);
		});

		it("should handle template file reading errors", async () => {
			(readFileSync as Mock).mockImplementation((path: string) => {
				if (path === "template.hbs") {
					throw new Error("Template file not found");
				}
				return JSON.stringify(mockOpenApiSpec);
			});

			await expect(
				processOpenApi({
					input: "spec.yaml",
					output: "output",
					namespace: "App\\Api\\V1",
					template: "template.hbs",
				}),
			).rejects.toThrow();
		});

		it("should handle directory creation errors", async () => {
			(mkdirSync as Mock).mockImplementation(() => {
				throw new Error("Permission denied");
			});

			await expect(
				processOpenApi({
					input: "spec.yaml",
					output: "output",
					namespace: "App\\Api\\V1",
				}),
			).rejects.toThrow();
		});

		it("should handle existing output directory", async () => {
			(existsSync as Mock).mockReturnValue(true);

			await processOpenApi({
				input: "spec.yaml",
				output: "output",
				namespace: "App\\Api\\V1",
			});

			expect(mkdirSync).not.toHaveBeenCalled();
		});
	});

	describe("Pipeline Integration", () => {
		it("should execute the full pipeline successfully", async () => {
			await processOpenApi({
				input: "spec.yaml",
				output: "output",
				namespace: "App\\Api\\V1",
			});

			expect(parse).toHaveBeenCalledWith(JSON.stringify(mockOpenApiSpec));
			expect(flatten).toHaveBeenCalledWith(mockParsed);
			expect(generatePhp).toHaveBeenCalled();
		});

		it("should handle parser errors gracefully", async () => {
			(parse as unknown as Mock).mockRejectedValue(
				new Error("Invalid OpenAPI spec"),
			);

			await expect(
				processOpenApi({
					input: "spec.yaml",
					output: "output",
					namespace: "App\\Api\\V1",
				}),
			).rejects.toThrow();
		});

		it("should use custom template if provided", async () => {
			const customTemplate = "custom template content";
			(readFileSync as Mock).mockImplementation((path: string) => {
				if (path === "template.hbs") {
					return customTemplate;
				}
				return JSON.stringify(mockOpenApiSpec);
			});

			await processOpenApi({
				input: "spec.yaml",
				output: "output",
				namespace: "App\\Api\\V1",
				template: "template.hbs",
			});

			expect(generatePhp).toHaveBeenCalledWith(mockFlattened, customTemplate);
		});

		it("should handle flattener errors", async () => {
			(flatten as unknown as Mock).mockImplementation(() => {
				throw new Error("Flattening failed");
			});

			await expect(
				processOpenApi({
					input: "spec.yaml",
					output: "output",
					namespace: "App\\Api\\V1",
				}),
			).rejects.toThrow();
		});

		it("should handle generator errors", async () => {
			(generatePhp as unknown as Mock).mockImplementation(() => {
				throw new Error("Generation failed");
			});

			await expect(
				processOpenApi({
					input: "spec.yaml",
					output: "output",
					namespace: "App\\Api\\V1",
				}),
			).rejects.toThrow();
		});
	});

	describe("Error Handling", () => {
		it("should handle invalid OpenAPI specification", async () => {
			(parse as unknown as Mock).mockRejectedValue(
				new Error("Invalid OpenAPI spec"),
			);

			await expect(
				processOpenApi({
					input: "spec.yaml",
					output: "output",
					namespace: "App\\Api\\V1",
				}),
			).rejects.toThrow();
		});

		it("should handle file system errors", async () => {
			(writeFileSync as Mock).mockImplementation(() => {
				throw new Error("Permission denied");
			});

			await expect(
				processOpenApi({
					input: "spec.yaml",
					output: "output",
					namespace: "App\\Api\\V1",
				}),
			).rejects.toThrow();
		});

		it("should handle invalid configuration", async () => {
			await expect(
				processOpenApi({
					input: "",
					output: "",
					namespace: "",
				}),
			).rejects.toThrow();
		});

		it("should handle invalid template format", async () => {
			(readFileSync as Mock).mockImplementation((path: string) => {
				if (path === "template.hbs") {
					return "{{invalid template";
				}
				return JSON.stringify(mockOpenApiSpec);
			});

			(generatePhp as unknown as Mock).mockImplementation(() => {
				throw new Error("Invalid template format");
			});

			await expect(
				processOpenApi({
					input: "spec.yaml",
					output: "output",
					namespace: "App\\Api\\V1",
					template: "template.hbs",
				}),
			).rejects.toThrow();
		});

		it("should handle empty OpenAPI specification", async () => {
			(readFileSync as Mock).mockReturnValue("");

			await expect(
				processOpenApi({
					input: "spec.yaml",
					output: "output",
					namespace: "App\\Api\\V1",
				}),
			).rejects.toThrow();
		});
	});
});
