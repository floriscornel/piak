<?php

declare(strict_types=1);

readonly class User
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $email = null,
        public ?string $bio = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: is_string($data['id']) ? $data['id'] : (string) $data['id'],
            name: is_string($data['name']) ? $data['name'] : (string) $data['name'],
            email: isset($data['email']) ? (is_string($data['email']) ? $data['email'] : (string) $data['email']) : null,
            bio: isset($data['bio']) ? (is_string($data['bio']) ? $data['bio'] : (string) $data['bio']) : null
        );
    }
}
