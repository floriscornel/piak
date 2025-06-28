<?php

declare(strict_types=1);

readonly class AdminUser
{
    public function __construct(
        // From User schema
        public string $id,
        public string $username,
        public string $email,
        public ?string $createdAt,

        // From Admin schema
        /** @var string[] */
        public array $permissions,
        public string $department,
        public ?int $accessLevel = null,

        // From Auditable schema
        public ?string $lastModifiedBy = null,
        public ?string $lastModifiedAt = null,
        public ?int $version = null,

        // From AdminUser specific properties
        public ?string $adminSince = null
    ) {}

    /**
     * Factory method to create from array data
     * Multiple inheritance via allOf: User + Admin + Auditable + AdminUser
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Handle permissions array from Admin schema
        $permissions = [];
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            foreach ($data['permissions'] as $permission) {
                $permissions[] = is_string($permission) ? $permission : (string) $permission;
            }
        }

        return new self(
            // From User schema
            id: is_string($data['id']) ? $data['id'] : (string) $data['id'],
            username: is_string($data['username']) ? $data['username'] : (string) $data['username'],
            email: is_string($data['email']) ? $data['email'] : (string) $data['email'],
            createdAt: isset($data['createdAt']) ? (is_string($data['createdAt']) ? $data['createdAt'] : (string) $data['createdAt']) : null,

            // From Admin schema
            permissions: $permissions,
            department: is_string($data['department']) ? $data['department'] : (string) $data['department'],
            accessLevel: isset($data['accessLevel']) ? (is_int($data['accessLevel']) ? $data['accessLevel'] : (int) $data['accessLevel']) : null,

            // From Auditable schema
            lastModifiedBy: isset($data['lastModifiedBy']) ? (is_string($data['lastModifiedBy']) ? $data['lastModifiedBy'] : (string) $data['lastModifiedBy']) : null,
            lastModifiedAt: isset($data['lastModifiedAt']) ? (is_string($data['lastModifiedAt']) ? $data['lastModifiedAt'] : (string) $data['lastModifiedAt']) : null,
            version: isset($data['version']) ? (is_int($data['version']) ? $data['version'] : (int) $data['version']) : null,

            // From AdminUser specific properties
            adminSince: isset($data['adminSince']) ? (is_string($data['adminSince']) ? $data['adminSince'] : (string) $data['adminSince']) : null
        );
    }
}
