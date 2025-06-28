<?php

declare(strict_types=1);

readonly class LocalizedDescription
{
    public function __construct(
        public ?string $en = null,
        public ?string $es = null,
        public ?string $fr = null,
        public ?string $default = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            en: isset($data['en']) && is_scalar($data['en']) ? (string) $data['en'] : null,
            es: isset($data['es']) && is_scalar($data['es']) ? (string) $data['es'] : null,
            fr: isset($data['fr']) && is_scalar($data['fr']) ? (string) $data['fr'] : null,
            default: isset($data['default']) && is_scalar($data['default']) ? (string) $data['default'] : null
        );
    }
}
