<?php

declare(strict_types=1);

abstract readonly class Event
{
    public function __construct(
        public string $type
    ) {}

    public static function fromArray(array $data): static
    {
        return match ($data['type']) {
            'user' => UserEvent::fromArray($data),
            'system' => SystemEvent::fromArray($data),
            'payment' => PaymentEvent::fromArray($data),
            default => throw new \InvalidArgumentException("Unknown event type: {$data['type']}")
        };
    }
}
