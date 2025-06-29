<?php

declare(strict_types=1);

require_once __DIR__.'/output/Pet.php';
require_once __DIR__.'/output/Category.php';
require_once __DIR__.'/output/Tag.php';
require_once __DIR__.'/output/User.php';
require_once __DIR__.'/output/Order.php';
require_once __DIR__.'/output/ApiResponse.php';
require_once __DIR__.'/output/Error.php';

/**
 * Basic validation test for generated Petstore classes
 * This test validates that the generated code is syntactically correct
 * and the classes can be instantiated.
 */
class PetstoreTest
{
    public function testGeneratedClassesExist(): void
    {
        $this->assertTrue(class_exists('Generated\\Pet'));
        $this->assertTrue(class_exists('Generated\\Category'));
        $this->assertTrue(class_exists('Generated\\Tag'));
        $this->assertTrue(class_exists('Generated\\User'));
        $this->assertTrue(class_exists('Generated\\Order'));
        $this->assertTrue(class_exists('Generated\\ApiResponse'));
        $this->assertTrue(class_exists('Generated\\Error'));
    }

    public function testPetClassCanBeInstantiated(): void
    {
        $pet = new Generated\Pet(
            photoUrls: ['https://example.com/photo1.jpg'],
            name: 'Fluffy',
            id: 1
        );

        $this->assertInstanceOf(Generated\Pet::class, $pet);
        $this->assertEquals(1, $pet->id);
        $this->assertEquals('Fluffy', $pet->name);
        $this->assertEquals(['https://example.com/photo1.jpg'], $pet->photoUrls);
    }

    public function testCategoryClassCanBeInstantiated(): void
    {
        $category = new Generated\Category(
            id: 1,
            name: 'Dogs'
        );

        $this->assertInstanceOf(Generated\Category::class, $category);
        $this->assertEquals(1, $category->id);
        $this->assertEquals('Dogs', $category->name);
    }

    public function testUserClassCanBeInstantiated(): void
    {
        $user = new Generated\User(
            id: 1,
            username: 'testuser',
            firstName: 'Test',
            lastName: 'User',
            email: 'test@example.com',
            password: 'password123',
            phone: '123-456-7890',
            userStatus: 1
        );

        $this->assertInstanceOf(Generated\User::class, $user);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('test@example.com', $user->email);
    }

    private function assertTrue(bool $condition, string $message = ''): void
    {
        if (! $condition) {
            throw new AssertionError($message ?: 'Assertion failed');
        }
        echo '✓ '.($message ?: 'Test passed')."\n";
    }

    private function assertInstanceOf(string $expected, object $actual): void
    {
        if (! ($actual instanceof $expected)) {
            throw new AssertionError("Expected instance of {$expected}, got ".get_class($actual));
        }
        echo "✓ Instance of {$expected} test passed\n";
    }

    private function assertEquals($expected, $actual): void
    {
        if ($expected !== $actual) {
            throw new AssertionError("Expected {$expected}, got {$actual}");
        }
        echo "✓ Equality test passed\n";
    }
}

// Run the tests
try {
    $test = new PetstoreTest;

    echo "Running Petstore MVP validation tests...\n\n";

    $test->testGeneratedClassesExist();
    $test->testPetClassCanBeInstantiated();
    $test->testCategoryClassCanBeInstantiated();
    $test->testUserClassCanBeInstantiated();

    echo "\n✅ All tests passed! Generated code is valid.\n";

} catch (Throwable $e) {
    echo "\n❌ Test failed: ".$e->getMessage()."\n";
    echo "Stack trace:\n".$e->getTraceAsString()."\n";
    exit(1);
}

class AssertionError extends Exception {}
