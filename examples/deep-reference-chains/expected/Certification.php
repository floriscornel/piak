<?php

declare(strict_types=1);

readonly class Certification
{
    public function __construct(
        public ?string $name = null,
        public ?string $issuer = null,
        public ?string $validUntil = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: isset($data['name']) && is_scalar($data['name']) ? (string) $data['name'] : null,
            issuer: isset($data['issuer']) && is_scalar($data['issuer']) ? (string) $data['issuer'] : null,
            validUntil: isset($data['validUntil']) && is_scalar($data['validUntil']) ? (string) $data['validUntil'] : null
        );
    }
}
