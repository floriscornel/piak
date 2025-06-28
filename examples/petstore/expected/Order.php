<?php

declare(strict_types=1);

readonly class Order
{
    public function __construct(
        public ?int $id = null,
        public ?int $petId = null,
        public ?int $quantity = null,
        public ?string $shipDate = null,
        public ?string $status = null,
        public ?bool $complete = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) && (is_int($data['id']) || is_numeric($data['id'])) ? (int) $data['id'] : null,
            petId: isset($data['petId']) && (is_int($data['petId']) || is_numeric($data['petId'])) ? (int) $data['petId'] : null,
            quantity: isset($data['quantity']) && (is_int($data['quantity']) || is_numeric($data['quantity'])) ? (int) $data['quantity'] : null,
            shipDate: isset($data['shipDate']) && is_string($data['shipDate']) ? $data['shipDate'] : null,
            status: isset($data['status']) && is_string($data['status']) ? $data['status'] : null,
            complete: isset($data['complete']) && is_bool($data['complete']) ? $data['complete'] : null
        );
    }
}
