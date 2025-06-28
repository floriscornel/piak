<?php

declare(strict_types=1);

readonly class Employee
{
    /**
     * @param  Skill[]  $skills
     * @param  Certification[]  $certifications
     */
    public function __construct(
        public string $id,
        public string $email,
        public EmployeeProfile $profile,
        public ?EmployeeContract $contract = null,
        public array $skills = [],
        public array $certifications = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['id']) || (! is_string($data['id']) && ! is_scalar($data['id']))) {
            throw new \InvalidArgumentException('id must be a string');
        }
        if (! isset($data['email']) || (! is_string($data['email']) && ! is_scalar($data['email']))) {
            throw new \InvalidArgumentException('email must be a string');
        }
        if (! isset($data['profile']) || ! is_array($data['profile'])) {
            throw new \InvalidArgumentException('profile must be an array');
        }

        $skills = [];
        if (isset($data['skills']) && is_array($data['skills'])) {
            foreach ($data['skills'] as $skill) {
                if (is_array($skill)) {
                    $skills[] = Skill::fromArray($skill);
                }
            }
        }

        $certifications = [];
        if (isset($data['certifications']) && is_array($data['certifications'])) {
            foreach ($data['certifications'] as $cert) {
                if (is_array($cert)) {
                    $certifications[] = Certification::fromArray($cert);
                }
            }
        }

        return new self(
            id: is_string($data['id']) ? $data['id'] : (string) $data['id'],
            email: is_string($data['email']) ? $data['email'] : (string) $data['email'],
            profile: EmployeeProfile::fromArray($data['profile']),
            contract: isset($data['contract']) && is_array($data['contract']) ? EmployeeContract::fromArray($data['contract']) : null,
            skills: $skills,
            certifications: $certifications
        );
    }
}
