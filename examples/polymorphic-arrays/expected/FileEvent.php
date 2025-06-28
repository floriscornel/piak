<?php

declare(strict_types=1);

readonly class FileEvent
{
    public function __construct(
        public string $type,
        public string $timestamp,
        public string $filename,
        public ?int $fileSize = null,
        public ?string $uploadedBy = null,
        public ?string $downloadUrl = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        // Validate required fields
        if (! isset($data['type']) || ! is_string($data['type'])) {
            throw new \InvalidArgumentException('FileEvent type must be a string');
        }
        if (! isset($data['timestamp']) || ! is_string($data['timestamp'])) {
            throw new \InvalidArgumentException('FileEvent timestamp must be a string');
        }
        if (! isset($data['filename']) || ! is_string($data['filename'])) {
            throw new \InvalidArgumentException('FileEvent filename must be a string');
        }

        // Handle optional fields
        $fileSize = null;
        if (isset($data['fileSize'])) {
            if (is_int($data['fileSize'])) {
                $fileSize = $data['fileSize'];
            } elseif (is_numeric($data['fileSize'])) {
                $fileSize = (int) $data['fileSize'];
            } else {
                throw new \InvalidArgumentException('FileEvent fileSize must be an integer');
            }
        }

        $uploadedBy = null;
        if (isset($data['uploadedBy'])) {
            if (is_string($data['uploadedBy'])) {
                $uploadedBy = $data['uploadedBy'];
            } elseif (is_numeric($data['uploadedBy'])) {
                $uploadedBy = (string) $data['uploadedBy'];
            } else {
                throw new \InvalidArgumentException('FileEvent uploadedBy must be a string or numeric value');
            }
        }

        $downloadUrl = null;
        if (isset($data['downloadUrl'])) {
            if (! is_string($data['downloadUrl'])) {
                throw new \InvalidArgumentException('FileEvent downloadUrl must be a string');
            }
            $downloadUrl = $data['downloadUrl'];
        }

        return new self(
            type: $data['type'],
            timestamp: $data['timestamp'],
            filename: $data['filename'],
            fileSize: $fileSize,
            uploadedBy: $uploadedBy,
            downloadUrl: $downloadUrl
        );
    }
}
