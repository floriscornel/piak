<?php

declare(strict_types=1);

readonly class TeamRole
{
    /**
     * @param  string[]  $responsibilities
     */
    public function __construct(
        public string $name,
        public int $level,
        public array $responsibilities = [],
        public ?RolePermissions $permissions = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['name']) || (! is_string($data['name']) && ! is_scalar($data['name']))) {
            throw new \InvalidArgumentException('name must be a string');
        }
        if (! isset($data['level']) || ! is_numeric($data['level'])) {
            throw new \InvalidArgumentException('level must be a number');
        }

        $responsibilities = [];
        if (isset($data['responsibilities']) && is_array($data['responsibilities'])) {
            foreach ($data['responsibilities'] as $responsibility) {
                if (is_string($responsibility) || is_scalar($responsibility)) {
                    $responsibilities[] = (string) $responsibility;
                }
            }
        }

        return new self(
            name: is_string($data['name']) ? $data['name'] : (string) $data['name'],
            level: (int) $data['level'],
            responsibilities: $responsibilities,
            permissions: isset($data['permissions']) && is_array($data['permissions']) ? RolePermissions::fromArray($data['permissions']) : null
        );
    }
}
