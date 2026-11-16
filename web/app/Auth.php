<?php

declare(strict_types=1);

namespace App;

final class Auth
{
    private string $username;
    private string $passwordHash;

    public function __construct(string $username, string $passwordHash)
    {
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function attempt(string $username, string $password): bool
    {
        if ($username !== $this->username) {
            return false;
        }

        if (!password_verify($password, $this->passwordHash)) {
            return false;
        }

        $_SESSION['auth_logged_in'] = true;
        $_SESSION['auth_username'] = $username;
        return true;
    }

    public function isLoggedIn(): bool
    {
        return !empty($_SESSION['auth_logged_in']) && ($_SESSION['auth_username'] ?? '') === $this->username;
    }

    public function logout(): void
    {
        unset($_SESSION['auth_logged_in'], $_SESSION['auth_username']);
    }

    public function requireLogin(): bool
    {
        if ($this->isLoggedIn()) {
            return true;
        }

        http_response_code(401);
        return false;
    }
}
