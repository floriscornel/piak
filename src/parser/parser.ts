import type { Input as FlattenInput } from "@/converter/flattenProperties";
import type { Property } from "@/converter/types/properties";
import { dereference } from "@scalar/openapi-parser";
import type { OpenAPIV3 } from "@scalar/openapi-types";

export async function parse(specification: string): Promise<FlattenInput> {
	const result: FlattenInput = {};

	const schemaToRef: Record<string, string> = {};

	const { schema, errors } = await dereference(specification, {
		onDereference: ({ schema, ref }) => {
			schemaToRef[JSON.stringify(schema)] = ref;
		},
	});
	if (errors && errors.length > 0) {
		throw new Error(errors.map((e) => e.message).join("\n"));
	}

	if (!schema) {
		throw new Error("No schema found");
	}

	if (!schema.openapi?.startsWith("3.0")) {
		throw new Error(
			`Only OpenAPI 3.0.x is supported, provided version: ${schema.openapi}`,
		);
	}

	// Keep track of incremental PropertyIDs
	let propertyID = 1;

	// Process all components first to ensure consistent property IDs
	if (schema.components?.schemas) {
		for (const [name, component] of Object.entries(schema.components.schemas)) {
			result[name] = {
				type: "object",
				propertyID: propertyID++,
				properties: processSchema(
					component as OpenAPIV3.SchemaObject,
					propertyID,
				),
			};
		}
	}

	const parseSchema = (name: string, schemaObj: OpenAPIV3.SchemaObject) => {
		const stringifiedSchema = JSON.stringify(schemaObj);
		const refName = schemaToRef[stringifiedSchema];
		if (refName && result[refName]) {
			result[name] = {
				type: "reference",
			};
		} else {
			result[name] = {
				type: "object",
				propertyID: propertyID++,
				properties: processSchema(schemaObj, propertyID),
			};
		}
	};

	console.debug("Parsed components", Object.keys(result));

	// Process all paths
	if (schema.paths) {
		for (const [path, pathItem] of Object.entries(schema.paths)) {
			for (const [method, operation] of Object.entries(pathItem)) {
				if (method === "parameters") continue; // Skip parameters as they're handled separately

				const op = operation as OpenAPIV3.OperationObject;

				// Process responses first
				if (op.responses) {
					for (const [statusCode, response] of Object.entries(op.responses)) {
						console.debug(
							"OP",
							op.operationId,
							"Response",
							statusCode,
							JSON.stringify(response),
						);
						const resp = response as OpenAPIV3.ResponseObject;
						const content = resp.content?.["application/json"];
						if (content?.schema) {
							const name = getName({
								path,
								method,
								type: "response",
								operationID: op.operationId,
							});
							if (name) {
								console.debug(
									"Response name",
									name,
									"content",
									JSON.stringify(content),
								);
								const schemaObj = content.schema as OpenAPIV3.SchemaObject;
								if (schemaObj.$ref) {
									// Handle $ref by using the referenced schema name
									const refName = schemaObj.$ref.split("/").pop();
									if (refName) {
										result[name] = {
											type: "object",
											propertyID: propertyID++,
											properties: processSchema(
												schema.components?.schemas?.[
													refName
												] as OpenAPIV3.SchemaObject,
												propertyID,
											),
										};
										console.debug(
											"Response name",
											name,
											"result",
											JSON.stringify(result[name]),
										);
									}
								} else {
									result[name] = {
										type: "object",
										propertyID: propertyID++,
										properties: processSchema(schemaObj, propertyID),
									};
									console.debug(
										"Response name",
										name,
										"result",
										JSON.stringify(result[name]),
									);
								}
							}
						}
					}
				}

				// Process request body if it exists
				if (op.requestBody) {
					const requestBody = op.requestBody as OpenAPIV3.RequestBodyObject;
					const content = requestBody.content?.["application/json"];
					if (content?.schema) {
						const name = getName({
							path,
							method,
							type: "request",
							operationID: op.operationId,
						});
						if (name) {
							console.debug("Request name", name, JSON.stringify(content));
							const schemaObj = content.schema as OpenAPIV3.SchemaObject;
							if (schemaObj.$ref) {
								// Handle $ref by using the referenced schema name
								const refName = schemaObj.$ref.split("/").pop();
								if (refName) {
									result[name] = {
										type: "object",
										propertyID: propertyID++,
										properties: processSchema(
											schema.components?.schemas?.[
												refName
											] as OpenAPIV3.SchemaObject,
											propertyID,
										),
									};
									console.debug(
										"Request name",
										name,
										JSON.stringify(result[name]),
									);
								}
							} else {
								result[name] = {
									type: "object",
									propertyID: propertyID++,
									properties: processSchema(schemaObj, propertyID),
								};
								console.debug(
									"Request name",
									name,
									JSON.stringify(result[name]),
								);
							}
						}
					}
				}
			}
		}
	}

	return result;
}

function processSchema(
	schema: OpenAPIV3.SchemaObject,
	propertyID: number,
): Record<string, Property> {
	const properties: Record<string, Property> = {};

	if (schema.type === "object" && schema.properties) {
		for (const [key, value] of Object.entries(schema.properties)) {
			properties[key] = processProperty(
				value as OpenAPIV3.SchemaObject,
				propertyID,
			);
		}
	} else if (schema.type === "array" && schema.items) {
		properties.items = {
			type: "array",
			items: processProperty(
				schema.items as OpenAPIV3.SchemaObject,
				propertyID,
			),
		};
	}

	return properties;
}

function processProperty(
	schema: OpenAPIV3.SchemaObject,
	propertyID: number,
): Property {
	if (schema.type === "object") {
		return {
			type: "object",
			propertyID: propertyID,
			properties: processSchema(schema, propertyID),
		};
	}

	if (schema.type === "array") {
		return {
			type: "array",
			items: processProperty(
				schema.items as OpenAPIV3.SchemaObject,
				propertyID,
			),
		};
	}

	// Handle primitive types
	switch (schema.type) {
		case "string": {
			const property: Property = { type: "string" };
			if (schema.format) property.format = schema.format;
			if (schema.enum) property.enum = schema.enum;
			return property;
		}
		case "number": {
			const property: Property = { type: "number" };
			if (schema.format) property.format = schema.format as "float" | "double";
			return property;
		}
		case "integer": {
			const property: Property = { type: "integer" };
			if (schema.format) property.format = schema.format as "int32" | "int64";
			return property;
		}
		case "boolean":
			return {
				type: "boolean",
			};
		default:
			throw new Error(`Unsupported type: ${schema.type}`);
	}
}

function getName(
	context:
		| {
				component: string;
		  }
		| {
				path: string;
				operationID?: string;
				method: string;
				type: "request" | "response";
		  },
): string {
	if ("component" in context) {
		return context.component;
	}

	const {
		path, // /tags
		operationID, // getTags | undefined
		method, // get
		type, // request | response
	} = context;

	// Skip request bodies for POST operations
	if (type === "request" && method.toLowerCase() === "post") {
		return "";
	}

	if (operationID) {
		return type === "request" ? `${operationID}Request` : operationID;
	}

	if (path && method && type) {
		return `${path}-${method}-${type}`;
	}

	return "";
}
