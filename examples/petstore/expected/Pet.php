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
        // Required fields with validation
        $name = $data['name'] ?? throw new \InvalidArgumentException('Pet name is required');
        if (! is_string($name)) {
            throw new \InvalidArgumentException('Pet name must be a string');
        }

        $photoUrls = $data['photoUrls'] ?? throw new \InvalidArgumentException('Pet photoUrls is required');
        if (! is_array($photoUrls)) {
            throw new \InvalidArgumentException('Pet photoUrls must be an array');
        }

        // Validate and process photo URLs
        $validatedPhotoUrls = array_map(
            static fn (mixed $url): string => is_string($url)
                ? $url
                : throw new \InvalidArgumentException('PhotoUrl must be a string'),
            $photoUrls
        );

        // Optional fields with safe processing
        $id = match (true) {
            ! isset($data['id']) => null,
            is_int($data['id']) => $data['id'],
            is_numeric($data['id']) => (int) $data['id'],
            default => null,
        };

        $category = isset($data['category']) && is_array($data['category'])
            ? Category::fromArray($data['category'])
            : null;

        $tags = isset($data['tags']) && is_array($data['tags'])
            ? array_map(
                static fn (mixed $tagData): Tag => is_array($tagData)
                    ? Tag::fromArray($tagData)
                    : throw new \InvalidArgumentException('Tag data must be an array'),
                $data['tags']
            )
            : [];

        $status = isset($data['status']) && is_string($data['status']) ? $data['status'] : null;

        return new self(
            name: $name,
            photoUrls: $validatedPhotoUrls,
            id: $id,
            category: $category,
            tags: $tags,
            status: $status
        );
    }
}
