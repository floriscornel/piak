<?php

declare(strict_types=1);

readonly class ApiError
{
    public function __construct(
        public string $code,
        public string $message
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['code']) || ! is_string($data['code'])) {
            throw new \InvalidArgumentException('ApiError code must be a string');
        }
        if (! isset($data['message']) || ! is_string($data['message'])) {
            throw new \InvalidArgumentException('ApiError message must be a string');
        }

        return new self(
            code: $data['code'],
            message: $data['message']
        );
    }
}
