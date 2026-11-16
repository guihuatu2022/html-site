<?php

declare(strict_types=1);

namespace App;

final class FileRepository
{
    public function __construct(private Database $database) {}

    public function list(int $limit = 100): array
    {
        $stmt = $this->database->pdo()->prepare('SELECT * FROM files ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', max(1, min(200, $limit)), \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function add(string $name, string $path, string $type = 'file', string $visibility = 'public'): int
    {
        $stmt = $this->database->pdo()->prepare('INSERT INTO files (name, path, type, visibility) VALUES (:name, :path, :type, :visibility)');
        $stmt->execute(['name' => $name, 'path' => $path, 'type' => $type, 'visibility' => $visibility]);
        return (int) $this->database->pdo()->lastInsertId();
    }
}
