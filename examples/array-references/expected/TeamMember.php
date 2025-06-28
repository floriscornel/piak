<?php

declare(strict_types=1);

readonly class TeamMember
{
    public function __construct(
        public string $memberId,
        public string $role,
        public ?string $joinedAt = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            memberId: $data['memberId'],
            role: $data['role'],
            joinedAt: $data['joinedAt'] ?? null
        );
    }
}
