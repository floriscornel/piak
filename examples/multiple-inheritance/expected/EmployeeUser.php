<?php

declare(strict_types=1);

readonly class EmployeeUser
{
    public function __construct(
        // From User schema
        public string $userId,        // Renamed to avoid conflict with Employee ID
        public string $username,
        public string $email,
        public ?string $createdAt,

        // From EmployeeUser schema (conflicts resolved)
        public string $id,            // Employee ID (takes precedence)
        public ?string $employeeNumber = null,
        public ?string $department = null
    ) {}

    /**
     * Factory method to create from array data
     * Property conflict: id field renamed to userId for User schema, id is Employee ID
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            // From User schema (id renamed to userId to avoid conflict)
            userId: is_string($data['userId']) ? $data['userId'] : (string) $data['userId'],
            username: is_string($data['username']) ? $data['username'] : (string) $data['username'],
            email: is_string($data['email']) ? $data['email'] : (string) $data['email'],
            createdAt: isset($data['createdAt']) ? (is_string($data['createdAt']) ? $data['createdAt'] : (string) $data['createdAt']) : null,

            // From EmployeeUser schema (id takes precedence as Employee ID)
            id: is_string($data['id']) ? $data['id'] : (string) $data['id'],
            employeeNumber: isset($data['employeeNumber']) ? (is_string($data['employeeNumber']) ? $data['employeeNumber'] : (string) $data['employeeNumber']) : null,
            department: isset($data['department']) ? (is_string($data['department']) ? $data['department'] : (string) $data['department']) : null
        );
    }
}
