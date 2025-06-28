<?php

declare(strict_types=1);

/**
 * Wrapper class for anyOf delivery types - can match multiple types simultaneously
 */
readonly class AnyOfDelivery
{
    public function __construct(
        public ?EmailDelivery $emailDelivery = null,
        public ?SmsDelivery $smsDelivery = null,
        public ?PushDelivery $pushDelivery = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $emailDelivery = null;
        $smsDelivery = null;
        $pushDelivery = null;

        // Try to match EmailDelivery (has email property)
        if (isset($data['email'])) {
            try {
                $emailDelivery = EmailDelivery::fromArray($data);
            } catch (\Throwable) {
                // Validation failed, skip this type
            }
        }

        // Try to match SmsDelivery (has phoneNumber property)
        if (isset($data['phoneNumber'])) {
            try {
                $smsDelivery = SmsDelivery::fromArray($data);
            } catch (\Throwable) {
                // Validation failed, skip this type
            }
        }

        // Try to match PushDelivery (has deviceToken property)
        if (isset($data['deviceToken'])) {
            try {
                $pushDelivery = PushDelivery::fromArray($data);
            } catch (\Throwable) {
                // Validation failed, skip this type
            }
        }

        // anyOf requires at least one match
        if ($emailDelivery === null && $smsDelivery === null && $pushDelivery === null) {
            throw new \InvalidArgumentException('Data does not match any delivery type schemas');
        }

        return new self(
            emailDelivery: $emailDelivery,
            smsDelivery: $smsDelivery,
            pushDelivery: $pushDelivery
        );
    }

    /**
     * Get the first available delivery type (for backward compatibility)
     */
    public function getDelivery(): EmailDelivery|SmsDelivery|PushDelivery
    {
        return $this->emailDelivery ?? $this->smsDelivery ?? $this->pushDelivery
            ?? throw new \LogicException('No delivery types available');
    }
}

readonly class NotificationResponse
{
    public function __construct(
        public ?string $id = null,
        public ?AnyOfDelivery $delivery = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $delivery = null;
        if (isset($data['delivery'])) {
            $delivery = AnyOfDelivery::fromArray($data['delivery']);
        }

        return new self(
            id: $data['id'] ?? null,
            delivery: $delivery
        );
    }
}
