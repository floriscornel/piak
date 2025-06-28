<?php

declare(strict_types=1);

readonly class Member
{
    public function __construct(
        public string $id,
        public string $username,
        public string $role,
        public ?string $email = null,
        public ?Profile $profile = null,
        /** @var string[] Team IDs to avoid circular references */
        public array $teams = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            username: $data['username'],
            role: $data['role'],
            email: $data['email'] ?? null,
            profile: isset($data['profile']) ? Profile::fromArray($data['profile']) : null,
            teams: $data['teams'] ?? []
        );
    }
}
