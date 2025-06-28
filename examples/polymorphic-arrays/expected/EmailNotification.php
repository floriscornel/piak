<?php

declare(strict_types=1);

readonly class EmailNotification
{
    public function __construct(
        public string $type,
        public string $recipient,
        public ?string $subject = null,
        public ?string $template = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Validate required fields
        if (! isset($data['type']) || ! is_string($data['type'])) {
            throw new \InvalidArgumentException('EmailNotification type must be a string');
        }
        if (! isset($data['recipient']) || ! is_string($data['recipient'])) {
            throw new \InvalidArgumentException('EmailNotification recipient must be a string');
        }

        // Handle optional fields
        $subject = null;
        if (isset($data['subject'])) {
            if (! is_string($data['subject'])) {
                throw new \InvalidArgumentException('EmailNotification subject must be a string');
            }
            $subject = $data['subject'];
        }

        $template = null;
        if (isset($data['template'])) {
            if (! is_string($data['template'])) {
                throw new \InvalidArgumentException('EmailNotification template must be a string');
            }
            $template = $data['template'];
        }

        return new self(
            type: $data['type'],
            recipient: $data['recipient'],
            subject: $subject,
            template: $template
        );
    }
}
