<?php

declare(strict_types=1);

namespace PDV\Services;

final class CsrfProtection
{
    public static function token(): string
    {
        self::startSession();
        $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

        return $_SESSION['csrf_token'];
    }

    public static function isValid(mixed $token): bool
    {
        self::startSession();
        $sessionToken = $_SESSION['csrf_token'] ?? null;

        return is_string($token)
            && is_string($sessionToken)
            && hash_equals($sessionToken, $token);
    }

    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'use_strict_mode' => true,
            ]);
        }
    }
}