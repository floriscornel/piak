<?php

declare(strict_types=1);

readonly class RichContent
{
    public function __construct(
        public string $type,
        public string $data,
        public ?string $encoding = null,
        public ?int $size = null,
        public ?string $checksum = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['type']) || (! is_string($data['type']) && ! is_scalar($data['type']))) {
            throw new \InvalidArgumentException('type must be a string');
        }
        if (! isset($data['data']) || (! is_string($data['data']) && ! is_scalar($data['data']))) {
            throw new \InvalidArgumentException('data must be a string');
        }

        return new self(
            type: is_string($data['type']) ? $data['type'] : (string) $data['type'],
            data: is_string($data['data']) ? $data['data'] : (string) $data['data'],
            encoding: isset($data['encoding']) && is_scalar($data['encoding']) ? (string) $data['encoding'] : null,
            size: isset($data['size']) && is_numeric($data['size']) ? (int) $data['size'] : null,
            checksum: isset($data['checksum']) && is_scalar($data['checksum']) ? (string) $data['checksum'] : null
        );
    }
}
