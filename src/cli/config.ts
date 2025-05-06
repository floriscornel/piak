import { z } from "zod";

export const ConfigSchema = z.object({
	input: z.string().describe("Path to the OpenAPI specification file"),
	output: z.string().describe("Output directory for generated PHP files"),
	namespace: z
		.string()
		.default("App\\Api\\V1")
		.describe("PHP namespace for generated classes"),
	template: z
		.string()
		.optional()
		.describe("Path to custom Handlebars template file"),
});

export type Config = z.infer<typeof ConfigSchema>;

export const defaultTemplate = `<?php
{{#if imports}}

{{#each imports}}
use {{this}};
{{/each}}
{{/if}}
{{#if namespace}}

namespace {{namespace}};
{{/if}}

readonly class {{className}} {
    {{#each properties}}
    {{#if isArray}}
    /**
     * @var {{arrayType}}[]
     */
    {{/if}}
    public {{type}} \${{name}};
    {{/each}}

    public function __construct(
        {{#each properties}}
        public {{type}} \${{name}}{{#unless @last}},{{/unless}}
        {{/each}}
    ) {}
}`;
