<?php

declare(strict_types=1);

readonly class TagObject
{
    public function __construct(
        public string $name,
        public ?string $category = null,
        public ?string $color = null,
        public ?float $weight = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['name']) || (! is_string($data['name']) && ! is_scalar($data['name']))) {
            throw new \InvalidArgumentException('name must be a string');
        }

        $weight = null;
        if (isset($data['weight']) && is_numeric($data['weight'])) {
            $weightValue = (float) $data['weight'];
            // Validate weight is between 0 and 1
            if ($weightValue >= 0.0 && $weightValue <= 1.0) {
                $weight = $weightValue;
            }
        }

        return new self(
            name: is_string($data['name']) ? $data['name'] : (string) $data['name'],
            category: isset($data['category']) && is_scalar($data['category']) ? (string) $data['category'] : null,
            color: isset($data['color']) && is_scalar($data['color']) ? (string) $data['color'] : null,
            weight: $weight
        );
    }
}
