<?php

declare(strict_types=1);

namespace App;

final class View
{
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function page(string $title, string $content, string $bodyClass = ''): string
    {
        $safeTitle = self::escape($title);
        $safeClass = self::escape($bodyClass);
        return '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $safeTitle . '</title><link rel="stylesheet" href="public/assets/css/app.css"></head><body class="' . $safeClass . '">' . $content . '</body></html>';
    }
}
