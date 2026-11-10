<?php

declare(strict_types=1);

namespace PDV\Repositories;

use PDO;
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