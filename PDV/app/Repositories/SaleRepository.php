<?php

declare(strict_types=1);

namespace PDV\Repositories;

use PDO;
use DateTimeImmutable;
use PDV\Models\Sale;
use PDV\Models\SaleItem;
use RuntimeException;

final class SaleRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function create(Sale $sale): Sale
    {
        $statement = $this->connection->prepare(
            'INSERT INTO vendas (cliente_id, valor_total, forma_pagamento)
             VALUES (:customer_id, :total, :payment_method)
             RETURNING id, cliente_id, data_venda, valor_total, forma_pagamento'
        );
        $statement->execute([
            'customer_id' => $sale->clienteId,
            'total' => $sale->valorTotal,
            'payment_method' => $sale->formaPagamento,
        ]);
        $row = $statement->fetch();

        if ($row === false) {
            throw new RuntimeException('Não foi possível registrar a venda.');
        }

        return new Sale(
            (int) $row['id'],
            $row['cliente_id'] === null ? null : (int) $row['cliente_id'],
            (string) $row['valor_total'],
            (string) $row['forma_pagamento'],
            (string) $row['data_venda'],
        );
    }

    public function createItem(SaleItem $item): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, subtotal)
             VALUES (:sale_id, :product_id, :quantity, :unit_price, :subtotal)'
        );
        $statement->execute([
            'sale_id' => $item->vendaId,
            'product_id' => $item->produtoId,
            'quantity' => $item->quantidade,
            'unit_price' => $item->precoUnitario,
            'subtotal' => $item->subtotal,
        ]);
    }

    /** @return Sale[] */
    public function findAll(array $filters = []): array
    {
        $conditions = [];
        $parameters = [];
        $startDate = $filters['data_inicial'] ?? '';
        $endDate = $filters['data_final'] ?? '';
        $paymentMethod = $filters['forma_pagamento'] ?? '';

        if ($startDate !== '') {
            $conditions[] = 'v.data_venda >= :start_date';
            $parameters['start_date'] = $startDate . ' 00:00:00';
        }

        if ($endDate !== '') {
            $endExclusive = DateTimeImmutable::createFromFormat('!Y-m-d', $endDate)
                ->modify('+1 day')
                ->format('Y-m-d');
            $conditions[] = 'v.data_venda < :end_date_exclusive';
            $parameters['end_date_exclusive'] = $endExclusive . ' 00:00:00';
        }

        if ($paymentMethod !== '') {
            $conditions[] = 'v.forma_pagamento = :payment_method';
            $parameters['payment_method'] = $paymentMethod;
        }

        $sql = 'SELECT v.id, v.cliente_id, v.data_venda, v.valor_total,
                       v.forma_pagamento, c.nome AS cliente_nome
                FROM vendas v
                LEFT JOIN clientes c ON c.id = v.cliente_id';
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY v.data_venda DESC, v.id DESC';

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $sales = [];

        foreach ($statement->fetchAll() as $row) {
            $sales[] = new Sale(
                (int) $row['id'],
                $row['cliente_id'] === null ? null : (int) $row['cliente_id'],
                (string) $row['valor_total'],
                (string) $row['forma_pagamento'],
                (string) $row['data_venda'],
                $row['cliente_nome'] === null ? null : (string) $row['cliente_nome'],
            );
        }

        return $sales;
    }

    public function findWithItems(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT v.id AS sale_id, v.cliente_id, v.data_venda, v.valor_total,
                    v.forma_pagamento, c.nome AS cliente_nome,
                    i.id AS item_id, i.produto_id, i.quantidade,
                    i.preco_unitario, i.subtotal, p.nome AS produto_nome
             FROM vendas v
             LEFT JOIN clientes c ON c.id = v.cliente_id
             INNER JOIN itens_venda i ON i.venda_id = v.id
             INNER JOIN produtos p ON p.id = i.produto_id
             WHERE v.id = :id
             ORDER BY i.id'
        );
        $statement->execute(['id' => $id]);
        $rows = $statement->fetchAll();

        if ($rows === []) {
            return null;
        }

        $first = $rows[0];
        $sale = new Sale(
            (int) $first['sale_id'],
            $first['cliente_id'] === null ? null : (int) $first['cliente_id'],
            (string) $first['valor_total'],
            (string) $first['forma_pagamento'],
            (string) $first['data_venda'],
            $first['cliente_nome'] === null ? null : (string) $first['cliente_nome'],
        );
        $items = [];

        foreach ($rows as $row) {
            $items[] = new SaleItem(
                (int) $row['item_id'],
                $sale->id,
                (int) $row['produto_id'],
                (int) $row['quantidade'],
                (string) $row['preco_unitario'],
                (string) $row['subtotal'],
                (string) $row['produto_nome'],
            );
        }

        return ['sale' => $sale, 'items' => $items];
    }
}