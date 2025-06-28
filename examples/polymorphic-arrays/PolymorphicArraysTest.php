<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once 'expected/MessageEvent.php';
require_once 'expected/FileEvent.php';
require_once 'expected/SystemEvent.php';
require_once 'expected/EmailNotification.php';
require_once 'expected/PushNotification.php';
require_once 'expected/SMSNotification.php';
require_once 'expected/Timeline.php';

class PolymorphicArraysTest extends TestCase
{
    // Test individual event classes
    public function test_message_event_from_array(): void
    {
        $data = [
            'type' => 'message',
            'timestamp' => '2023-01-01T10:00:00Z',
            'message' => 'Hello world!',
            'author' => 'john_doe',
            'mentions' => ['@alice', '@bob'],
        ];

        $event = MessageEvent::fromArray($data);

        $this->assertSame('message', $event->type);
        $this->assertSame('2023-01-01T10:00:00Z', $event->timestamp);
        $this->assertSame('Hello world!', $event->message);
        $this->assertSame('john_doe', $event->author);
        $this->assertSame(['@alice', '@bob'], $event->mentions);
    }

    public function test_file_event_from_array(): void
    {
        $data = [
            'type' => 'file',
            'timestamp' => '2023-01-01T11:30:00Z',
            'filename' => 'document.pdf',
            'fileSize' => 2048576,
            'uploadedBy' => 'alice_smith',
            'downloadUrl' => 'https://example.com/files/document.pdf',
        ];

        $event = FileEvent::fromArray($data);

        $this->assertSame('file', $event->type);
        $this->assertSame('2023-01-01T11:30:00Z', $event->timestamp);
        $this->assertSame('document.pdf', $event->filename);
        $this->assertSame(2048576, $event->fileSize);
        $this->assertSame('alice_smith', $event->uploadedBy);
        $this->assertSame('https://example.com/files/document.pdf', $event->downloadUrl);
    }

    public function test_system_event_from_array(): void
    {
        $data = [
            'type' => 'system',
            'timestamp' => '2023-01-01T12:15:00Z',
            'action' => 'user_login',
            'component' => 'auth_service',
            'severity' => 'info',
        ];

        $event = SystemEvent::fromArray($data);

        $this->assertSame('system', $event->type);
        $this->assertSame('2023-01-01T12:15:00Z', $event->timestamp);
        $this->assertSame('user_login', $event->action);
        $this->assertSame('auth_service', $event->component);
        $this->assertSame('info', $event->severity);
    }

    // Test individual notification classes
    public function test_email_notification_from_array(): void
    {
        $data = [
            'type' => 'email',
            'recipient' => 'user@example.com',
            'subject' => 'Welcome to our platform',
            'template' => 'welcome_template',
        ];

        $notification = EmailNotification::fromArray($data);

        $this->assertSame('email', $notification->type);
        $this->assertSame('user@example.com', $notification->recipient);
        $this->assertSame('Welcome to our platform', $notification->subject);
        $this->assertSame('welcome_template', $notification->template);
    }

    public function test_push_notification_from_array(): void
    {
        $data = [
            'type' => 'push',
            'deviceToken' => 'abc123def456',
            'title' => 'New Message',
            'body' => 'You have a new message waiting',
            'badge' => 5,
        ];

        $notification = PushNotification::fromArray($data);

        $this->assertSame('push', $notification->type);
        $this->assertSame('abc123def456', $notification->deviceToken);
        $this->assertSame('New Message', $notification->title);
        $this->assertSame('You have a new message waiting', $notification->body);
        $this->assertSame(5, $notification->badge);
    }

    public function test_sms_notification_from_array(): void
    {
        $data = [
            'type' => 'sms',
            'phoneNumber' => '+1234567890',
            'message' => 'Your verification code is 123456',
            'carrier' => 'Verizon',
        ];

        $notification = SMSNotification::fromArray($data);

        $this->assertSame('sms', $notification->type);
        $this->assertSame('+1234567890', $notification->phoneNumber);
        $this->assertSame('Your verification code is 123456', $notification->message);
        $this->assertSame('Verizon', $notification->carrier);
    }

    // Test polymorphic array processing in Timeline
    public function test_timeline_with_mixed_events(): void
    {
        $data = [
            'events' => [
                [
                    'type' => 'message',
                    'timestamp' => '2023-01-01T10:00:00Z',
                    'message' => 'Project started',
                    'author' => 'project_manager',
                ],
                [
                    'type' => 'file',
                    'timestamp' => '2023-01-01T10:30:00Z',
                    'filename' => 'requirements.doc',
                    'fileSize' => 51200,
                    'uploadedBy' => 'analyst',
                ],
                [
                    'type' => 'system',
                    'timestamp' => '2023-01-01T11:00:00Z',
                    'action' => 'backup_created',
                    'component' => 'storage_service',
                    'severity' => 'info',
                ],
            ],
        ];

        $timeline = Timeline::fromArray($data);

        $this->assertCount(3, $timeline->events);

        // Verify first event (MessageEvent)
        $this->assertInstanceOf(MessageEvent::class, $timeline->events[0]);
        $this->assertSame('message', $timeline->events[0]->type);
        $this->assertSame('Project started', $timeline->events[0]->message);

        // Verify second event (FileEvent)
        $this->assertInstanceOf(FileEvent::class, $timeline->events[1]);
        $this->assertSame('file', $timeline->events[1]->type);
        $this->assertSame('requirements.doc', $timeline->events[1]->filename);

        // Verify third event (SystemEvent)
        $this->assertInstanceOf(SystemEvent::class, $timeline->events[2]);
        $this->assertSame('system', $timeline->events[2]->type);
        $this->assertSame('backup_created', $timeline->events[2]->action);
    }

    public function test_timeline_with_mixed_notifications(): void
    {
        $data = [
            'notifications' => [
                [
                    'type' => 'email',
                    'recipient' => 'admin@example.com',
                    'subject' => 'System Alert',
                    'template' => 'alert_template',
                ],
                [
                    'type' => 'push',
                    'deviceToken' => 'mobile123',
                    'title' => 'Update Available',
                    'body' => 'A new version is available',
                ],
                [
                    'type' => 'sms',
                    'phoneNumber' => '+9876543210',
                    'message' => 'Critical system alert',
                ],
            ],
        ];

        $timeline = Timeline::fromArray($data);

        $this->assertCount(3, $timeline->notifications);

        // Verify first notification (EmailNotification)
        $this->assertInstanceOf(EmailNotification::class, $timeline->notifications[0]);
        $this->assertSame('email', $timeline->notifications[0]->type);
        $this->assertSame('admin@example.com', $timeline->notifications[0]->recipient);

        // Verify second notification (PushNotification)
        $this->assertInstanceOf(PushNotification::class, $timeline->notifications[1]);
        $this->assertSame('push', $timeline->notifications[1]->type);
        $this->assertSame('mobile123', $timeline->notifications[1]->deviceToken);

        // Verify third notification (SMSNotification)
        $this->assertInstanceOf(SMSNotification::class, $timeline->notifications[2]);
        $this->assertSame('sms', $timeline->notifications[2]->type);
        $this->assertSame('+9876543210', $timeline->notifications[2]->phoneNumber);
    }

    public function test_timeline_with_both_events_and_notifications(): void
    {
        $data = [
            'events' => [
                [
                    'type' => 'message',
                    'timestamp' => '2023-01-01T09:00:00Z',
                    'message' => 'Good morning team!',
                    'author' => 'team_lead',
                    'mentions' => ['@everyone'],
                ],
            ],
            'notifications' => [
                [
                    'type' => 'email',
                    'recipient' => 'team@example.com',
                    'subject' => 'Daily Standup Reminder',
                ],
            ],
        ];

        $timeline = Timeline::fromArray($data);

        $this->assertCount(1, $timeline->events);
        $this->assertCount(1, $timeline->notifications);

        $this->assertInstanceOf(MessageEvent::class, $timeline->events[0]);
        $this->assertSame('Good morning team!', $timeline->events[0]->message);
        $this->assertSame(['@everyone'], $timeline->events[0]->mentions);

        $this->assertInstanceOf(EmailNotification::class, $timeline->notifications[0]);
        $this->assertSame('Daily Standup Reminder', $timeline->notifications[0]->subject);
    }

    // Test empty arrays
    public function test_timeline_with_empty_arrays(): void
    {
        $data = [
            'events' => [],
            'notifications' => [],
        ];

        $timeline = Timeline::fromArray($data);

        $this->assertSame([], $timeline->events);
        $this->assertSame([], $timeline->notifications);
    }

    public function test_timeline_with_missing_arrays(): void
    {
        $data = [];

        $timeline = Timeline::fromArray($data);

        $this->assertSame([], $timeline->events);
        $this->assertSame([], $timeline->notifications);
    }

    // Test error handling
    public function test_timeline_with_unknown_event_type(): void
    {
        $data = [
            'events' => [
                [
                    'type' => 'unknown_event',
                    'timestamp' => '2023-01-01T10:00:00Z',
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown event type: unknown_event');
        Timeline::fromArray($data);
    }

    public function test_timeline_with_unknown_notification_type(): void
    {
        $data = [
            'notifications' => [
                [
                    'type' => 'unknown_notification',
                    'message' => 'test',
                ],
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown notification type: unknown_notification');
        Timeline::fromArray($data);
    }

    // Test optional fields
    public function test_event_with_optional_fields(): void
    {
        $data = [
            'type' => 'message',
            'timestamp' => '2023-01-01T10:00:00Z',
            'message' => 'Simple message',
            // No author or mentions
        ];

        $event = MessageEvent::fromArray($data);

        $this->assertSame('message', $event->type);
        $this->assertSame('Simple message', $event->message);
        $this->assertNull($event->author);
        $this->assertSame([], $event->mentions);
    }

    public function test_notification_with_optional_fields(): void
    {
        $data = [
            'type' => 'push',
            'deviceToken' => 'minimal123',
            // No title, body, or badge
        ];

        $notification = PushNotification::fromArray($data);

        $this->assertSame('push', $notification->type);
        $this->assertSame('minimal123', $notification->deviceToken);
        $this->assertNull($notification->title);
        $this->assertNull($notification->body);
        $this->assertNull($notification->badge);
    }

    // Test type conversion
    public function test_type_conversion_in_polymorphic_arrays(): void
    {
        $data = [
            'events' => [
                [
                    'type' => 'file',
                    'timestamp' => '2023-01-01T10:00:00Z',
                    'filename' => 'data.txt',
                    'fileSize' => '1024',  // string instead of int
                    'uploadedBy' => 123,    // number instead of string
                ],
            ],
        ];

        $timeline = Timeline::fromArray($data);

        $this->assertCount(1, $timeline->events);
        $fileEvent = $timeline->events[0];

        $this->assertInstanceOf(FileEvent::class, $fileEvent);
        $this->assertSame(1024, $fileEvent->fileSize);  // Converted to int
        $this->assertSame('123', $fileEvent->uploadedBy);  // Converted to string
    }

    // Test complex polymorphic scenario
    public function test_large_polymorphic_timeline(): void
    {
        $data = [
            'events' => [
                ['type' => 'message', 'timestamp' => '2023-01-01T09:00:00Z', 'message' => 'Start of day'],
                ['type' => 'system', 'timestamp' => '2023-01-01T09:05:00Z', 'action' => 'system_startup'],
                ['type' => 'file', 'timestamp' => '2023-01-01T09:10:00Z', 'filename' => 'report.pdf'],
                ['type' => 'message', 'timestamp' => '2023-01-01T09:15:00Z', 'message' => 'Report uploaded'],
                ['type' => 'system', 'timestamp' => '2023-01-01T09:20:00Z', 'action' => 'backup_started'],
            ],
            'notifications' => [
                ['type' => 'email', 'recipient' => 'user1@example.com', 'subject' => 'Daily report'],
                ['type' => 'push', 'deviceToken' => 'device1', 'title' => 'Report ready'],
                ['type' => 'sms', 'phoneNumber' => '+1111111111', 'message' => 'Check report'],
                ['type' => 'email', 'recipient' => 'user2@example.com', 'subject' => 'Backup started'],
            ],
        ];

        $timeline = Timeline::fromArray($data);

        $this->assertCount(5, $timeline->events);
        $this->assertCount(4, $timeline->notifications);

        // Verify the polymorphic nature
        $eventTypes = array_map(fn ($event) => get_class($event), $timeline->events);
        $this->assertSame([
            MessageEvent::class,
            SystemEvent::class,
            FileEvent::class,
            MessageEvent::class,
            SystemEvent::class,
        ], $eventTypes);

        $notificationTypes = array_map(fn ($notification) => get_class($notification), $timeline->notifications);
        $this->assertSame([
            EmailNotification::class,
            PushNotification::class,
            SMSNotification::class,
            EmailNotification::class,
        ], $notificationTypes);
    }
}
