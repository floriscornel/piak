<?php

declare(strict_types=1);

readonly class RolePermissions
{
    public function __construct(
        public ?bool $read = null,
        public ?bool $write = null,
        public ?bool $admin = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            read: isset($data['read']) && is_bool($data['read']) ? $data['read'] : null,
            write: isset($data['write']) && is_bool($data['write']) ? $data['write'] : null,
            admin: isset($data['admin']) && is_bool($data['admin']) ? $data['admin'] : null
        );
    }
}
