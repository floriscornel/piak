<?php

declare(strict_types=1);

require_once __DIR__.'/expected/UserSettings.php';
require_once __DIR__.'/expected/ProfileRequest.php';
require_once __DIR__.'/expected/ProfileResponse.php';

use PHPUnit\Framework\TestCase;

class NullableOptionalTest extends TestCase
{
    public function test_user_settings_from_array(): void
    {
        $json = [
            'theme' => 'dark',
            'notifications' => true,
            'privacy' => 'private',
            'customCss' => '.btn { color: red; }',
            'preferences' => [
                'language' => 'en',
                'timezone' => 'UTC',
            ],
        ];

        $settings = UserSettings::fromArray($json);

        $this->assertSame('dark', $settings->theme);
        $this->assertTrue($settings->notifications);
        $this->assertSame('private', $settings->privacy);
        $this->assertSame('.btn { color: red; }', $settings->customCss);
        $this->assertSame([
            'language' => 'en',
            'timezone' => 'UTC',
        ], $settings->preferences);
    }

    public function test_user_settings_from_array_required_only(): void
    {
        $json = [
            'theme' => 'light',
            'notifications' => false,
        ];

        $settings = UserSettings::fromArray($json);

        $this->assertSame('light', $settings->theme);
        $this->assertFalse($settings->notifications);
        $this->assertNull($settings->privacy);      // Optional - defaults to null
        $this->assertNull($settings->customCss);    // Optional and nullable - defaults to null
        $this->assertNull($settings->preferences);  // Nullable - defaults to null
    }

    public function test_user_settings_from_array_with_null_custom_css(): void
    {
        $json = [
            'theme' => 'auto',
            'notifications' => true,
            'customCss' => null,  // Explicitly null - allowed since nullable
        ];

        $settings = UserSettings::fromArray($json);

        $this->assertSame('auto', $settings->theme);
        $this->assertTrue($settings->notifications);
        $this->assertNull($settings->customCss);
    }

    public function test_profile_request_from_array(): void
    {
        $json = [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'bio' => 'Software developer with 5 years experience',
            'avatar' => 'https://example.com/avatar.jpg',
            'settings' => [
                'theme' => 'dark',
                'notifications' => true,
                'privacy' => 'public',
            ],
        ];

        $request = ProfileRequest::fromArray($json);

        $this->assertSame('johndoe', $request->username);
        $this->assertSame('john@example.com', $request->email);
        $this->assertSame('Software developer with 5 years experience', $request->bio);
        $this->assertSame('https://example.com/avatar.jpg', $request->avatar);
        $this->assertInstanceOf(UserSettings::class, $request->settings);
        $this->assertSame('dark', $request->settings->theme);
    }

    public function test_profile_request_from_array_required_only(): void
    {
        $json = [
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'bio' => null,  // Required but nullable - must be present even if null
        ];

        $request = ProfileRequest::fromArray($json);

        $this->assertSame('janedoe', $request->username);
        $this->assertSame('jane@example.com', $request->email);
        $this->assertNull($request->bio);       // Required but nullable
        $this->assertNull($request->avatar);    // Optional - defaults to null
        $this->assertNull($request->settings);  // Optional - defaults to null
    }

    public function test_profile_request_from_array_without_optional_fields(): void
    {
        $json = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'bio' => 'Test bio',
            // avatar and settings are optional - not provided
        ];

        $request = ProfileRequest::fromArray($json);

        $this->assertSame('testuser', $request->username);
        $this->assertSame('test@example.com', $request->email);
        $this->assertSame('Test bio', $request->bio);
        $this->assertNull($request->avatar);    // Optional field not provided
        $this->assertNull($request->settings);  // Optional field not provided
    }

    public function test_profile_response_from_array(): void
    {
        $json = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'bio' => 'Experienced developer',
            'avatar' => 'https://example.com/avatar.jpg',
            'settings' => [
                'theme' => 'dark',
                'notifications' => false,
                'customCss' => '.profile { border: 1px solid #ccc; }',
            ],
            'createdAt' => '2023-01-15T10:30:00Z',
            'updatedAt' => '2023-12-01T15:45:30Z',
        ];

        $response = ProfileResponse::fromArray($json);

        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $response->id);
        $this->assertSame('johndoe', $response->username);
        $this->assertSame('john@example.com', $response->email);
        $this->assertSame('Experienced developer', $response->bio);
        $this->assertSame('https://example.com/avatar.jpg', $response->avatar);
        $this->assertInstanceOf(UserSettings::class, $response->settings);
        $this->assertSame('dark', $response->settings->theme);
        $this->assertSame('2023-01-15T10:30:00Z', $response->createdAt);
        $this->assertSame('2023-12-01T15:45:30Z', $response->updatedAt);
    }

    public function test_profile_response_from_array_with_nulls(): void
    {
        $json = [
            'id' => '987fcdeb-51a2-43d1-9f12-5678901234ab',
            'username' => 'newuser',
            'email' => 'new@example.com',
            'bio' => null,        // Nullable
            'avatar' => null,     // Nullable
            'settings' => null,   // Nullable reference
            'createdAt' => '2023-12-15T08:00:00Z',
            'updatedAt' => null,   // Never updated - nullable
        ];

        $response = ProfileResponse::fromArray($json);

        $this->assertSame('987fcdeb-51a2-43d1-9f12-5678901234ab', $response->id);
        $this->assertSame('newuser', $response->username);
        $this->assertSame('new@example.com', $response->email);
        $this->assertNull($response->bio);
        $this->assertNull($response->avatar);
        $this->assertNull($response->settings);
        $this->assertSame('2023-12-15T08:00:00Z', $response->createdAt);
        $this->assertNull($response->updatedAt);
    }

    public function test_profile_response_from_array_minimal(): void
    {
        $json = [];  // All properties are optional in response

        $response = ProfileResponse::fromArray($json);

        $this->assertNull($response->id);
        $this->assertNull($response->username);
        $this->assertNull($response->email);
        $this->assertNull($response->bio);
        $this->assertNull($response->avatar);
        $this->assertNull($response->settings);
        $this->assertNull($response->createdAt);
        $this->assertNull($response->updatedAt);
    }

    public function test_nullable_vs_optional_distinction(): void
    {
        // Test the key distinction: required+nullable vs optional

        // This should work - bio is required but can be null
        $validData = [
            'username' => 'test',
            'email' => 'test@test.com',
            'bio' => null,  // Required but nullable
        ];

        $request = ProfileRequest::fromArray($validData);
        $this->assertNull($request->bio);

        // This should also work - bio is provided with value
        $validData2 = [
            'username' => 'test2',
            'email' => 'test2@test.com',
            'bio' => 'My bio',
        ];

        $request2 = ProfileRequest::fromArray($validData2);
        $this->assertSame('My bio', $request2->bio);
    }

    public function test_nested_nullable_reference(): void
    {
        $json = [
            'username' => 'usertest',
            'email' => 'user@test.com',
            'bio' => 'Test user',
            'settings' => [
                'theme' => 'light',
                'notifications' => true,
                'privacy' => null,    // Optional in nested object
                'customCss' => null,  // Optional and nullable in nested object
                'preferences' => null, // Nullable in nested object
            ],
        ];

        $request = ProfileRequest::fromArray($json);

        $this->assertInstanceOf(UserSettings::class, $request->settings);
        $this->assertSame('light', $request->settings->theme);
        $this->assertTrue($request->settings->notifications);
        $this->assertNull($request->settings->privacy);
        $this->assertNull($request->settings->customCss);
        $this->assertNull($request->settings->preferences);
    }

    public function test_all_of_nullable_reference_in_response(): void
    {
        // Test allOf with nullable: true pattern in ProfileResponse.settings
        $json = [
            'id' => 'test-id',
            'settings' => [
                'theme' => 'auto',
                'notifications' => false,
            ],
        ];

        $response = ProfileResponse::fromArray($json);

        $this->assertInstanceOf(UserSettings::class, $response->settings);
        $this->assertSame('auto', $response->settings->theme);
        $this->assertFalse($response->settings->notifications);
    }

    public function test_property_types_consistency(): void
    {
        // Verify that property types are consistent with OpenAPI spec
        $request = new ProfileRequest('user', 'user@test.com', null);
        $response = new ProfileResponse;
        $settings = new UserSettings('dark', true);

        // Required properties in ProfileRequest
        $this->assertIsString($request->username);
        $this->assertIsString($request->email);
        // bio is nullable but still accessible
        $this->assertNull($request->bio);

        // All properties in ProfileResponse are optional/nullable
        $this->assertNull($response->id);
        $this->assertNull($response->bio);
        $this->assertNull($response->settings);

        // Required properties in UserSettings
        $this->assertIsString($settings->theme);
        $this->assertIsBool($settings->notifications);
        $this->assertNull($settings->privacy);  // Optional
    }
}
