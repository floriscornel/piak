<?php

declare(strict_types=1);

readonly class Project
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public ?string $description = null,
        /** @var string[] */
        public array $tags = [],
        /** @var string[] Member IDs */
        public array $assignees = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            status: $data['status'],
            description: $data['description'] ?? null,
            tags: $data['tags'] ?? [],
            assignees: $data['assignees'] ?? []
        );
    }
}
