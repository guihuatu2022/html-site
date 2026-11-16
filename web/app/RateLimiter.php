<?php

declare(strict_types=1);

namespace App;

final class RateLimiter
{
    public function __construct(private Database $database) {}

    public function allow(string $key, int $maxAttempts = 8, int $windowSeconds = 900): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $key)) return false;
        $file = dirname(__DIR__) . '/storage/limits/' . $key . '.json';
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) return false;
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
        $now = time();
        $attempts = array_values(array_filter(is_array($data['attempts'] ?? null) ? $data['attempts'] : [], static fn ($at) => is_int($at) && $at > $now - $windowSeconds));
        if (count($attempts) >= $maxAttempts) return false;
        $attempts[] = $now;
        file_put_contents($file, json_encode(['attempts' => $attempts]), LOCK_EX);
        return true;
    }
}
