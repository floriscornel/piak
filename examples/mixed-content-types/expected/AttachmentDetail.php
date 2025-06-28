<?php

declare(strict_types=1);

readonly class AttachmentDetail
{
    public function __construct(
        public string $url,
        public string $filename,
        public ?int $size = null,
        public ?string $mimeType = null,
        public string|LocalizedDescription|null $description = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['url']) || (! is_string($data['url']) && ! is_scalar($data['url']))) {
            throw new \InvalidArgumentException('url must be a string');
        }
        if (! isset($data['filename']) || (! is_string($data['filename']) && ! is_scalar($data['filename']))) {
            throw new \InvalidArgumentException('filename must be a string');
        }

        $description = null;
        if (isset($data['description'])) {
            if (is_string($data['description']) || is_scalar($data['description'])) {
                $description = (string) $data['description'];
            } elseif (is_array($data['description'])) {
                $description = LocalizedDescription::fromArray($data['description']);
            }
        }

        return new self(
            url: is_string($data['url']) ? $data['url'] : (string) $data['url'],
            filename: is_string($data['filename']) ? $data['filename'] : (string) $data['filename'],
            size: isset($data['size']) && is_numeric($data['size']) ? (int) $data['size'] : null,
            mimeType: isset($data['mimeType']) && is_scalar($data['mimeType']) ? (string) $data['mimeType'] : null,
            description: $description
        );
    }

    // Helper methods for tests
    public function isDescriptionString(): bool
    {
        return is_string($this->description);
    }

    public function getDescriptionString(): string
    {
        if (! $this->isDescriptionString()) {
            throw new \InvalidArgumentException('Description is not a string');
        }

        return $this->description;
    }

    public function isDescriptionObject(): bool
    {
        return $this->description instanceof LocalizedDescription;
    }

    public function getDescriptionObject(): LocalizedDescription
    {
        if (! $this->isDescriptionObject()) {
            throw new \InvalidArgumentException('Description is not a LocalizedDescription object');
        }

        return $this->description;
    }
}
