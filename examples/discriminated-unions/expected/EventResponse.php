<?php

declare(strict_types=1);

readonly class EventResponse
{
    public function __construct(
        public ?string $id = null,
        public ?string $status = null,
        public ?Event $event = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            status: $data['status'] ?? null,
            event: isset($data['event']) ? Event::fromArray($data['event']) : null
        );
    }
}
