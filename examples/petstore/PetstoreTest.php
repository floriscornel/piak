<?php

declare(strict_types=1);

require_once __DIR__.'/expected/Category.php';
require_once __DIR__.'/expected/Tag.php';
require_once __DIR__.'/expected/User.php';
require_once __DIR__.'/expected/Order.php';
require_once __DIR__.'/expected/ApiResponse.php';
require_once __DIR__.'/expected/ApiError.php';
require_once __DIR__.'/expected/Pet.php';

use PHPUnit\Framework\TestCase;

class PetstoreTest extends TestCase
{
    public function test_category_from_array(): void
    {
        $json = [
            'id' => 1,
            'name' => 'Dogs',
        ];

        $category = Category::fromArray($json);

        $this->assertSame(1, $category->id);
        $this->assertSame('Dogs', $category->name);
    }

    public function test_category_from_array_minimal(): void
    {
        $json = [];

        $category = Category::fromArray($json);

        $this->assertNull($category->id);
        $this->assertNull($category->name);
    }

    public function test_tag_from_array(): void
    {
        $json = [
            'id' => 1,
            'name' => 'friendly',
        ];

        $tag = Tag::fromArray($json);

        $this->assertSame(1, $tag->id);
        $this->assertSame('friendly', $tag->name);
    }

    public function test_tag_from_array_minimal(): void
    {
        $json = [];

        $tag = Tag::fromArray($json);

        $this->assertNull($tag->id);
        $this->assertNull($tag->name);
    }

    public function test_user_from_array(): void
    {
        $json = [
            'id' => 10,
            'username' => 'theUser',
            'firstName' => 'John',
            'lastName' => 'James',
            'email' => 'john@email.com',
            'password' => '12345',
            'phone' => '12345',
            'userStatus' => 1,
        ];

        $user = User::fromArray($json);

        $this->assertSame(10, $user->id);
        $this->assertSame('theUser', $user->username);
        $this->assertSame('John', $user->firstName);
        $this->assertSame('James', $user->lastName);
        $this->assertSame('john@email.com', $user->email);
        $this->assertSame('12345', $user->password);
        $this->assertSame('12345', $user->phone);
        $this->assertSame(1, $user->userStatus);
    }

    public function test_user_from_array_minimal(): void
    {
        $json = [];

        $user = User::fromArray($json);

        $this->assertNull($user->id);
        $this->assertNull($user->username);
        $this->assertNull($user->firstName);
        $this->assertNull($user->lastName);
        $this->assertNull($user->email);
        $this->assertNull($user->password);
        $this->assertNull($user->phone);
        $this->assertNull($user->userStatus);
    }

    public function test_order_from_array(): void
    {
        $json = [
            'id' => 10,
            'petId' => 198772,
            'quantity' => 7,
            'shipDate' => '2023-06-15T10:30:00Z',
            'status' => 'approved',
            'complete' => true,
        ];

        $order = Order::fromArray($json);

        $this->assertSame(10, $order->id);
        $this->assertSame(198772, $order->petId);
        $this->assertSame(7, $order->quantity);
        $this->assertSame('2023-06-15T10:30:00Z', $order->shipDate);
        $this->assertSame('approved', $order->status);
        $this->assertTrue($order->complete);
    }

    public function test_order_from_array_minimal(): void
    {
        $json = [];

        $order = Order::fromArray($json);

        $this->assertNull($order->id);
        $this->assertNull($order->petId);
        $this->assertNull($order->quantity);
        $this->assertNull($order->shipDate);
        $this->assertNull($order->status);
        $this->assertNull($order->complete);
    }

    public function test_api_response_from_array(): void
    {
        $json = [
            'code' => 200,
            'type' => 'success',
            'message' => 'Operation completed successfully',
        ];

        $response = ApiResponse::fromArray($json);

        $this->assertSame(200, $response->code);
        $this->assertSame('success', $response->type);
        $this->assertSame('Operation completed successfully', $response->message);
    }

    public function test_api_response_from_array_minimal(): void
    {
        $json = [];

        $response = ApiResponse::fromArray($json);

        $this->assertNull($response->code);
        $this->assertNull($response->type);
        $this->assertNull($response->message);
    }

    public function test_error_from_array(): void
    {
        $json = [
            'code' => 'VALIDATION_ERROR',
            'message' => 'Invalid input provided',
        ];

        $error = ApiError::fromArray($json);

        $this->assertSame('VALIDATION_ERROR', $error->code);
        $this->assertSame('Invalid input provided', $error->message);
    }

    public function test_error_from_array_throws_exception_for_missing_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ApiError code must be a string');

        $json = [
            'message' => 'Invalid input provided',
        ];

        ApiError::fromArray($json);
    }

    public function test_error_from_array_throws_exception_for_missing_message(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ApiError message must be a string');

        $json = [
            'code' => 'VALIDATION_ERROR',
        ];

        ApiError::fromArray($json);
    }

    public function test_pet_from_array_complete(): void
    {
        $json = [
            'id' => 10,
            'name' => 'doggie',
            'category' => [
                'id' => 1,
                'name' => 'Dogs',
            ],
            'photoUrls' => [
                'https://example.com/photo1.jpg',
                'https://example.com/photo2.jpg',
            ],
            'tags' => [
                [
                    'id' => 1,
                    'name' => 'friendly',
                ],
                [
                    'id' => 2,
                    'name' => 'large',
                ],
            ],
            'status' => 'available',
        ];

        $pet = Pet::fromArray($json);

        $this->assertSame(10, $pet->id);
        $this->assertSame('doggie', $pet->name);
        $this->assertInstanceOf(Category::class, $pet->category);
        $this->assertSame(1, $pet->category->id);
        $this->assertSame('Dogs', $pet->category->name);
        $this->assertSame(['https://example.com/photo1.jpg', 'https://example.com/photo2.jpg'], $pet->photoUrls);
        $this->assertCount(2, $pet->tags);
        $this->assertInstanceOf(Tag::class, $pet->tags[0]);
        $this->assertSame(1, $pet->tags[0]->id);
        $this->assertSame('friendly', $pet->tags[0]->name);
        $this->assertInstanceOf(Tag::class, $pet->tags[1]);
        $this->assertSame(2, $pet->tags[1]->id);
        $this->assertSame('large', $pet->tags[1]->name);
        $this->assertSame('available', $pet->status);
    }

    public function test_pet_from_array_minimal(): void
    {
        $json = [
            'name' => 'buddy',
            'photoUrls' => ['https://example.com/buddy.jpg'],
        ];

        $pet = Pet::fromArray($json);

        $this->assertNull($pet->id);
        $this->assertSame('buddy', $pet->name);
        $this->assertNull($pet->category);
        $this->assertSame(['https://example.com/buddy.jpg'], $pet->photoUrls);
        $this->assertSame([], $pet->tags);
        $this->assertNull($pet->status);
    }

    public function test_pet_throws_exception_for_missing_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pet name must be a string');

        $json = [
            'photoUrls' => ['https://example.com/photo.jpg'],
        ];

        Pet::fromArray($json);
    }

    public function test_pet_throws_exception_for_missing_photo_urls(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Pet photoUrls must be an array');

        $json = [
            'name' => 'buddy',
        ];

        Pet::fromArray($json);
    }

    public function test_pet_throws_exception_for_invalid_photo_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PhotoUrl must be a string');

        $json = [
            'name' => 'buddy',
            'photoUrls' => [123], // Invalid: number instead of string
        ];

        Pet::fromArray($json);
    }

    public function test_pet_throws_exception_for_invalid_tag_data(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag data must be an array');

        $json = [
            'name' => 'buddy',
            'photoUrls' => ['https://example.com/photo.jpg'],
            'tags' => ['invalid'], // Invalid: string instead of array
        ];

        Pet::fromArray($json);
    }

    public function test_pet_with_empty_tags_array(): void
    {
        $json = [
            'name' => 'buddy',
            'photoUrls' => ['https://example.com/photo.jpg'],
            'tags' => [],
        ];

        $pet = Pet::fromArray($json);

        $this->assertSame('buddy', $pet->name);
        $this->assertSame([], $pet->tags);
    }

    public function test_pet_without_category(): void
    {
        $json = [
            'name' => 'buddy',
            'photoUrls' => ['https://example.com/photo.jpg'],
            'category' => null,
        ];

        $pet = Pet::fromArray($json);

        $this->assertSame('buddy', $pet->name);
        $this->assertNull($pet->category);
    }

    public function test_type_conversion(): void
    {
        $json = [
            'id' => '10', // String that should be converted to int
            'name' => 'Dogs',
        ];

        $category = Category::fromArray($json);

        $this->assertSame(10, $category->id);
        $this->assertSame('Dogs', $category->name);
    }
}
