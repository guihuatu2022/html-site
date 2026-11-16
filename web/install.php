<?php

declare(strict_types=1);

$bootstrap = require __DIR__ . '/bootstrap.php';
$database = new App\Database($bootstrap->config('database', []));

$message = '';
if (App\Installer::isInstalled($database)) {
    http_response_code(403);
    exit('安装已完成。');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = (string) ($_POST['driver'] ?? 'mysql');
    $dbConfig = $driver === 'sqlite'
        ? ['driver' => 'sqlite', 'sqlite_path' => dirname(__DIR__) . '/storage/database.sqlite']
        : [
            'driver' => 'mysql', 'host' => trim((string) ($_POST['host'] ?? '127.0.0.1')),
            'port' => (int) ($_POST['port'] ?? 3306), 'name' => trim((string) ($_POST['name'] ?? '')),
            'user' => trim((string) ($_POST['user'] ?? '')), 'password' => (string) ($_POST['password'] ?? ''),
        ];
    try {
        $database = new App\Database($dbConfig);
        App\Installer::install($database, trim((string) ($_POST['admin_user'] ?? '')), (string) ($_POST['admin_password'] ?? ''), trim((string) ($_POST['site_name'] ?? '')));
        $configPath = __DIR__ . '/config/installed.php';
        $installedConfig = ['app' => ['name' => trim((string) ($_POST['site_name'] ?? 'Guihuatu Blog')) ?: 'Guihuatu Blog'], 'database' => $dbConfig];
        $written = file_put_contents($configPath, "<?php\nreturn " . var_export($installedConfig, true) . ";\n", LOCK_EX);
        if ($written === false) throw new RuntimeException('无法写入安装配置，请检查 web/config 目录权限。');
        @chmod($configPath, 0640);
        header('Location: admin.php');
        exit;
    } catch (Throwable $error) {
        $message = htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}
?>
<!doctype html>
<html lang="zh-CN"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>安装博客</title>
<style>body{font:16px sans-serif;background:#f7f1e3;color:#2c2c2c;margin:0;padding:32px}.box{max-width:680px;margin:auto;background:#fff;padding:28px;border:1px solid #dcd3b2;border-radius:8px}label{display:block;margin:14px 0}input,select{box-sizing:border-box;width:100%;padding:10px;border:1px solid #dcd3b2;border-radius:4px}button{background:#8b3a3a;color:#fff;border:0;padding:12px 20px;cursor:pointer}.error{color:#a93226}</style>
<div class="box"><h1>安装个人博客</h1><p>先配置数据库并创建管理员。SQLite 适合快速试用，MySQL/MariaDB 适合常规部署。</p><?php if ($message !== ''): ?><p class="error"><?= $message ?></p><?php endif; ?>
<form method="post"><label>数据库类型<select name="driver" id="driver" onchange="document.getElementById('mysql-fields').hidden=this.value==='sqlite'"><option value="mysql">MySQL / MariaDB</option><option value="sqlite">SQLite</option></select></label>
<div id="mysql-fields"><label>数据库主机<input name="host" value="127.0.0.1"></label><label>端口<input name="port" type="number" value="3306"></label><label>数据库名<input name="name" required></label><label>数据库用户名<input name="user" required></label><label>数据库密码<input name="password" type="password"></label></div>
<label>网站名称<input name="site_name" value="Guihuatu Blog"></label><label>管理员用户名<input name="admin_user" required></label><label>管理员密码（至少 12 个字符）<input name="admin_password" type="password" minlength="12" required></label><button type="submit">开始安装</button></form></div>
