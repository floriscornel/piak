<?php

declare(strict_types=1);

readonly class PushNotification
{
    public function __construct(
        public string $type,
        public string $deviceToken,
        public ?string $title = null,
        public ?string $body = null,
        public ?int $badge = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Validate required fields
        if (! isset($data['type']) || ! is_string($data['type'])) {
            throw new \InvalidArgumentException('PushNotification type must be a string');
        }
        if (! isset($data['deviceToken']) || ! is_string($data['deviceToken'])) {
            throw new \InvalidArgumentException('PushNotification deviceToken must be a string');
        }

        // Handle optional fields
        $title = null;
        if (isset($data['title'])) {
            if (! is_string($data['title'])) {
                throw new \InvalidArgumentException('PushNotification title must be a string');
            }
            $title = $data['title'];
        }

        $body = null;
        if (isset($data['body'])) {
            if (! is_string($data['body'])) {
                throw new \InvalidArgumentException('PushNotification body must be a string');
            }
            $body = $data['body'];
        }

        $badge = null;
        if (isset($data['badge'])) {
            if (is_int($data['badge'])) {
                $badge = $data['badge'];
            } elseif (is_numeric($data['badge'])) {
                $badge = (int) $data['badge'];
            } else {
                throw new \InvalidArgumentException('PushNotification badge must be an integer');
            }
        }

        return new self(
            type: $data['type'],
            deviceToken: $data['deviceToken'],
            title: $title,
            body: $body,
            badge: $badge
        );
    }
}
