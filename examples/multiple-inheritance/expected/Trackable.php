<?php

declare(strict_types=1);

readonly class Trackable
{
    public function __construct(
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $sessionId = null
    ) {}

    /**
     * Factory method to create from array data
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ipAddress: isset($data['ipAddress']) ? (is_string($data['ipAddress']) ? $data['ipAddress'] : (string) $data['ipAddress']) : null,
            userAgent: isset($data['userAgent']) ? (is_string($data['userAgent']) ? $data['userAgent'] : (string) $data['userAgent']) : null,
            sessionId: isset($data['sessionId']) ? (is_string($data['sessionId']) ? $data['sessionId'] : (string) $data['sessionId']) : null
        );
    }
}
