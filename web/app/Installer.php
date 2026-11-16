<?php

declare(strict_types=1);

namespace App;

final class Installer
{
    public static function ensureSchema(Database $database): void
    {
        $pdo = $database->pdo();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $idType = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
        $datetime = $driver === 'sqlite' ? 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP' : 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $longText = $driver === 'sqlite' ? 'TEXT' : 'LONGTEXT';

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (id {$idType}, username VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at {$datetime})");
        $pdo->exec("CREATE TABLE IF NOT EXISTS posts (id {$idType}, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL UNIQUE, summary TEXT NOT NULL DEFAULT '', content {$longText} NOT NULL DEFAULT '', status VARCHAR(32) NOT NULL DEFAULT 'draft', visibility VARCHAR(32) NOT NULL DEFAULT 'public', password_hash VARCHAR(255) NULL, created_at {$datetime}, updated_at {$datetime})");
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (key_name VARCHAR(128) PRIMARY KEY, value_text TEXT)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS files (id {$idType}, name VARCHAR(255) NOT NULL, path VARCHAR(1024) NOT NULL, type VARCHAR(64) NOT NULL DEFAULT 'file', visibility VARCHAR(32) NOT NULL DEFAULT 'public', password_hash VARCHAR(255) NULL, created_at {$datetime})");
    }

    public static function isInstalled(Database $database): bool
    {
        try {
            $row = $database->fetchOne('SELECT COUNT(*) AS total FROM users');
            return (int) ($row['total'] ?? 0) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function install(Database $database, string $username, string $password, string $siteName): void
    {
        self::ensureSchema($database);
        if (self::isInstalled($database)) throw new \RuntimeException('The installation has already been completed.');
        if ($username === '' || strlen($password) < 12) throw new \InvalidArgumentException('Enter a username and a password of at least 12 characters.');

        $pdo = $database->pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :hash)')->execute([
                'username' => $username,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $stmt = $pdo->prepare('INSERT INTO settings (key_name, value_text) VALUES (:key, :value)');
            $stmt->execute(['key' => 'site_name', 'value' => $siteName !== '' ? $siteName : 'Guihuatu Blog']);
            $stmt->execute(['key' => 'site_description', 'value' => '个人博客与网盘内容集合']);
            $pdo->commit();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public static function ensureDefaults(Database $database): void
    {
        $settings = ['site_name' => 'Guihuatu Blog', 'site_description' => '个人博客与网盘内容集合'];
        foreach ($settings as $key => $value) {
            if ($database->fetchOne('SELECT key_name FROM settings WHERE key_name = :key', ['key' => $key]) === null) {
                $database->pdo()->prepare('INSERT INTO settings (key_name, value_text) VALUES (:key, :value)')->execute(['key' => $key, 'value' => $value]);
            }
        }
    }
}
