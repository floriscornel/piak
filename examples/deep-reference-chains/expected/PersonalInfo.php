<?php

declare(strict_types=1);

readonly class PersonalInfo
{
    public function __construct(
        public ?string $birthDate = null,
        public ?string $phone = null,
        public ?string $address = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            birthDate: isset($data['birthDate']) && is_scalar($data['birthDate']) ? (string) $data['birthDate'] : null,
            phone: isset($data['phone']) && is_scalar($data['phone']) ? (string) $data['phone'] : null,
            address: isset($data['address']) && is_scalar($data['address']) ? (string) $data['address'] : null
        );
    }
}
