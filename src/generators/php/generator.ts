import type { Output as FlattenOutput } from "@/converter/flattenProperties";
import Handlebars from "handlebars";

interface Property {
	type: string;
	format?: string;
	items?: {
		type: string;
		refID?: number;
	};
}

function getPhpType(type: string, format?: string): string {
	switch (type) {
		case "string":
			return "string";
		case "number":
		case "integer":
			return "int";
		case "boolean":
			return "bool";
		case "array":
			return "array";
		default:
			return "mixed";
	}
}

export function generatePhp(
	input: FlattenOutput,
	template: string,
): Record<string, string> {
	const output: Record<string, string> = {};

	for (const [className, schema] of Object.entries(input)) {
		const imports = new Set<string>();
		const properties = Object.entries(schema.properties || {}).map(
			([name, prop]) => {
				let type = getPhpType(prop.type);
				let isArray = false;
				let arrayType = "";

				// Handle array types
				if (
					prop.type === "array" &&
					(prop as Property).items?.type === "reference"
				) {
					const refName = Object.keys(input).find(
						(key) => input[key].propertyID === (prop as Property).items?.refID,
					);
					if (refName) {
						type = "array";
						isArray = true;
						arrayType = refName;
						imports.add(`${refName}`);
					}
				}

				return { name, type, isArray, arrayType };
			},
		);

		const phpCode = Handlebars.compile(template)({
			className,
			namespace: "App\\Api\\V1",
			properties,
			imports: Array.from(imports).map((imp) => `App\\Api\\V1\\${imp}`),
		});

		output[`${className}.php`] = phpCode;
	}

	return output;
}
