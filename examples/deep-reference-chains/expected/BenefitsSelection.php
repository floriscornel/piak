<?php

declare(strict_types=1);

readonly class BenefitsSelection
{
    public function __construct(
        public ?string $healthPlan = null,
        public ?string $retirement = null,
        public ?int $vacation = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            healthPlan: isset($data['healthPlan']) && is_scalar($data['healthPlan']) ? (string) $data['healthPlan'] : null,
            retirement: isset($data['retirement']) && is_scalar($data['retirement']) ? (string) $data['retirement'] : null,
            vacation: isset($data['vacation']) && is_numeric($data['vacation']) ? (int) $data['vacation'] : null
        );
    }
}
