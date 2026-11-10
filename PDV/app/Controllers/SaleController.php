<?php

declare(strict_types=1);

namespace PDV\Controllers;

use DomainException;
use PDV\Repositories\SaleRepository;
use PDV\Services\Money;
use PDV\Services\OperatorGuard;
use PDV\Services\SaleService;

final class SaleController
{
    public function __construct(private SaleService $saleService, private SaleRepository $sales)
    {
    }

    public function store(): array
    {
        if (!OperatorGuard::isAuthenticated()) {
            return OperatorGuard::accessRequired();
        }
        if (!OperatorGuard::hasValidCsrfToken($_POST['_csrf'] ?? null)) {
            return $this->page('errors/403', 'Requisição não autorizada', [
                'message' => 'Não foi possível validar esta requisição. Atualize a página e tente novamente.',
            ], 403);
        }

        try {
            $customerId = $this->optionalCustomerId($_POST['cliente_id'] ?? null);
            $paymentMethod = $_POST['forma_pagamento'] ?? null;
            if (!is_string($paymentMethod)) {
                throw new DomainException('Selecione uma forma de pagamento válida.');
            }

            $saleId = $this->saleService->finalize($customerId, trim($paymentMethod));
        } catch (DomainException $exception) {
            OperatorGuard::startSession();
            $_SESSION['sale_error'] = $exception->getMessage();

            return ['redirect' => '/carrinho'];
        }

        return ['redirect' => '/vendas/confirmacao?id=' . $saleId];
    }

    public function confirmation(): array
    {
        if (!OperatorGuard::isAuthenticated()) {
            return OperatorGuard::accessRequired();
        }

        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $saleData = $id === false ? null : $this->sales->findWithItems($id);

        if ($saleData === null) {
            return $this->page('errors/404', 'Venda não encontrada', [
                'message' => 'A venda solicitada não foi encontrada.',
            ], 404);
        }

        $sale = $saleData['sale'];
        $items = [];
        foreach ($saleData['items'] as $item) {
            $items[] = [
                'sale_item' => $item,
                'unit_price' => Money::format(Money::toCents($item->precoUnitario)),
                'subtotal' => Money::format(Money::toCents($item->subtotal)),
            ];
        }

        return $this->page('sales/confirmation', 'Venda confirmada', [
            'sale' => $sale,
            'total' => Money::format(Money::toCents($sale->valorTotal)),
            'paymentLabel' => $this->paymentLabel($sale->formaPagamento),
            'items' => $items,
        ]);
    }

    private function optionalCustomerId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value) && !is_int($value)) {
            throw new DomainException('Selecione um cliente válido.');
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new DomainException('Selecione um cliente válido.');
        }

        return $id;
    }

    private function paymentLabel(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'dinheiro' => 'Dinheiro',
            'pix' => 'Pix',
            'cartao' => 'Cartão',
            default => 'Não informado',
        };
    }

    private function page(string $view, string $title, array $data = [], int $status = 200): array
    {
        http_response_code($status);

        return ['view' => $view, 'title' => $title, 'message' => $data['message'] ?? '', 'data' => $data];
    }

}