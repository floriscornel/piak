<?php

declare(strict_types=1);

readonly class NotificationRequest
{
    public function __construct(
        public TextMessage|RichMessage $message,
        public ?string $priority = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Type detection for oneOf union: TextMessage vs RichMessage
        $messageData = $data['message'];
        $message = self::detectMessageType($messageData);

        return new self(
            message: $message,
            priority: $data['priority'] ?? null
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function detectMessageType(array $data): TextMessage|RichMessage
    {
        // RichMessage has 'html' property, TextMessage has 'content'
        if (isset($data['html'])) {
            return RichMessage::fromArray($data);
        } elseif (isset($data['content'])) {
            return TextMessage::fromArray($data);
        }

        // Fallback: try to determine based on available properties
        throw new \InvalidArgumentException('Cannot determine message type from data');
    }
}
