<?php

declare(strict_types=1);

readonly class UserPreferences
{
    public function __construct(
        public string $userId,
        public string $theme,
        public ?DynamicSettings $settings = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['userId'],
            theme: $data['theme'],
            settings: isset($data['settings']) ? DynamicSettings::fromArray($data['settings']) : null
        );
    }
}
