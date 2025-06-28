<?php

declare(strict_types=1);

readonly class TextMessage
{
    public function __construct(
        public string $content,
        public string $encoding = 'utf8'
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            content: $data['content'],
            encoding: $data['encoding'] ?? 'utf8'
        );
    }
}
