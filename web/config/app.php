<?php

declare(strict_types=1);

$installedConfig = __DIR__ . '/installed.php';
$installed = is_file($installedConfig) ? require $installedConfig : [];

return array_replace_recursive([
    'app' => [
        'name' => env('APP_NAME', 'Guihuatu Blog'),
        'env' => env('APP_ENV', 'production'),
        'url' => env('APP_URL', ''),
    ],
    'database' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (int) env('DB_PORT', 3306),
        'name' => env('DB_NAME', 'html_site_blog'),
        'user' => env('DB_USER', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'sqlite_path' => env('DB_SQLITE_PATH', __DIR__ . '/../storage/database.sqlite'),
    ],
    'openlist' => [
        'base_url' => rtrim((string) env('OPENLIST_BASE_URL', ''), '/'),
        'token' => env('OPENLIST_TOKEN', ''),
        'password' => env('OPENLIST_PASSWORD', ''),
        'root_path' => env('OPENLIST_ROOT_PATH', '/'),
    ],
], is_array($installed) ? $installed : []);
