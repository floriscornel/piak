<?php

declare(strict_types=1);

readonly class ErrorResponse
{
    public function __construct(
        public bool $success,
        public string|ErrorDetail $error,
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
        if (! isset($data['error'])) {
            throw new \InvalidArgumentException('error is required');
        }

        $error = match (true) {
            is_string($data['error']) => $data['error'],
            is_array($data['error']) => ErrorDetail::fromArray($data['error']),
            default => throw new \InvalidArgumentException('Error must be string or ErrorDetail object')
        };

        return new self(
            success: $data['success'],
            error: $error,
            meta: isset($data['meta']) ? (object) $data['meta'] : null
        );
    }
}
