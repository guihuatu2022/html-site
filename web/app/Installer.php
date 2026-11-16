<?php

declare(strict_types=1);

namespace App;

final class Installer
{
    public static function ensureSchema(Database $database): void
    {
        $pdo = $database->pdo();
        $mysql = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql';
        $id = $mysql ? 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $text = $mysql ? 'LONGTEXT' : 'TEXT';
        $timestamp = $mysql ? 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP' : 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP';

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (id {$id}, username VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at {$timestamp})");
        $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
            id {$id}, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE,
            summary TEXT NOT NULL, content {$text} NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'draft', visibility VARCHAR(32) NOT NULL DEFAULT 'public',
            password_hash VARCHAR(255) NULL, created_at {$timestamp}, updated_at {$timestamp}
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (key_name VARCHAR(128) PRIMARY KEY, value_text {$text} NULL)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS files (
            id {$id}, name VARCHAR(255) NOT NULL, path VARCHAR(1024) NOT NULL,
            type VARCHAR(64) NOT NULL DEFAULT 'file', visibility VARCHAR(32) NOT NULL DEFAULT 'public',
            password_hash VARCHAR(255) NULL, created_at {$timestamp}
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(64) PRIMARY KEY, applied_at {$timestamp})");
    }

    public static function isInstalled(Database $database): bool
    {
        try {
            return (int) ($database->fetchOne('SELECT COUNT(*) AS total FROM users')['total'] ?? 0) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function install(Database $database, string $username, string $password, string $siteName): void
    {
        self::ensureSchema($database);
        if (self::isInstalled($database)) {
            throw new \RuntimeException('The application is already installed.');
        }
        if ($username === '' || strlen($password) < 12) {
            throw new \InvalidArgumentException('Enter a username and a password of at least 12 characters.');
        }

        $pdo = $database->pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)');
            $stmt->execute(['username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
            $settings = [
                'site_name' => $siteName !== '' ? $siteName : 'Guihuatu Blog',
                'site_description' => '个人博客',
            ];
            $insert = $pdo->prepare('INSERT INTO settings (key_name, value_text) VALUES (:key, :value)');
            foreach ($settings as $key => $value) {
                $insert->execute(['key' => $key, 'value' => $value]);
            }
            $pdo->commit();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }
}
