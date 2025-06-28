<?php

declare(strict_types=1);

readonly class Timeline
{
    public function __construct(
        /** @var array<MessageEvent|FileEvent|SystemEvent> */
        public array $events = [],
        /** @var array<EmailNotification|PushNotification|SMSNotification> */
        public array $notifications = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $events = [];
        $eventsData = $data['events'] ?? [];
        if (is_array($eventsData)) {
            foreach ($eventsData as $eventData) {
                if (! is_array($eventData) || ! isset($eventData['type']) || ! is_string($eventData['type'])) {
                    throw new \InvalidArgumentException('Event data must be an array with a string type field');
                }

                $events[] = match ($eventData['type']) {
                    'message' => MessageEvent::fromArray($eventData),
                    'file' => FileEvent::fromArray($eventData),
                    'system' => SystemEvent::fromArray($eventData),
                    default => throw new \InvalidArgumentException("Unknown event type: {$eventData['type']}")
                };
            }
        }

        $notifications = [];
        $notificationsData = $data['notifications'] ?? [];
        if (is_array($notificationsData)) {
            foreach ($notificationsData as $notificationData) {
                if (! is_array($notificationData) || ! isset($notificationData['type']) || ! is_string($notificationData['type'])) {
                    throw new \InvalidArgumentException('Notification data must be an array with a string type field');
                }

                $notifications[] = match ($notificationData['type']) {
                    'email' => EmailNotification::fromArray($notificationData),
                    'push' => PushNotification::fromArray($notificationData),
                    'sms' => SMSNotification::fromArray($notificationData),
                    default => throw new \InvalidArgumentException("Unknown notification type: {$notificationData['type']}")
                };
            }
        }

        return new self(events: $events, notifications: $notifications);
    }
}
