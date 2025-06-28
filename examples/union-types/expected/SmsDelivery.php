<?php

declare(strict_types=1);

readonly class SmsDelivery
{
    public function __construct(
        public ?string $phoneNumber = null,
        public ?string $carrier = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            phoneNumber: $data['phoneNumber'] ?? null,
            carrier: $data['carrier'] ?? null
        );
    }
}
