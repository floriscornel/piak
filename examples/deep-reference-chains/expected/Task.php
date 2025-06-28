<?php

declare(strict_types=1);

readonly class Task
{
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?float $estimatedHours = null,
        public ?string $priority = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $priority = null;
        if (isset($data['priority']) && is_scalar($data['priority'])) {
            $priorityValue = (string) $data['priority'];
            // Validate enum values
            if (in_array($priorityValue, ['low', 'medium', 'high', 'urgent'], true)) {
                $priority = $priorityValue;
            }
        }

        return new self(
            id: isset($data['id']) && is_scalar($data['id']) ? (string) $data['id'] : null,
            title: isset($data['title']) && is_scalar($data['title']) ? (string) $data['title'] : null,
            estimatedHours: isset($data['estimatedHours']) && is_numeric($data['estimatedHours']) ? (float) $data['estimatedHours'] : null,
            priority: $priority
        );
    }
}
