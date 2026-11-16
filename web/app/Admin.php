<?php

declare(strict_types=1);

namespace App;

final class Admin
{
    public static function handle(Database $database): void
    {
        $auth = new Auth($database);
        $posts = new PostRepository($database);
        $files = new FileRepository($database);
        $settings = new SettingsRepository($database);
        $action = (string) ($_POST['action'] ?? $_GET['action'] ?? 'dashboard');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $publicActions = ['login'];
            if (!in_array($action, $publicActions, true)) {
                if (!$auth->isLoggedIn()) {
                    http_response_code(401);
                    self::renderLogin('请先登录后台。');
                    return;
                }
                if (!$auth->verifyCsrf($_POST['_csrf'] ?? null)) {
                    http_response_code(400);
                    self::renderDashboard($database, $auth, $posts, $files, $settings, '表单校验失败，请刷新页面后重试.');
                    return;
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
            if ($auth->attempt(trim((string) ($_POST['username'] ?? '')), (string) ($_POST['password'] ?? ''))) {
                header('Location: admin.php');
                return;
            }
            self::renderLogin('用户名或密码错误。');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'logout') {
            $auth->logout();
            header('Location: admin.php');
            return;
        }

        if (!$auth->isLoggedIn()) {
            self::renderLogin('');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save-post') {
            $id = (int) ($_POST['id'] ?? 0);
            $post = $id > 0 ? $posts->findById($id) : new Post();
            if ($post === null) {
                self::renderDashboard($database, $auth, $posts, $files, $settings, '文章不存在。');
                return;
            }

            $post->title = trim((string) ($_POST['title'] ?? ''));
            $post->slug = self::slug((string) ($_POST['slug'] ?? ''), $post->title);
            $post->summary = trim((string) ($_POST['summary'] ?? ''));
            $post->content = trim((string) ($_POST['content'] ?? ''));
            $post->status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? (string) $_POST['status'] : 'draft';
            $post->visibility = in_array($_POST['visibility'] ?? '', ['public', 'password', 'private'], true) ? (string) $_POST['visibility'] : 'public';

            $accessPassword = (string) ($_POST['access_password'] ?? '');
            if ($post->visibility === 'password') {
                if ($accessPassword !== '') {
                    $post->passwordHash = password_hash($accessPassword, PASSWORD_DEFAULT);
                } elseif ($post->passwordHash === null) {
                    self::renderDashboard($database, $auth, $posts, $files, $settings, '密码保护文章必须设置访问密码。', $post);
                    return;
                }
            } else {
                $post->passwordHash = null;
            }

            if ($post->title === '' || $post->slug === '') {
                self::renderDashboard($database, $auth, $posts, $files, $settings, '标题不能为空，且必须生成有效别名。', $post);
                return;
            }

            try {
                $posts->save($post);
                header('Location: admin.php?saved=1');
                return;
            } catch (\Throwable) {
                self::renderDashboard($database, $auth, $posts, $files, $settings, '保存失败，可能是文章别名重复。', $post);
                return;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete-post') {
            $posts->delete((int) ($_POST['id'] ?? 0));
            header('Location: admin.php?deleted=1');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save-settings') {
            $settings->setMany([
                'site_name' => trim((string) ($_POST['site_name'] ?? '')),
                'site_description' => trim((string) ($_POST['site_description'] ?? '')),
                'seo_title' => trim((string) ($_POST['seo_title'] ?? '')),
                'seo_description' => trim((string) ($_POST['seo_description'] ?? '')),
                'timezone' => trim((string) ($_POST['timezone'] ?? 'Asia/Shanghai')),
            ]);
            header('Location: admin.php?settings=1');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'test-openlist') {
            try {
                $client = new OpenListClient([
                    'base_url' => trim((string) ($_POST['openlist_url'] ?? '')),
                    'token' => trim((string) ($_POST['openlist_token'] ?? '')),
                    'root_path' => trim((string) ($_POST['openlist_root'] ?? '/')),
                ]);
                $client->listFolder('/');
                self::renderDashboard($database, $auth, $posts, $files, $settings, 'OpenList 连接成功。');
            } catch (\Throwable $error) {
                self::renderDashboard($database, $auth, $posts, $files, $settings, 'OpenList 连接失败：' . $error->getMessage());
            }
            return;
        }

        $message = isset($_GET['saved']) ? '文章已保存。' : (isset($_GET['deleted']) ? '文章已删除。' : (isset($_GET['settings']) ? '设置已保存。' : ''));
        self::renderDashboard($database, $auth, $posts, $files, $settings, $message);
    }

    private static function slug(string $slug, string $title): string
    {
        $slug = trim($slug);
        if ($slug === '') {
            $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $title));
        }
        return trim($slug, '-');
    }

    private static function renderLogin(string $message): void
    {
        $messageHtml = $message !== '' ? '<div class="notice notice-error">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>' : '';
        echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>后台登录</title><link rel="stylesheet" href="public/assets/css/app.css"></head><body class="admin-page"><main class="login-card"><p class="eyebrow">后台管理</p><h1>欢迎回来</h1>' . $messageHtml . '<form method="post"><input type="hidden" name="action" value="login"><label>用户名<input name="username" autocomplete="username" required></label><label>密码<input type="password" name="password" autocomplete="current-password" required></label><button class="button" type="submit">登录</button></form><p><a href="index.php">返回前台</a></p></main></body></html>';
    }

    private static function renderDashboard(Database $database, Auth $auth, PostRepository $posts, FileRepository $files, SettingsRepository $settings, string $message, ?Post $editing = null): void
    {
        $published = $posts->listForAdmin('published', 100);
        $drafts = $posts->listForAdmin('draft', 100);
        $token = htmlspecialchars($auth->csrfToken(), ENT_QUOTES, 'UTF-8');
        $notice = $message !== '' ? '<div class="notice">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>' : '';
        $postRows = '';
        foreach (array_merge($published, $drafts) as $post) {
            $title = htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8');
            $slug = htmlspecialchars($post->slug, ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars($post->status, ENT_QUOTES, 'UTF-8');
            $visibility = htmlspecialchars($post->visibility, ENT_QUOTES, 'UTF-8');
            $postRows .= '<tr><td>' . $title . '</td><td>' . $slug . '</td><td>' . $status . '</td><td>' . $visibility . '</td><td><a href="?edit=' . $post->id . '">编辑</a> <form class="inline" method="post"><input type="hidden" name="_csrf" value="' . $token . '"><input type="hidden" name="action" value="delete-post"><input type="hidden" name="id" value="' . $post->id . '"><button class="link-danger" type="submit">删除</button></form></td></tr>';
        }
        if ($postRows === '') $postRows = '<tr><td colspan="5">还没有文章。</td></tr>';

        if ($editing === null && isset($_GET['edit'])) $editing = $posts->findById((int) $_GET['edit']);
        $editing ??= new Post();
        $id = $editing->id;
        $title = htmlspecialchars($editing->title, ENT_QUOTES, 'UTF-8');
        $slug = htmlspecialchars($editing->slug, ENT_QUOTES, 'UTF-8');
        $summary = htmlspecialchars($editing->summary, ENT_QUOTES, 'UTF-8');
        $content = htmlspecialchars($editing->content, ENT_QUOTES, 'UTF-8');
        $statusDraft = $editing->status === 'draft' ? 'selected' : '';
        $statusPublished = $editing->status === 'published' ? 'selected' : '';
        $visPublic = $editing->visibility === 'public' ? 'selected' : '';
        $visPassword = $editing->visibility === 'password' ? 'selected' : '';
        $visPrivate = $editing->visibility === 'private' ? 'selected' : '';
        $siteName = htmlspecialchars($settings->get('site_name', 'Guihuatu Blog') ?? 'Guihuatu Blog', ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($settings->get('site_description', '') ?? '', ENT_QUOTES, 'UTF-8');
        $seoTitle = htmlspecialchars($settings->get('seo_title', '') ?? '', ENT_QUOTES, 'UTF-8');
        $seoDescription = htmlspecialchars($settings->get('seo_description', '') ?? '', ENT_QUOTES, 'UTF-8');
        $openUrl = htmlspecialchars($settings->get('openlist_url', '') ?? '', ENT_QUOTES, 'UTF-8');
        $openRoot = htmlspecialchars($settings->get('openlist_root', '/') ?? '/', ENT_QUOTES, 'UTF-8');

        echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>后台管理</title><link rel="stylesheet" href="public/assets/css/app.css"></head><body class="admin-page">';
        echo '<header class="admin-top"><div><span class="brand-mark">印</span><strong>' . $siteName . '</strong><span class="muted">管理后台</span></div><form method="post"><input type="hidden" name="_csrf" value="' . $token . '"><input type="hidden" name="action" value="logout"><button class="button button-quiet" type="submit">退出</button></form></header>';
        echo '<main class="admin-shell"><nav class="admin-nav"><a href="#posts">文章管理</a><a href="#settings">站点设置</a><a href="#storage">OpenList</a><a href="index.php">查看前台</a></nav>';
        echo $notice;
        echo '<section class="stats"><article class="stat-card"><span>文章</span><strong>' . (count($published) + count($drafts)) . '</strong></article><article class="stat-card"><span>已发布</span><strong>' . count($published) . '</strong></article><article class="stat-card"><span>文件记录</span><strong>' . count($files->list(200)) . '</strong></article></section>';
        echo '<section class="admin-panel" id="posts"><div class="section-heading"><div><p class="eyebrow">内容管理</p><h1>文章</h1></div></div>';
        echo '<form method="post" class="post-form"><input type="hidden" name="_csrf" value="' . $token . '"><input type="hidden" name="action" value="save-post"><input type="hidden" name="id" value="' . $id . '"><label>标题<input name="title" value="' . $title . '" required></label><label>URL 别名<input name="slug" value="' . $slug . '" placeholder="可用英文和短横线"></label><label>摘要<textarea name="summary" rows="3">' . $summary . '</textarea></label><label>正文<textarea name="content" rows="12">' . $content . '</textarea></label><div class="form-row"><label>状态<select name="status"><option value="draft" ' . $statusDraft . '>草稿</option><option value="published" ' . $statusPublished . '>已发布</option></select></label><label>可见性<select name="visibility"><option value="public" ' . $visPublic . '>公开</option><option value="password" ' . $visPassword . '>密码保护（密码字段随后补齐）</option><option value="private" ' . $visPrivate . '>仅管理员</option></select></label></div><label>访问密码（密码保护文章）<input type="password" name="access_password" autocomplete="new-password"></label><button class="button" type="submit">保存文章</button></form><div class="table-wrap"><table><thead><tr><th>标题</th><th>别名</th><th>状态</th><th>权限</th><th>操作</th></tr></thead><tbody>' . $postRows . '</tbody></table></div></section>';
        echo '<section class="admin-panel" id="settings"><p class="eyebrow">站点配置</p><h2>基础与 SEO 设置</h2><form method="post" class="post-form"><input type="hidden" name="_csrf" value="' . $token . '"><input type="hidden" name="action" value="save-settings"><label>站点名称<input name="site_name" value="' . $siteName . '"></label><label>站点描述<textarea name="site_description" rows="2">' . $description . '</textarea></label><label>SEO 标题<input name="seo_title" value="' . $seoTitle . '"></label><label>SEO 描述<textarea name="seo_description" rows="2">' . $seoDescription . '</textarea></label><button class="button" type="submit">保存设置</button></form></section>';
        echo '<section class="admin-panel" id="storage"><p class="eyebrow">文件存储</p><h2>OpenList 连接检测</h2><form method="post" class="post-form"><input type="hidden" name="_csrf" value="' . $token . '"><input type="hidden" name="action" value="test-openlist"><label>OpenList URL<input type="url" name="openlist_url" value="' . $openUrl . '" placeholder="https://files.example.com"></label><label>API Token<input type="password" name="openlist_token" autocomplete="new-password" placeholder="留空则不修改已保存凭证"></label><label>博客根目录<input name="openlist_root" value="' . $openRoot . '"></label><button class="button button-secondary" type="submit">测试目录读取</button><p class="hint">真实 OpenList 连接需使用你的实例地址与 Token。凭证目前不会自动持久化到安装配置。</p></form></section></main></body></html>';
    }
}
