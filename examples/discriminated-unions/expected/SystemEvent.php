<?php

declare(strict_types=1);

readonly class SystemEvent extends Event
{
    public function __construct(
        public string $component,
        public string $level,
        public ?string $message = null,
        /** @var string[] */
        public array $stackTrace = []
    ) {
        parent::__construct('system');
    }

    public static function fromArray(array $data): static
    {
        return new static(
            component: $data['component'],
            level: $data['level'],
            message: $data['message'] ?? null,
            stackTrace: $data['stackTrace'] ?? []
        );
    }
}
