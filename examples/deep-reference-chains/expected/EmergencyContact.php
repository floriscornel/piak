<?php

declare(strict_types=1);

readonly class EmergencyContact
{
    public function __construct(
        public ?string $name = null,
        public ?string $relationship = null,
        public ?string $phone = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: isset($data['name']) && is_scalar($data['name']) ? (string) $data['name'] : null,
            relationship: isset($data['relationship']) && is_scalar($data['relationship']) ? (string) $data['relationship'] : null,
            phone: isset($data['phone']) && is_scalar($data['phone']) ? (string) $data['phone'] : null
        );
    }
}
