<?php

declare(strict_types=1);

readonly class DynamicSettings
{
    public function __construct(
        public ?bool $notifications = null,
        public ?string $language = null,
        /** @var array<string, string> */
        public array $additionalProperties = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $knownProperties = ['notifications', 'language'];
        $additionalProperties = [];

        // Extract additional properties (only strings according to schema)
        foreach ($data as $key => $value) {
            if (! in_array($key, $knownProperties, true)) {
                if (is_string($value)) {
                    $additionalProperties[$key] = $value;
                }
                // Note: In production, you might want to throw an exception for invalid types
            }
        }

        return new self(
            notifications: isset($data['notifications']) ? (bool) $data['notifications'] : null,
            language: $data['language'] ?? null,
            additionalProperties: $additionalProperties
        );
    }
}
