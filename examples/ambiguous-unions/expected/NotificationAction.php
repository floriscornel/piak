<?php

declare(strict_types=1);

readonly class NotificationAction
{
    public function __construct(
        public ?string $label = null,
        public ?string $url = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'] ?? null,
            url: $data['url'] ?? null
        );
    }
}
