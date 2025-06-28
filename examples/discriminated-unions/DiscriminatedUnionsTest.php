<?php

declare(strict_types=1);

require_once __DIR__.'/expected/Event.php';
require_once __DIR__.'/expected/UserEvent.php';
require_once __DIR__.'/expected/SystemEvent.php';
require_once __DIR__.'/expected/PaymentEvent.php';
require_once __DIR__.'/expected/EventResponse.php';

use PHPUnit\Framework\TestCase;

class DiscriminatedUnionsTest extends TestCase
{
    public function test_user_event_from_array(): void
    {
        $json = [
            'type' => 'user',
            'userId' => '550e8400-e29b-41d4-a716-446655440000',
            'action' => 'login',
            'metadata' => [
                'ip' => '192.168.1.1',
                'userAgent' => 'Mozilla/5.0',
            ],
        ];

        $event = UserEvent::fromArray($json);

        $this->assertSame('user', $event->type);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $event->userId);
        $this->assertSame('login', $event->action);
        $this->assertSame([
            'ip' => '192.168.1.1',
            'userAgent' => 'Mozilla/5.0',
        ], $event->metadata);
    }

    public function test_user_event_from_array_without_metadata(): void
    {
        $json = [
            'type' => 'user',
            'userId' => '123e4567-e89b-12d3-a456-426614174000',
            'action' => 'signup',
        ];

        $event = UserEvent::fromArray($json);

        $this->assertSame('user', $event->type);
        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $event->userId);
        $this->assertSame('signup', $event->action);
        $this->assertSame([], $event->metadata);
    }

    public function test_system_event_from_array(): void
    {
        $json = [
            'type' => 'system',
            'component' => 'database',
            'level' => 'error',
            'message' => 'Connection timeout',
            'stackTrace' => [
                'at DatabaseConnection.connect()',
                'at Application.start()',
            ],
        ];

        $event = SystemEvent::fromArray($json);

        $this->assertSame('system', $event->type);
        $this->assertSame('database', $event->component);
        $this->assertSame('error', $event->level);
        $this->assertSame('Connection timeout', $event->message);
        $this->assertSame([
            'at DatabaseConnection.connect()',
            'at Application.start()',
        ], $event->stackTrace);
    }

    public function test_system_event_from_array_minimal(): void
    {
        $json = [
            'type' => 'system',
            'component' => 'auth',
            'level' => 'info',
        ];

        $event = SystemEvent::fromArray($json);

        $this->assertSame('system', $event->type);
        $this->assertSame('auth', $event->component);
        $this->assertSame('info', $event->level);
        $this->assertNull($event->message);
        $this->assertSame([], $event->stackTrace);
    }

    public function test_payment_event_from_array(): void
    {
        $json = [
            'type' => 'payment',
            'transactionId' => 'txn_1234567890',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'completed',
        ];

        $event = PaymentEvent::fromArray($json);

        $this->assertSame('payment', $event->type);
        $this->assertSame('txn_1234567890', $event->transactionId);
        $this->assertSame(99.99, $event->amount);
        $this->assertSame('USD', $event->currency);
        $this->assertSame('completed', $event->status);
    }

    public function test_payment_event_from_array_with_string_amount(): void
    {
        $json = [
            'type' => 'payment',
            'transactionId' => 'txn_0987654321',
            'amount' => '150.75',  // String amount should be converted to float
        ];

        $event = PaymentEvent::fromArray($json);

        $this->assertSame('payment', $event->type);
        $this->assertSame('txn_0987654321', $event->transactionId);
        $this->assertSame(150.75, $event->amount);
        $this->assertNull($event->currency);
        $this->assertNull($event->status);
    }

    public function test_event_from_array_discriminates_user_event(): void
    {
        $json = [
            'type' => 'user',
            'userId' => '987fcdeb-51a2-43d1-9f12-5678901234ab',
            'action' => 'logout',
        ];

        $event = Event::fromArray($json);

        $this->assertInstanceOf(UserEvent::class, $event);
        $this->assertSame('user', $event->type);
        $this->assertSame('987fcdeb-51a2-43d1-9f12-5678901234ab', $event->userId);
        $this->assertSame('logout', $event->action);
    }

    public function test_event_from_array_discriminates_system_event(): void
    {
        $json = [
            'type' => 'system',
            'component' => 'cache',
            'level' => 'warning',
            'message' => 'Cache miss rate high',
        ];

        $event = Event::fromArray($json);

        $this->assertInstanceOf(SystemEvent::class, $event);
        $this->assertSame('system', $event->type);
        $this->assertSame('cache', $event->component);
        $this->assertSame('warning', $event->level);
        $this->assertSame('Cache miss rate high', $event->message);
    }

    public function test_event_from_array_discriminates_payment_event(): void
    {
        $json = [
            'type' => 'payment',
            'transactionId' => 'txn_abc123def456',
            'amount' => 299.50,
            'currency' => 'EUR',
        ];

        $event = Event::fromArray($json);

        $this->assertInstanceOf(PaymentEvent::class, $event);
        $this->assertSame('payment', $event->type);
        $this->assertSame('txn_abc123def456', $event->transactionId);
        $this->assertSame(299.50, $event->amount);
        $this->assertSame('EUR', $event->currency);
    }

    public function test_event_from_array_throws_on_unknown_type(): void
    {
        $json = [
            'type' => 'unknown',
            'someProperty' => 'value',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown event type: unknown');

        Event::fromArray($json);
    }

    public function test_event_response_from_array(): void
    {
        $json = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'status' => 'processed',
            'event' => [
                'type' => 'user',
                'userId' => '987fcdeb-51a2-43d1-9f12-5678901234ab',
                'action' => 'login',
                'metadata' => [
                    'source' => 'mobile_app',
                ],
            ],
        ];

        $response = EventResponse::fromArray($json);

        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $response->id);
        $this->assertSame('processed', $response->status);
        $this->assertInstanceOf(UserEvent::class, $response->event);
        $this->assertSame('user', $response->event->type);
        $this->assertSame('987fcdeb-51a2-43d1-9f12-5678901234ab', $response->event->userId);
        $this->assertSame('login', $response->event->action);
        $this->assertSame(['source' => 'mobile_app'], $response->event->metadata);
    }

    public function test_event_response_from_array_with_system_event(): void
    {
        $json = [
            'id' => 'evt_789',
            'status' => 'pending',
            'event' => [
                'type' => 'system',
                'component' => 'logger',
                'level' => 'critical',
                'message' => 'Disk space low',
                'stackTrace' => ['Logger.write()', 'System.check()'],
            ],
        ];

        $response = EventResponse::fromArray($json);

        $this->assertSame('evt_789', $response->id);
        $this->assertSame('pending', $response->status);
        $this->assertInstanceOf(SystemEvent::class, $response->event);
        $this->assertSame('system', $response->event->type);
        $this->assertSame('logger', $response->event->component);
        $this->assertSame('critical', $response->event->level);
        $this->assertSame('Disk space low', $response->event->message);
    }

    public function test_event_response_from_array_minimal(): void
    {
        $json = [
            'id' => 'evt_minimal',
        ];

        $response = EventResponse::fromArray($json);

        $this->assertSame('evt_minimal', $response->id);
        $this->assertNull($response->status);
        $this->assertNull($response->event);
    }

    public function test_event_response_from_array_without_event(): void
    {
        $json = [
            'id' => 'evt_no_event',
            'status' => 'failed',
        ];

        $response = EventResponse::fromArray($json);

        $this->assertSame('evt_no_event', $response->id);
        $this->assertSame('failed', $response->status);
        $this->assertNull($response->event);
    }

    public function test_discriminator_inheritance_structure(): void
    {
        // Test that all event types properly inherit from Event
        $userEvent = new UserEvent('user123', 'login');
        $systemEvent = new SystemEvent('api', 'info');
        $paymentEvent = new PaymentEvent('txn123', 100.0);

        $this->assertInstanceOf(Event::class, $userEvent);
        $this->assertInstanceOf(Event::class, $systemEvent);
        $this->assertInstanceOf(Event::class, $paymentEvent);

        $this->assertSame('user', $userEvent->type);
        $this->assertSame('system', $systemEvent->type);
        $this->assertSame('payment', $paymentEvent->type);
    }
}
