<?php

declare(strict_types=1);

readonly class ContentRequest
{
    /**
     * @param  array<string|AttachmentDetail>  $attachments
     * @param  array<string|TagObject>  $tags
     */
    public function __construct(
        public string $title,
        public string|RichContent $body,
        public null|string|MetadataObject $metadata = null,
        public array $attachments = [],
        public array $tags = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['title']) || (! is_string($data['title']) && ! is_scalar($data['title']))) {
            throw new \InvalidArgumentException('title must be a string');
        }
        if (! isset($data['body'])) {
            throw new \InvalidArgumentException('body is required');
        }

        return new self(
            title: is_string($data['title']) ? $data['title'] : (string) $data['title'],
            body: self::parseBody($data['body']),
            metadata: self::parseMetadata($data['metadata'] ?? null),
            attachments: self::parseAttachments($data['attachments'] ?? []),
            tags: self::parseTags($data['tags'] ?? [])
        );
    }

    private static function parseBody(mixed $body): string|RichContent
    {
        return match (true) {
            is_string($body) => $body,
            is_array($body) && isset($body['type'], $body['data']) => RichContent::fromArray($body),
            default => throw new \InvalidArgumentException('Body must be string or RichContent object')
        };
    }

    private static function parseMetadata(mixed $metadata): string|MetadataObject|null
    {
        return match (true) {
            $metadata === null => null,
            is_string($metadata) => $metadata,
            is_array($metadata) => MetadataObject::fromArray($metadata),
            default => throw new \InvalidArgumentException('Metadata must be string or MetadataObject')
        };
    }

    /**
     * @return array<string|AttachmentDetail>
     */
    private static function parseAttachments(mixed $attachments): array
    {
        if (! is_array($attachments)) {
            return [];
        }

        $result = [];
        foreach ($attachments as $attachment) {
            $result[] = match (true) {
                is_string($attachment) => $attachment,
                is_array($attachment) => AttachmentDetail::fromArray($attachment),
                default => throw new \InvalidArgumentException('Attachment must be string or AttachmentDetail object')
            };
        }

        return $result;
    }

    /**
     * @return array<string|TagObject>
     */
    private static function parseTags(mixed $tags): array
    {
        if (! is_array($tags) || empty($tags)) {
            return [];
        }

        $result = [];
        foreach ($tags as $tag) {
            $result[] = match (true) {
                is_string($tag) => $tag,
                is_array($tag) => TagObject::fromArray($tag),
                default => throw new \InvalidArgumentException('Tag must be string or TagObject')
            };
        }

        return $result;
    }

    public function isRichContent(): bool
    {
        return $this->body instanceof RichContent;
    }

    public function hasStructuredMetadata(): bool
    {
        return $this->metadata instanceof MetadataObject;
    }

    public function hasDetailedAttachments(): bool
    {
        return ! empty($this->attachments) && $this->attachments[0] instanceof AttachmentDetail;
    }

    public function hasStructuredTags(): bool
    {
        return ! empty($this->tags) && $this->tags[0] instanceof TagObject;
    }

    // Helper methods for tests
    public function isBodyString(): bool
    {
        return is_string($this->body);
    }

    public function getBodyString(): string
    {
        if (! $this->isBodyString()) {
            throw new \InvalidArgumentException('Body is not a string');
        }

        return $this->body;
    }

    public function isBodyObject(): bool
    {
        return $this->body instanceof RichContent;
    }

    public function getBodyObject(): RichContent
    {
        if (! $this->isBodyObject()) {
            throw new \InvalidArgumentException('Body is not a RichContent object');
        }

        return $this->body;
    }

    public function isMetadataString(): bool
    {
        return is_string($this->metadata);
    }

    public function getMetadataString(): string
    {
        if (! $this->isMetadataString()) {
            throw new \InvalidArgumentException('Metadata is not a string');
        }

        return $this->metadata;
    }

    public function isMetadataObject(): bool
    {
        return $this->metadata instanceof MetadataObject;
    }

    public function getMetadataObject(): MetadataObject
    {
        if (! $this->isMetadataObject()) {
            throw new \InvalidArgumentException('Metadata is not a MetadataObject');
        }

        return $this->metadata;
    }
}
