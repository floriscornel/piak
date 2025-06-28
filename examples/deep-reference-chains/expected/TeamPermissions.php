<?php

declare(strict_types=1);

readonly class TeamPermissions
{
    /**
     * @param  CustomPermission[]  $customPermissions
     */
    public function __construct(
        public ?bool $canEdit = null,
        public ?bool $canDelete = null,
        public ?bool $canManage = null,
        public array $customPermissions = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $customPermissions = [];
        if (isset($data['customPermissions']) && is_array($data['customPermissions'])) {
            foreach ($data['customPermissions'] as $permission) {
                if (is_array($permission)) {
                    $customPermissions[] = CustomPermission::fromArray($permission);
                }
            }
        }

        return new self(
            canEdit: isset($data['canEdit']) && is_bool($data['canEdit']) ? $data['canEdit'] : null,
            canDelete: isset($data['canDelete']) && is_bool($data['canDelete']) ? $data['canDelete'] : null,
            canManage: isset($data['canManage']) && is_bool($data['canManage']) ? $data['canManage'] : null,
            customPermissions: $customPermissions
        );
    }
}
