import type { PropertyWithID } from "@/converter/types/properties";
import { nameArrayItem } from "@/utils/naming";

export type Input = Record<string, PropertyWithID>;

export type Output = Record<string, PropertyWithID>;

const flattenProperty = (
	property: PropertyWithID,
): [PropertyWithID, Record<string, PropertyWithID>] => {
	const references: Record<string, PropertyWithID> = {};
	for (const [key, value] of Object.entries(property.properties)) {
		if (value.type === "object") {
			const [child, childReferences] = flattenProperty(value);
			// Add the child object to the references
			Object.assign(references, { [key]: child }, childReferences);
			// Replace the object with a reference
			property.properties[key] = {
				type: "reference",
				refID: child.propertyID,
			};
		}
		// If the child object is an array of objects, replace with an array of references
		if (value.type === "array" && value.items.type === "object") {
			const [child, childReferences] = flattenProperty(value.items);
			// Add the child object to the references
			Object.assign(
				references,
				{ [nameArrayItem(key)]: child },
				childReferences,
			);
			// Replace the array with a reference
			value.items = { type: "reference", refID: child.propertyID };
		}
	}
	return [property, references];
};

export function flatten(input: Input): Output {
	const output: Output = {};
	for (const [key, value] of Object.entries(input)) {
		const [flattened, references] = flattenProperty(value);
		Object.assign(output, { [key]: flattened }, references);
	}
	return output;
}
