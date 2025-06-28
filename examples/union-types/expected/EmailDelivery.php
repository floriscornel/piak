<?php

declare(strict_types=1);

readonly class EmailDelivery
{
    public function __construct(
        public ?string $email = null,
        public ?string $messageId = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'] ?? null,
            messageId: $data['messageId'] ?? null
        );
    }
}
