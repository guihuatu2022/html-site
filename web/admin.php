<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$app = require __DIR__ . '/bootstrap.php';
$databaseConfig = $app->config('database', []);
$securityConfig = $app->config('security', []);
$db = new App\Database($databaseConfig);
App\Installer::ensureSchema($db);
App\Installer::ensureDefaults($db);
$repo = new App\PostRepository($db);

$auth = new App\Auth((string) ($securityConfig['admin_user'] ?? 'admin'), (string) ($securityConfig['admin_password'] ?? 'change-me'));

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
    $auth->requireLogin();
    $post = new App\Post();
    $post->id = (int) ($_POST['id'] ?? 0);
    $post->title = trim((string) ($_POST['title'] ?? ''));
    $post->slug = trim((string) ($_POST['slug'] ?? ''));
    $post->summary = trim((string) ($_POST['summary'] ?? ''));
    $post->content = trim((string) ($_POST['content'] ?? ''));
    $post->status = trim((string) ($_POST['status'] ?? 'draft')) ?: 'draft';
    $post->visibility = trim((string) ($_POST['visibility'] ?? 'public')) ?: 'public';

    if ($post->slug === '') {
        $post->slug = strtolower((string) preg_replace('/[^a-z0-9\-]+/i', '-', $post->title));
        $post->slug = trim($post->slug, '-');
    }

    if ($post->title !== '') {
        $repo->save($post);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete-post') {
    $auth->requireLogin();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        $repo->delete($id);
    }
}

if (! $auth->isLoggedIn()) {
    echo renderLoginForm();
    exit;
}

$published = $repo->list('published', 20);
$drafts = $repo->list('draft', 20);
$siteName = $db->fetchOne("SELECT value_text FROM settings WHERE key_name = 'site_name'")['value_text'] ?? 'Guihuatu Blog';

$stats = [
    'posts' => count($published) + count($drafts),
    'files' => (int) $db->fetchOne('SELECT COUNT(*) AS total FROM files')['total'],
    'site_name' => $siteName,
];

echo renderDashboard($stats, $published, $drafts);

function renderLoginForm(): string
{
    $message = (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'login')
        ? '<p style="color:#fca5a5;">用户名或密码错误。</p>'
        : '';

    return <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>后台登录</title>
  <style>
    body { margin: 0; background: linear-gradient(135deg, #0f172a, #111827); font-family: sans-serif; color: #f8fafc; }
    .wrap { min-height: 100vh; display: grid; place-items: center; }
    form { width: min(420px, calc(100vw - 32px)); background: rgba(15, 23, 42, 0.9); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 16px; padding: 28px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
    input, select, textarea { width: 100%; box-sizing: border-box; margin: 10px 0 18px; padding: 12px 14px; border-radius: 10px; border: 1px solid rgba(148,163,184,0.25); background: rgba(15, 23, 42, 0.8); color: white; }
    button { width: 100%; padding: 12px; border: 0; border-radius: 10px; background: #38bdf8; color: #082f49; font-weight: 700; cursor: pointer; }
    p { margin: 0 0 12px; }
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
        <input type="text" name="username" value="" required>
      </label>
      <label>
        <span>密码</span>
        <input type="password" name="password" required>
      </label>
      <button type="submit">登录</button>
      <p style="margin-top:14px;"><a href="/" style="color:#7dd3fc;">返回首页</a></p>
    </form>
  </div>
</body>
</html>
HTML;
}

function renderDashboard(array $stats, array $published, array $drafts): string
{
    $siteName = htmlspecialchars((string) $stats['site_name'], ENT_QUOTES, 'UTF-8');
    $postCount = (int) $stats['posts'];
    $fileCount = (int) $stats['files'];

    $publishedRows = '';
    foreach ($published as $post) {
        $title = htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8');
        $slug = htmlspecialchars($post->slug, ENT_QUOTES, 'UTF-8');
        $publishedRows .= "
            <tr>
              <td>{$title}</td>
              <td>{$slug}</td>
              <td>已发布</td>
              <td>
                <form method='post' style='display:inline;'>
                  <input type='hidden' name='action' value='delete-post'>
                  <input type='hidden' name='id' value='{$post->id}'>
                  <button type='submit'>删除</button>
                </form>
              </td>
            </tr>
        ";
    }

    $draftRows = '';
    foreach ($drafts as $post) {
        $title = htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8');
        $slug = htmlspecialchars($post->slug, ENT_QUOTES, 'UTF-8');
        $draftRows .= "
            <tr>
              <td>{$title}</td>
              <td>{$slug}</td>
              <td>草稿</td>
              <td>
                <form method='post' style='display:inline;'>
                  <input type='hidden' name='action' value='delete-post'>
                  <input type='hidden' name='id' value='{$post->id}'>
                  <button type='submit'>删除</button>
                </form>
              </td>
            </tr>
        ";
    }

    return <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>{$siteName} - 后台</title>
  <style>
    body { margin: 0; padding: 0; font-family: sans-serif; background: #f3f4f6; color: #111827; }
    .topbar { background: #111827; color: white; padding: 18px 32px; display: flex; justify-content: space-between; align-items: center; }
    .card-grid { max-width: 1100px; margin: 30px auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
    .card { background: white; border-radius: 12px; padding: 22px; box-shadow: 0 10px 30px rgba(15,23,42,0.08); }
    .muted { color: #64748b; }
    .big { font-size: 2rem; font-weight: 700; }
    .panel { max-width: 1100px; margin: 0 auto 26px; background: white; padding: 24px; border-radius: 12px; box-shadow: 0 10px 30px rgba(15,23,42,0.08); }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid #e5e7eb; }
    input, select, textarea { width: 100%; box-sizing: border-box; padding: 10px 12px; margin-top: 8px; margin-bottom: 12px; border: 1px solid #d1d5db; border-radius: 8px; }
    textarea { min-height: 180px; }
    button { padding: 9px 14px; border: 0; border-radius: 8px; background: #0f172a; color: white; cursor: pointer; }
  </style>
</head>
<body>
  <div class="topbar">
    <div>{$siteName}</div>
    <div>
      <form method="post" style="display:inline;">
        <input type="hidden" name="action" value="logout">
        <button type="submit" style="background:transparent;border:0;color:#f8fafc;cursor:pointer;">退出</button>
      </form>
    </div>
  </div>

  <div class="card-grid">
    <div class="card">
      <div class="muted">文章</div>
      <div class="big">{$postCount}</div>
    </div>
    <div class="card">
      <div class="muted">文件</div>
      <div class="big">{$fileCount}</div>
    </div>
    <div class="card">
      <div class="muted">状态</div>
      <div class="big">在线</div>
    </div>
  </div>

  <div class="panel">
    <h2>新增 / 编辑文章</h2>
    <form method="post">
      <input type="hidden" name="action" value="save-post">
      <label>标题
        <input type="text" name="title" placeholder="文章标题">
      </label>
      <label>别名 / slug
        <input type="text" name="slug" placeholder="例如：hello-world">
      </label>
      <label>摘要
        <textarea name="summary" placeholder="文章摘要"></textarea>
      </label>
      <label>正文
        <textarea name="content" placeholder="文章正文"></textarea>
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
          <option value="password">密码保护</option>
          <option value="private">私有</option>
        </select>
      </label>
      <button type="submit">保存文章</button>
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
</body>
</html>
HTML;
}
