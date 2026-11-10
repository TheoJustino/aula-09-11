<?php

declare(strict_types=1);

namespace PDV\Controllers;

use PDV\Services\OperatorGuard;

final class OperatorController
{
    public function show(): array
    {
        if (OperatorGuard::isAuthenticated()) {
            return ['redirect' => '/carrinho'];
        }

        OperatorGuard::startSession();
        $error = $_SESSION['operator_access_error'] ?? null;
        unset($_SESSION['operator_access_error']);

        return [
            'view' => 'operator/access',
            'title' => 'Acesso do operador',
            'message' => '',
            'data' => [
                'csrfToken' => OperatorGuard::csrfToken(),
                'error' => $error,
            ],
        ];
    }

    public function authenticate(): array
    {
        if (!OperatorGuard::hasValidCsrfToken($_POST['_csrf'] ?? null)) {
            return $this->forbidden();
        }

        $secret = $_POST['secret'] ?? null;
        if (!is_string($secret) || !OperatorGuard::authenticate($secret)) {
            OperatorGuard::startSession();
            $_SESSION['operator_access_error'] = 'Não foi possível validar o acesso. Verifique os dados e tente novamente.';

            return ['redirect' => '/operador/acesso'];
        }

        unset($_SESSION['operator_access_error']);

        return ['redirect' => '/carrinho'];
    }

    public function logout(): array
    {
        if (!OperatorGuard::hasValidCsrfToken($_POST['_csrf'] ?? null)) {
            return $this->forbidden();
        }

        OperatorGuard::logout();

        return ['redirect' => '/operador/acesso'];
    }

    private function forbidden(): array
    {
        http_response_code(403);

        return [
            'view' => 'errors/403',
            'title' => 'Requisição não autorizada',
            'message' => 'Não foi possível validar esta requisição. Atualize a página e tente novamente.',
            'data' => [],
        ];
    }
}