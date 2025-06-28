<?php

declare(strict_types=1);

readonly class Product
{
    public function __construct(
        public string $id,
        public string $name,
        public ?float $price = null,
        public ?string $description = null,
        public ?string $category = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: is_string($data['id']) ? $data['id'] : (string) $data['id'],
            name: is_string($data['name']) ? $data['name'] : (string) $data['name'],
            price: isset($data['price']) ? (is_float($data['price']) ? $data['price'] : (float) $data['price']) : null,
            description: isset($data['description']) ? (is_string($data['description']) ? $data['description'] : (string) $data['description']) : null,
            category: isset($data['category']) ? (is_string($data['category']) ? $data['category'] : (string) $data['category']) : null
        );
    }
}
