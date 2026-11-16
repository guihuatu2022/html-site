<?php

declare(strict_types=1);

namespace App;

final class SettingsRepository
{
    public function __construct(private Database $database) {}

    public function get(string $key, ?string $default = null): ?string
    {
        $row = $this->database->fetchOne('SELECT value_text FROM settings WHERE key_name = :key', ['key' => $key]);
        return $row === null ? $default : (string) ($row['value_text'] ?? '');
    }

    public function setMany(array $settings): void
    {
        $pdo = $this->database->pdo();
        $pdo->beginTransaction();
        try {
            foreach ($settings as $key => $value) {
                if (!is_string($key) || !preg_match('/^[a-z][a-z0-9_.-]{0,127}$/', $key)) {
                    throw new \InvalidArgumentException('Invalid setting key.');
                }
                $existing = $this->get($key);
                if ($existing === null) {
                    $pdo->prepare('INSERT INTO settings (key_name, value_text) VALUES (:key, :value)')->execute(['key' => $key, 'value' => (string) $value]);
                } else {
                    $pdo->prepare('UPDATE settings SET value_text = :value WHERE key_name = :key')->execute(['key' => $key, 'value' => (string) $value]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }
}
