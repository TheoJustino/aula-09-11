<?php

declare(strict_types=1);

namespace PDV\Services;

use DomainException;
use PDO;
use PDV\Models\Sale;
use PDV\Models\SaleItem;
use PDV\Repositories\CustomerRepository;
use PDV\Repositories\ProductRepository;
use PDV\Repositories\SaleRepository;
use Throwable;

final class SaleService
{
    private const PAYMENT_METHODS = ['dinheiro', 'pix', 'cartao'];

    public function __construct(
        private PDO $connection,
        private ProductRepository $products,
        private CustomerRepository $customers,
        private SaleRepository $sales,
        private CartService $cart,
    ) {
    }

    public function finalize(?int $customerId, string $paymentMethod): int
    {
        $quantities = $this->cart->quantities();
        if ($quantities === []) {
            throw new DomainException('Adicione ao menos um produto antes de finalizar a venda.');
        }
        if (!in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            throw new DomainException('Selecione uma forma de pagamento válida.');
        }
        if ($customerId !== null && ($customerId < 1 || $this->customers->findById($customerId) === null)) {
            throw new DomainException('O cliente selecionado não foi encontrado.');
        }

        $this->connection->beginTransaction();

        try {
            $products = $this->products->lockByIds(array_keys($quantities));
            if (count($products) !== count($quantities)) {
                throw new DomainException('Um ou mais produtos do carrinho não estão mais disponíveis.');
            }

            $saleItems = [];
            $totalCents = 0;
            foreach ($quantities as $productId => $quantity) {
                $product = $products[$productId] ?? null;
                if ($product === null) {
                    throw new DomainException('Um ou mais produtos do carrinho não estão mais disponíveis.');
                }
                if ($quantity < 1 || $quantity > $product['quantidade']) {
                    throw new DomainException('O estoque de um ou mais produtos foi alterado. Revise o carrinho.');
                }

                $unitPriceCents = Money::toCents($product['preco_unitario']);
                $subtotalCents = Money::multiply($unitPriceCents, $quantity);
                $totalCents = Money::add($totalCents, $subtotalCents);
                $saleItems[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price_cents' => $unitPriceCents,
                    'subtotal_cents' => $subtotalCents,
                ];
            }

            $sale = $this->sales->create(new Sale(
                null,
                $customerId,
                Money::toDecimal($totalCents),
                $paymentMethod,
            ));
            if ($sale->id === null) {
                throw new DomainException('Não foi possível registrar a venda.');
            }

            foreach ($saleItems as $item) {
                $this->sales->createItem(new SaleItem(
                    null,
                    $sale->id,
                    $item['product_id'],
                    $item['quantity'],
                    Money::toDecimal($item['unit_price_cents']),
                    Money::toDecimal($item['subtotal_cents']),
                ));

                if (!$this->products->decreaseStock($item['product_id'], $item['quantity'])) {
                    throw new DomainException('Não foi possível atualizar o estoque. Revise o carrinho.');
                }
            }

            if (!$this->connection->commit()) {
                throw new DomainException('Não foi possível confirmar a venda.');
            }
        } catch (Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }

        $this->cart->clear();

        return $sale->id;
    }
}