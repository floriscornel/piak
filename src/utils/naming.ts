export const nameArrayItem = (arrayKey: string): string => {
	return `${arrayKey.charAt(0).toUpperCase() + arrayKey.slice(1)}Item`;
};
