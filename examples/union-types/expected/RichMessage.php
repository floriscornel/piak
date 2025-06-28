<?php

declare(strict_types=1);

readonly class RichMessage
{
    public function __construct(
        public string $html,
        /** @var string[] */
        public array $attachments = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            html: $data['html'],
            attachments: $data['attachments'] ?? []
        );
    }
}
