<?php

declare(strict_types=1);

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value !== false && $value !== null && $value !== '') {
        return $value;
    }

    return $default;
}

require __DIR__ . '/app/App.php';
require __DIR__ . '/app/OpenListClient.php';
require __DIR__ . '/app/Router.php';

$config = require __DIR__ . '/config/app.php';
$app = new App\App($config);

return $app;
