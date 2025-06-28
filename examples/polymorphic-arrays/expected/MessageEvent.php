<?php

declare(strict_types=1);

readonly class MessageEvent
{
    public function __construct(
        public string $type,
        public string $timestamp,
        public string $message,
        public ?string $author = null,
        /** @var string[] */
        public array $mentions = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Validate required fields
        if (! isset($data['type']) || ! is_string($data['type'])) {
            throw new \InvalidArgumentException('MessageEvent type must be a string');
        }
        if (! isset($data['timestamp']) || ! is_string($data['timestamp'])) {
            throw new \InvalidArgumentException('MessageEvent timestamp must be a string');
        }
        if (! isset($data['message']) || ! is_string($data['message'])) {
            throw new \InvalidArgumentException('MessageEvent message must be a string');
        }

        // Handle optional author
        $author = null;
        if (isset($data['author'])) {
            if (! is_string($data['author'])) {
                throw new \InvalidArgumentException('MessageEvent author must be a string');
            }
            $author = $data['author'];
        }

        // Handle mentions array
        $mentions = [];
        if (isset($data['mentions'])) {
            if (! is_array($data['mentions'])) {
                throw new \InvalidArgumentException('MessageEvent mentions must be an array');
            }
            foreach ($data['mentions'] as $mention) {
                if (! is_string($mention)) {
                    throw new \InvalidArgumentException('MessageEvent mention must be a string');
                }
                $mentions[] = $mention;
            }
        }

        return new self(
            type: $data['type'],
            timestamp: $data['timestamp'],
            message: $data['message'],
            author: $author,
            mentions: $mentions
        );
    }
}
