<?php

declare(strict_types=1);

readonly class SystemEvent
{
    public function __construct(
        public string $type,
        public string $timestamp,
        public string $action,
        public ?string $component = null,
        public ?string $severity = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Validate required fields
        if (! isset($data['type']) || ! is_string($data['type'])) {
            throw new \InvalidArgumentException('SystemEvent type must be a string');
        }
        if (! isset($data['timestamp']) || ! is_string($data['timestamp'])) {
            throw new \InvalidArgumentException('SystemEvent timestamp must be a string');
        }
        if (! isset($data['action']) || ! is_string($data['action'])) {
            throw new \InvalidArgumentException('SystemEvent action must be a string');
        }

        // Handle optional fields
        $component = null;
        if (isset($data['component'])) {
            if (! is_string($data['component'])) {
                throw new \InvalidArgumentException('SystemEvent component must be a string');
            }
            $component = $data['component'];
        }

        $severity = null;
        if (isset($data['severity'])) {
            if (! is_string($data['severity'])) {
                throw new \InvalidArgumentException('SystemEvent severity must be a string');
            }
            $severity = $data['severity'];
        }

        return new self(
            type: $data['type'],
            timestamp: $data['timestamp'],
            action: $data['action'],
            component: $component,
            severity: $severity
        );
    }
}
