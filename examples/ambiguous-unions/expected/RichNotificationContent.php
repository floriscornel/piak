<?php

declare(strict_types=1);

readonly class RichNotificationContent
{
    public function __construct(
        public ?string $title = null,
        public ?string $body = null,
        /** @var NotificationAction[] */
        public array $actions = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $actions = [];
        foreach ($data['actions'] ?? [] as $actionData) {
            $actions[] = NotificationAction::fromArray($actionData);
        }

        return new self(
            title: $data['title'] ?? null,
            body: $data['body'] ?? null,
            actions: $actions
        );
    }
}
