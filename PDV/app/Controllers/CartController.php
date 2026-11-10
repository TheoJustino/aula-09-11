<?php

declare(strict_types=1);

namespace PDV\Controllers;

use DomainException;
use PDV\Repositories\CustomerRepository;
use PDV\Repositories\ProductRepository;
use PDV\Services\CartService;
use PDV\Services\OperatorGuard;

final class CartController
{
    public function __construct(
        private CartService $cart,
        private ProductRepository $products,
        private CustomerRepository $customers,
    ) {
    }

    public function index(): array
    {
        if (!OperatorGuard::isAuthenticated()) {
            return OperatorGuard::accessRequired();
        }

        $summary = $this->cart->summary();
        $products = $this->products->findAvailable();
        $customers = $this->customers->findAll();
        $csrfToken = $this->csrfToken();
        $success = $_SESSION['cart_success'] ?? null;
        $error = $_SESSION['cart_error'] ?? $_SESSION['sale_error'] ?? null;
        unset($_SESSION['cart_success'], $_SESSION['cart_error'], $_SESSION['sale_error']);
        $canCheckout = $summary['items'] !== [];

        foreach ($summary['items'] as $item) {
            if ($item['unavailable'] || $item['quantity'] > $item['stock']) {
                $canCheckout = false;
                break;
            }
        }

        return $this->page('cart/index', 'Carrinho', [
            'items' => $summary['items'],
            'total' => $summary['total'],
            'products' => $products,
            'customers' => $customers,
            'csrfToken' => $csrfToken,
            'success' => $success,
            'error' => $error,
            'canCheckout' => $canCheckout,
        ]);
    }

    public function add(): array
    {
        if (!OperatorGuard::isAuthenticated()) {
            return OperatorGuard::accessRequired();
        }
        if (!OperatorGuard::hasValidCsrfToken($_POST['_csrf'] ?? null)) {
            return $this->forbidden();
        }

        try {
            $productId = $this->positivePostInt('produto_id');
            $quantity = $this->positivePostInt('quantidade');
            $this->cart->add($productId, $quantity);
        } catch (DomainException $exception) {
            return $this->redirectWithFlash('cart_error', $exception->getMessage());
        }

        return $this->redirectWithFlash('cart_success', 'Produto adicionado ao carrinho.');
    }

    public function update(): array
    {
        if (!OperatorGuard::isAuthenticated()) {
            return OperatorGuard::accessRequired();
        }
        if (!OperatorGuard::hasValidCsrfToken($_POST['_csrf'] ?? null)) {
            return $this->forbidden();
        }

        try {
            $productId = $this->positivePostInt('produto_id');
            $quantity = $this->positivePostInt('quantidade');
            $this->cart->update($productId, $quantity);
        } catch (DomainException $exception) {
            return $this->redirectWithFlash('cart_error', $exception->getMessage());
        }

        return $this->redirectWithFlash('cart_success', 'Quantidade atualizada.');
    }

    public function remove(): array
    {
        if (!OperatorGuard::isAuthenticated()) {
            return OperatorGuard::accessRequired();
        }
        if (!OperatorGuard::hasValidCsrfToken($_POST['_csrf'] ?? null)) {
            return $this->forbidden();
        }

        try {
            $productId = $this->positivePostInt('produto_id');
            $this->cart->remove($productId);
        } catch (DomainException $exception) {
            return $this->redirectWithFlash('cart_error', $exception->getMessage());
        }

        return $this->redirectWithFlash('cart_success', 'Produto removido do carrinho.');
    }

    private function positivePostInt(string $key): int
    {
        $value = $_POST[$key] ?? null;
        if (!is_string($value) && !is_int($value)) {
            throw new DomainException('Revise os dados enviados.');
        }

        $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($number === false) {
            throw new DomainException('Revise os dados enviados.');
        }

        return $number;
    }

    private function redirectWithFlash(string $key, string $message): array
    {
        OperatorGuard::startSession();
        $_SESSION[$key] = $message;

        return ['redirect' => '/carrinho'];
    }

    private function forbidden(): array
    {
        return $this->page('errors/403', 'Requisição não autorizada', [
            'message' => 'Não foi possível validar esta requisição. Atualize a página e tente novamente.',
        ], 403);
    }

    private function page(string $view, string $title, array $data = [], int $status = 200): array
    {
        http_response_code($status);

        return ['view' => $view, 'title' => $title, 'message' => '', 'data' => $data];
    }

}