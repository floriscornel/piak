<?php

declare(strict_types=1);

readonly class Profile
{
    public function __construct(
        public ?string $displayName = null,
        public ?string $bio = null,
        public ?string $location = null,
        public ?string $website = null,
        /** @var SocialLink[] */
        public array $socialLinks = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $socialLinks = [];
        if (isset($data['socialLinks'])) {
            foreach ($data['socialLinks'] as $linkData) {
                $socialLinks[] = SocialLink::fromArray($linkData);
            }
        }

        return new self(
            displayName: $data['displayName'] ?? null,
            bio: $data['bio'] ?? null,
            location: $data['location'] ?? null,
            website: $data['website'] ?? null,
            socialLinks: $socialLinks
        );
    }
}
