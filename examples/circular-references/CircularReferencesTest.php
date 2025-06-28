<?php

declare(strict_types=1);

require_once __DIR__.'/expected/CircularUser.php';
require_once __DIR__.'/expected/UserProfile.php';
require_once __DIR__.'/expected/UserProfileSettings.php';

use PHPUnit\Framework\TestCase;

class CircularReferencesTest extends TestCase
{
    public function test_user_from_array_minimal(): void
    {
        $json = [
            'id' => 'user-123',
            'username' => 'johndoe',
        ];

        $user = CircularUser::fromArray($json);

        $this->assertSame('user-123', $user->id);
        $this->assertSame('johndoe', $user->username);
        $this->assertNull($user->email);
        $this->assertNull($user->profile);
    }

    public function test_user_from_array_with_profile(): void
    {
        $json = [
            'id' => 'user-123',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'profile' => [
                'id' => 'profile-456',
                'userId' => 'user-123',
                'displayName' => 'John Doe',
                'bio' => 'Software developer',
            ],
        ];

        $user = CircularUser::fromArray($json);

        $this->assertSame('user-123', $user->id);
        $this->assertSame('johndoe', $user->username);
        $this->assertSame('john@example.com', $user->email);

        // Profile should be loaded
        $this->assertInstanceOf(UserProfile::class, $user->profile);
        $this->assertSame('profile-456', $user->profile->id);
        $this->assertSame('user-123', $user->profile->userId);
        $this->assertSame('John Doe', $user->profile->displayName);
        $this->assertSame('Software developer', $user->profile->bio);

        // Circular reference should be broken - profile.user should be null
        $this->assertNull($user->profile->user);
    }

    public function test_profile_from_array_minimal(): void
    {
        $json = [
            'id' => 'profile-456',
            'userId' => 'user-123',
        ];

        $profile = UserProfile::fromArray($json);

        $this->assertSame('profile-456', $profile->id);
        $this->assertSame('user-123', $profile->userId);
        $this->assertNull($profile->displayName);
        $this->assertNull($profile->bio);
        $this->assertNull($profile->user);
        $this->assertNull($profile->settings);
    }

    public function test_profile_from_array_with_user(): void
    {
        $json = [
            'id' => 'profile-456',
            'userId' => 'user-123',
            'displayName' => 'John Doe',
            'bio' => 'Developer and tech enthusiast',
            'user' => [
                'id' => 'user-123',
                'username' => 'johndoe',
                'email' => 'john@example.com',
            ],
        ];

        $profile = UserProfile::fromArray($json);

        $this->assertSame('profile-456', $profile->id);
        $this->assertSame('user-123', $profile->userId);
        $this->assertSame('John Doe', $profile->displayName);
        $this->assertSame('Developer and tech enthusiast', $profile->bio);

        // User should be loaded
        $this->assertInstanceOf(CircularUser::class, $profile->user);
        $this->assertSame('user-123', $profile->user->id);
        $this->assertSame('johndoe', $profile->user->username);
        $this->assertSame('john@example.com', $profile->user->email);

        // Circular reference should be broken - user.profile should be null
        $this->assertNull($profile->user->profile);
    }

    public function test_profile_from_array_with_settings(): void
    {
        $json = [
            'id' => 'profile-456',
            'userId' => 'user-123',
            'displayName' => 'John Doe',
            'settings' => [
                'theme' => 'dark',
                'privacy' => 'public',
            ],
        ];

        $profile = UserProfile::fromArray($json);

        $this->assertSame('profile-456', $profile->id);
        $this->assertSame('user-123', $profile->userId);
        $this->assertSame('John Doe', $profile->displayName);

        // Settings should be loaded
        $this->assertInstanceOf(UserProfileSettings::class, $profile->settings);
        $this->assertSame('dark', $profile->settings->theme);
        $this->assertSame('public', $profile->settings->privacy);

        // Circular reference should be broken - settings.profile should be null
        $this->assertNull($profile->settings->profile);
    }

    public function test_profile_settings_from_array_minimal(): void
    {
        $json = [];

        $settings = UserProfileSettings::fromArray($json);

        $this->assertNull($settings->theme);
        $this->assertNull($settings->privacy);
        $this->assertNull($settings->profile);
    }

    public function test_profile_settings_from_array_with_profile(): void
    {
        $json = [
            'theme' => 'light',
            'privacy' => 'private',
            'profile' => [
                'id' => 'profile-789',
                'userId' => 'user-456',
                'displayName' => 'Jane Smith',
            ],
        ];

        $settings = UserProfileSettings::fromArray($json);

        $this->assertSame('light', $settings->theme);
        $this->assertSame('private', $settings->privacy);

        // Profile should be loaded
        $this->assertInstanceOf(UserProfile::class, $settings->profile);
        $this->assertSame('profile-789', $settings->profile->id);
        $this->assertSame('user-456', $settings->profile->userId);
        $this->assertSame('Jane Smith', $settings->profile->displayName);

        // Circular reference should be broken - profile.settings should be null
        $this->assertNull($settings->profile->settings);
    }

    public function test_complex_circular_structure(): void
    {
        // Test a complex structure with multiple levels and circular references
        $json = [
            'id' => 'user-complex',
            'username' => 'complexuser',
            'email' => 'complex@example.com',
            'profile' => [
                'id' => 'profile-complex',
                'userId' => 'user-complex',
                'displayName' => 'Complex User',
                'bio' => 'A user with complex circular references',
                'user' => [
                    'id' => 'user-complex',
                    'username' => 'complexuser',
                    'email' => 'complex@example.com',
                    'profile' => [
                        'id' => 'profile-complex',
                        'userId' => 'user-complex',
                        // More circular data that should be handled
                    ],
                ],
                'settings' => [
                    'theme' => 'dark',
                    'privacy' => 'private',
                    'profile' => [
                        'id' => 'profile-complex',
                        'userId' => 'user-complex',
                        'displayName' => 'Complex User',
                        'settings' => [
                            'theme' => 'dark',
                            'privacy' => 'private',
                            // More circular data
                        ],
                    ],
                ],
            ],
        ];

        $user = CircularUser::fromArray($json);

        // Verify top-level user
        $this->assertSame('user-complex', $user->id);
        $this->assertSame('complexuser', $user->username);
        $this->assertSame('complex@example.com', $user->email);

        // Verify profile is loaded
        $this->assertInstanceOf(UserProfile::class, $user->profile);
        $this->assertSame('profile-complex', $user->profile->id);
        $this->assertSame('Complex User', $user->profile->displayName);

        // Verify circular reference from profile to user is broken
        $this->assertNull($user->profile->user);

        // Verify profile settings are loaded
        $this->assertInstanceOf(UserProfileSettings::class, $user->profile->settings);
        $this->assertSame('dark', $user->profile->settings->theme);
        $this->assertSame('private', $user->profile->settings->privacy);

        // Verify circular reference from settings to profile is broken
        $this->assertNull($user->profile->settings->profile);
    }

    public function test_from_array_without_profile_method(): void
    {
        $json = [
            'id' => 'user-no-profile',
            'username' => 'noprofile',
            'email' => 'noprofile@example.com',
        ];

        $user = CircularUser::fromArrayWithoutProfile($json);

        $this->assertSame('user-no-profile', $user->id);
        $this->assertSame('noprofile', $user->username);
        $this->assertSame('noprofile@example.com', $user->email);
        $this->assertNull($user->profile); // Profile should always be null in this method
    }

    public function test_from_array_without_user_method(): void
    {
        $json = [
            'id' => 'profile-no-user',
            'userId' => 'user-some-id',
            'displayName' => 'No User Profile',
            'settings' => [
                'theme' => 'light',
                'privacy' => 'public',
            ],
        ];

        $profile = UserProfile::fromArrayWithoutUser($json);

        $this->assertSame('profile-no-user', $profile->id);
        $this->assertSame('user-some-id', $profile->userId);
        $this->assertSame('No User Profile', $profile->displayName);
        $this->assertNull($profile->user); // User should always be null in this method

        // Settings should still be loaded
        $this->assertInstanceOf(UserProfileSettings::class, $profile->settings);
        $this->assertSame('light', $profile->settings->theme);
    }

    public function test_from_array_without_settings_method(): void
    {
        $json = [
            'id' => 'profile-no-settings',
            'userId' => 'user-some-id',
            'displayName' => 'No Settings Profile',
            'user' => [
                'id' => 'user-some-id',
                'username' => 'someuser',
            ],
        ];

        $profile = UserProfile::fromArrayWithoutSettings($json);

        $this->assertSame('profile-no-settings', $profile->id);
        $this->assertSame('user-some-id', $profile->userId);
        $this->assertSame('No Settings Profile', $profile->displayName);
        $this->assertNull($profile->settings); // Settings should always be null in this method

        // User should still be loaded
        $this->assertInstanceOf(CircularUser::class, $profile->user);
        $this->assertSame('user-some-id', $profile->user->id);
    }

    public function test_from_array_without_profile_method_in_profile_settings(): void
    {
        $json = [
            'theme' => 'auto',
            'privacy' => 'friends',
        ];

        $settings = UserProfileSettings::fromArrayWithoutProfile($json);

        $this->assertSame('auto', $settings->theme);
        $this->assertSame('friends', $settings->privacy);
        $this->assertNull($settings->profile); // Profile should always be null in this method
    }

    public function test_circular_reference_prevention_validation(): void
    {
        // Ensure that we can create objects with complex nested data
        // without infinite recursion or stack overflow

        $user = new CircularUser('test-id', 'testuser');
        $profile = new UserProfile('profile-id', 'test-id', 'Test User');
        $settings = new UserProfileSettings('dark', 'private');

        // Verify objects can be created independently
        $this->assertSame('test-id', $user->id);
        $this->assertSame('profile-id', $profile->id);
        $this->assertSame('dark', $settings->theme);

        // Verify circular object creation doesn't cause issues
        $userWithProfile = new CircularUser('user-id', 'user', 'user@test.com', $profile);
        $profileWithUser = new UserProfile('prof-id', 'user-id', 'Test', 'Bio', $user, $settings);
        $settingsWithProfile = new UserProfileSettings('light', 'public', $profile);

        $this->assertInstanceOf(UserProfile::class, $userWithProfile->profile);
        $this->assertInstanceOf(CircularUser::class, $profileWithUser->user);
        $this->assertInstanceOf(UserProfile::class, $settingsWithProfile->profile);
    }

    public function test_realistic_api_response(): void
    {
        // Test with realistic API response structure
        $apiResponse = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'username' => 'alice_dev',
            'email' => 'alice@techcorp.com',
            'profile' => [
                'id' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'userId' => '550e8400-e29b-41d4-a716-446655440000',
                'displayName' => 'Alice Johnson',
                'bio' => 'Senior Software Engineer specializing in distributed systems',
                'settings' => [
                    'theme' => 'dark',
                    'privacy' => 'public',
                ],
            ],
        ];

        $user = CircularUser::fromArray($apiResponse);

        // Validate complete structure
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $user->id);
        $this->assertSame('alice_dev', $user->username);
        $this->assertSame('alice@techcorp.com', $user->email);

        $profile = $user->profile;
        $this->assertInstanceOf(UserProfile::class, $profile);
        $this->assertSame('6ba7b810-9dad-11d1-80b4-00c04fd430c8', $profile->id);
        $this->assertSame('Alice Johnson', $profile->displayName);
        $this->assertSame('Senior Software Engineer specializing in distributed systems', $profile->bio);
        $this->assertNull($profile->user); // Circular reference broken

        $settings = $profile->settings;
        $this->assertInstanceOf(UserProfileSettings::class, $settings);
        $this->assertSame('dark', $settings->theme);
        $this->assertSame('public', $settings->privacy);
        $this->assertNull($settings->profile); // Circular reference broken
    }
}
