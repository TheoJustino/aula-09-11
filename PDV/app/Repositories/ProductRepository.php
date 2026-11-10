<?php

declare(strict_types=1);

namespace PDV\Repositories;

use PDO;

final class ProductRepository
{
    public function __construct(private PDO $connection)
    {
    }

    public function findAvailable(): array
    {
        $statement = $this->connection->query(
            'SELECT id, cod, nome, quantidade, preco_unitario
             FROM produtos
             WHERE quantidade > 0
             ORDER BY nome, id'
        );

        return array_map(self::normalize(...), $statement->fetchAll());
    }

    public function findAll(?string $search = null): array
    {
        $sql = 'SELECT id, cod, nome, categoria, validade, quantidade,
                       preco_unitario, preco_pacote, imagem
                FROM produtos';
        $parameters = [];

        if ($search !== null && trim($search) !== '') {
            $sql .= ' WHERE LOWER(nome) LIKE LOWER(:name_search)
                      OR LOWER(CAST(cod AS TEXT)) LIKE LOWER(:code_search)';
            $parameters['name_search'] = '%' . trim($search) . '%';
            $parameters['code_search'] = '%' . trim($search) . '%';
        }

        $sql .= ' ORDER BY nome, id';
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findForManagementById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, cod, nome, categoria, validade, quantidade,
                    preco_unitario, preco_pacote, imagem
             FROM produtos
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();

        return $product === false ? null : $product;
    }

    public function create(array $product): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO produtos
                (cod, nome, categoria, validade, quantidade, preco_unitario, preco_pacote, imagem)
             VALUES
                (:cod, :nome, :categoria, :validade, :quantidade, :preco_unitario, :preco_pacote, :imagem)
             RETURNING id'
        );
        $statement->execute($product);

        return (int) $statement->fetchColumn();
    }

    public function update(int $id, array $product): bool
    {
        $product['id'] = $id;
        $statement = $this->connection->prepare(
            'UPDATE produtos
             SET cod = :cod,
                 nome = :nome,
                 categoria = :categoria,
                 validade = :validade,
                 quantidade = :quantidade,
                 preco_unitario = :preco_unitario,
                 preco_pacote = :preco_pacote,
                 imagem = :imagem
             WHERE id = :id'
        );

        return $statement->execute($product) && $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM produtos WHERE id = :id');

        return $statement->execute(['id' => $id]) && $statement->rowCount() > 0;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, cod, nome, quantidade, preco_unitario FROM produtos WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : self::normalize($row);
    }

    public function findByIds(array $ids): array
    {
        return $this->queryByIds($ids, false);
    }

    public function lockByIds(array $ids): array
    {
        return $this->queryByIds($ids, true);
    }

    public function decreaseStock(int $id, int $quantity): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE produtos
             SET quantidade = quantidade - :quantity
             WHERE id = :id AND quantidade >= :minimum_quantity'
        );
        $statement->execute([
            'quantity' => $quantity,
            'id' => $id,
            'minimum_quantity' => $quantity,
        ]);

        return $statement->rowCount() === 1;
    }

    private function queryByIds(array $ids, bool $forUpdate): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $placeholders = [];
        $parameters = [];
        foreach ($ids as $index => $id) {
            $placeholder = 'product_id_' . $index;
            $placeholders[] = ':' . $placeholder;
            $parameters[$placeholder] = $id;
        }

        $sql = 'SELECT id, cod, nome, quantidade, preco_unitario
                FROM produtos
                WHERE id IN (' . implode(', ', $placeholders) . ')
                ORDER BY id';
        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $products = [];
        foreach ($statement->fetchAll() as $row) {
            $product = self::normalize($row);
            $products[$product['id']] = $product;
        }

        return $products;
    }

    private static function normalize(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'cod' => (string) ($row['cod'] ?? ''),
            'nome' => (string) $row['nome'],
            'quantidade' => (int) $row['quantidade'],
            'preco_unitario' => (string) $row['preco_unitario'],
        ];
    }
}