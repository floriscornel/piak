<?php

declare(strict_types=1);

require_once 'expected/Employee.php';
require_once 'expected/EmployeeProfile.php';
require_once 'expected/EmployeePreferences.php';
require_once 'expected/TeamMember.php';
require_once 'expected/TeamMembersList.php';
require_once 'expected/EmployeeContract.php';
require_once 'expected/Skill.php';
require_once 'expected/Certification.php';
require_once 'expected/PersonalInfo.php';
require_once 'expected/WorkInfo.php';
require_once 'expected/EmergencyContact.php';
require_once 'expected/CommunicationPreferences.php';
require_once 'expected/WorkspacePreferences.php';
require_once 'expected/BenefitsSelection.php';
require_once 'expected/SchedulePreferences.php';
require_once 'expected/TeamRole.php';
require_once 'expected/RolePermissions.php';
require_once 'expected/TeamPermissions.php';
require_once 'expected/CustomPermission.php';
require_once 'expected/WorkloadAssignment.php';
require_once 'expected/Task.php';

use PHPUnit\Framework\TestCase;

class DeepReferenceChainsTest extends TestCase
{
    public function test_employee_with_minimal_data(): void
    {
        $data = [
            'id' => 'emp-123',
            'email' => 'john.doe@example.com',
            'profile' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
        ];

        $employee = Employee::fromArray($data);

        $this->assertSame('emp-123', $employee->id);
        $this->assertSame('john.doe@example.com', $employee->email);
        $this->assertSame('John', $employee->profile->firstName);
        $this->assertSame('Doe', $employee->profile->lastName);
        $this->assertNull($employee->contract);
        $this->assertEmpty($employee->skills);
        $this->assertEmpty($employee->certifications);
    }

    public function test_employee_with_complete_data(): void
    {
        $data = [
            'id' => 'emp-456',
            'email' => 'jane.smith@example.com',
            'profile' => [
                'firstName' => 'Jane',
                'lastName' => 'Smith',
                'personalInfo' => [
                    'birthDate' => '1990-05-15',
                    'phone' => '+1-555-0123',
                    'address' => '123 Main St, City, State 12345',
                ],
                'workInfo' => [
                    'startDate' => '2020-01-15',
                    'position' => 'Senior Developer',
                    'level' => 'L5',
                ],
                'emergency' => [
                    'name' => 'John Smith',
                    'relationship' => 'Spouse',
                    'phone' => '+1-555-0124',
                ],
            ],
            'contract' => [
                'type' => 'fulltime',
                'salary' => 95000.50,
                'benefits' => ['health', 'dental', '401k'],
            ],
            'skills' => [
                ['name' => 'PHP', 'level' => 'expert'],
                ['name' => 'JavaScript', 'level' => 'advanced'],
                ['name' => 'Python', 'level' => 'intermediate'],
            ],
            'certifications' => [
                [
                    'name' => 'AWS Solutions Architect',
                    'issuer' => 'Amazon',
                    'validUntil' => '2025-12-31',
                ],
                [
                    'name' => 'Certified Scrum Master',
                    'issuer' => 'Scrum Alliance',
                    'validUntil' => '2024-06-30',
                ],
            ],
        ];

        $employee = Employee::fromArray($data);

        // Test employee basic info
        $this->assertSame('emp-456', $employee->id);
        $this->assertSame('jane.smith@example.com', $employee->email);

        // Test profile
        $this->assertSame('Jane', $employee->profile->firstName);
        $this->assertSame('Smith', $employee->profile->lastName);

        // Test personal info (deep level 3)
        $this->assertNotNull($employee->profile->personalInfo);
        $this->assertSame('1990-05-15', $employee->profile->personalInfo->birthDate);
        $this->assertSame('+1-555-0123', $employee->profile->personalInfo->phone);
        $this->assertSame('123 Main St, City, State 12345', $employee->profile->personalInfo->address);

        // Test work info (deep level 3)
        $this->assertNotNull($employee->profile->workInfo);
        $this->assertSame('2020-01-15', $employee->profile->workInfo->startDate);
        $this->assertSame('Senior Developer', $employee->profile->workInfo->position);
        $this->assertSame('L5', $employee->profile->workInfo->level);

        // Test emergency contact (deep level 3)
        $this->assertNotNull($employee->profile->emergency);
        $this->assertSame('John Smith', $employee->profile->emergency->name);
        $this->assertSame('Spouse', $employee->profile->emergency->relationship);
        $this->assertSame('+1-555-0124', $employee->profile->emergency->phone);

        // Test contract
        $this->assertNotNull($employee->contract);
        $this->assertSame('fulltime', $employee->contract->type);
        $this->assertSame(95000.50, $employee->contract->salary);
        $this->assertSame(['health', 'dental', '401k'], $employee->contract->benefits);

        // Test skills array
        $this->assertCount(3, $employee->skills);
        $this->assertSame('PHP', $employee->skills[0]->name);
        $this->assertSame('expert', $employee->skills[0]->level);
        $this->assertSame('JavaScript', $employee->skills[1]->name);
        $this->assertSame('advanced', $employee->skills[1]->level);

        // Test certifications array
        $this->assertCount(2, $employee->certifications);
        $this->assertSame('AWS Solutions Architect', $employee->certifications[0]->name);
        $this->assertSame('Amazon', $employee->certifications[0]->issuer);
        $this->assertSame('2025-12-31', $employee->certifications[0]->validUntil);
    }

    public function test_employee_preferences_deep_nesting(): void
    {
        $data = [
            'id' => 'emp-789',
            'email' => 'bob.wilson@example.com',
            'profile' => [
                'firstName' => 'Bob',
                'lastName' => 'Wilson',
                'preferences' => [
                    'communication' => [
                        'email' => true,
                        'slack' => true,
                        'sms' => false,
                    ],
                    'workspace' => [
                        'remoteWork' => true,
                        'deskType' => 'standing',
                        'equipment' => ['laptop', 'monitor', 'headphones'],
                    ],
                    'benefits' => [
                        'healthPlan' => 'premium',
                        'retirement' => '401k',
                        'vacation' => 25,
                    ],
                    'schedule' => [
                        'startTime' => '09:00',
                        'endTime' => '17:00',
                        'flexibleHours' => true,
                    ],
                ],
            ],
        ];

        $employee = Employee::fromArray($data);
        $prefs = $employee->profile->preferences;

        $this->assertNotNull($prefs);

        // Test communication preferences (level 4 deep)
        $this->assertNotNull($prefs->communication);
        $this->assertTrue($prefs->communication->email);
        $this->assertTrue($prefs->communication->slack);
        $this->assertFalse($prefs->communication->sms);

        // Test workspace preferences (level 4 deep)
        $this->assertNotNull($prefs->workspace);
        $this->assertTrue($prefs->workspace->remoteWork);
        $this->assertSame('standing', $prefs->workspace->deskType);
        $this->assertSame(['laptop', 'monitor', 'headphones'], $prefs->workspace->equipment);

        // Test benefits selection (level 4 deep)
        $this->assertNotNull($prefs->benefits);
        $this->assertSame('premium', $prefs->benefits->healthPlan);
        $this->assertSame('401k', $prefs->benefits->retirement);
        $this->assertSame(25, $prefs->benefits->vacation);

        // Test schedule preferences (level 4 deep)
        $this->assertNotNull($prefs->schedule);
        $this->assertSame('09:00', $prefs->schedule->startTime);
        $this->assertSame('17:00', $prefs->schedule->endTime);
        $this->assertTrue($prefs->schedule->flexibleHours);
    }

    public function test_team_member_with_complex_structure(): void
    {
        $data = [
            'id' => 'tm-001',
            'employeeId' => 'emp-456',
            'teamId' => 'team-dev',
            'role' => [
                'name' => 'Tech Lead',
                'level' => 7,
                'responsibilities' => ['code review', 'mentoring', 'architecture'],
                'permissions' => [
                    'read' => true,
                    'write' => true,
                    'admin' => false,
                ],
            ],
            'permissions' => [
                'canEdit' => true,
                'canDelete' => false,
                'canManage' => true,
                'customPermissions' => [
                    ['name' => 'deploy', 'granted' => true],
                    ['name' => 'review', 'granted' => true],
                ],
            ],
            'workload' => [
                'hoursPerWeek' => 40.0,
                'allocation' => 0.8,
                'tasks' => [
                    [
                        'id' => 'task-001',
                        'title' => 'Implement authentication',
                        'estimatedHours' => 16.0,
                        'priority' => 'high',
                    ],
                    [
                        'id' => 'task-002',
                        'title' => 'Code review sprint',
                        'estimatedHours' => 8.0,
                        'priority' => 'medium',
                    ],
                ],
            ],
        ];

        $teamMember = TeamMember::fromArray($data);

        // Test basic team member info
        $this->assertSame('tm-001', $teamMember->id);
        $this->assertSame('emp-456', $teamMember->employeeId);
        $this->assertSame('team-dev', $teamMember->teamId);

        // Test role (level 2 deep)
        $this->assertSame('Tech Lead', $teamMember->role->name);
        $this->assertSame(7, $teamMember->role->level);
        $this->assertSame(['code review', 'mentoring', 'architecture'], $teamMember->role->responsibilities);

        // Test role permissions (level 3 deep)
        $this->assertNotNull($teamMember->role->permissions);
        $this->assertTrue($teamMember->role->permissions->read);
        $this->assertTrue($teamMember->role->permissions->write);
        $this->assertFalse($teamMember->role->permissions->admin);

        // Test team permissions (level 2 deep)
        $this->assertNotNull($teamMember->permissions);
        $this->assertTrue($teamMember->permissions->canEdit);
        $this->assertFalse($teamMember->permissions->canDelete);
        $this->assertTrue($teamMember->permissions->canManage);

        // Test custom permissions (level 3 deep)
        $this->assertCount(2, $teamMember->permissions->customPermissions);
        $this->assertSame('deploy', $teamMember->permissions->customPermissions[0]->name);
        $this->assertTrue($teamMember->permissions->customPermissions[0]->granted);
        $this->assertSame('review', $teamMember->permissions->customPermissions[1]->name);
        $this->assertTrue($teamMember->permissions->customPermissions[1]->granted);

        // Test workload (level 2 deep)
        $this->assertNotNull($teamMember->workload);
        $this->assertSame(40.0, $teamMember->workload->hoursPerWeek);
        $this->assertSame(0.8, $teamMember->workload->allocation);

        // Test tasks (level 3 deep)
        $this->assertCount(2, $teamMember->workload->tasks);
        $this->assertSame('task-001', $teamMember->workload->tasks[0]->id);
        $this->assertSame('Implement authentication', $teamMember->workload->tasks[0]->title);
        $this->assertSame(16.0, $teamMember->workload->tasks[0]->estimatedHours);
        $this->assertSame('high', $teamMember->workload->tasks[0]->priority);
    }

    public function test_team_members_list(): void
    {
        $data = [
            'members' => [
                [
                    'id' => 'tm-001',
                    'employeeId' => 'emp-001',
                    'teamId' => 'team-001',
                    'role' => [
                        'name' => 'Developer',
                        'level' => 3,
                    ],
                ],
                [
                    'id' => 'tm-002',
                    'employeeId' => 'emp-002',
                    'teamId' => 'team-001',
                    'role' => [
                        'name' => 'Designer',
                        'level' => 4,
                    ],
                ],
            ],
            'pagination' => [
                'page' => 1,
                'size' => 2,
                'total' => 2,
            ],
        ];

        $list = TeamMembersList::fromArray($data);

        $this->assertCount(2, $list->members);
        $this->assertSame('tm-001', $list->members[0]->id);
        $this->assertSame('Developer', $list->members[0]->role->name);
        $this->assertSame(3, $list->members[0]->role->level);
        $this->assertSame('tm-002', $list->members[1]->id);
        $this->assertSame('Designer', $list->members[1]->role->name);
        $this->assertSame(4, $list->members[1]->role->level);
    }

    public function test_skill_enum_validation(): void
    {
        $validData = ['name' => 'PHP', 'level' => 'expert'];
        $skill = Skill::fromArray($validData);
        $this->assertSame('expert', $skill->level);

        $invalidData = ['name' => 'PHP', 'level' => 'invalid'];
        $skill = Skill::fromArray($invalidData);
        $this->assertNull($skill->level); // Should be null for invalid enum value
    }

    public function test_task_priority_enum_validation(): void
    {
        $validData = ['id' => 'task-1', 'title' => 'Test', 'priority' => 'urgent'];
        $task = Task::fromArray($validData);
        $this->assertSame('urgent', $task->priority);

        $invalidData = ['id' => 'task-1', 'title' => 'Test', 'priority' => 'invalid'];
        $task = Task::fromArray($invalidData);
        $this->assertNull($task->priority); // Should be null for invalid enum value
    }

    public function test_error_handling_for_required_fields(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('id must be a string');

        Employee::fromArray(['email' => 'test@example.com']); // Missing required 'id'
    }

    public function test_error_handling_for_invalid_profile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('profile must be an array');

        Employee::fromArray([
            'id' => 'emp-123',
            'email' => 'test@example.com',
            'profile' => 'invalid', // Should be array
        ]);
    }

    public function test_error_handling_for_invalid_team_role(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('name must be a string');

        TeamRole::fromArray(['level' => 1]); // Missing required 'name'
    }

    public function test_array_filtering_for_invalid_items(): void
    {
        $data = [
            'id' => 'emp-123',
            'email' => 'test@example.com',
            'profile' => [
                'firstName' => 'Test',
                'lastName' => 'User',
            ],
            'skills' => [
                ['name' => 'PHP', 'level' => 'expert'], // Valid
                'invalid_skill', // Invalid - not an array
                ['name' => 'JavaScript', 'level' => 'advanced'], // Valid
            ],
        ];

        $employee = Employee::fromArray($data);

        // Should have only the 2 valid skills, invalid one filtered out
        $this->assertCount(2, $employee->skills);
        $this->assertSame('PHP', $employee->skills[0]->name);
        $this->assertSame('JavaScript', $employee->skills[1]->name);
    }

    public function test_deep_optional_fields(): void
    {
        $data = [
            'id' => 'emp-minimal',
            'email' => 'minimal@example.com',
            'profile' => [
                'firstName' => 'Minimal',
                'lastName' => 'User',
                'preferences' => [
                    'communication' => [
                        'email' => true,
                        // Missing slack and sms - should be null
                    ],
                ],
            ],
        ];

        $employee = Employee::fromArray($data);

        $this->assertNotNull($employee->profile->preferences);
        $this->assertNotNull($employee->profile->preferences->communication);
        $this->assertTrue($employee->profile->preferences->communication->email);
        $this->assertNull($employee->profile->preferences->communication->slack);
        $this->assertNull($employee->profile->preferences->communication->sms);
        $this->assertNull($employee->profile->preferences->workspace);
        $this->assertNull($employee->profile->preferences->benefits);
        $this->assertNull($employee->profile->preferences->schedule);
    }
}
