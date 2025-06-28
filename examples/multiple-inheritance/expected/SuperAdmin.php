<?php

declare(strict_types=1);

readonly class SuperAdmin
{
    public function __construct(
        // From User schema (via AdminUser)
        public string $id,
        public string $username,
        public string $email,
        public ?string $createdAt,

        // From Admin schema (via AdminUser)
        /** @var string[] */
        public array $permissions,
        public string $department,
        public ?int $accessLevel,

        // From Auditable schema (via AdminUser)
        public ?string $lastModifiedBy,
        public ?string $lastModifiedAt,
        public ?int $version,

        // From AdminUser specific properties
        public ?string $adminSince,

        // From Trackable schema
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $sessionId,

        // From SuperAdmin specific properties
        public string $securityClearance,
        public ?string $emergencyContact = null
    ) {}

    /**
     * Factory method to create from array data
     * Nested allOf: AdminUser (User + Admin + Auditable) + Trackable + SuperAdmin
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
            // From User schema (via AdminUser)
            id: is_string($data['id']) ? $data['id'] : (string) $data['id'],
            username: is_string($data['username']) ? $data['username'] : (string) $data['username'],
            email: is_string($data['email']) ? $data['email'] : (string) $data['email'],
            createdAt: isset($data['createdAt']) ? (is_string($data['createdAt']) ? $data['createdAt'] : (string) $data['createdAt']) : null,

            // From Admin schema (via AdminUser)
            permissions: $permissions,
            department: is_string($data['department']) ? $data['department'] : (string) $data['department'],
            accessLevel: isset($data['accessLevel']) ? (is_int($data['accessLevel']) ? $data['accessLevel'] : (int) $data['accessLevel']) : null,

            // From Auditable schema (via AdminUser)
            lastModifiedBy: isset($data['lastModifiedBy']) ? (is_string($data['lastModifiedBy']) ? $data['lastModifiedBy'] : (string) $data['lastModifiedBy']) : null,
            lastModifiedAt: isset($data['lastModifiedAt']) ? (is_string($data['lastModifiedAt']) ? $data['lastModifiedAt'] : (string) $data['lastModifiedAt']) : null,
            version: isset($data['version']) ? (is_int($data['version']) ? $data['version'] : (int) $data['version']) : null,

            // From AdminUser specific properties
            adminSince: isset($data['adminSince']) ? (is_string($data['adminSince']) ? $data['adminSince'] : (string) $data['adminSince']) : null,

            // From Trackable schema
            ipAddress: isset($data['ipAddress']) ? (is_string($data['ipAddress']) ? $data['ipAddress'] : (string) $data['ipAddress']) : null,
            userAgent: isset($data['userAgent']) ? (is_string($data['userAgent']) ? $data['userAgent'] : (string) $data['userAgent']) : null,
            sessionId: isset($data['sessionId']) ? (is_string($data['sessionId']) ? $data['sessionId'] : (string) $data['sessionId']) : null,

            // From SuperAdmin specific properties
            securityClearance: is_string($data['securityClearance']) ? $data['securityClearance'] : (string) $data['securityClearance'],
            emergencyContact: isset($data['emergencyContact']) ? (is_string($data['emergencyContact']) ? $data['emergencyContact'] : (string) $data['emergencyContact']) : null
        );
    }
}
