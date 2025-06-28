<?php

declare(strict_types=1);

readonly class PaymentEvent extends Event
{
    public function __construct(
        public string $transactionId,
        public float $amount,
        public ?string $currency = null,
        public ?string $status = null
    ) {
        parent::__construct('payment');
    }

    public static function fromArray(array $data): static
    {
        return new static(
            transactionId: $data['transactionId'],
            amount: (float) $data['amount'],
            currency: $data['currency'] ?? null,
            status: $data['status'] ?? null
        );
    }
}
