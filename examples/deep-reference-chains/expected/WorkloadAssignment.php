<?php

declare(strict_types=1);

readonly class WorkloadAssignment
{
    /**
     * @param  Task[]  $tasks
     */
    public function __construct(
        public ?float $hoursPerWeek = null,
        public ?float $allocation = null,
        public array $tasks = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $tasks = [];
        if (isset($data['tasks']) && is_array($data['tasks'])) {
            foreach ($data['tasks'] as $task) {
                if (is_array($task)) {
                    $tasks[] = Task::fromArray($task);
                }
            }
        }

        return new self(
            hoursPerWeek: isset($data['hoursPerWeek']) && is_numeric($data['hoursPerWeek']) ? (float) $data['hoursPerWeek'] : null,
            allocation: isset($data['allocation']) && is_numeric($data['allocation']) ? (float) $data['allocation'] : null,
            tasks: $tasks
        );
    }
}
