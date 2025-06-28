<?php

declare(strict_types=1);

readonly class MenuNode
{
    public function __construct(
        public string $id,
        public string $label,
        public ?string $href = null,
        public ?string $icon = null,
        /** @var MenuNode[] Limited depth to prevent infinite recursion */
        public array $children = []
    ) {}

    /**
     * Factory method to create from array data with depth limiting
     *
     * @param  array<string, mixed>  $data
     * @param  int  $maxDepth  Maximum recursion depth to prevent infinite loops
     * @param  int  $currentDepth  Current depth (internal parameter)
     */
    public static function fromArray(array $data, int $maxDepth = 10, int $currentDepth = 0): self
    {
        $children = [];

        // Only process children if we haven't reached max depth
        if ($currentDepth < $maxDepth && isset($data['children']) && is_array($data['children'])) {
            foreach ($data['children'] as $childData) {
                if (is_array($childData) && isset($childData['id']) && isset($childData['label'])) {
                    $children[] = self::fromArray($childData, $maxDepth, $currentDepth + 1);
                }
            }
        }

        // Validate required fields
        if (! isset($data['id']) || ! isset($data['label'])) {
            throw new InvalidArgumentException('MenuNode requires id and label fields');
        }

        return new self(
            id: is_string($data['id']) ? $data['id'] : (string) $data['id'],
            label: is_string($data['label']) ? $data['label'] : (string) $data['label'],
            href: isset($data['href']) ? (is_string($data['href']) ? $data['href'] : (string) $data['href']) : null,
            icon: isset($data['icon']) ? (is_string($data['icon']) ? $data['icon'] : (string) $data['icon']) : null,
            children: $children
        );
    }

    /**
     * Alternative factory method using ID references (like Category/Comment pattern)
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArrayWithIds(array $data): self
    {
        // This would use childrenIds approach for consistency
        // but MenuNode schema doesn't have IDs pattern, so we keep object approach
        return self::fromArray($data);
    }

    /**
     * Flatten hierarchy for safer handling
     *
     * @return array<string, MenuNode>
     */
    public function flatten(): array
    {
        $result = [$this->id => $this];
        foreach ($this->children as $child) {
            $result = array_merge($result, $child->flatten());
        }

        return $result;
    }

    /**
     * Get all descendant node IDs as flat array
     *
     * @return string[]
     */
    public function getAllChildrenIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getAllChildrenIds());
        }

        return $ids;
    }

    /**
     * Check if this node has children
     */
    public function hasChildren(): bool
    {
        return ! empty($this->children);
    }

    /**
     * Get the depth of this node's tree
     */
    public function getDepth(): int
    {
        if (empty($this->children)) {
            return 1;
        }

        $maxChildDepth = 0;
        foreach ($this->children as $child) {
            $maxChildDepth = max($maxChildDepth, $child->getDepth());
        }

        return 1 + $maxChildDepth;
    }
}
