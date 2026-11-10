<?php

declare(strict_types=1);

namespace PDV\Services;

use DomainException;

final class Money
{
    private const MAX_CENTS = 99_999_999_999_999;

    public static function toCents(string $amount): int
    {
        if (preg_match('/\A([0-9]+)(?:\.([0-9]+))?\z/', $amount, $matches) !== 1) {
            throw new DomainException('Um produto possui preço inválido.');
        }

        $whole = ltrim($matches[1], '0');
        $whole = $whole === '' ? '0' : $whole;

        if (strlen($whole) > 12) {
            throw new DomainException('O valor da venda excede o limite permitido.');
        }

        $fraction = str_pad($matches[2] ?? '', 3, '0');
        $cents = ((int) $whole * 100) + (int) substr($fraction, 0, 2);

        if ((int) $fraction[2] >= 5) {
            $cents++;
        }
        if ($cents > self::MAX_CENTS) {
            throw new DomainException('O valor da venda excede o limite permitido.');
        }

        return $cents;
    }

    public static function multiply(int $cents, int $quantity): int
    {
        if ($cents < 0 || $cents > self::MAX_CENTS || $quantity < 1
            || ($cents > 0 && $quantity > intdiv(self::MAX_CENTS, $cents))) {
            throw new DomainException('O subtotal excede o limite permitido.');
        }

        return $cents * $quantity;
    }

    public static function add(int $total, int $amount): int
    {
        if ($total < 0 || $amount < 0 || $total > self::MAX_CENTS || $amount > self::MAX_CENTS - $total) {
            throw new DomainException('O total da venda excede o limite permitido.');
        }

        return $total + $amount;
    }

    public static function toDecimal(int $cents): string
    {
        if ($cents < 0) {
            throw new DomainException('Valor monetário inválido.');
        }

        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function format(int $cents): string
    {
        $whole = (string) intdiv($cents, 100);
        $grouped = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole);

        return 'R$ ' . $grouped . ',' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}