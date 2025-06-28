<?php

declare(strict_types=1);

readonly class EmployeeProfile
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public ?PersonalInfo $personalInfo = null,
        public ?WorkInfo $workInfo = null,
        public ?EmployeePreferences $preferences = null,
        public ?EmergencyContact $emergency = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['firstName']) || (! is_string($data['firstName']) && ! is_scalar($data['firstName']))) {
            throw new \InvalidArgumentException('firstName must be a string');
        }
        if (! isset($data['lastName']) || (! is_string($data['lastName']) && ! is_scalar($data['lastName']))) {
            throw new \InvalidArgumentException('lastName must be a string');
        }

        return new self(
            firstName: is_string($data['firstName']) ? $data['firstName'] : (string) $data['firstName'],
            lastName: is_string($data['lastName']) ? $data['lastName'] : (string) $data['lastName'],
            personalInfo: isset($data['personalInfo']) && is_array($data['personalInfo']) ? PersonalInfo::fromArray($data['personalInfo']) : null,
            workInfo: isset($data['workInfo']) && is_array($data['workInfo']) ? WorkInfo::fromArray($data['workInfo']) : null,
            preferences: isset($data['preferences']) && is_array($data['preferences']) ? EmployeePreferences::fromArray($data['preferences']) : null,
            emergency: isset($data['emergency']) && is_array($data['emergency']) ? EmergencyContact::fromArray($data['emergency']) : null
        );
    }
}
