<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/Database.php';
require_once dirname(__DIR__) . '/app/Models/Customer.php';
require_once dirname(__DIR__) . '/app/Models/Sale.php';
require_once dirname(__DIR__) . '/app/Models/SaleItem.php';
require_once dirname(__DIR__) . '/app/Repositories/CustomerRepository.php';
require_once dirname(__DIR__) . '/app/Repositories/ProductRepository.php';
require_once dirname(__DIR__) . '/app/Repositories/SaleRepository.php';
require_once dirname(__DIR__) . '/app/Controllers/CustomerController.php';
require_once dirname(__DIR__) . '/app/Controllers/CartController.php';
require_once dirname(__DIR__) . '/app/Controllers/SaleController.php';
require_once dirname(__DIR__) . '/app/Controllers/OperatorController.php';
require_once dirname(__DIR__) . '/app/Services/Money.php';
require_once dirname(__DIR__) . '/app/Services/CartService.php';
require_once dirname(__DIR__) . '/app/Services/SaleService.php';
require_once dirname(__DIR__) . '/app/Services/OperatorGuard.php';

use PDV\Config\Database;
use PDV\Controllers\CartController;
use PDV\Controllers\CustomerController;
use PDV\Controllers\OperatorController;
use PDV\Controllers\SaleController;
use PDV\Repositories\CustomerRepository;
use PDV\Repositories\ProductRepository;
use PDV\Repositories\SaleRepository;
use PDV\Services\CartService;
use PDV\Services\SaleService;

$customerController = static fn (): CustomerController => new CustomerController(
    new CustomerRepository(Database::getConnection()),
);
$cartController = static function (): CartController {
    $connection = Database::getConnection();
    $products = new ProductRepository($connection);

    return new CartController(
        new CartService($products),
        $products,
        new CustomerRepository($connection),
    );
};
$saleController = static function (): SaleController {
    $connection = Database::getConnection();
    $products = new ProductRepository($connection);
    $sales = new SaleRepository($connection);
    $cart = new CartService($products);
    $service = new SaleService(
        $connection,
        $products,
        new CustomerRepository($connection),
        $sales,
        $cart,
    );

    return new SaleController($service, $sales);
};
$operatorController = static fn (): OperatorController => new OperatorController();

return [
    'GET /' => static fn (): array => [
        'view' => 'home',
        'title' => 'MVC funcionando',
        'message' => 'A estrutura inicial da aplicação PDV está funcionando.',
    ],
    'GET /clientes' => static fn (): array => $customerController()->index(),
    'GET /clientes/novo' => static fn (): array => $customerController()->create(),
    'POST /clientes' => static fn (): array => $customerController()->store(),
    'GET /clientes/editar' => static fn (): array => $customerController()->edit(),
    'POST /clientes/atualizar' => static fn (): array => $customerController()->update(),
    'POST /clientes/excluir' => static fn (): array => $customerController()->delete(),
    'GET /carrinho' => static fn (): array => $cartController()->index(),
    'POST /carrinho/itens' => static fn (): array => $cartController()->add(),
    'POST /carrinho/quantidade' => static fn (): array => $cartController()->update(),
    'POST /carrinho/remover' => static fn (): array => $cartController()->remove(),
    'POST /vendas' => static fn (): array => $saleController()->store(),
    'GET /vendas/confirmacao' => static fn (): array => $saleController()->confirmation(),
    'GET /operador/acesso' => static fn (): array => $operatorController()->show(),
    'POST /operador/acesso' => static fn (): array => $operatorController()->authenticate(),
    'POST /operador/sair' => static fn (): array => $operatorController()->logout(),
];