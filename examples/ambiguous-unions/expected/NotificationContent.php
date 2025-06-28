<?php

declare(strict_types=1);

readonly class NotificationContent
{
    public function __construct(
        public string|RichNotificationContent $content
    ) {}

    public static function fromString(string $text): self
    {
        return new self($text);
    }

    public static function fromRichContent(RichNotificationContent $richContent): self
    {
        return new self($richContent);
    }

    public static function fromArray(array|string $data): self
    {
        if (is_string($data)) {
            return self::fromString($data);
        }

        return self::fromRichContent(RichNotificationContent::fromArray($data));
    }

    public function isPlainText(): bool
    {
        return is_string($this->content);
    }

    public function isRichContent(): bool
    {
        return $this->content instanceof RichNotificationContent;
    }
}
