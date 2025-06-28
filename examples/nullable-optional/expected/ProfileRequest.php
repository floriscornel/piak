<?php

declare(strict_types=1);

readonly class ProfileRequest
{
    public function __construct(
        public string $username,
        public string $email,
        public ?string $bio,           // Required but nullable (no default)
        public ?string $avatar = null, // Optional (has default)
        public ?UserSettings $settings = null // Optional reference (has default)
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            username: $data['username'],
            email: $data['email'],
            bio: $data['bio'], // Required but can be null - no ?? null needed
            avatar: $data['avatar'] ?? null,
            settings: isset($data['settings']) ? UserSettings::fromArray($data['settings']) : null
        );
    }
}
