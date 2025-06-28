<?php

declare(strict_types=1);

readonly class ApiResponse
{
    public function __construct(
        public ?int $code = null,
        public ?string $type = null,
        public ?string $message = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: isset($data['code']) && (is_int($data['code']) || is_numeric($data['code'])) ? (int) $data['code'] : null,
            type: isset($data['type']) && is_string($data['type']) ? $data['type'] : null,
            message: isset($data['message']) && is_string($data['message']) ? $data['message'] : null
        );
    }
}
