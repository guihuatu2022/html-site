<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
$app = require __DIR__ . '/bootstrap.php';
$database = $app->database();
App\Schema::ensure($database);
App\Admin::handle($database);
