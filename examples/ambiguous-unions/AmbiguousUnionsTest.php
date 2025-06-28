<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once 'expected/Product.php';
require_once 'expected/User.php';
require_once 'expected/Order.php';
require_once 'expected/SearchResult.php';
require_once 'expected/DatabaseConnection.php';
require_once 'expected/DatabaseConfig.php';
require_once 'expected/NotificationAction.php';
require_once 'expected/RichNotificationContent.php';
require_once 'expected/NotificationContent.php';

class AmbiguousUnionsTest extends TestCase
{
    // Test individual component classes
    public function test_product_from_array(): void
    {
        $data = [
            'id' => 'prod-123',
            'name' => 'Gaming Laptop',
            'price' => 1299.99,
            'description' => 'High-performance gaming laptop',
            'category' => 'Electronics',
        ];

        $product = Product::fromArray($data);

        $this->assertSame('prod-123', $product->id);
        $this->assertSame('Gaming Laptop', $product->name);
        $this->assertSame(1299.99, $product->price);
        $this->assertSame('High-performance gaming laptop', $product->description);
        $this->assertSame('Electronics', $product->category);
    }

    public function test_user_from_array(): void
    {
        $data = [
            'id' => 'user-456',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'Software developer',
        ];

        $user = User::fromArray($data);

        $this->assertSame('user-456', $user->id);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
        $this->assertSame('Software developer', $user->bio);
    }

    public function test_order_from_array(): void
    {
        $data = [
            'id' => 'order-789',
            'name' => 'Electronics Order',
            'total' => 2599.98,
            'status' => 'shipped',
            'items' => ['laptop', 'mouse', 'keyboard'],
        ];

        $order = Order::fromArray($data);

        $this->assertSame('order-789', $order->id);
        $this->assertSame('Electronics Order', $order->name);
        $this->assertSame(2599.98, $order->total);
        $this->assertSame('shipped', $order->status);
        $this->assertSame(['laptop', 'mouse', 'keyboard'], $order->items);
    }

    // Test SearchResult type detection - clear cases
    public function test_search_result_detects_product(): void
    {
        $data = [
            'id' => 'prod-123',
            'name' => 'Gaming Laptop',
            'price' => 1299.99,  // Unique to Product
            'category' => 'Electronics',  // Unique to Product
        ];

        $searchResult = SearchResult::fromArray($data);

        $this->assertInstanceOf(Product::class, $searchResult->result);
        $this->assertSame('Gaming Laptop', $searchResult->result->name);
        $this->assertSame(1299.99, $searchResult->result->price);
    }

    public function test_search_result_detects_user(): void
    {
        $data = [
            'id' => 'user-456',
            'name' => 'John Doe',
            'email' => 'john@example.com',  // Unique to User
            'bio' => 'Software developer',  // Unique to User
        ];

        $searchResult = SearchResult::fromArray($data);

        $this->assertInstanceOf(User::class, $searchResult->result);
        $this->assertSame('John Doe', $searchResult->result->name);
        $this->assertSame('john@example.com', $searchResult->result->email);
    }

    public function test_search_result_detects_order(): void
    {
        $data = [
            'id' => 'order-789',
            'name' => 'Electronics Order',
            'total' => 2599.98,  // Unique to Order
            'status' => 'shipped',  // Unique to Order
            'items' => ['laptop'],  // Unique to Order
        ];

        $searchResult = SearchResult::fromArray($data);

        $this->assertInstanceOf(Order::class, $searchResult->result);
        $this->assertSame('Electronics Order', $searchResult->result->name);
        $this->assertSame(2599.98, $searchResult->result->total);
    }

    // Test SearchResult heuristic detection - ambiguous cases
    public function test_search_result_heuristic_product(): void
    {
        $data = [
            'id' => 'ambiguous-123',
            'name' => 'Something',
            'price' => '999',  // Numeric string suggests Product
        ];

        $searchResult = SearchResult::fromArray($data);

        $this->assertInstanceOf(Product::class, $searchResult->result);
        $this->assertSame('Something', $searchResult->result->name);
        $this->assertSame(999.0, $searchResult->result->price);
    }

    public function test_search_result_fallback_to_user(): void
    {
        $data = [
            'id' => 'fallback-456',
            'name' => 'Ambiguous Name',
            // No unique identifiers - should default to User
        ];

        $searchResult = SearchResult::fromArray($data);

        $this->assertInstanceOf(User::class, $searchResult->result);
        $this->assertSame('Ambiguous Name', $searchResult->result->name);
    }

    public function test_search_result_invalid_data(): void
    {
        $data = [
            'invalid' => 'data',
            // Missing required id and name
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to determine SearchResult type from data');
        SearchResult::fromArray($data);
    }

    // Test DatabaseConfig string|object union
    public function test_database_config_from_string(): void
    {
        $connectionString = 'mysql://user:pass@localhost:3306/database';

        $config = DatabaseConfig::fromString($connectionString);

        $this->assertTrue($config->isConnectionString());
        $this->assertFalse($config->isConnectionObject());
        $this->assertSame($connectionString, $config->config);
    }

    public function test_database_config_from_connection(): void
    {
        $connection = new DatabaseConnection(
            host: 'localhost',
            database: 'testdb',
            port: 5432,
            username: 'admin',
            password: 'secret'
        );

        $config = DatabaseConfig::fromConnection($connection);

        $this->assertFalse($config->isConnectionString());
        $this->assertTrue($config->isConnectionObject());
        $this->assertSame($connection, $config->config);
    }

    public function test_database_config_from_array_string(): void
    {
        $connectionString = 'postgresql://admin:secret@db.example.com:5432/myapp';

        $config = DatabaseConfig::fromArray($connectionString);

        $this->assertTrue($config->isConnectionString());
        $this->assertSame($connectionString, $config->config);
    }

    public function test_database_config_from_array_object(): void
    {
        $data = [
            'host' => 'db.example.com',
            'database' => 'production',
            'port' => 3306,
            'username' => 'api_user',
            'password' => 'secure_password',
        ];

        $config = DatabaseConfig::fromArray($data);

        $this->assertTrue($config->isConnectionObject());
        $this->assertInstanceOf(DatabaseConnection::class, $config->config);
        $this->assertSame('db.example.com', $config->config->host);
        $this->assertSame('production', $config->config->database);
        $this->assertSame(3306, $config->config->port);
    }

    // Test NotificationContent string|object union
    public function test_notification_content_from_string(): void
    {
        $plainText = 'Your order has been shipped!';

        $content = NotificationContent::fromString($plainText);

        $this->assertTrue($content->isPlainText());
        $this->assertFalse($content->isRichContent());
        $this->assertSame($plainText, $content->content);
    }

    public function test_notification_content_from_rich_content(): void
    {
        $richContent = new RichNotificationContent(
            title: 'Order Update',
            body: 'Your order #12345 has been shipped',
            actions: [
                new NotificationAction(
                    label: 'Track Package',
                    url: 'https://tracking.example.com/12345'
                ),
            ]
        );

        $content = NotificationContent::fromRichContent($richContent);

        $this->assertFalse($content->isPlainText());
        $this->assertTrue($content->isRichContent());
        $this->assertSame($richContent, $content->content);
    }

    public function test_notification_content_from_array_string(): void
    {
        $plainText = 'Simple notification message';

        $content = NotificationContent::fromArray($plainText);

        $this->assertTrue($content->isPlainText());
        $this->assertSame($plainText, $content->content);
    }

    public function test_notification_content_from_array_object(): void
    {
        $data = [
            'title' => 'Important Update',
            'body' => 'Please review your account settings',
            'actions' => [
                [
                    'label' => 'Review Settings',
                    'url' => 'https://app.example.com/settings',
                ],
                [
                    'label' => 'Contact Support',
                    'url' => 'https://support.example.com',
                ],
            ],
        ];

        $content = NotificationContent::fromArray($data);

        $this->assertTrue($content->isRichContent());
        $this->assertInstanceOf(RichNotificationContent::class, $content->content);
        $this->assertSame('Important Update', $content->content->title);
        $this->assertSame('Please review your account settings', $content->content->body);
        $this->assertCount(2, $content->content->actions);
        $this->assertSame('Review Settings', $content->content->actions[0]->label);
        $this->assertSame('Contact Support', $content->content->actions[1]->label);
    }

    // Test RichNotificationContent and NotificationAction
    public function test_rich_notification_content_from_array(): void
    {
        $data = [
            'title' => 'Welcome!',
            'body' => 'Thanks for joining our platform',
            'actions' => [
                [
                    'label' => 'Get Started',
                    'url' => 'https://app.example.com/onboarding',
                ],
            ],
        ];

        $richContent = RichNotificationContent::fromArray($data);

        $this->assertSame('Welcome!', $richContent->title);
        $this->assertSame('Thanks for joining our platform', $richContent->body);
        $this->assertCount(1, $richContent->actions);
        $this->assertSame('Get Started', $richContent->actions[0]->label);
        $this->assertSame('https://app.example.com/onboarding', $richContent->actions[0]->url);
    }

    public function test_rich_notification_content_empty_actions(): void
    {
        $data = [
            'title' => 'Simple Message',
            'body' => 'No actions required',
        ];

        $richContent = RichNotificationContent::fromArray($data);

        $this->assertSame('Simple Message', $richContent->title);
        $this->assertSame('No actions required', $richContent->body);
        $this->assertSame([], $richContent->actions);
    }

    public function test_notification_action_from_array(): void
    {
        $data = [
            'label' => 'View Details',
            'url' => 'https://example.com/details/123',
        ];

        $action = NotificationAction::fromArray($data);

        $this->assertSame('View Details', $action->label);
        $this->assertSame('https://example.com/details/123', $action->url);
    }

    // Type conversion tests
    public function test_type_conversion_in_ambiguous_unions(): void
    {
        // Test numeric string conversion in Product
        $data = [
            'id' => 456,  // number to string
            'name' => 'Test Product',
            'price' => '123.45',  // string to float
            'category' => 'Test',
        ];

        $searchResult = SearchResult::fromArray($data);
        $this->assertInstanceOf(Product::class, $searchResult->result);
        $this->assertSame('456', $searchResult->result->id);
        $this->assertSame(123.45, $searchResult->result->price);
    }

    // Edge case tests
    public function test_minimal_product_data(): void
    {
        $data = [
            'id' => 'minimal-prod',
            'name' => 'Minimal Product',
            'price' => 0,  // Unique identifier for Product
        ];

        $searchResult = SearchResult::fromArray($data);

        $this->assertInstanceOf(Product::class, $searchResult->result);
        $this->assertSame('Minimal Product', $searchResult->result->name);
        $this->assertSame(0.0, $searchResult->result->price);
        $this->assertNull($searchResult->result->description);
        $this->assertNull($searchResult->result->category);
    }

    public function test_minimal_database_connection(): void
    {
        $data = [
            'host' => 'localhost',
            'database' => 'test',
        ];

        $connection = DatabaseConnection::fromArray($data);

        $this->assertSame('localhost', $connection->host);
        $this->assertSame('test', $connection->database);
        $this->assertNull($connection->port);
        $this->assertNull($connection->username);
        $this->assertNull($connection->password);
    }
}
