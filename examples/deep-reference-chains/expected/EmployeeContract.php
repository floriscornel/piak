<?php

declare(strict_types=1);

readonly class EmployeeContract
{
    /**
     * @param  string[]  $benefits
     */
    public function __construct(
        public ?string $type = null,
        public ?float $salary = null,
        public array $benefits = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $benefits = [];
        if (isset($data['benefits']) && is_array($data['benefits'])) {
            foreach ($data['benefits'] as $benefit) {
                if (is_string($benefit) || is_scalar($benefit)) {
                    $benefits[] = (string) $benefit;
                }
            }
        }

        return new self(
            type: isset($data['type']) && is_scalar($data['type']) ? (string) $data['type'] : null,
            salary: isset($data['salary']) && is_numeric($data['salary']) ? (float) $data['salary'] : null,
            benefits: $benefits
        );
    }
}
