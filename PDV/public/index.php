<?php

declare(strict_types=1);

$routes = require dirname(__DIR__) . '/config/routes.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath)) ?: '/';
}

$route = $routes["$method $path"] ?? null;

if ($route === null) {
    http_response_code(404);
    $page = [
        'view' => 'home',
        'title' => 'Página não encontrada',
        'message' => 'A rota solicitada não foi encontrada.',
    ];
} else {
    $page = $route();
}

$title = $page['title'];
$message = $page['message'];
ob_start();
require dirname(__DIR__) . '/app/Views/' . $page['view'] . '.php';
$content = ob_get_clean();

require dirname(__DIR__) . '/app/Views/layouts/main.php';