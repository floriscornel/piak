<?php

declare(strict_types=1);

readonly class SuccessResponse
{
    public function __construct(
        public bool $success,
        public mixed $data,
        public ?object $meta = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['success']) || ! is_bool($data['success'])) {
            throw new \InvalidArgumentException('success must be a boolean');
        }
        if (! isset($data['data'])) {
            throw new \InvalidArgumentException('data is required');
        }

        return new self(
            success: $data['success'],
            data: $data['data'],
            meta: isset($data['meta']) ? (object) $data['meta'] : null
        );
    }
}
