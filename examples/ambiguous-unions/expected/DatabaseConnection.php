<?php

declare(strict_types=1);

readonly class DatabaseConnection
{
    public function __construct(
        public string $host,
        public string $database,
        public ?int $port = null,
        public ?string $username = null,
        public ?string $password = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            host: $data['host'],
            database: $data['database'],
            port: $data['port'] ?? null,
            username: $data['username'] ?? null,
            password: $data['password'] ?? null
        );
    }
}
