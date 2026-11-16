<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
$app = require __DIR__ . '/bootstrap.php';
$database = $app->database();
App\Schema::ensure($database);

if (App\Installer::isInstalled($database)) {
    http_response_code(403);
    exit('安装已完成。若要重新安装，请先备份并清空数据库。');
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = (string) ($_POST['driver'] ?? 'mysql');
    try {
        $dbConfig = $driver === 'sqlite'
            ? ['driver' => 'sqlite', 'sqlite_path' => dirname(__DIR__) . '/storage/database.sqlite']
            : [
                'driver' => 'mysql',
                'host' => trim((string) ($_POST['host'] ?? '127.0.0.1')),
                'port' => (int) ($_POST['port'] ?? 3306),
                'name' => trim((string) ($_POST['name'] ?? '')),
                'user' => trim((string) ($_POST['user'] ?? '')),
                'password' => (string) ($_POST['password'] ?? ''),
            ];
        $database = new App\Database($dbConfig);
        App\Installer::install(
            $database,
            trim((string) ($_POST['admin_user'] ?? '')),
            (string) ($_POST['admin_password'] ?? ''),
            trim((string) ($_POST['site_name'] ?? ''))
        );
        $installedConfig = [
            'app' => ['name' => trim((string) ($_POST['site_name'] ?? 'Guihuatu Blog')) ?: 'Guihuatu Blog'],
            'database' => $dbConfig,
        ];
        $target = __DIR__ . '/config/installed.php';
        $written = file_put_contents($target, "<?php\nreturn " . var_export($installedConfig, true) . ";\n", LOCK_EX);
        if ($written === false) throw new RuntimeException('无法写入安装配置，请检查目录权限。');
        @chmod($target, 0640);
        header('Location: admin.php');
        exit;
    } catch (Throwable $error) {
        $message = htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}
?>
<!doctype html>
<html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>安装博客</title><link rel="stylesheet" href="public/assets/css/app.css"></head>
<body class="install-page"><main class="install-card"><p class="eyebrow">初次安装</p><h1>安装个人博客</h1><p>配置数据库并创建管理员。SQLite 适合快速试用，MySQL/MariaDB 适合常规部署。</p>
<?php if ($message !== ''): ?><div class="notice notice-error"><?= $message ?></div><?php endif; ?>
<form method="post"><label>数据库类型<select name="driver" id="driver" onchange="document.getElementById('mysql-fields').hidden=this.value==='sqlite'"><option value="mysql">MySQL / MariaDB</option><option value="sqlite">SQLite</option></select></label>
<div id="mysql-fields"><label>主机<input name="host" value="127.0.0.1"></label><label>端口<input name="port" type="number" value="3306"></label><label>数据库名<input name="name" required></label><label>数据库用户名<input name="user" required></label><label>数据库密码<input name="password" type="password"></label></div>
<label>网站名称<input name="site_name" value="Guihuatu Blog"></label><label>管理员用户名<input name="admin_user" required></label><label>管理员密码（至少 12 个字符）<input name="admin_password" type="password" minlength="12" required></label><button class="button" type="submit">开始安装</button></form></main></body></html>
