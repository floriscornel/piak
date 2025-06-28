<?php

declare(strict_types=1);

readonly class OrganizationSettings
{
    public function __construct(
        public ?string $visibility = null,
        /** @var string[] */
        public array $allowedEmailDomains = [],
        /** @var string[] */
        public array $features = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            visibility: $data['visibility'] ?? null,
            allowedEmailDomains: $data['allowedEmailDomains'] ?? [],
            features: $data['features'] ?? []
        );
    }
}
