<?php

declare(strict_types=1);

namespace App;

final class App
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value ?? $default;
    }

    public function databaseDsn(): string
    {
        $driver = strtolower((string) ($this->config['database']['driver'] ?? 'mysql'));
        if ($driver === 'sqlite') {
            return 'sqlite:' . (string) ($this->config['database']['sqlite_path'] ?? __DIR__ . '/../storage/database.sqlite');
        }

        $host = (string) ($this->config['database']['host'] ?? '127.0.0.1');
        $port = (int) ($this->config['database']['port'] ?? 3306);
        $name = (string) ($this->config['database']['name'] ?? 'html_site_blog');
        return sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);
    }

    public function database(): Database
    {
        return new Database($this->config['database'] ?? []);
    }

    public function openListClient(): OpenListClient
    {
        return new OpenListClient($this->config['openlist'] ?? []);
    }
}
