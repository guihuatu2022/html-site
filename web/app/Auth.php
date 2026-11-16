<?php

declare(strict_types=1);

namespace App;

final class Database
{
    private \PDO $pdo;

    public function __construct(array $config)
    {
        $driver = strtolower((string) ($config['driver'] ?? 'mysql'));

        if ($driver === 'sqlite') {
            $path = (string) ($config['sqlite_path'] ?? __DIR__ . '/../storage/database.sqlite');
            $this->pdo = new \PDO('sqlite:' . $path);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return;
        }

        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (int) ($config['port'] ?? 3306);
        $dbname = (string) ($config['name'] ?? 'html_site_blog');
        $user = (string) ($config['user'] ?? 'root');
        $password = (string) ($config['password'] ?? '');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbname);
        $this->pdo = new \PDO($dsn, $user, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }

    public function exec(string $sql): bool
    {
        return $this->pdo->exec($sql) !== false;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function tableExists(string $tableName): bool
    {
        if ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $result = $this->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name = :name", ['name' => $tableName]);
            return $result !== null;
        }

        $result = $this->fetchOne('SHOW TABLES LIKE :name', ['name' => $tableName]);
        return $result !== null;
    }
}
