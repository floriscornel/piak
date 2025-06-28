<?php

declare(strict_types=1);

readonly class ErrorDetail
{
    public function __construct(
        public ?string $code = null,
        public ?string $message = null,
        public ?string $field = null,
        public string|int|float|bool|object|null $value = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $value = null;
        if (isset($data['value'])) {
            $rawValue = $data['value'];
            if (is_string($rawValue) || is_int($rawValue) || is_float($rawValue) || is_bool($rawValue) || is_object($rawValue)) {
                $value = $rawValue;
            }
        }

        return new self(
            code: isset($data['code']) && is_scalar($data['code']) ? (string) $data['code'] : null,
            message: isset($data['message']) && is_scalar($data['message']) ? (string) $data['message'] : null,
            field: isset($data['field']) && is_scalar($data['field']) ? (string) $data['field'] : null,
            value: $value
        );
    }
}
