<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
$app = require __DIR__ . '/bootstrap.php';
$router = new App\Router($app);
echo $router->dispatch();
