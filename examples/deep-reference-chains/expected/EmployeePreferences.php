<?php

declare(strict_types=1);

readonly class EmployeePreferences
{
    public function __construct(
        public ?CommunicationPreferences $communication = null,
        public ?WorkspacePreferences $workspace = null,
        public ?BenefitsSelection $benefits = null,
        public ?SchedulePreferences $schedule = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            communication: isset($data['communication']) ? CommunicationPreferences::fromArray($data['communication']) : null,
            workspace: isset($data['workspace']) ? WorkspacePreferences::fromArray($data['workspace']) : null,
            benefits: isset($data['benefits']) ? BenefitsSelection::fromArray($data['benefits']) : null,
            schedule: isset($data['schedule']) ? SchedulePreferences::fromArray($data['schedule']) : null
        );
    }
}
