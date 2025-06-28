<?php

declare(strict_types=1);

readonly class AuthorDetail
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $role = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) && is_scalar($data['id']) ? (string) $data['id'] : null,
            name: isset($data['name']) && is_scalar($data['name']) ? (string) $data['name'] : null,
            email: isset($data['email']) && is_scalar($data['email']) ? (string) $data['email'] : null,
            role: isset($data['role']) && is_scalar($data['role']) ? (string) $data['role'] : null
        );
    }
}
