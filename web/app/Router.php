<?php

declare(strict_types=1);

namespace App;

final class Router
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function dispatch(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = trim($path, '/');

        if ($path === '') {
            return $this->home();
        }

        if ($path === 'posts') {
            return $this->postsList();
        }

        if (str_starts_with($path, 'posts/')) {
            return $this->postDetail(substr($path, strlen('posts/')));
        }

        if ($path === 'api/openlist') {
            return $this->openListApi();
        }

        if ($path === 'admin') {
            return $this->adminRedirect();
        }

        return $this->notFound();
    }

    private function home(): string
    {
        $appName = htmlspecialchars((string) $this->app->config('app.name', 'Guihuatu Blog'), ENT_QUOTES, 'UTF-8');
        $database = $this->app->database();
        Installer::ensureSchema($database);
        Installer::ensureDefaults($database);

        $repo = new PostRepository($database);
        $posts = $repo->listPublished(6);

        $cards = '';
        foreach ($posts as $post) {
            $title = htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8');
            $summary = htmlspecialchars($post->summary ?: '暂无摘要', ENT_QUOTES, 'UTF-8');
            $slug = htmlspecialchars($post->slug, ENT_QUOTES, 'UTF-8');
            $cards .= "
                <article class='card'>
                    <span class='tag'>文章</span>
                    <h3><a href='/posts/{$slug}'>{$title}</a></h3>
                    <p>{$summary}</p>
                </article>
            ";
        }

        if ($cards === '') {
            $cards = "<article class='card empty'><h3>还没有公开文章</h3><p>管理员登录后台后可发布第一篇文章。</p></article>";
        }

        return <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$appName}</title>
  <style>
    :root { --bg: #f4efe7; --paper: #fffdf9; --ink: #1c1b1a; --muted: #665d52; --accent: #8c4f3b; --line: #e7dfd2; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: var(--bg); color: var(--ink); }
    a { color: inherit; text-decoration: none; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 32px 20px 80px; }
    .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 0 30px; }
    .brand { font-size: 1.7rem; font-weight: 700; }
    .nav { display: flex; gap: 18px; color: var(--muted); }
    .hero { background: linear-gradient(135deg, #f5f0e5, #fff); border: 1px solid var(--line); border-radius: 18px; padding: 36px 30px; box-shadow: 0 18px 38px rgba(28,27,26,0.04); }
    .hero h1 { margin: 0; font-size: clamp(2.2rem, 4vw, 4rem); }
    .hero p { max-width: 700px; color: var(--muted); line-height: 1.8; }
    .actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
    .button { display: inline-block; background: var(--accent); color: white; padding: 12px 18px; border-radius: 999px; font-weight: 600; }
    .secondary { background: white; color: var(--ink); border: 1px solid var(--line); }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-top: 30px; }
    .card { background: var(--paper); border: 1px solid var(--line); border-radius: 16px; padding: 20px; }
    .tag { display: inline-block; background: #f3e0d5; color: var(--accent); padding: 4px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; }
    .card h3 { margin: 16px 0 10px; font-size: 1.4rem; }
    .card p { margin: 0; line-height: 1.7; color: var(--muted); }
    .empty { background: #fff; }
  </style>
</head>
<body>
  <div class="wrap">
    <header class="topbar">
      <div class="brand">{$appName}</div>
      <nav class="nav">
        <a href="/">首页</a>
        <a href="/posts">文章</a>
        <a href="/admin.php">后台</a>
      </nav>
    </header>

    <section class="hero">
      <h1>记录、整理、分享。</h1>
      <p>基于 PHP 的个人博客与网盘内容站点，保留原本的静态页面风格，并逐步迁移到以文章为核心的内容平台。</p>
      <div class="actions">
        <a class="button" href="/posts">阅读文章</a>
        <a class="button secondary" href="/admin.php">管理后台</a>
      </div>
    </section>

    <section class="grid">
      {$cards}
    </section>
  </div>
</body>
</html>
HTML;
    }

    private function postsList(): string
    {
        $database = $this->app->database();
        Installer::ensureSchema($database);
        Installer::ensureDefaults($database);

        $posts = (new PostRepository($database))->listPublished(20);
        $items = '';

        foreach ($posts as $post) {
            $title = htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8');
            $summary = htmlspecialchars($post->summary ?: '暂无摘要', ENT_QUOTES, 'UTF-8');
            $slug = htmlspecialchars($post->slug, ENT_QUOTES, 'UTF-8');
            $items .= "
                <article class='item'>
                    <span class='meta'>公开文章</span>
                    <h2><a href='/posts/{$slug}'>{$title}</a></h2>
                    <p>{$summary}</p>
                </article>
            ";
        }

        if ($items === '') {
            $items = "<article class='item empty'><h2>暂无文章</h2><p>管理员尚未发布内容。</p></article>";
        }

        return <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>文章列表</title>
  <style>
    body { margin: 0; background: #f5f0e7; color: #1f2937; font-family: sans-serif; }
    .wrap { max-width: 920px; margin: 0 auto; padding: 48px 20px 80px; }
    .top { margin-bottom: 24px; }
    .top a { color: #1f2937; }
    .item { background: white; border: 1px solid #e5ded1; border-radius: 14px; padding: 22px; margin-bottom: 18px; }
    .meta { display: inline-block; background: #fbe9df; color: #8c4f3b; font-size: 0.72rem; font-weight: 700; padding: 5px 8px; border-radius: 999px; }
    h2 { margin: 16px 0 10px; font-size: 1.7rem; }
    p { color: #52525b; line-height: 1.8; }
    .empty { text-align: center; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top"><a href="/">← 返回首页</a></div>
    <h1>文章列表</h1>
    {$items}
  </div>
</body>
</html>
HTML;
    }

    private function postDetail(string $slug): string
    {
        $database = $this->app->database();
        Installer::ensureSchema($database);
        $post = (new PostRepository($database))->findBySlug($slug);

        if ($post === null) {
            return $this->notFound();
        }

        $title = htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8');
        $summary = htmlspecialchars($post->summary, ENT_QUOTES, 'UTF-8');
        $content = nl2br(htmlspecialchars($post->content, ENT_QUOTES, 'UTF-8'));

        return <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$title}</title>
  <style>
    body { margin: 0; background: #f7f2ea; font-family: sans-serif; color: #1f2937; }
    .wrap { max-width: 900px; margin: 0 auto; padding: 48px 20px 80px; }
    article { background: white; border: 1px solid #e5ded1; border-radius: 18px; padding: 26px; }
    h1 { margin-top: 0; font-size: clamp(2rem, 4vw, 3.2rem); }
    .summary { color: #6b7280; font-style: italic; }
    .content { line-height: 1.9; }
    a { color: #1f2937; }
  </style>
</head>
<body>
  <div class="wrap">
    <p><a href="/posts">← 返回文章列表</a> | <a href="/">首页</a></p>
    <article>
      <h1>{$title}</h1>
      <div class="summary">{$summary}</div>
      <div class="content">{$content}</div>
    </article>
  </div>
</body>
</html>
HTML;
    }

    private function openListApi(): string
    {
        header('Content-Type: application/json; charset=utf-8');
        $client = $this->app->openListClient();
        $items = $client->listFolder('/');

        return json_encode([
            'ok' => true,
            'source' => 'OpenList foundation',
            'items' => $items,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function adminRedirect(): string
    {
        header('Location: /admin.php', true, 302);
        return '';
    }

    private function notFound(): string
    {
        http_response_code(404);
        return <<<HTML
<!doctype html>
<html lang="zh-CN">
<head><meta charset="utf-8"><title>404</title></head>
<body>
  <h1>404 Not Found</h1>
  <p>当前页面不存在。</p>
  <p><a href="/">返回首页</a></p>
</body>
</html>
HTML;
    }
}
