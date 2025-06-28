<?php

declare(strict_types=1);

readonly class UserProfile
{
    public function __construct(
        public string $id,
        public string $userId,  // Keep userId for reference
        public ?string $displayName = null,
        public ?string $bio = null,
        public ?CircularUser $user = null,  // Keep object reference as per OpenAPI spec
        public ?UserProfileSettings $settings = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Load User but prevent circular reference by not loading Profile back into User
        $user = null;
        if (isset($data['user'])) {
            $user = CircularUser::fromArrayWithoutProfile($data['user']);
        }

        // Load ProfileSettings but prevent circular reference
        $settings = null;
        if (isset($data['settings'])) {
            $settings = UserProfileSettings::fromArrayWithoutProfile($data['settings']);
        }

        return new self(
            id: $data['id'],
            userId: $data['userId'],
            displayName: $data['displayName'] ?? null,
            bio: $data['bio'] ?? null,
            user: $user,
            settings: $settings
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArrayWithoutUser(array $data): self
    {
        // Create Profile without loading User to break circular reference
        $settings = null;
        if (isset($data['settings'])) {
            $settings = UserProfileSettings::fromArrayWithoutProfile($data['settings']);
        }

        return new self(
            id: $data['id'],
            userId: $data['userId'],
            displayName: $data['displayName'] ?? null,
            bio: $data['bio'] ?? null,
            user: null, // Don't load user to prevent circular reference
            settings: $settings
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArrayWithoutSettings(array $data): self
    {
        // Create Profile without loading ProfileSettings to break circular reference
        $user = null;
        if (isset($data['user'])) {
            $user = CircularUser::fromArrayWithoutProfile($data['user']);
        }

        return new self(
            id: $data['id'],
            userId: $data['userId'],
            displayName: $data['displayName'] ?? null,
            bio: $data['bio'] ?? null,
            user: $user,
            settings: null // Don't load settings to prevent circular reference
        );
    }
}
