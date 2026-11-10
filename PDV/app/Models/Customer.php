<?php

declare(strict_types=1);

namespace PDV\Models;

final class Customer
{
    public function __construct(
        public ?int $id,
        public string $nome,
        public string $cpf,
        public ?string $telefone,
        public ?string $email,
        public ?string $createdAt = null,
    ) {
    }
}