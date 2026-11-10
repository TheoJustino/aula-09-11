<?php

declare(strict_types=1);

namespace PDV\Services;

use DomainException;
use PDV\Repositories\ProductRepository;

final class CartService
{
    private const SESSION_KEY = 'pdv_cart';

    public function __construct(private ProductRepository $products)
    {
    }

    public function quantities(): array
    {
        $this->startSession();
        $sessionCart = $_SESSION[self::SESSION_KEY] ?? [];
        if (!is_array($sessionCart)) {
            $_SESSION[self::SESSION_KEY] = [];
            return [];
        }

        $cart = [];
        foreach ($sessionCart as $productId => $quantity) {
            $id = filter_var((string) $productId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $validQuantity = filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id !== false && $validQuantity !== false) {
                $cart[$id] = $validQuantity;
            }
        }

        $_SESSION[self::SESSION_KEY] = $cart;

        return $cart;
    }

    public function add(int $productId, int $quantity): void
    {
        $this->assertQuantity($quantity);
        $product = $this->products->findById($productId);
        if ($product === null) {
            throw new DomainException('O produto selecionado não foi encontrado.');
        }

        $cart = $this->quantities();
        $currentQuantity = $cart[$productId] ?? 0;
        if ($currentQuantity > $product['quantidade'] || $quantity > $product['quantidade'] - $currentQuantity) {
            throw new DomainException('A quantidade solicitada excede o estoque disponível.');
        }

        $cart[$productId] = $currentQuantity + $quantity;
        $_SESSION[self::SESSION_KEY] = $cart;
    }

    public function update(int $productId, int $quantity): void
    {
        $this->assertQuantity($quantity);
        $cart = $this->quantities();
        if (!array_key_exists($productId, $cart)) {
            throw new DomainException('O produto não está no carrinho.');
        }

        $product = $this->products->findById($productId);
        if ($product === null) {
            throw new DomainException('O produto selecionado não foi encontrado.');
        }
        if ($quantity > $product['quantidade']) {
            throw new DomainException('A quantidade solicitada excede o estoque disponível.');
        }

        $cart[$productId] = $quantity;
        $_SESSION[self::SESSION_KEY] = $cart;
    }

    public function remove(int $productId): void
    {
        $cart = $this->quantities();
        if (!array_key_exists($productId, $cart)) {
            throw new DomainException('O produto não está no carrinho.');
        }

        unset($cart[$productId]);
        $_SESSION[self::SESSION_KEY] = $cart;
    }

    public function summary(): array
    {
        $cart = $this->quantities();
        $products = $this->products->findByIds(array_keys($cart));
        $items = [];
        $totalCents = 0;

        foreach ($cart as $productId => $quantity) {
            $product = $products[$productId] ?? null;
            if ($product === null) {
                $items[] = [
                    'product_id' => $productId,
                    'name' => 'Produto indisponível',
                    'code' => '',
                    'quantity' => $quantity,
                    'stock' => 0,
                    'unit_price' => 'R$ 0,00',
                    'subtotal' => 'R$ 0,00',
                    'unavailable' => true,
                ];
                continue;
            }

            $unitPriceCents = Money::toCents($product['preco_unitario']);
            $subtotalCents = Money::multiply($unitPriceCents, $quantity);
            $totalCents = Money::add($totalCents, $subtotalCents);
            $items[] = [
                'product_id' => $productId,
                'name' => $product['nome'],
                'code' => $product['cod'],
                'quantity' => $quantity,
                'stock' => $product['quantidade'],
                'unit_price' => Money::format($unitPriceCents),
                'subtotal' => Money::format($subtotalCents),
                'unavailable' => false,
            ];
        }

        return ['items' => $items, 'total_cents' => $totalCents, 'total' => Money::format($totalCents)];
    }

    public function clear(): void
    {
        $this->startSession();
        unset($_SESSION[self::SESSION_KEY]);
    }

    private function assertQuantity(int $quantity): void
    {
        if ($quantity < 1) {
            throw new DomainException('A quantidade deve ser um número inteiro maior que zero.');
        }
    }

    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);
        }
    }
}