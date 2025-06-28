<?php

declare(strict_types=1);

readonly class Auditable
{
    public function __construct(
        public ?string $lastModifiedBy = null,
        public ?string $lastModifiedAt = null,
        public ?int $version = null
    ) {}

    /**
     * Factory method to create from array data
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            lastModifiedBy: isset($data['lastModifiedBy']) ? (is_string($data['lastModifiedBy']) ? $data['lastModifiedBy'] : (string) $data['lastModifiedBy']) : null,
            lastModifiedAt: isset($data['lastModifiedAt']) ? (is_string($data['lastModifiedAt']) ? $data['lastModifiedAt'] : (string) $data['lastModifiedAt']) : null,
            version: isset($data['version']) ? (is_int($data['version']) ? $data['version'] : (int) $data['version']) : null
        );
    }
}
