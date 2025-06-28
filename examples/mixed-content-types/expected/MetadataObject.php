<?php

declare(strict_types=1);

readonly class MetadataObject
{
    /**
     * @param  string[]  $keywords
     */
    public function __construct(
        public ?string $source = null,
        public ?string $created = null,
        public string|AuthorDetail|null $author = null,
        public array $keywords = [],
        public ?int $priority = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $keywords = [];
        if (isset($data['keywords']) && is_array($data['keywords'])) {
            foreach ($data['keywords'] as $keyword) {
                if (is_string($keyword) || is_scalar($keyword)) {
                    $keywords[] = (string) $keyword;
                }
            }
        }

        return new self(
            source: isset($data['source']) && is_scalar($data['source']) ? (string) $data['source'] : null,
            created: isset($data['created']) && is_scalar($data['created']) ? (string) $data['created'] : null,
            author: self::parseAuthor($data['author'] ?? null),
            keywords: $keywords,
            priority: isset($data['priority']) && is_numeric($data['priority']) ? (int) $data['priority'] : null
        );
    }

    private static function parseAuthor(mixed $author): string|AuthorDetail|null
    {
        return match (true) {
            $author === null => null,
            is_string($author) => $author,
            is_array($author) => AuthorDetail::fromArray($author),
            is_scalar($author) => (string) $author,
            default => throw new \InvalidArgumentException('Author must be string or AuthorDetail object')
        };
    }

    public function hasDetailedAuthor(): bool
    {
        return $this->author instanceof AuthorDetail;
    }

    // Helper methods for tests
    public function isAuthorString(): bool
    {
        return is_string($this->author);
    }

    public function getAuthorString(): string
    {
        if (! $this->isAuthorString()) {
            throw new \InvalidArgumentException('Author is not a string');
        }

        return $this->author;
    }

    public function isAuthorObject(): bool
    {
        return $this->author instanceof AuthorDetail;
    }

    public function getAuthorObject(): AuthorDetail
    {
        if (! $this->isAuthorObject()) {
            throw new \InvalidArgumentException('Author is not an AuthorDetail object');
        }

        return $this->author;
    }
}
