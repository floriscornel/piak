<?php

declare(strict_types=1);

readonly class Team
{
    public function __construct(
        public string $id,
        public string $name,
        /** @var TeamMember[] */
        public array $members,
        public ?string $description = null,
        /** @var string[] */
        public array $permissions = [],
        /** @var Project[] */
        public array $projects = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $members = [];
        foreach ($data['members'] as $memberData) {
            $members[] = TeamMember::fromArray($memberData);
        }

        $projects = [];
        if (isset($data['projects'])) {
            foreach ($data['projects'] as $projectData) {
                $projects[] = Project::fromArray($projectData);
            }
        }

        return new self(
            id: $data['id'],
            name: $data['name'],
            members: $members,
            description: $data['description'] ?? null,
            permissions: $data['permissions'] ?? [],
            projects: $projects
        );
    }
}
