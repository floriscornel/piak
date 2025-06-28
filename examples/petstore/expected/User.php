<?php

declare(strict_types=1);

readonly class User
{
    public function __construct(
        public ?int $id = null,
        public ?string $username = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $phone = null,
        public ?int $userStatus = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) && (is_int($data['id']) || is_numeric($data['id'])) ? (int) $data['id'] : null,
            username: isset($data['username']) && is_string($data['username']) ? $data['username'] : null,
            firstName: isset($data['firstName']) && is_string($data['firstName']) ? $data['firstName'] : null,
            lastName: isset($data['lastName']) && is_string($data['lastName']) ? $data['lastName'] : null,
            email: isset($data['email']) && is_string($data['email']) ? $data['email'] : null,
            password: isset($data['password']) && is_string($data['password']) ? $data['password'] : null,
            phone: isset($data['phone']) && is_string($data['phone']) ? $data['phone'] : null,
            userStatus: isset($data['userStatus']) && (is_int($data['userStatus']) || is_numeric($data['userStatus'])) ? (int) $data['userStatus'] : null
        );
    }
}
