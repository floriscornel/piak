<?php

declare(strict_types=1);

readonly class Skill
{
    public function __construct(
        public ?string $name = null,
        public ?string $level = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $level = null;
        if (isset($data['level']) && is_scalar($data['level'])) {
            $levelValue = (string) $data['level'];
            // Validate enum values
            if (in_array($levelValue, ['beginner', 'intermediate', 'advanced', 'expert'], true)) {
                $level = $levelValue;
            }
        }

        return new self(
            name: isset($data['name']) && is_scalar($data['name']) ? (string) $data['name'] : null,
            level: $level
        );
    }
}
