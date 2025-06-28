<?php

declare(strict_types=1);

readonly class Order
{
    public function __construct(
        public string $id,
        public string $name,
        public ?float $total = null,
        public ?string $status = null,
        /** @var string[] */
        public array $items = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Handle items array
        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $items[] = is_string($item) ? $item : (string) $item;
            }
        }

        return new self(
            id: is_string($data['id']) ? $data['id'] : (string) $data['id'],
            name: is_string($data['name']) ? $data['name'] : (string) $data['name'],
            total: isset($data['total']) ? (is_float($data['total']) ? $data['total'] : (float) $data['total']) : null,
            status: isset($data['status']) ? (is_string($data['status']) ? $data['status'] : (string) $data['status']) : null,
            items: $items
        );
    }
}
