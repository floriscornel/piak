<?php

declare(strict_types=1);

readonly class UserProfileSettings
{
    public function __construct(
        public ?string $theme = null,
        public ?string $privacy = null,
        public ?UserProfile $profile = null  // Keep object reference as per OpenAPI spec
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Load Profile but prevent circular reference by not loading ProfileSettings back
        $profile = null;
        if (isset($data['profile'])) {
            $profile = UserProfile::fromArrayWithoutSettings($data['profile']);
        }

        return new self(
            theme: $data['theme'] ?? null,
            privacy: $data['privacy'] ?? null,
            profile: $profile
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArrayWithoutProfile(array $data): self
    {
        // Create ProfileSettings without loading Profile to break circular reference
        return new self(
            theme: $data['theme'] ?? null,
            privacy: $data['privacy'] ?? null,
            profile: null // Don't load profile to prevent circular reference
        );
    }
}
