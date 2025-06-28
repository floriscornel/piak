<?php

declare(strict_types=1);

readonly class Admin
{
    public function __construct(
        /** @var string[] */
        public array $permissions,
        public string $department,
        public ?int $accessLevel = null
    ) {}

    /**
     * Factory method to create from array data
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Handle permissions array
        $permissions = [];
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            foreach ($data['permissions'] as $permission) {
                $permissions[] = is_string($permission) ? $permission : (string) $permission;
            }
        }

        return new self(
            permissions: $permissions,
            department: is_string($data['department']) ? $data['department'] : (string) $data['department'],
            accessLevel: isset($data['accessLevel']) ? (is_int($data['accessLevel']) ? $data['accessLevel'] : (int) $data['accessLevel']) : null
        );
    }
}
