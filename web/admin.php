<?php

declare(strict_types=1);

namespace App;

final class Admin
{
    public static function handle(Database $database): void
    {
        $auth = new Auth($database);
        $repo = new PostRepository($database);
        $action = $_POST['action'] ?? $_GET['action'] ?? 'dashboard';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $auth->attempt($username, $password);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'logout') {
            $auth->logout();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save-post') {
            if (!$auth->requireLogin()) {
                self::renderLogin(true);
                return;
            }

            $id = (int) ($_POST['id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $slug = trim((string) ($_POST['slug'] ?? ''));
            $summary = trim((string) ($_POST['summary'] ?? ''));
            $content = trim((string) ($_POST['content'] ?? ''));
            $status = trim((string) ($_POST['status'] ?? 'draft')) ?: 'draft';
            $visibility = trim((string) ($_POST['visibility'] ?? 'public')) ?: 'public';

            if ($title === '') {
                self::showDashboard($database, $auth, $repo, '文章标题不能为空。');
                return;
            }

            if ($slug === '') {
                $slug = strtolower((string) preg_replace('/[^a-z0-9\-]+/i', '-', $title));
                $slug = trim((string) $slug, '-');
            }

            if ($slug === '') {
                $slug = 'untitled-post';
            }

            $post = $id > 0 ? $repo->findById($id) : new Post();
            if ($post === null) {
                $post = new Post();
            }

            $post->title = $title;
            $post->slug = $slug;
            $post->summary = $summary;
            $post->content = $content;
            $post->status = $status;
            $post->visibility = $visibility;
            $repo->save($post);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete-post') {
            if (!$auth->requireLogin()) {
                self::renderLogin(true);
                return;
            }

            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $repo->delete($id);
            }
        }

        if (!$auth->isLoggedIn()) {
            self::renderLogin(false);
            return;
        }

        self::showDashboard($database, $auth, $repo, '');
    }

    private static function renderLogin(bool $failed): void
    {
        $message = $failed ? '<p style="color:#c2410c; margin-bottom: 18px;">用户名或密码错误。</p>' : '';
        echo <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>后台登录</title>
  <style>
    body { margin: 0; background: #111827; color: #f3f4f6; font-family: sans-serif; }
    .wrap { min-height: 100vh; display: grid; place-items: center; }
    form { width: min(420px, calc(100vw - 32px)); background: rgba(17,24,39,0.92); border: 1px solid rgba(148,163,184,0.2); border-radius: 16px; padding: 28px; box-shadow: 0 24px 60px rgba(0,0,0,0.35); }
    input, button { width: 100%; box-sizing: border-box; padding: 12px 14px; border-radius: 10px; border: 1px solid rgba(148,163,184,0.2); }
    input { background: rgba(15,23,42,0.85); color: white; margin-bottom: 16px; }
    button { background: #f59e0b; color: #111827; font-weight: 700; border: none; cursor: pointer; }
    a { color: #93c5fd; }
  </style>
</head>
<body>
  <div class="wrap">
    <form method="post">
      <input type="hidden" name="action" value="login">
      <h2>后台登录</h2>
      {$message}
      <label>
        <span>用户名</span>
        <input type="text" name="username" required>
      </label>
      <label>
        <span>密码</span>
        <input type="password" name="password" required>
      </label>
      <button type="submit">登录</button>
      <p><a href="/">返回首页</a></p>
    </form>
  </div>
</body>
</html>
HTML;
    }

    private static function showDashboard(Database $database, Auth $auth, PostRepository $repo, string $message): void
    {
        $published = $repo->listForAdmin('published', 50);
        $drafts = $repo->listForAdmin('draft', 50);
        $siteNameRow = $database->fetchOne("SELECT value_text FROM settings WHERE key_name = 'site_name'");
        $siteName = htmlspecialchars((string) ($siteNameRow['value_text'] ?? 'Guihuatu Blog'), ENT_QUOTES, 'UTF-8');
        $messageHtml = $message !== '' ? '<div style="padding:12px 14px; margin-bottom:18px; background:#fff7ed; border:1px solid #fed7aa; color:#9a5c1e; border-radius:10px;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>' : '';

        $publishedRows = '';
        foreach ($published as $post) {
            $publishedRows .= self::tableRow($post, 'published');
        }
        $draftRows = '';
        foreach ($drafts as $post) {
            $draftRows .= self::tableRow($post, 'draft');
        }

        echo <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$siteName} - 后台</title>
  <style>
    body { margin: 0; background: #f3f4f6; color: #111827; font-family: sans-serif; }
    .topbar { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #111827; color: #f9fafb; }
    .wrap { max-width: 1200px; margin: 0 auto; padding: 28px 20px 80px; }
    .panel { background: white; border-radius: 12px; padding: 20px; border: 1px solid #e5e7eb; box-shadow: 0 12px 26px rgba(17,24,39,0.06); margin-bottom: 26px; }
    .columns { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 22px; }
    .stat { background: white; border:1px solid #e5e7eb; border-radius: 12px; padding: 18px; }
    .stat strong { font-size: 2rem; display: block; margin-top: 10px; }
    input, select, textarea, button { box-sizing: border-box; width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid #d1d5db; }
    label { display: block; margin-bottom: 14px; }
    textarea { min-height: 180px; }
    .btn { background: #111827; color: white; cursor: pointer; }
    .btn-danger { background: #b91c1c; color: white; border: none; }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e5e7eb; }
    .tiny { font-size: 0.8rem; color: #6b7280; }
  </style>
</head>
<body>
  <div class="topbar">
    <div>{$siteName}</div>
    <div>
      <form method="post" style="display:inline;">
        <input type="hidden" name="action" value="logout">
        <button type="submit" class="btn-danger" style="padding:8px 12px; width:auto;">退出</button>
      </form>
    </div>
  </div>

  <div class="wrap">
    <div class="columns">
      <div class="stat"><div class="tiny">文章总数</div><strong>{count($published) + count($drafts)}</strong></div>
      <div class="stat"><div class="tiny">已发布</div><strong>{count($published)}</strong></div>
      <div class="stat"><div class="tiny">草稿</div><strong>{count($drafts)}</strong></div>
    </div>

    {$messageHtml}

    <div class="panel">
      <h2>新增 / 编辑文章</h2>
      <form method="post">
        <input type="hidden" name="action" value="save-post">
        <label>标题
          <input type="text" name="title" placeholder="文章标题" required>
        </label>
        <label>别名（slug）
          <input type="text" name="slug" placeholder="例如：hello-world">
        </label>
        <label>摘要
          <textarea name="summary" placeholder="文章摘要"></textarea>
        </label>
        <label>正文
          <textarea name="content" placeholder="正文内容"></textarea>
        </label>
        <label>状态
          <select name="status">
            <option value="draft">草稿</option>
            <option value="published">已发布</option>
          </select>
        </label>
        <label>可见性
          <select name="visibility">
            <option value="public">公开</option>
            <option value="private">私有</option>
          </select>
        </label>
        <button type="submit" class="btn">保存文章</button>
      </form>
    </div>

    <div class="panel">
      <h2>已发布文章</h2>
      <table>
        <thead><tr><th>标题</th><th>链接</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>{$publishedRows}</tbody>
      </table>
    </div>

    <div class="panel">
      <h2>草稿</h2>
      <table>
        <thead><tr><th>标题</th><th>链接</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>{$draftRows}</tbody>
      </table>
    </div>
  </div>
</body>
</html>
HTML;
    }

    private static function tableRow(Post $post, string $status): string
    {
        $title = htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8');
        $slug = htmlspecialchars($post->slug, ENT_QUOTES, 'UTF-8');
        return "
            <tr>
              <td>{$title}</td>
              <td><a href='/posts/{$slug}' target='_blank'>/posts/{$slug}</a></td>
              <td>{$status}</td>
              <td>
                <form method='post' style='display:inline;'>
                  <input type='hidden' name='action' value='delete-post'>
                  <input type='hidden' name='id' value='{$post->id}'>
                  <button type='submit' class='btn-danger' style='padding:8px 12px; width:auto;'>删除</button>
                </form>
              </td>
            </tr>
        ";
    }
}
