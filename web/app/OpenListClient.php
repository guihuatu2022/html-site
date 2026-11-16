<?php

declare(strict_types=1);

namespace App;

final class OpenListClient
{
    private string $baseUrl;
    private string $token;
    private string $rootPath;
    private int $timeout;

    public function __construct(array $config)
    {
        $baseUrl = trim((string) ($config['base_url'] ?? ''));
        if ($baseUrl !== '' && !in_array(parse_url($baseUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('OpenList URL must use HTTP or HTTPS.');
        }
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = trim((string) ($config['token'] ?? ''));
        $this->rootPath = $this->normalizeRoot((string) ($config['root_path'] ?? '/'));
        $this->timeout = max(3, min(30, (int) ($config['timeout'] ?? 10)));
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->token !== '';
    }

    public function listFolder(string $relativePath = '/'): array
    {
        $response = $this->post('/api/fs/list', ['path' => $this->resolvePath($relativePath), 'password' => '']);
        if (($response['code'] ?? null) !== 200 || !is_array($response['data'] ?? null)) {
            throw new \RuntimeException((string) ($response['message'] ?? 'OpenList failed to list the directory.'));
        }
        return $response['data'];
    }

    public function probe(string $relativePath = '/'): array
    {
        return $this->post('/api/fs/list', ['path' => $this->resolvePath($relativePath), 'password' => '']);
    }

    public function makeDirectory(string $relativePath): array
    {
        return $this->post('/api/fs/mkdir', ['path' => $this->resolvePath($relativePath)]);
    }

    public function upload(string $relativeDirectory, string $filePath, string $safeName): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('OpenList is not configured.');
        }
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException('Upload source file is unavailable.');
        }
        $destination = $this->resolvePath(rtrim($relativeDirectory, '/') . '/' . $safeName);
        $url = $this->baseUrl . '/api/fs/put';
        $headers = [
            'Authorization: ' . $this->token,
            'File-Path: ' . $destination,
            'Content-Type: application/octet-stream',
        ];
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open upload source file.');
        }
        try {
            return $this->request($url, 'PUT', $handle, $headers, filesize($filePath) ?: null);
        } finally {
            fclose($handle);
        }
    }

    public function resolvePath(string $relativePath): string
    {
        $relative = str_replace('\\', '/', trim($relativePath));
        $segments = [];
        foreach (explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.') continue;
            if ($segment === '..' || str_contains($segment, "\0")) {
                throw new \InvalidArgumentException('Invalid OpenList path.');
            }
            $segments[] = $segment;
        }
        $suffix = implode('/', $segments);
        return $this->rootPath === '/' ? '/' . $suffix : rtrim($this->rootPath, '/') . ($suffix === '' ? '' : '/' . $suffix);
    }

    private function normalizeRoot(string $root): string
    {
        $root = '/' . trim(str_replace('\\', '/', $root), '/');
        return $root === '//' ? '/' : $root;
    }

    private function post(string $endpoint, array $payload): array
    {
        return $this->request($this->baseUrl . $endpoint, 'POST', json_encode($payload, JSON_THROW_ON_ERROR), [
            'Authorization: ' . $this->token,
            'Content-Type: application/json',
        ]);
    }

    /** @param resource|string $body */
    private function request(string $url, string $method, mixed $body, array $headers, ?int $length = null): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('OpenList is not configured.');
        }
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('The PHP cURL extension is required for OpenList integration.');
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);

        if (is_resource($body)) {
            curl_setopt($curl, CURLOPT_UPLOAD, true);
            curl_setopt($curl, CURLOPT_INFILE, $body);
            if ($length !== null) curl_setopt($curl, CURLOPT_INFILESIZE, $length);
        } else {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($responseBody === false) {
            throw new \RuntimeException('OpenList request failed: ' . $error);
        }
        $decoded = json_decode((string) $responseBody, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('OpenList returned an invalid response (HTTP ' . $status . ').');
        }
        if ($status < 200 || $status >= 300 || (isset($decoded['code']) && (int) $decoded['code'] !== 200)) {
            throw new \RuntimeException((string) ($decoded['message'] ?? 'OpenList request failed (HTTP ' . $status . ').'));
        }
        return $decoded;
    }
}
