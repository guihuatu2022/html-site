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
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public function attempt(string $username, string $password): bool
    {
        $user = $this->database->fetchOne(
            'SELECT id, username, password_hash FROM users WHERE username = :username',
            ['username' => $username]
        );

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = (string) $user['username'];

        return true;
    }

    public function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']) && !empty($_SESSION['username']);
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function requireLogin(): bool
    {
        if ($this->isLoggedIn()) {
            return true;
        }

        http_response_code(401);
        return false;
    }

    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf_token'];
    }

    public function verifyCsrf(?string $token): bool
    {
        return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals((string) $_SESSION['csrf_token'], $token);
    }
}
