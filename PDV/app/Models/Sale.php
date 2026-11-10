<?php

declare(strict_types=1);

namespace PDV\Models;

final class Sale
{
    public function __construct(
        public ?int $id,
        public ?int $clienteId,
        public string $valorTotal,
        public string $formaPagamento,
        public ?string $dataVenda = null,
        public ?string $clienteNome = null,
    ) {
    }
}