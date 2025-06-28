<?php

declare(strict_types=1);

readonly class Pet
{
    public function __construct(
        public string $name,
        /** @var string[] */
        public array $photoUrls,
        public ?int $id = null,
        public ?Category $category = null,
        /** @var Tag[] */
        public array $tags = [],
        public ?string $status = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['name']) || ! is_string($data['name'])) {
            throw new \InvalidArgumentException('Pet name must be a string');
        }
        if (! isset($data['photoUrls']) || ! is_array($data['photoUrls'])) {
            throw new \InvalidArgumentException('Pet photoUrls must be an array');
        }

        // Process photoUrls array
        $photoUrls = [];
        foreach ($data['photoUrls'] as $url) {
            if (! is_string($url)) {
                throw new \InvalidArgumentException('PhotoUrl must be a string');
            }
            $photoUrls[] = $url;
        }

        // Process category if present
        $category = null;
        if (isset($data['category']) && is_array($data['category'])) {
            /** @var array<string, mixed> $categoryData */
            $categoryData = $data['category'];
            $category = Category::fromArray($categoryData);
        }

        // Process tags array if present
        $tags = [];
        if (isset($data['tags']) && is_array($data['tags'])) {
            foreach ($data['tags'] as $tagData) {
                if (! is_array($tagData)) {
                    throw new \InvalidArgumentException('Tag data must be an array');
                }
                /** @var array<string, mixed> $tagDataTyped */
                $tagDataTyped = $tagData;
                $tags[] = Tag::fromArray($tagDataTyped);
            }
        }

        return new self(
            name: $data['name'],
            photoUrls: $photoUrls,
            id: isset($data['id']) && (is_int($data['id']) || is_numeric($data['id'])) ? (int) $data['id'] : null,
            category: $category,
            tags: $tags,
            status: isset($data['status']) && is_string($data['status']) ? $data['status'] : null
        );
    }
}
