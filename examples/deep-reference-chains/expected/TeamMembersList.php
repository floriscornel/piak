<?php

declare(strict_types=1);

readonly class TeamMembersList
{
    /**
     * @param  TeamMember[]  $members
     */
    public function __construct(
        public array $members = [],
        public ?object $pagination = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $members = [];
        if (isset($data['members']) && is_array($data['members'])) {
            foreach ($data['members'] as $member) {
                if (is_array($member)) {
                    $members[] = TeamMember::fromArray($member);
                }
            }
        }

        return new self(
            members: $members,
            pagination: isset($data['pagination']) ? (object) $data['pagination'] : null
        );
    }
}
