<?php

declare(strict_types=1);

namespace PDV\Models;

final class SaleItem
{
    public function __construct(
        public ?int $id,
        public int $vendaId,
        public int $produtoId,
        public int $quantidade,
        public string $precoUnitario,
        public string $subtotal,
        public ?string $produtoNome = null,
    ) {
    }
}