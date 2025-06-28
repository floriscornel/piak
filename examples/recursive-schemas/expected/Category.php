<?php

declare(strict_types=1);

readonly class Category
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public ?string $parentId = null,    // Break recursion with ID reference
        /** @var string[] */
        public array $childrenIds = [],      // Array of child IDs instead of objects
        public ?int $depth = null
    ) {}

    /**
     * Factory method to create from array data
     * Uses ID references to prevent infinite recursion in tree structures
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

        // Extract children IDs instead of creating child objects (prevents recursion)
        $childrenIds = [];
        if (isset($data['children']) && is_array($data['children'])) {
            foreach ($data['children'] as $child) {
                if (is_array($child) && isset($child['id'])) {
                    $childrenIds[] = is_string($child['id']) ? $child['id'] : (string) $child['id'];
                } elseif (is_string($child)) {
                    $childrenIds[] = $child;
                }
            }
        } elseif (isset($data['childrenIds']) && is_array($data['childrenIds'])) {
            foreach ($data['childrenIds'] as $id) {
                $childrenIds[] = is_string($id) ? $id : (string) $id;
            }
        }

        return new self(
            id: is_string($data['id']) ? $data['id'] : (string) $data['id'],
            name: is_string($data['name']) ? $data['name'] : (string) $data['name'],
            description: isset($data['description']) ? (is_string($data['description']) ? $data['description'] : (string) $data['description']) : null,
            parentId: $parentId,
            childrenIds: $childrenIds,
            depth: isset($data['depth']) ? (is_int($data['depth']) ? $data['depth'] : (int) $data['depth']) : null
        );
    }

    /**
     * Get all child category IDs as flat array
     *
     * @return string[]
     */
    public function getAllChildrenIds(): array
    {
        return $this->childrenIds;
    }

    /**
     * Check if this category is a root category (no parent)
     */
    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    /**
     * Check if this category has children
     */
    public function hasChildren(): bool
    {
        return ! empty($this->childrenIds);
    }
}
