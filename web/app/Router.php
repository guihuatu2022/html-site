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
        $path = '/' . trim($path, '/');

        if ($path === '/') {
            return $this->home();
        }

        if ($path === '/admin') {
            return $this->admin();
        }

        if ($path === '/posts') {
            return $this->postsList();
        }

        if (str_starts_with($path, '/posts/')) {
            $slug = substr($path, strlen('/posts/'));
            return $this->postDetail($slug);
        }

        if ($path === '/api/openlist') {
            return $this->openListApi();
        }

        return $this->notFound();
    }

    private function home(): string
    {
        $name = $this->app->config('app.name', 'Guihuatu Blog');

        return <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$name}</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; background: #111827; color: #f9fafb; }
    main { max-width: 960px; margin: 0 auto; padding: 80px 20px 60px; }
    .card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 18px; padding: 28px; }
    .nav { display: flex; gap: 18px; margin-bottom: 24px; }
    a { color: #8ec5ff; }
  </style>
</head>
<body>
  <main>
    <div class="nav">
      <a href="/">首页</a>
      <a href="/posts">文章</a>
      <a href="/admin.php">后台</a>
    </div>
    <div class="card">
      <p>博客系统重构骨架</p>
      <h1>{$name}</h1>
      <p>新 PHP 博客系统已启动，当前已具备文章列表、详情和后台基础能力。</p>
      <ul>
        <li><a href="/posts">查看文章列表</a></li>
        <li><a href="/admin.php">进入后台</a></li>
        <li><a href="/api/openlist">查看 OpenList 接口基线</a></li>
      </ul>
    </div>
  </main>
</body>
</html>
HTML;
    }

    private function admin(): string
    {
        return <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>后台入口</title>
  <style>
    body { font-family: sans-serif; padding: 40px; background: #f3f4f6; color: #111827; }
    .panel { max-width: 700px; margin: 0 auto; background: white; border-radius: 12px; padding: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
  </style>
</head>
<body>
  <div class="panel">
    <h1>后台入口</h1>
    <p>已具备文章管理、导航管理、相册管理、网盘管理和设置入口的扩展能力。</p>
    <p><a href="/">返回首页</a></p>
    <p><a href="/admin.php">进入后台登录</a></p>
  </div>
</body>
</html>
HTML;
    }

    private function postsList(): string
    {
        $database = new Database($this->app->config('database', []));
        Installer::ensureSchema($database);
        Installer::ensureDefaults($database);

        $posts = (new PostRepository($database))->list('published', 20);
        $items = '';

        foreach ($posts as $post) {
            $title = htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8');
            $summary = htmlspecialchars($post->summary ?: '暂无摘要', ENT_QUOTES, 'UTF-8');
            $slug = htmlspecialchars($post->slug, ENT_QUOTES, 'UTF-8');
            $items .= "<li><a href=\"/posts/{$slug}\">{$title}</a><p>{$summary}</p></li>";
        }

        if ($items === '') {
            $items = '<li>尚未发布文章。</li>';
        }

        return <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>文章列表</title>
  <style>
    body { font-family: sans-serif; margin: 0; background: #f8fafc; color: #0f172a; }
    main { max-width: 900px; margin: 0 auto; padding: 48px 20px; }
    ul { list-style: none; padding: 0; }
    li { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 18px; }
    a { color: #0f172a; text-decoration: none; }
    a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <main>
    <h1>文章列表</h1>
    <p><a href="/">返回首页</a> | <a href="/admin.php">后台管理</a></p>
    <ul>{$items}</ul>
  </main>
</body>
</html>
HTML;
    }

    private function postDetail(string $slug): string
    {
        $database = new Database($this->app->config('database', []));
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
    body { font-family: sans-serif; margin: 0; background: #f8fafc; color: #0f172a; }
    main { max-width: 900px; margin: 0 auto; padding: 48px 20px; }
    article { background: white; border-radius: 14px; padding: 28px; border: 1px solid #e2e8f0; }
    p { line-height: 1.8; }
  </style>
</head>
<body>
  <main>
    <p><a href="/posts">返回文章列表</a> | <a href="/">首页</a></p>
    <article>
      <h1>{$title}</h1>
      <p><em>{$summary}</em></p>
      <div>{$content}</div>
    </article>
  </main>
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

    private function notFound(): string
    {
        http_response_code(404);
        return <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>404</title>
</head>
<body>
  <h1>404 Not Found</h1>
  <p>当前页面不存在，请返回首页。</p>
  <p><a href="/">返回首页</a></p>
</body>
</html>
HTML;
    }
}
