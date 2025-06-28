<?php

declare(strict_types=1);

require_once __DIR__.'/expected/SocialLink.php';
require_once __DIR__.'/expected/Profile.php';
require_once __DIR__.'/expected/OrganizationSettings.php';
require_once __DIR__.'/expected/TeamMember.php';
require_once __DIR__.'/expected/Project.php';
require_once __DIR__.'/expected/Member.php';
require_once __DIR__.'/expected/Team.php';
require_once __DIR__.'/expected/Organization.php';

use PHPUnit\Framework\TestCase;

class ArrayReferencesTest extends TestCase
{
    public function test_social_link_from_array(): void
    {
        $json = [
            'platform' => 'github',
            'url' => 'https://github.com/johndoe',
            'verified' => true,
        ];

        $link = SocialLink::fromArray($json);

        $this->assertSame('github', $link->platform);
        $this->assertSame('https://github.com/johndoe', $link->url);
        $this->assertTrue($link->verified);
    }

    public function test_social_link_from_array_with_default_verified(): void
    {
        $json = [
            'platform' => 'twitter',
            'url' => 'https://twitter.com/johndoe',
        ];

        $link = SocialLink::fromArray($json);

        $this->assertSame('twitter', $link->platform);
        $this->assertSame('https://twitter.com/johndoe', $link->url);
        $this->assertFalse($link->verified); // Default value
    }

    public function test_profile_from_array_with_social_links(): void
    {
        $json = [
            'displayName' => 'John Doe',
            'bio' => 'Software developer and open source enthusiast',
            'location' => 'San Francisco, CA',
            'website' => 'https://johndoe.dev',
            'socialLinks' => [
                [
                    'platform' => 'github',
                    'url' => 'https://github.com/johndoe',
                    'verified' => true,
                ],
                [
                    'platform' => 'linkedin',
                    'url' => 'https://linkedin.com/in/johndoe',
                    'verified' => false,
                ],
            ],
        ];

        $profile = Profile::fromArray($json);

        $this->assertSame('John Doe', $profile->displayName);
        $this->assertSame('Software developer and open source enthusiast', $profile->bio);
        $this->assertSame('San Francisco, CA', $profile->location);
        $this->assertSame('https://johndoe.dev', $profile->website);
        $this->assertCount(2, $profile->socialLinks);

        $this->assertInstanceOf(SocialLink::class, $profile->socialLinks[0]);
        $this->assertSame('github', $profile->socialLinks[0]->platform);
        $this->assertTrue($profile->socialLinks[0]->verified);

        $this->assertInstanceOf(SocialLink::class, $profile->socialLinks[1]);
        $this->assertSame('linkedin', $profile->socialLinks[1]->platform);
        $this->assertFalse($profile->socialLinks[1]->verified);
    }

    public function test_profile_from_array_minimal(): void
    {
        $json = [];

        $profile = Profile::fromArray($json);

        $this->assertNull($profile->displayName);
        $this->assertNull($profile->bio);
        $this->assertNull($profile->location);
        $this->assertNull($profile->website);
        $this->assertSame([], $profile->socialLinks);
    }

    public function test_organization_settings_from_array(): void
    {
        $json = [
            'visibility' => 'private',
            'allowedEmailDomains' => ['company.com', 'contractor.com'],
            'features' => ['projects', 'teams', 'wiki'],
        ];

        $settings = OrganizationSettings::fromArray($json);

        $this->assertSame('private', $settings->visibility);
        $this->assertSame(['company.com', 'contractor.com'], $settings->allowedEmailDomains);
        $this->assertSame(['projects', 'teams', 'wiki'], $settings->features);
    }

    public function test_team_member_from_array(): void
    {
        $json = [
            'memberId' => '123e4567-e89b-12d3-a456-426614174000',
            'role' => 'lead',
            'joinedAt' => '2023-01-15T10:30:00Z',
        ];

        $teamMember = TeamMember::fromArray($json);

        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $teamMember->memberId);
        $this->assertSame('lead', $teamMember->role);
        $this->assertSame('2023-01-15T10:30:00Z', $teamMember->joinedAt);
    }

    public function test_project_from_array(): void
    {
        $json = [
            'id' => 'proj-123',
            'name' => 'Mobile App Redesign',
            'status' => 'active',
            'description' => 'Complete redesign of the mobile application',
            'tags' => ['mobile', 'ui', 'react-native'],
            'assignees' => ['user-123', 'user-456', 'user-789'],
        ];

        $project = Project::fromArray($json);

        $this->assertSame('proj-123', $project->id);
        $this->assertSame('Mobile App Redesign', $project->name);
        $this->assertSame('active', $project->status);
        $this->assertSame('Complete redesign of the mobile application', $project->description);
        $this->assertSame(['mobile', 'ui', 'react-native'], $project->tags);
        $this->assertSame(['user-123', 'user-456', 'user-789'], $project->assignees);
    }

    public function test_project_from_array_minimal(): void
    {
        $json = [
            'id' => 'proj-minimal',
            'name' => 'Basic Project',
            'status' => 'planning',
        ];

        $project = Project::fromArray($json);

        $this->assertSame('proj-minimal', $project->id);
        $this->assertSame('Basic Project', $project->name);
        $this->assertSame('planning', $project->status);
        $this->assertNull($project->description);
        $this->assertSame([], $project->tags);
        $this->assertSame([], $project->assignees);
    }

    public function test_member_from_array_with_profile(): void
    {
        $json = [
            'id' => 'user-123',
            'username' => 'johndoe',
            'role' => 'admin',
            'email' => 'john@company.com',
            'profile' => [
                'displayName' => 'John Doe',
                'bio' => 'Lead developer',
                'location' => 'New York',
                'socialLinks' => [
                    [
                        'platform' => 'github',
                        'url' => 'https://github.com/johndoe',
                    ],
                ],
            ],
            'teams' => ['team-1', 'team-2'], // ID array to avoid circular references
        ];

        $member = Member::fromArray($json);

        $this->assertSame('user-123', $member->id);
        $this->assertSame('johndoe', $member->username);
        $this->assertSame('admin', $member->role);
        $this->assertSame('john@company.com', $member->email);
        $this->assertInstanceOf(Profile::class, $member->profile);
        $this->assertSame('John Doe', $member->profile->displayName);
        $this->assertCount(1, $member->profile->socialLinks);
        $this->assertSame(['team-1', 'team-2'], $member->teams);
    }

    public function test_member_from_array_minimal(): void
    {
        $json = [
            'id' => 'user-456',
            'username' => 'janedoe',
            'role' => 'member',
        ];

        $member = Member::fromArray($json);

        $this->assertSame('user-456', $member->id);
        $this->assertSame('janedoe', $member->username);
        $this->assertSame('member', $member->role);
        $this->assertNull($member->email);
        $this->assertNull($member->profile);
        $this->assertSame([], $member->teams);
    }

    public function test_team_from_array_with_members_and_projects(): void
    {
        $json = [
            'id' => 'team-alpha',
            'name' => 'Alpha Team',
            'description' => 'Frontend development team',
            'members' => [
                [
                    'memberId' => 'user-123',
                    'role' => 'lead',
                    'joinedAt' => '2023-01-01T00:00:00Z',
                ],
                [
                    'memberId' => 'user-456',
                    'role' => 'developer',
                ],
            ],
            'permissions' => ['read', 'write', 'admin'],
            'projects' => [
                [
                    'id' => 'proj-1',
                    'name' => 'Website Redesign',
                    'status' => 'active',
                    'tags' => ['web', 'react'],
                ],
            ],
        ];

        $team = Team::fromArray($json);

        $this->assertSame('team-alpha', $team->id);
        $this->assertSame('Alpha Team', $team->name);
        $this->assertSame('Frontend development team', $team->description);

        $this->assertCount(2, $team->members);
        $this->assertInstanceOf(TeamMember::class, $team->members[0]);
        $this->assertSame('user-123', $team->members[0]->memberId);
        $this->assertSame('lead', $team->members[0]->role);
        $this->assertInstanceOf(TeamMember::class, $team->members[1]);
        $this->assertSame('user-456', $team->members[1]->memberId);
        $this->assertSame('developer', $team->members[1]->role);

        $this->assertSame(['read', 'write', 'admin'], $team->permissions);

        $this->assertCount(1, $team->projects);
        $this->assertInstanceOf(Project::class, $team->projects[0]);
        $this->assertSame('proj-1', $team->projects[0]->id);
        $this->assertSame('Website Redesign', $team->projects[0]->name);
    }

    public function test_team_from_array_minimal(): void
    {
        $json = [
            'id' => 'team-beta',
            'name' => 'Beta Team',
            'members' => [
                [
                    'memberId' => 'user-789',
                    'role' => 'observer',
                ],
            ],
        ];

        $team = Team::fromArray($json);

        $this->assertSame('team-beta', $team->id);
        $this->assertSame('Beta Team', $team->name);
        $this->assertNull($team->description);
        $this->assertCount(1, $team->members);
        $this->assertSame([], $team->permissions);
        $this->assertSame([], $team->projects);
    }

    public function test_organization_from_array_complete(): void
    {
        $json = [
            'id' => 'org-123',
            'name' => 'Tech Corp',
            'description' => 'A technology company',
            'teams' => [
                [
                    'id' => 'team-1',
                    'name' => 'Engineering',
                    'members' => [
                        [
                            'memberId' => 'user-1',
                            'role' => 'lead',
                        ],
                    ],
                    'permissions' => ['read', 'write'],
                ],
            ],
            'members' => [
                [
                    'id' => 'user-1',
                    'username' => 'alice',
                    'role' => 'owner',
                    'email' => 'alice@techcorp.com',
                    'teams' => ['team-1'],
                ],
            ],
            'settings' => [
                'visibility' => 'public',
                'features' => ['projects', 'teams'],
            ],
        ];

        $org = Organization::fromArray($json);

        $this->assertSame('org-123', $org->id);
        $this->assertSame('Tech Corp', $org->name);
        $this->assertSame('A technology company', $org->description);

        $this->assertCount(1, $org->teams);
        $this->assertInstanceOf(Team::class, $org->teams[0]);
        $this->assertSame('team-1', $org->teams[0]->id);
        $this->assertSame('Engineering', $org->teams[0]->name);

        $this->assertCount(1, $org->members);
        $this->assertInstanceOf(Member::class, $org->members[0]);
        $this->assertSame('user-1', $org->members[0]->id);
        $this->assertSame('alice', $org->members[0]->username);

        $this->assertInstanceOf(OrganizationSettings::class, $org->settings);
        $this->assertSame('public', $org->settings->visibility);
    }

    public function test_organization_from_array_minimal(): void
    {
        $json = [
            'id' => 'org-minimal',
            'name' => 'Minimal Org',
            'teams' => [],
        ];

        $org = Organization::fromArray($json);

        $this->assertSame('org-minimal', $org->id);
        $this->assertSame('Minimal Org', $org->name);
        $this->assertNull($org->description);
        $this->assertSame([], $org->teams);
        $this->assertSame([], $org->members);
        $this->assertNull($org->settings);
    }

    public function test_circular_reference_avoidance(): void
    {
        // Test that we use ID arrays instead of object references to prevent circular deps
        $memberJson = [
            'id' => 'user-circular',
            'username' => 'circular_user',
            'role' => 'member',
            'teams' => ['team-1', 'team-2'], // String IDs, not Team objects
        ];

        $member = Member::fromArray($memberJson);

        // teams should be string array, not Team object array
        $this->assertIsArray($member->teams);
        $this->assertSame(['team-1', 'team-2'], $member->teams);
        foreach ($member->teams as $teamId) {
            $this->assertIsString($teamId);
        }

        $projectJson = [
            'id' => 'proj-circular',
            'name' => 'Circular Project',
            'status' => 'active',
            'assignees' => ['user-1', 'user-2'], // String IDs, not Member objects
        ];

        $project = Project::fromArray($projectJson);

        // assignees should be string array, not Member object array
        $this->assertIsArray($project->assignees);
        $this->assertSame(['user-1', 'user-2'], $project->assignees);
        foreach ($project->assignees as $assigneeId) {
            $this->assertIsString($assigneeId);
        }
    }

    public function test_complex_nested_arrays(): void
    {
        // Test deeply nested structure with multiple array types
        $json = [
            'id' => 'org-complex',
            'name' => 'Complex Organization',
            'teams' => [
                [
                    'id' => 'team-complex',
                    'name' => 'Complex Team',
                    'members' => [
                        [
                            'memberId' => 'complex-user',
                            'role' => 'lead',
                            'joinedAt' => '2023-06-01T12:00:00Z',
                        ],
                    ],
                    'permissions' => ['read', 'write', 'delete'],
                    'projects' => [
                        [
                            'id' => 'complex-proj',
                            'name' => 'Complex Project',
                            'status' => 'active',
                            'description' => 'A very complex project',
                            'tags' => ['complex', 'enterprise', 'scalable'],
                            'assignees' => ['user-1', 'user-2', 'user-3'],
                        ],
                    ],
                ],
            ],
            'members' => [
                [
                    'id' => 'complex-member',
                    'username' => 'complex_user',
                    'role' => 'admin',
                    'email' => 'complex@example.com',
                    'profile' => [
                        'displayName' => 'Complex User',
                        'bio' => 'Handles complex systems',
                        'socialLinks' => [
                            [
                                'platform' => 'github',
                                'url' => 'https://github.com/complex',
                                'verified' => true,
                            ],
                            [
                                'platform' => 'linkedin',
                                'url' => 'https://linkedin.com/in/complex',
                            ],
                        ],
                    ],
                    'teams' => ['team-complex', 'team-other'],
                ],
            ],
        ];

        $org = Organization::fromArray($json);

        // Verify top-level organization
        $this->assertSame('org-complex', $org->id);
        $this->assertSame('Complex Organization', $org->name);

        // Verify nested team with members and projects
        $team = $org->teams[0];
        $this->assertSame('team-complex', $team->id);
        $this->assertCount(1, $team->members);
        $this->assertSame(['read', 'write', 'delete'], $team->permissions);
        $this->assertCount(1, $team->projects);

        // Verify project with arrays
        $project = $team->projects[0];
        $this->assertSame('complex-proj', $project->id);
        $this->assertSame(['complex', 'enterprise', 'scalable'], $project->tags);
        $this->assertSame(['user-1', 'user-2', 'user-3'], $project->assignees);

        // Verify member with profile and social links
        $member = $org->members[0];
        $this->assertSame('complex-member', $member->id);
        $this->assertInstanceOf(Profile::class, $member->profile);
        $this->assertCount(2, $member->profile->socialLinks);
        $this->assertTrue($member->profile->socialLinks[0]->verified);
        $this->assertFalse($member->profile->socialLinks[1]->verified);
    }

    public function test_empty_arrays_handling(): void
    {
        // Test various empty array scenarios
        $json = [
            'id' => 'org-empty',
            'name' => 'Empty Arrays Org',
            'teams' => [],
            'members' => [],
        ];

        $org = Organization::fromArray($json);

        $this->assertSame([], $org->teams);
        $this->assertSame([], $org->members);

        // Test profile with empty social links
        $profileJson = [
            'displayName' => 'User with no links',
            'socialLinks' => [],
        ];

        $profile = Profile::fromArray($profileJson);
        $this->assertSame([], $profile->socialLinks);
    }
}
