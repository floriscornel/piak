<?php

declare(strict_types=1);

readonly class SocialLink
{
    public function __construct(
        public string $platform,
        public string $url,
        public bool $verified = false
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            platform: $data['platform'],
            url: $data['url'],
            verified: $data['verified'] ?? false
        );
    }
}
