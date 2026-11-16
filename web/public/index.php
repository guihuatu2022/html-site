<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$app = require dirname(__DIR__) . '/bootstrap.php';
$router = new App\Router($app);

echo $router->dispatch();
