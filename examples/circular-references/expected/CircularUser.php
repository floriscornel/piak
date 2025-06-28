<?php

declare(strict_types=1);

readonly class CircularUser
{
    public function __construct(
        public string $id,
        public string $username,
        public ?string $email = null,
        public ?UserProfile $profile = null  // Keep object reference as per OpenAPI spec
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Load Profile but prevent circular reference by not loading User back into Profile
        $profile = null;
        if (isset($data['profile'])) {
            $profile = UserProfile::fromArrayWithoutUser($data['profile']);
        }

        return new self(
            id: $data['id'],
            username: $data['username'],
            email: $data['email'] ?? null,
            profile: $profile
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArrayWithoutProfile(array $data): self
    {
        // Create User without loading Profile to break circular reference
        return new self(
            id: $data['id'],
            username: $data['username'],
            email: $data['email'] ?? null,
            profile: null // Don't load profile to prevent circular reference
        );
    }
}
