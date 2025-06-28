<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once 'expected/BaseUser.php';
require_once 'expected/Admin.php';
require_once 'expected/Auditable.php';
require_once 'expected/Trackable.php';
require_once 'expected/AdminUser.php';
require_once 'expected/SuperAdmin.php';
require_once 'expected/EmployeeUser.php';

class MultipleInheritanceTest extends TestCase
{
    // Base schema tests
    public function test_user_from_array(): void
    {
        $data = [
            'id' => 'user-123',
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'createdAt' => '2023-01-01T10:00:00Z',
        ];

        $user = BaseUser::fromArray($data);

        $this->assertSame('user-123', $user->id);
        $this->assertSame('john_doe', $user->username);
        $this->assertSame('john@example.com', $user->email);
        $this->assertSame('2023-01-01T10:00:00Z', $user->createdAt);
    }

    public function test_user_from_array_optional_fields(): void
    {
        $data = [
            'id' => 'user-456',
            'username' => 'jane_doe',
            'email' => 'jane@example.com',
        ];

        $user = BaseUser::fromArray($data);

        $this->assertSame('user-456', $user->id);
        $this->assertSame('jane_doe', $user->username);
        $this->assertSame('jane@example.com', $user->email);
        $this->assertNull($user->createdAt);
    }

    public function test_admin_from_array(): void
    {
        $data = [
            'permissions' => ['read', 'write', 'admin'],
            'department' => 'IT',
            'accessLevel' => 8,
        ];

        $admin = Admin::fromArray($data);

        $this->assertSame(['read', 'write', 'admin'], $admin->permissions);
        $this->assertSame('IT', $admin->department);
        $this->assertSame(8, $admin->accessLevel);
    }

    public function test_admin_from_array_empty_permissions(): void
    {
        $data = [
            'permissions' => [],
            'department' => 'HR',
        ];

        $admin = Admin::fromArray($data);

        $this->assertSame([], $admin->permissions);
        $this->assertSame('HR', $admin->department);
        $this->assertNull($admin->accessLevel);
    }

    public function test_auditable_from_array(): void
    {
        $data = [
            'lastModifiedBy' => 'admin-123',
            'lastModifiedAt' => '2023-01-01T15:30:00Z',
            'version' => 3,
        ];

        $auditable = Auditable::fromArray($data);

        $this->assertSame('admin-123', $auditable->lastModifiedBy);
        $this->assertSame('2023-01-01T15:30:00Z', $auditable->lastModifiedAt);
        $this->assertSame(3, $auditable->version);
    }

    public function test_trackable_from_array(): void
    {
        $data = [
            'ipAddress' => '192.168.1.100',
            'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'sessionId' => 'sess-abc123',
        ];

        $trackable = Trackable::fromArray($data);

        $this->assertSame('192.168.1.100', $trackable->ipAddress);
        $this->assertSame('Mozilla/5.0 (Windows NT 10.0; Win64; x64)', $trackable->userAgent);
        $this->assertSame('sess-abc123', $trackable->sessionId);
    }

    // Multiple inheritance tests
    public function test_admin_user_from_array(): void
    {
        $data = [
            // User properties
            'id' => 'admin-123',
            'username' => 'admin_user',
            'email' => 'admin@company.com',
            'createdAt' => '2023-01-01T08:00:00Z',

            // Admin properties
            'permissions' => ['read', 'write', 'delete', 'admin'],
            'department' => 'IT Security',
            'accessLevel' => 9,

            // Auditable properties
            'lastModifiedBy' => 'super-admin',
            'lastModifiedAt' => '2023-06-15T14:20:00Z',
            'version' => 5,

            // AdminUser specific properties
            'adminSince' => '2022-03-15',
        ];

        $adminUser = AdminUser::fromArray($data);

        // User properties
        $this->assertSame('admin-123', $adminUser->id);
        $this->assertSame('admin_user', $adminUser->username);
        $this->assertSame('admin@company.com', $adminUser->email);
        $this->assertSame('2023-01-01T08:00:00Z', $adminUser->createdAt);

        // Admin properties
        $this->assertSame(['read', 'write', 'delete', 'admin'], $adminUser->permissions);
        $this->assertSame('IT Security', $adminUser->department);
        $this->assertSame(9, $adminUser->accessLevel);

        // Auditable properties
        $this->assertSame('super-admin', $adminUser->lastModifiedBy);
        $this->assertSame('2023-06-15T14:20:00Z', $adminUser->lastModifiedAt);
        $this->assertSame(5, $adminUser->version);

        // AdminUser specific properties
        $this->assertSame('2022-03-15', $adminUser->adminSince);
    }

    public function test_super_admin_from_array(): void
    {
        $data = [
            // User properties (via AdminUser)
            'id' => 'super-123',
            'username' => 'super_admin',
            'email' => 'super@company.com',
            'createdAt' => '2020-01-01T00:00:00Z',

            // Admin properties (via AdminUser)
            'permissions' => ['*'],
            'department' => 'Executive',
            'accessLevel' => 10,

            // Auditable properties (via AdminUser)
            'lastModifiedBy' => 'system',
            'lastModifiedAt' => '2023-12-01T12:00:00Z',
            'version' => 15,

            // AdminUser properties
            'adminSince' => '2020-01-01',

            // Trackable properties
            'ipAddress' => '10.0.0.1',
            'userAgent' => 'Internal-System/1.0',
            'sessionId' => 'super-session-xyz',

            // SuperAdmin specific properties
            'securityClearance' => 'top-secret',
            'emergencyContact' => '+1-555-0911',
        ];

        $superAdmin = SuperAdmin::fromArray($data);

        // User properties
        $this->assertSame('super-123', $superAdmin->id);
        $this->assertSame('super_admin', $superAdmin->username);
        $this->assertSame('super@company.com', $superAdmin->email);
        $this->assertSame('2020-01-01T00:00:00Z', $superAdmin->createdAt);

        // Admin properties
        $this->assertSame(['*'], $superAdmin->permissions);
        $this->assertSame('Executive', $superAdmin->department);
        $this->assertSame(10, $superAdmin->accessLevel);

        // Auditable properties
        $this->assertSame('system', $superAdmin->lastModifiedBy);
        $this->assertSame('2023-12-01T12:00:00Z', $superAdmin->lastModifiedAt);
        $this->assertSame(15, $superAdmin->version);

        // AdminUser properties
        $this->assertSame('2020-01-01', $superAdmin->adminSince);

        // Trackable properties
        $this->assertSame('10.0.0.1', $superAdmin->ipAddress);
        $this->assertSame('Internal-System/1.0', $superAdmin->userAgent);
        $this->assertSame('super-session-xyz', $superAdmin->sessionId);

        // SuperAdmin specific properties
        $this->assertSame('top-secret', $superAdmin->securityClearance);
        $this->assertSame('+1-555-0911', $superAdmin->emergencyContact);
    }

    public function test_employee_user_from_array_with_property_conflict(): void
    {
        $data = [
            // User properties (id renamed to userId)
            'userId' => 'user-789',
            'username' => 'employee_one',
            'email' => 'employee@company.com',
            'createdAt' => '2023-05-15T09:00:00Z',

            // EmployeeUser properties (id is Employee ID)
            'id' => 'EMP-001',
            'employeeNumber' => 'E2023001',
            'department' => 'Engineering',
        ];

        $employeeUser = EmployeeUser::fromArray($data);

        // User properties (id field renamed)
        $this->assertSame('user-789', $employeeUser->userId);
        $this->assertSame('employee_one', $employeeUser->username);
        $this->assertSame('employee@company.com', $employeeUser->email);
        $this->assertSame('2023-05-15T09:00:00Z', $employeeUser->createdAt);

        // EmployeeUser properties (id takes precedence)
        $this->assertSame('EMP-001', $employeeUser->id);
        $this->assertSame('E2023001', $employeeUser->employeeNumber);
        $this->assertSame('Engineering', $employeeUser->department);
    }

    // Edge case tests
    public function test_admin_user_with_missing_optional_fields(): void
    {
        $data = [
            // Required User properties
            'id' => 'admin-minimal',
            'username' => 'minimal_admin',
            'email' => 'minimal@company.com',

            // Required Admin properties
            'permissions' => ['read'],
            'department' => 'Support',
        ];

        $adminUser = AdminUser::fromArray($data);

        // Required fields populated
        $this->assertSame('admin-minimal', $adminUser->id);
        $this->assertSame('minimal_admin', $adminUser->username);
        $this->assertSame('minimal@company.com', $adminUser->email);
        $this->assertSame(['read'], $adminUser->permissions);
        $this->assertSame('Support', $adminUser->department);

        // Optional fields are null
        $this->assertNull($adminUser->createdAt);
        $this->assertNull($adminUser->accessLevel);
        $this->assertNull($adminUser->lastModifiedBy);
        $this->assertNull($adminUser->lastModifiedAt);
        $this->assertNull($adminUser->version);
        $this->assertNull($adminUser->adminSince);
    }

    public function test_super_admin_with_required_security_clearance(): void
    {
        $data = [
            // Minimal required fields from all schemas
            'id' => 'super-minimal',
            'username' => 'minimal_super',
            'email' => 'minimal@secure.com',
            'permissions' => ['admin'],
            'department' => 'Security',
            'securityClearance' => 'confidential',  // Required for SuperAdmin
        ];

        $superAdmin = SuperAdmin::fromArray($data);

        $this->assertSame('super-minimal', $superAdmin->id);
        $this->assertSame('minimal_super', $superAdmin->username);
        $this->assertSame('minimal@secure.com', $superAdmin->email);
        $this->assertSame(['admin'], $superAdmin->permissions);
        $this->assertSame('Security', $superAdmin->department);
        $this->assertSame('confidential', $superAdmin->securityClearance);

        // Optional SuperAdmin field
        $this->assertNull($superAdmin->emergencyContact);
    }

    public function test_type_conversion_in_all_of(): void
    {
        $data = [
            'id' => 123,  // number instead of string
            'username' => 'type_test',
            'email' => 'test@example.com',
            'permissions' => ['read'],
            'department' => 'Testing',
            'accessLevel' => '7',  // string instead of int
            'version' => '3',  // string instead of int
        ];

        $adminUser = AdminUser::fromArray($data);

        // Type conversion should work
        $this->assertSame('123', $adminUser->id);
        $this->assertSame(7, $adminUser->accessLevel);
        $this->assertSame(3, $adminUser->version);
    }

    public function test_nested_all_of_inheritance(): void
    {
        // Test that SuperAdmin properly inherits ALL properties from AdminUser
        // which itself inherits from User + Admin + Auditable
        $data = [
            'id' => 'nested-test',
            'username' => 'nested_admin',
            'email' => 'nested@test.com',
            'permissions' => ['everything'],
            'department' => 'All',
            'securityClearance' => 'secret',
        ];

        $superAdmin = SuperAdmin::fromArray($data);

        // Properties should flow through the entire inheritance chain
        $this->assertSame('nested-test', $superAdmin->id);  // From User (via AdminUser)
        $this->assertSame(['everything'], $superAdmin->permissions);  // From Admin (via AdminUser)
        $this->assertSame('secret', $superAdmin->securityClearance);  // From SuperAdmin
    }
}
