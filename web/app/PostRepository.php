<?php

declare(strict_types=1);

namespace App;

final class PostRepository
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    public function listPublished(int $limit = 12): array
    {
        $stmt = $this->database->pdo()->prepare(
            'SELECT * FROM posts WHERE status = :status AND visibility = :visibility ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':status', 'published', \PDO::PARAM_STR);
        $stmt->bindValue(':visibility', 'public', \PDO::PARAM_STR);
        $stmt->bindValue(':limit', max(1, min(100, $limit)), \PDO::PARAM_INT);
        $stmt->execute();

        $posts = [];
        foreach ($stmt->fetchAll() as $row) {
            $posts[] = Post::fromArray($row);
        }
        return $posts;
    }

    public function listForAdmin(string $status = 'published', int $limit = 50): array
    {
        $stmt = $this->database->pdo()->prepare(
            'SELECT * FROM posts WHERE status = :status ORDER BY updated_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':status', $status, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', max(1, min(200, $limit)), \PDO::PARAM_INT);
        $stmt->execute();

        $posts = [];
        foreach ($stmt->fetchAll() as $row) {
            $posts[] = Post::fromArray($row);
        }
        return $posts;
    }

    public function findById(int $id): ?Post
    {
        $row = $this->database->fetchOne('SELECT * FROM posts WHERE id = :id', ['id' => $id]);
        return $row ? Post::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?Post
    {
        $row = $this->database->fetchOne('SELECT * FROM posts WHERE slug = :slug', ['slug' => $slug]);
        return $row ? Post::fromArray($row) : null;
    }

    public function save(Post $post): int
    {
        if ($post->id > 0) {
            $this->database->pdo()->prepare(
                'UPDATE posts SET title = :title, slug = :slug, summary = :summary, content = :content, status = :status, visibility = :visibility, password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            )->execute([
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'summary' => $post->summary,
                'content' => $post->content,
                'status' => $post->status,
                'visibility' => $post->visibility,
                'password_hash' => $post->passwordHash,
            ]);
            return $post->id;
        }

        $stmt = $this->database->pdo()->prepare(
            'INSERT INTO posts (title, slug, summary, content, status, visibility, password_hash) VALUES (:title, :slug, :summary, :content, :status, :visibility, :password_hash)'
        );
        $stmt->execute([
            'title' => $post->title,
            'slug' => $post->slug,
            'summary' => $post->summary,
            'content' => $post->content,
            'status' => $post->status,
            'visibility' => $post->visibility,
            'password_hash' => $post->passwordHash,
        ]);

        return (int) $this->database->pdo()->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->database->pdo()->prepare('DELETE FROM posts WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
