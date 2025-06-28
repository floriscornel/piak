<?php

declare(strict_types=1);

readonly class DatabaseConfig
{
    public function __construct(
        public string|DatabaseConnection $config
    ) {}

    public static function fromString(string $connectionString): self
    {
        return new self($connectionString);
    }

    public static function fromConnection(DatabaseConnection $connection): self
    {
        return new self($connection);
    }

    public static function fromArray(array|string $data): self
    {
        if (is_string($data)) {
            return self::fromString($data);
        }

        return self::fromConnection(DatabaseConnection::fromArray($data));
    }

    public function isConnectionString(): bool
    {
        return is_string($this->config);
    }

    public function isConnectionObject(): bool
    {
        return $this->config instanceof DatabaseConnection;
    }
}
