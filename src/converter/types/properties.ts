// See https://spec.openapis.org/oas/v3.0.3.html#data-types for more information

type IntegerType = {
	type: "integer";
	format?: "int32" | "int64";
};

type NumberType = {
	type: "number";
	format?: "float" | "double";
};

type StringType = {
	type: "string";
	format?:
		| "byte"
		| "binary"
		| "date"
		| "date-time"
		| "password"
		| "uuid"
		| "email"
		| string;
	enum?: string[];
};

type BooleanType = {
	type: "boolean";
};

type ArrayType = {
	type: "array";
	items: Property;
};

type ObjectType = {
	propertyID: number; // This is used to for references
	type: "object";
	properties: Record<string, Property>;
};

type ReferenceType = {
	type: "reference";
	refID?: number; // Refers to the propertyID of the object
};

export type Property = (
	| IntegerType
	| NumberType
	| StringType
	| BooleanType
	| ArrayType
	| ObjectType
	| ReferenceType
) & {
	description?: string;
};

export type PropertyWithID = Property & ObjectType;
