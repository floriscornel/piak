<?php

declare(strict_types=1);

readonly class Comment
{
    public function __construct(
        public string $id,
        public string $content,
        public string $author,
        public ?string $timestamp = null,
        public ?string $parentId = null,     // Break recursion with ID reference
        /** @var string[] */
        public array $replyIds = [],         // Array of reply IDs instead of objects
        public ?int $level = null
    ) {}

    /**
     * Factory method to create from array data
     * Uses ID references to prevent infinite recursion in threaded comments
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Extract parent ID instead of creating parent object (prevents recursion)
        $parentId = null;
        if (isset($data['parent']) && is_array($data['parent'])) {
            $parentId = $data['parent']['id'] ?? null;
        } elseif (isset($data['parentId'])) {
            $parentId = $data['parentId'];
        }

        // Extract reply IDs instead of creating reply objects (prevents recursion)
        $replyIds = [];
        if (isset($data['replies']) && is_array($data['replies'])) {
            foreach ($data['replies'] as $reply) {
                if (is_array($reply) && isset($reply['id'])) {
                    $replyIds[] = is_string($reply['id']) ? $reply['id'] : (string) $reply['id'];
                } elseif (is_string($reply)) {
                    $replyIds[] = $reply;
                }
            }
        } elseif (isset($data['replyIds']) && is_array($data['replyIds'])) {
            foreach ($data['replyIds'] as $id) {
                $replyIds[] = is_string($id) ? $id : (string) $id;
            }
        }

        return new self(
            id: is_string($data['id']) ? $data['id'] : (string) $data['id'],
            content: is_string($data['content']) ? $data['content'] : (string) $data['content'],
            author: is_string($data['author']) ? $data['author'] : (string) $data['author'],
            timestamp: isset($data['timestamp']) ? (is_string($data['timestamp']) ? $data['timestamp'] : (string) $data['timestamp']) : null,
            parentId: $parentId,
            replyIds: $replyIds,
            level: isset($data['level']) ? (is_int($data['level']) ? $data['level'] : (int) $data['level']) : null
        );
    }

    /**
     * Get all reply IDs as flat array
     *
     * @return string[]
     */
    public function getAllReplyIds(): array
    {
        return $this->replyIds;
    }

    /**
     * Check if this is a top-level comment (no parent)
     */
    public function isTopLevel(): bool
    {
        return $this->parentId === null;
    }

    /**
     * Check if this comment has replies
     */
    public function hasReplies(): bool
    {
        return ! empty($this->replyIds);
    }
}
