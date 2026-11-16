<?php

declare(strict_types=1);

if (is_file(__DIR__ . '/.env') && is_readable(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\r\n\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        return ($value !== false && $value !== null && $value !== '') ? $value : $default;
    }
}

foreach (['App.php', 'Database.php', 'Auth.php', 'Installer.php', 'OpenListClient.php', 'Post.php', 'PostRepository.php', 'Router.php'] as $file) {
    require_once __DIR__ . '/app/' . $file;
}

$config = require __DIR__ . '/config/app.php';
return new App\App($config);
