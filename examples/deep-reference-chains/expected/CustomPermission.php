<?php

declare(strict_types=1);

readonly class CustomPermission
{
    public function __construct(
        public ?string $name = null,
        public ?bool $granted = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: isset($data['name']) && is_scalar($data['name']) ? (string) $data['name'] : null,
            granted: isset($data['granted']) && is_bool($data['granted']) ? $data['granted'] : null
        );
    }
}
