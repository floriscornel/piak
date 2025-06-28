<?php

declare(strict_types=1);

readonly class PushDelivery
{
    public function __construct(
        public ?string $deviceToken = null,
        public ?string $platform = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            deviceToken: $data['deviceToken'] ?? null,
            platform: $data['platform'] ?? null
        );
    }
}
