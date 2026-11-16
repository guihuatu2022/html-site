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
        $post->passwordHash = isset($row['password_hash']) ? (string) $row['password_hash'] : null;
        $post->createdAt = (string) ($row['created_at'] ?? '');
        $post->updatedAt = (string) ($row['updated_at'] ?? '');
        return $post;
    }
}
