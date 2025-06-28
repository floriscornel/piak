<?php

declare(strict_types=1);

readonly class SMSNotification
{
    public function __construct(
        public string $type,
        public string $phoneNumber,
        public ?string $message = null,
        public ?string $carrier = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Validate required fields
        if (! isset($data['type']) || ! is_string($data['type'])) {
            throw new \InvalidArgumentException('SMSNotification type must be a string');
        }
        if (! isset($data['phoneNumber']) || ! is_string($data['phoneNumber'])) {
            throw new \InvalidArgumentException('SMSNotification phoneNumber must be a string');
        }

        // Handle optional fields
        $message = null;
        if (isset($data['message'])) {
            if (! is_string($data['message'])) {
                throw new \InvalidArgumentException('SMSNotification message must be a string');
            }
            $message = $data['message'];
        }

        $carrier = null;
        if (isset($data['carrier'])) {
            if (! is_string($data['carrier'])) {
                throw new \InvalidArgumentException('SMSNotification carrier must be a string');
            }
            $carrier = $data['carrier'];
        }

        return new self(
            type: $data['type'],
            phoneNumber: $data['phoneNumber'],
            message: $message,
            carrier: $carrier
        );
    }
}
