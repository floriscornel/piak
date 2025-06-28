<?php

declare(strict_types=1);

readonly class Metadata
{
    public function __construct(
        /** @var array<string, string|int|bool> */
        public array $additionalProperties = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $additionalProperties = [];

        // All properties are additional since this is a pure dynamic object
        foreach ($data as $key => $value) {
            // Only allow string, int, bool according to oneOf schema
            if (is_string($value) || is_int($value) || is_bool($value)) {
                $additionalProperties[$key] = $value;
            }
            // Note: In production, you might want to throw an exception for invalid types
        }

        return new self(
            additionalProperties: $additionalProperties
        );
    }
}
