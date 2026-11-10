<?php

declare(strict_types=1);

$routes = require dirname(__DIR__) . '/config/routes.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

if (
    $basePath !== ''
    && $basePath !== '/'
    && ($path === $basePath || str_starts_with($path, $basePath . '/'))
) {
    $path = substr($path, strlen($basePath)) ?: '/';
}

$route = $routes["$method $path"] ?? null;

if ($route === null) {
    http_response_code(404);
    $page = [
        'view' => 'errors/404',
        'title' => 'Página não encontrada',
        'message' => 'A rota solicitada não foi encontrada.',
    ];
} else {
    try {
        if (PDV\Services\OperatorGuard::requiresAuthentication($method, $path)
            && !PDV\Services\OperatorGuard::isAuthenticated()) {
            $page = PDV\Services\OperatorGuard::accessRequired();
        } else {
            $page = $route();
        }
    } catch (Throwable $exception) {
        error_log('Falha ao processar uma requisição da aplicação PDV.');
        http_response_code(500);
        $page = [
            'view' => 'errors/500',
            'title' => 'Erro interno',
            'message' => 'Ocorreu um erro ao processar a solicitação. Tente novamente mais tarde.',
        ];
    }
}

$redirect = $page['redirect'] ?? null;
$isSaleConfirmationRedirect = is_string($redirect)
    && preg_match('~\A/vendas/confirmacao\?id=[1-9][0-9]*\z~', $redirect) === 1;

if (
    $redirect === '/clientes'
    || $redirect === '/carrinho'
    || $redirect === '/operador/acesso'
    || $isSaleConfirmationRedirect
) {
    header('Location: ' . $basePath . $redirect, true, 303);
    exit;
}

$title = $page['title'];
$message = $page['message'];
$data = $page['data'] ?? [];
$baseUrl = $basePath;
ob_start();
require dirname(__DIR__) . '/app/Views/' . $page['view'] . '.php';
$content = ob_get_clean();

require dirname(__DIR__) . '/app/Views/layouts/main.php';