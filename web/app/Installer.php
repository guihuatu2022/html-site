<?php

declare(strict_types=1);

namespace App;

final class Installer
{
    public static function ensureSchema(Database $database): void
    {
        $pdo = $database->pdo();

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                summary TEXT,
                content LONGTEXT,
                status VARCHAR(32) NOT NULL DEFAULT 'draft',
                visibility VARCHAR(32) NOT NULL DEFAULT 'public',
                password_hash VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS settings (
                key_name VARCHAR(128) PRIMARY KEY,
                value_text TEXT
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                path VARCHAR(512) NOT NULL,
                type VARCHAR(64) DEFAULT 'file',
                visibility VARCHAR(32) NOT NULL DEFAULT 'public',
                password_hash VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );
    }

    public static function ensureDefaults(Database $database): void
    {
        $stmt = $database->pdo()->prepare('SELECT value_text FROM settings WHERE key_name = :key');
        $keys = ['site_name', 'site_description'];

        foreach ($keys as $key) {
            $stmt->execute(['key' => $key]);
            if ($stmt->fetch() !== false) {
                continue;
            }

            $default = match ($key) {
                'site_name' => 'Guihuatu Blog',
                'site_description' => '个人博客与网盘内容集合',
                default => '',
            };

            $database->pdo()->prepare('INSERT INTO settings (key_name, value_text) VALUES (:key, :value)')->execute([
                'key' => $key,
                'value' => $default,
            ]);
        }
    }
}
