<?php

declare(strict_types=1);

namespace App;

final class OpenListClient
{
    private string $baseUrl;
    private string $token;
    private string $password;
    private string $rootPath;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim((string) ($config['base_url'] ?? 'http://localhost:5244'), '/');
        $this->token = (string) ($config['token'] ?? '');
        $this->password = (string) ($config['password'] ?? '');
        $this->rootPath = (string) ($config['root_path'] ?? '/');
    }

    public function listFolder(string $path = '/'): array
    {
        $url = $this->baseUrl . '/api/fs/list';
        $payload = [
            'path' => $this->normalizePath($path),
            'password' => $this->password,
        ];

        $response = $this->request($url, $payload);

        if (!isset($response['data'])) {
            return [];
        }

        return $response['data'];
    }

    public function stat(string $path): ?array
    {
        $url = $this->baseUrl . '/api/fs/get';
        $payload = [
            'path' => $this->normalizePath($path),
            'password' => $this->password,
        ];

        $response = $this->request($url, $payload);
        return $response['data'] ?? null;
    }

    public function downloadUrl(string $path): string
    {
        $normalized = $this->normalizePath($path);
        return $this->baseUrl . '/api/fs/get?path=' . rawurlencode($normalized) . '&password=' . rawurlencode($this->password);
    }

    private function normalizePath(string $path): string
    {
        $value = $path === '' ? '/' : $path;
        $value = str_replace('\\', '/', $value);

        if ($value !== '/') {
            $value = '/' . trim($value, '/');
        }

        if ($this->rootPath !== '/' && strpos($value, $this->rootPath) === 0) {
            return $value;
        }

        return $this->rootPath === '/' ? $value : $this->rootPath . trim($value, '/');
    }

    private function request(string $url, array $payload): array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: {$this->token}\r\n",
                'content' => json_encode($payload, JSON_THROW_ON_ERROR),
                'timeout' => 20,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false || $body === '') {
            return [];
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }
}
