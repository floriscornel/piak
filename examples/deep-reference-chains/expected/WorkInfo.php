<?php

declare(strict_types=1);

readonly class WorkInfo
{
    public function __construct(
        public ?string $startDate = null,
        public ?string $position = null,
        public ?string $level = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            startDate: isset($data['startDate']) && is_scalar($data['startDate']) ? (string) $data['startDate'] : null,
            position: isset($data['position']) && is_scalar($data['position']) ? (string) $data['position'] : null,
            level: isset($data['level']) && is_scalar($data['level']) ? (string) $data['level'] : null
        );
    }
}
