<?php

declare(strict_types=1);

readonly class SchedulePreferences
{
    public function __construct(
        public ?string $startTime = null,
        public ?string $endTime = null,
        public ?bool $flexibleHours = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            startTime: isset($data['startTime']) && is_scalar($data['startTime']) ? (string) $data['startTime'] : null,
            endTime: isset($data['endTime']) && is_scalar($data['endTime']) ? (string) $data['endTime'] : null,
            flexibleHours: isset($data['flexibleHours']) && is_bool($data['flexibleHours']) ? $data['flexibleHours'] : null
        );
    }
}
