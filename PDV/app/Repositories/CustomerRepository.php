<?php

declare(strict_types=1);

namespace PDV\Repositories;

use PDO;
use PDV\Models\Customer;
use RuntimeException;

final class CustomerRepository
{
    public function __construct(private PDO $connection)
    {
    }

    /** @return Customer[] */
    public function findAll(): array
    {
        $statement = $this->connection->query(
            'SELECT id, nome, cpf, telefone, email, created_at FROM clientes ORDER BY nome, id'
        );

        return array_map(static fn (array $row): Customer => self::hydrate($row), $statement->fetchAll());
    }

    public function findById(int $id): ?Customer
    {
        $statement = $this->connection->prepare(
            'SELECT id, nome, cpf, telefone, email, created_at FROM clientes WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : self::hydrate($row);
    }

    public function findByCpf(string $cpf): ?Customer
    {
        $statement = $this->connection->prepare(
            'SELECT id, nome, cpf, telefone, email, created_at FROM clientes WHERE cpf = :cpf'
        );
        $statement->execute(['cpf' => $cpf]);
        $row = $statement->fetch();

        return $row === false ? null : self::hydrate($row);
    }

    public function create(Customer $customer): Customer
    {
        $statement = $this->connection->prepare(
            'INSERT INTO clientes (nome, cpf, telefone, email)
             VALUES (:nome, :cpf, :telefone, :email)
             RETURNING id, nome, cpf, telefone, email, created_at'
        );
        $statement->execute(self::parameters($customer));
        $row = $statement->fetch();

        if ($row === false) {
            throw new RuntimeException('Não foi possível cadastrar o cliente.');
        }

        return self::hydrate($row);
    }

    public function update(Customer $customer): bool
    {
        if ($customer->id === null) {
            return false;
        }

        $parameters = self::parameters($customer);
        $parameters['id'] = $customer->id;
        $statement = $this->connection->prepare(
            'UPDATE clientes
             SET nome = :nome, cpf = :cpf, telefone = :telefone, email = :email
             WHERE id = :id'
        );

        return $statement->execute($parameters) && $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM clientes WHERE id = :id');

        return $statement->execute(['id' => $id]) && $statement->rowCount() > 0;
    }

    private static function parameters(Customer $customer): array
    {
        return [
            'nome' => $customer->nome,
            'cpf' => $customer->cpf,
            'telefone' => $customer->telefone,
            'email' => $customer->email,
        ];
    }

    private static function hydrate(array $row): Customer
    {
        return new Customer(
            (int) $row['id'],
            $row['nome'],
            $row['cpf'],
            $row['telefone'],
            $row['email'],
            $row['created_at'],
        );
    }
}