<?php

declare(strict_types=1);

namespace App;

final class Schema
{
    public static function ensure(Database $database): void
    {
        Installer::ensureSchema($database);
        Installer::ensureDefaults($database);
    }
}
