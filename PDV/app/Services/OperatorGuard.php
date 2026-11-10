<?php

declare(strict_types=1);

namespace PDV\Services;

final class OperatorGuard
{
    private const AUTH_SESSION_KEY = 'operator_authenticated';
    private const HASH_ENVIRONMENT_KEY = 'PDV_OPERATOR_SECRET_HASH';
    private const PROTECTED_ROUTES = [
        'GET /carrinho',
        'POST /carrinho/itens',
        'POST /carrinho/quantidade',
        'POST /carrinho/remover',
        'POST /vendas',
        'GET /vendas/confirmacao',
        'GET /clientes',
        'GET /clientes/novo',
        'POST /clientes',
        'GET /clientes/editar',
        'POST /clientes/atualizar',
        'POST /clientes/excluir',
    ];

    public static function requiresAuthentication(string $method, string $path): bool
    {
        return in_array($method . ' ' . $path, self::PROTECTED_ROUTES, true);
    }

    public static function accessRequired(): array
    {
        http_response_code(401);

        return [
            'view' => 'operator/access',
            'title' => 'Acesso do operador',
            'message' => '',
            'data' => [
                'csrfToken' => self::csrfToken(),
                'error' => 'Acesso restrito. Informe o segredo do operador para continuar.',
            ],
        ];
    }

    public static function isAuthenticated(): bool
    {
        self::startSession();

        return ($_SESSION[self::AUTH_SESSION_KEY] ?? false) === true;
    }

    public static function csrfToken(): string
    {
        self::startSession();
        $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

        return $_SESSION['csrf_token'];
    }

    public static function hasValidCsrfToken(mixed $token): bool
    {
        self::startSession();
        $sessionToken = $_SESSION['csrf_token'] ?? null;

        return is_string($token)
            && is_string($sessionToken)
            && hash_equals($sessionToken, $token);
    }

    public static function authenticate(string $secret): bool
    {
        self::startSession();
        $hash = self::configuredSecretHash();

        if ($hash === '' || !password_verify($secret, $hash)) {
            return false;
        }

        if (!session_regenerate_id(true)) {
            return false;
        }

        $_SESSION[self::AUTH_SESSION_KEY] = true;

        return true;
    }

    public static function logout(): void
    {
        self::startSession();
        unset($_SESSION[self::AUTH_SESSION_KEY]);
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

    private static function configuredSecretHash(): string
    {
        $hash = getenv(self::HASH_ENVIRONMENT_KEY);
        if (!is_string($hash) || $hash === '') {
            $hash = $_SERVER[self::HASH_ENVIRONMENT_KEY] ?? $_ENV[self::HASH_ENVIRONMENT_KEY] ?? '';
        }

        return is_string($hash) ? trim($hash) : '';
    }
}