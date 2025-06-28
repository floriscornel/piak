<?php

declare(strict_types=1);

require_once __DIR__.'/expected/TextMessage.php';
require_once __DIR__.'/expected/RichMessage.php';
require_once __DIR__.'/expected/EmailDelivery.php';
require_once __DIR__.'/expected/SmsDelivery.php';
require_once __DIR__.'/expected/PushDelivery.php';
require_once __DIR__.'/expected/NotificationRequest.php';
require_once __DIR__.'/expected/NotificationResponse.php';

use PHPUnit\Framework\TestCase;

class UnionTypesTest extends TestCase
{
    public function test_text_message_from_array(): void
    {
        $json = [
            'content' => 'Hello, World!',
            'encoding' => 'utf8',
        ];

        $textMessage = TextMessage::fromArray($json);

        $this->assertSame('Hello, World!', $textMessage->content);
        $this->assertSame('utf8', $textMessage->encoding);
    }

    public function test_text_message_from_array_with_default_encoding(): void
    {
        $json = [
            'content' => 'Hello without encoding',
        ];

        $textMessage = TextMessage::fromArray($json);

        $this->assertSame('Hello without encoding', $textMessage->content);
        $this->assertSame('utf8', $textMessage->encoding); // Default value
    }

    public function test_rich_message_from_array(): void
    {
        $json = [
            'html' => '<p>Rich <strong>content</strong></p>',
            'attachments' => ['file1.pdf', 'image.jpg'],
        ];

        $richMessage = RichMessage::fromArray($json);

        $this->assertSame('<p>Rich <strong>content</strong></p>', $richMessage->html);
        $this->assertSame(['file1.pdf', 'image.jpg'], $richMessage->attachments);
    }

    public function test_rich_message_from_array_without_attachments(): void
    {
        $json = [
            'html' => '<h1>No attachments</h1>',
        ];

        $richMessage = RichMessage::fromArray($json);

        $this->assertSame('<h1>No attachments</h1>', $richMessage->html);
        $this->assertSame([], $richMessage->attachments);
    }

    public function test_notification_request_from_array_with_text_message(): void
    {
        $json = [
            'message' => [
                'content' => 'Simple text notification',
                'encoding' => 'utf8',
            ],
            'priority' => 'high',
        ];

        $request = NotificationRequest::fromArray($json);

        $this->assertInstanceOf(TextMessage::class, $request->message);
        $this->assertSame('Simple text notification', $request->message->content);
        $this->assertSame('utf8', $request->message->encoding);
        $this->assertSame('high', $request->priority);
    }

    public function test_notification_request_from_array_with_rich_message(): void
    {
        $json = [
            'message' => [
                'html' => '<div>Rich notification</div>',
                'attachments' => ['doc.pdf'],
            ],
            'priority' => 'normal',
        ];

        $request = NotificationRequest::fromArray($json);

        $this->assertInstanceOf(RichMessage::class, $request->message);
        $this->assertSame('<div>Rich notification</div>', $request->message->html);
        $this->assertSame(['doc.pdf'], $request->message->attachments);
        $this->assertSame('normal', $request->priority);
    }

    public function test_notification_request_from_array_without_priority(): void
    {
        $json = [
            'message' => [
                'content' => 'No priority message',
            ],
        ];

        $request = NotificationRequest::fromArray($json);

        $this->assertInstanceOf(TextMessage::class, $request->message);
        $this->assertNull($request->priority);
    }

    public function test_notification_request_from_array_throws_on_ambiguous_message(): void
    {
        $json = [
            'message' => [
                'neither' => 'content nor html',
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot determine message type from data');

        NotificationRequest::fromArray($json);
    }

    public function test_email_delivery_from_array(): void
    {
        $json = [
            'email' => 'user@example.com',
            'messageId' => 'msg-123',
        ];

        $delivery = EmailDelivery::fromArray($json);

        $this->assertSame('user@example.com', $delivery->email);
        $this->assertSame('msg-123', $delivery->messageId);
    }

    public function test_sms_delivery_from_array(): void
    {
        $json = [
            'phoneNumber' => '+1234567890',
            'carrier' => 'Verizon',
        ];

        $delivery = SmsDelivery::fromArray($json);

        $this->assertSame('+1234567890', $delivery->phoneNumber);
        $this->assertSame('Verizon', $delivery->carrier);
    }

    public function test_push_delivery_from_array(): void
    {
        $json = [
            'deviceToken' => 'abc123xyz',
            'platform' => 'ios',
        ];

        $delivery = PushDelivery::fromArray($json);

        $this->assertSame('abc123xyz', $delivery->deviceToken);
        $this->assertSame('ios', $delivery->platform);
    }

    public function test_anyof_delivery_from_array_single_email(): void
    {
        $json = [
            'email' => 'test@example.com',
            'messageId' => 'msg-456',
        ];

        $delivery = AnyOfDelivery::fromArray($json);

        $this->assertInstanceOf(EmailDelivery::class, $delivery->emailDelivery);
        $this->assertNull($delivery->smsDelivery);
        $this->assertNull($delivery->pushDelivery);
        $this->assertSame('test@example.com', $delivery->emailDelivery->email);
    }

    public function test_anyof_delivery_from_array_single_sms(): void
    {
        $json = [
            'phoneNumber' => '+9876543210',
            'carrier' => 'T-Mobile',
        ];

        $delivery = AnyOfDelivery::fromArray($json);

        $this->assertNull($delivery->emailDelivery);
        $this->assertInstanceOf(SmsDelivery::class, $delivery->smsDelivery);
        $this->assertNull($delivery->pushDelivery);
        $this->assertSame('+9876543210', $delivery->smsDelivery->phoneNumber);
    }

    public function test_anyof_delivery_from_array_multiple_matches(): void
    {
        // Data that matches both email and SMS schemas
        $json = [
            'email' => 'user@example.com',
            'messageId' => 'msg-789',
            'phoneNumber' => '+1111111111',
            'carrier' => 'AT&T',
        ];

        $delivery = AnyOfDelivery::fromArray($json);

        // Both should be populated since data matches both schemas
        $this->assertInstanceOf(EmailDelivery::class, $delivery->emailDelivery);
        $this->assertInstanceOf(SmsDelivery::class, $delivery->smsDelivery);
        $this->assertNull($delivery->pushDelivery);

        $this->assertSame('user@example.com', $delivery->emailDelivery->email);
        $this->assertSame('msg-789', $delivery->emailDelivery->messageId);
        $this->assertSame('+1111111111', $delivery->smsDelivery->phoneNumber);
        $this->assertSame('AT&T', $delivery->smsDelivery->carrier);
    }

    public function test_anyof_delivery_from_array_all_three_matches(): void
    {
        // Data that matches all three schemas
        $json = [
            'email' => 'all@example.com',
            'messageId' => 'msg-all',
            'phoneNumber' => '+5555555555',
            'carrier' => 'Sprint',
            'deviceToken' => 'token123',
            'platform' => 'android',
        ];

        $delivery = AnyOfDelivery::fromArray($json);

        // All three should be populated
        $this->assertInstanceOf(EmailDelivery::class, $delivery->emailDelivery);
        $this->assertInstanceOf(SmsDelivery::class, $delivery->smsDelivery);
        $this->assertInstanceOf(PushDelivery::class, $delivery->pushDelivery);

        $this->assertSame('all@example.com', $delivery->emailDelivery->email);
        $this->assertSame('+5555555555', $delivery->smsDelivery->phoneNumber);
        $this->assertSame('token123', $delivery->pushDelivery->deviceToken);
    }

    public function test_anyof_delivery_from_array_throws_on_no_matches(): void
    {
        $json = [
            'invalid' => 'data',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data does not match any delivery type schemas');

        AnyOfDelivery::fromArray($json);
    }

    public function test_anyof_delivery_get_delivery_returns_first_available(): void
    {
        $json = [
            'phoneNumber' => '+2222222222',
            'deviceToken' => 'token456',
        ];

        $delivery = AnyOfDelivery::fromArray($json);

        // Should return the first non-null delivery (SMS in this case)
        $firstDelivery = $delivery->getDelivery();
        $this->assertInstanceOf(SmsDelivery::class, $firstDelivery);
        $this->assertSame('+2222222222', $firstDelivery->phoneNumber);
    }

    public function test_notification_response_from_array(): void
    {
        $json = [
            'id' => 'notification-123',
            'delivery' => [
                'email' => 'response@example.com',
                'messageId' => 'response-msg',
            ],
        ];

        $response = NotificationResponse::fromArray($json);

        $this->assertSame('notification-123', $response->id);
        $this->assertInstanceOf(AnyOfDelivery::class, $response->delivery);
        $this->assertInstanceOf(EmailDelivery::class, $response->delivery->emailDelivery);
        $this->assertSame('response@example.com', $response->delivery->emailDelivery->email);
    }

    public function test_notification_response_from_array_without_delivery(): void
    {
        $json = [
            'id' => 'notification-456',
        ];

        $response = NotificationResponse::fromArray($json);

        $this->assertSame('notification-456', $response->id);
        $this->assertNull($response->delivery);
    }
}
