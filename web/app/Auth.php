<?php

declare(strict_types=1);

namespace App;

final class Auth
{
    private Database $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
        if (session_status() === PHP_SESSION_NONE) {
            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            session_set_cookie_params(['httponly' => true, 'secure' => $secure, 'samesite' => 'Lax', 'path' => '/']);
            session_start();
        }
    }

    public function attempt(string $username, string $password): bool
    {
        $user = $this->database->fetchOne('SELECT id, username, password_hash FROM users WHERE username = :username', ['username' => $username]);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) return false;
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = (string) $user['username'];
        return true;
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id'], $_SESSION['username']);
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return (string) $_SESSION['csrf_token'];
    }

    public function verifyCsrf(?string $token): bool
    {
        return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], $token);
    }
}
