<?php

declare(strict_types=1);

readonly class SearchResult
{
    public function __construct(
        public Product|User|Order $result
    ) {}

    /**
     * Factory method to detect type based on properties
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Type detection logic based on unique properties
        if (isset($data['price']) || isset($data['category'])) {
            return new self(Product::fromArray($data));
        }

        if (isset($data['email']) || isset($data['bio'])) {
            return new self(User::fromArray($data));
        }

        if (isset($data['total']) || isset($data['status']) || isset($data['items'])) {
            return new self(Order::fromArray($data));
        }

        // Fallback: try to match based on required properties
        if (isset($data['id'], $data['name'])) {
            // All three types have id+name, need heuristics
            // Check for number fields to suggest Product
            if (is_numeric($data['price'] ?? null)) {
                return new self(Product::fromArray($data));
            }

            // Default to User if no clear indicators
            return new self(User::fromArray($data));
        }

        throw new \InvalidArgumentException('Unable to determine SearchResult type from data');
    }
}
