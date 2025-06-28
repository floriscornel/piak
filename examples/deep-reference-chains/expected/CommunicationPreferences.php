<?php

declare(strict_types=1);

readonly class CommunicationPreferences
{
    public function __construct(
        public ?bool $email = null,
        public ?bool $slack = null,
        public ?bool $sms = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: isset($data['email']) && is_bool($data['email']) ? $data['email'] : null,
            slack: isset($data['slack']) && is_bool($data['slack']) ? $data['slack'] : null,
            sms: isset($data['sms']) && is_bool($data['sms']) ? $data['sms'] : null
        );
    }
}
