<?php

declare(strict_types=1);

readonly class ProfileResponse
{
    public function __construct(
        public ?string $id = null,
        public ?string $username = null,
        public ?string $email = null,
        public ?string $bio = null,        // Nullable
        public ?string $avatar = null,     // Nullable
        public ?UserSettings $settings = null, // Nullable reference
        public ?string $createdAt = null,
        public ?string $updatedAt = null   // Nullable datetime
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            username: $data['username'] ?? null,
            email: $data['email'] ?? null,
            bio: $data['bio'] ?? null,
            avatar: $data['avatar'] ?? null,
            settings: isset($data['settings']) ? UserSettings::fromArray($data['settings']) : null,
            createdAt: $data['createdAt'] ?? null,
            updatedAt: $data['updatedAt'] ?? null
        );
    }
}
