<?php

declare(strict_types=1);

readonly class UserEvent extends Event
{
    public function __construct(
        public string $userId,
        public string $action,
        /** @var array<string, string> */
        public array $metadata = []
    ) {
        parent::__construct('user');
    }

    public static function fromArray(array $data): static
    {
        return new static(
            userId: $data['userId'],
            action: $data['action'],
            metadata: $data['metadata'] ?? []
        );
    }
}
