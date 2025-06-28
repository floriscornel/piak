<?php

declare(strict_types=1);

readonly class TeamMember
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public string $teamId,
        public TeamRole $role,
        public ?Employee $employee = null,
        public ?TeamPermissions $permissions = null,
        public ?WorkloadAssignment $workload = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['id']) || (! is_string($data['id']) && ! is_scalar($data['id']))) {
            throw new \InvalidArgumentException('id must be a string');
        }
        if (! isset($data['employeeId']) || (! is_string($data['employeeId']) && ! is_scalar($data['employeeId']))) {
            throw new \InvalidArgumentException('employeeId must be a string');
        }
        if (! isset($data['teamId']) || (! is_string($data['teamId']) && ! is_scalar($data['teamId']))) {
            throw new \InvalidArgumentException('teamId must be a string');
        }
        if (! isset($data['role']) || ! is_array($data['role'])) {
            throw new \InvalidArgumentException('role must be an array');
        }

        return new self(
            id: is_string($data['id']) ? $data['id'] : (string) $data['id'],
            employeeId: is_string($data['employeeId']) ? $data['employeeId'] : (string) $data['employeeId'],
            teamId: is_string($data['teamId']) ? $data['teamId'] : (string) $data['teamId'],
            role: TeamRole::fromArray($data['role']),
            employee: isset($data['employee']) && is_array($data['employee']) ? Employee::fromArray($data['employee']) : null,
            permissions: isset($data['permissions']) && is_array($data['permissions']) ? TeamPermissions::fromArray($data['permissions']) : null,
            workload: isset($data['workload']) && is_array($data['workload']) ? WorkloadAssignment::fromArray($data['workload']) : null
        );
    }
}
