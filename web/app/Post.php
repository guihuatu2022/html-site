<?php

declare(strict_types=1);

namespace App;

final class Post
{
    public int $id = 0;
    public string $title = '';
    public string $slug = '';
    public string $summary = '';
    public string $content = '';
    public string $status = 'draft';
    public string $visibility = 'public';
    public ?string $passwordHash = null;
    public string $createdAt = '';
    public string $updatedAt = '';

    public static function fromArray(array $row): self
    {
        $post = new self();
        $post->id = (int) ($row['id'] ?? 0);
        $post->title = (string) ($row['title'] ?? '');
        $post->slug = (string) ($row['slug'] ?? '');
        $post->summary = (string) ($row['summary'] ?? '');
        $post->content = (string) ($row['content'] ?? '');
        $post->status = (string) ($row['status'] ?? 'draft');
        $post->visibility = (string) ($row['visibility'] ?? 'public');
        $post->passwordHash = array_key_exists('password_hash', $row) && $row['password_hash'] !== null ? (string) $row['password_hash'] : null;
        $post->createdAt = (string) ($row['created_at'] ?? '');
        $post->updatedAt = (string) ($row['updated_at'] ?? '');
        return $post;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'content' => $this->content,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'password_hash' => $this->passwordHash,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
