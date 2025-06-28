<?php

declare(strict_types=1);

readonly class BaseUser
{
    public function __construct(
        public string $id,
        public string $username,
        public string $email,
        public ?string $createdAt = null
    ) {}

    /**
     * Factory method to create from array data
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: is_string($data['id']) ? $data['id'] : (string) $data['id'],
            username: is_string($data['username']) ? $data['username'] : (string) $data['username'],
            email: is_string($data['email']) ? $data['email'] : (string) $data['email'],
            createdAt: isset($data['createdAt']) ? (is_string($data['createdAt']) ? $data['createdAt'] : (string) $data['createdAt']) : null
        );
    }
}
