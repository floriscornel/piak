<?php

declare(strict_types=1);

readonly class ShippingAddress
{
    public function __construct(
        public string $country,
        public string $addressLine1,
        public ?string $addressLine2 = null,
        public ?string $city = null,
        public ?string $postalCode = null,
        public ?string $state = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['country']) || (! is_string($data['country']) && ! is_scalar($data['country']))) {
            throw new \InvalidArgumentException('country must be a string');
        }
        if (! isset($data['addressLine1']) || (! is_string($data['addressLine1']) && ! is_scalar($data['addressLine1']))) {
            throw new \InvalidArgumentException('addressLine1 must be a string');
        }

        $instance = new self(
            country: is_string($data['country']) ? $data['country'] : (string) $data['country'],
            addressLine1: is_string($data['addressLine1']) ? $data['addressLine1'] : (string) $data['addressLine1'],
            addressLine2: isset($data['addressLine2']) && is_scalar($data['addressLine2']) ? (is_string($data['addressLine2']) ? $data['addressLine2'] : (string) $data['addressLine2']) : null,
            city: isset($data['city']) && is_scalar($data['city']) ? (is_string($data['city']) ? $data['city'] : (string) $data['city']) : null,
            postalCode: isset($data['postalCode']) && is_scalar($data['postalCode']) ? (is_string($data['postalCode']) ? $data['postalCode'] : (string) $data['postalCode']) : null,
            state: isset($data['state']) && is_scalar($data['state']) ? (is_string($data['state']) ? $data['state'] : (string) $data['state']) : null
        );

        // Validate country-specific requirements
        self::validateCountrySpecificFields($instance);

        return $instance;
    }

    private static function validateCountrySpecificFields(self $address): void
    {
        match ($address->country) {
            'US' => self::validateUSAddress($address),
            'UK' => self::validateUKAddress($address),
            default => null // No specific validation for other countries
        };
    }

    private static function validateUSAddress(self $address): void
    {
        if (empty($address->state) || empty($address->postalCode)) {
            throw new \InvalidArgumentException('US addresses require state and postalCode');
        }

        if (! preg_match('/^[0-9]{5}(-[0-9]{4})?$/', $address->postalCode)) {
            throw new \InvalidArgumentException('US postal code must be in format 12345 or 12345-6789');
        }
    }

    private static function validateUKAddress(self $address): void
    {
        if (empty($address->postalCode)) {
            throw new \InvalidArgumentException('UK addresses require postalCode');
        }

        if (! preg_match('/^[A-Z]{1,2}[0-9]{1,2}[A-Z]?\s?[0-9][A-Z]{2}$/', $address->postalCode)) {
            throw new \InvalidArgumentException('UK postal code must be in valid UK format (e.g., SW1A 1AA)');
        }
    }
}
