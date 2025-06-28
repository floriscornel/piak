<?php

declare(strict_types=1);

require_once __DIR__.'/expected/UserPreferences.php';
require_once __DIR__.'/expected/DynamicSettings.php';
require_once __DIR__.'/expected/Metadata.php';

use PHPUnit\Framework\TestCase;

class DynamicPropertiesTest extends TestCase
{
    public function test_user_preferences_from_array_with_complete_data(): void
    {
        $json = [
            'userId' => '550e8400-e29b-41d4-a716-446655440000',
            'theme' => 'dark',
            'settings' => [
                'notifications' => true,
                'language' => 'en',
                'customTheme' => 'purple',
                'fontSize' => 'large',
            ],
        ];

        $userPreferences = UserPreferences::fromArray($json);

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $userPreferences->userId);
        $this->assertSame('dark', $userPreferences->theme);
        $this->assertInstanceOf(DynamicSettings::class, $userPreferences->settings);
        $this->assertTrue($userPreferences->settings->notifications);
        $this->assertSame('en', $userPreferences->settings->language);
        $this->assertSame(['customTheme' => 'purple', 'fontSize' => 'large'], $userPreferences->settings->additionalProperties);
    }

    public function test_user_preferences_from_array_minimal_data(): void
    {
        $json = [
            'userId' => '550e8400-e29b-41d4-a716-446655440000',
            'theme' => 'light',
        ];

        $userPreferences = UserPreferences::fromArray($json);

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $userPreferences->userId);
        $this->assertSame('light', $userPreferences->theme);
        $this->assertNull($userPreferences->settings);
    }

    public function test_dynamic_settings_from_array_with_additional_properties(): void
    {
        $json = [
            'notifications' => false,
            'language' => 'fr',
            'customColor' => 'blue',
            'sidebarWidth' => '300px',
            'autoSave' => 'enabled',
        ];

        $settings = DynamicSettings::fromArray($json);

        $this->assertFalse($settings->notifications);
        $this->assertSame('fr', $settings->language);
        $this->assertSame([
            'customColor' => 'blue',
            'sidebarWidth' => '300px',
            'autoSave' => 'enabled',
        ], $settings->additionalProperties);
    }

    public function test_dynamic_settings_from_array_only_known_properties(): void
    {
        $json = [
            'notifications' => true,
            'language' => 'es',
        ];

        $settings = DynamicSettings::fromArray($json);

        $this->assertTrue($settings->notifications);
        $this->assertSame('es', $settings->language);
        $this->assertSame([], $settings->additionalProperties);
    }

    public function test_dynamic_settings_from_array_only_additional_properties(): void
    {
        $json = [
            'customProperty1' => 'value1',
            'customProperty2' => 'value2',
        ];

        $settings = DynamicSettings::fromArray($json);

        $this->assertNull($settings->notifications);
        $this->assertNull($settings->language);
        $this->assertSame([
            'customProperty1' => 'value1',
            'customProperty2' => 'value2',
        ], $settings->additionalProperties);
    }

    public function test_dynamic_settings_from_array_filters_non_string_additional_properties(): void
    {
        $json = [
            'notifications' => true,
            'stringProp' => 'valid',
            'numberProp' => 123,        // Should be filtered out (not string)
            'boolProp' => false,        // Should be filtered out (not string)
            'arrayProp' => ['invalid'], // Should be filtered out (not string)
            'validString' => 'included',
        ];

        $settings = DynamicSettings::fromArray($json);

        $this->assertTrue($settings->notifications);
        $this->assertSame([
            'stringProp' => 'valid',
            'validString' => 'included',
        ], $settings->additionalProperties);
    }

    public function test_metadata_from_array_with_mixed_types(): void
    {
        $json = [
            'stringValue' => 'hello',
            'intValue' => 42,
            'boolValue' => true,
            'floatValue' => 3.14,     // Should be filtered out (not int)
        ];

        $metadata = Metadata::fromArray($json);

        $this->assertSame([
            'stringValue' => 'hello',
            'intValue' => 42,
            'boolValue' => true,
        ], $metadata->additionalProperties);
    }

    public function test_metadata_from_array_empty_data(): void
    {
        $json = [];

        $metadata = Metadata::fromArray($json);

        $this->assertSame([], $metadata->additionalProperties);
    }

    public function test_metadata_from_array_filters_invalid_types(): void
    {
        $json = [
            'validString' => 'keep',
            'validInt' => 123,
            'validBool' => false,
            'invalidArray' => ['remove'],
            'invalidObject' => ['key' => 'remove'],
            'invalidNull' => null,
            'invalidFloat' => 1.23,
        ];

        $metadata = Metadata::fromArray($json);

        $this->assertSame([
            'validString' => 'keep',
            'validInt' => 123,
            'validBool' => false,
        ], $metadata->additionalProperties);
    }

    public function test_user_preferences_theme_enum_validation(): void
    {
        // Test valid enum values
        $validThemes = ['light', 'dark', 'auto'];

        foreach ($validThemes as $theme) {
            $json = [
                'userId' => '550e8400-e29b-41d4-a716-446655440000',
                'theme' => $theme,
            ];

            $userPreferences = UserPreferences::fromArray($json);
            $this->assertSame($theme, $userPreferences->theme);
        }
    }
}
