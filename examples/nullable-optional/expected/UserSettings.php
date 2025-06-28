<?php

declare(strict_types=1);

readonly class UserSettings
{
    public function __construct(
        public string $theme,              // Required
        public bool $notifications,       // Required
        public ?string $privacy = null,   // Optional
        public ?string $customCss = null, // Optional and nullable
        /** @var array<string, string>|null */
        public ?array $preferences = null // Nullable dynamic object
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            theme: $data['theme'],
            notifications: $data['notifications'],
            privacy: $data['privacy'] ?? null,
            customCss: $data['customCss'] ?? null,
            preferences: $data['preferences'] ?? null
        );
    }
}
