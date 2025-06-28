<?php

declare(strict_types=1);

readonly class WorkspacePreferences
{
    /**
     * @param  string[]  $equipment
     */
    public function __construct(
        public ?bool $remoteWork = null,
        public ?string $deskType = null,
        public array $equipment = []
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $equipment = [];
        if (isset($data['equipment']) && is_array($data['equipment'])) {
            foreach ($data['equipment'] as $item) {
                if (is_string($item) || is_scalar($item)) {
                    $equipment[] = (string) $item;
                }
            }
        }

        return new self(
            remoteWork: isset($data['remoteWork']) && is_bool($data['remoteWork']) ? $data['remoteWork'] : null,
            deskType: isset($data['deskType']) && is_scalar($data['deskType']) ? (string) $data['deskType'] : null,
            equipment: $equipment
        );
    }
}
