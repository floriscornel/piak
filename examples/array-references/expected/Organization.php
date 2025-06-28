<?php

declare(strict_types=1);

readonly class Organization
{
    public function __construct(
        public string $id,
        public string $name,
        /** @var Team[] */
        public array $teams,
        public ?string $description = null,
        /** @var Member[] */
        public array $members = [],
        public ?OrganizationSettings $settings = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $teams = [];
        foreach ($data['teams'] as $teamData) {
            $teams[] = Team::fromArray($teamData);
        }

        $members = [];
        if (isset($data['members'])) {
            foreach ($data['members'] as $memberData) {
                $members[] = Member::fromArray($memberData);
            }
        }

        return new self(
            id: $data['id'],
            name: $data['name'],
            teams: $teams,
            description: $data['description'] ?? null,
            members: $members,
            settings: isset($data['settings']) ? OrganizationSettings::fromArray($data['settings']) : null
        );
    }
}
