<?php

declare(strict_types=1);

readonly class Category
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) && (is_int($data['id']) || is_numeric($data['id'])) ? (int) $data['id'] : null,
            name: isset($data['name']) && is_string($data['name']) ? $data['name'] : null
        );
    }
}
