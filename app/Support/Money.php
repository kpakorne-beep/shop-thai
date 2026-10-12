<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function toCents($amount): int
    {
        $amount = (string) $amount;

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new InvalidArgumentException('Money amounts must have at most two decimal places.');
        }

        $whole = ltrim($matches[1], '0');
        $whole = $whole === '' ? '0' : $whole;
        if (strlen($whole) > 10) {
            throw new InvalidArgumentException('Money amount is too large.');
        }

        return ((int) $whole * 100) + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    public static function fromCents(int $cents): string
    {
        $whole = intdiv($cents, 100);
        $fraction = str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);

        return $whole.'.'.$fraction;
    }
}
