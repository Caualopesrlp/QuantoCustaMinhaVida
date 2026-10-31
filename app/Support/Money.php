<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    /**
     * Converte entrada monetária (ex: "1.011,25", "1011.25", 1011.25, "R$ 30,00") para centavos (int).
     */
    public static function toCents(int|float|string $value): int
    {
        if (is_int($value)) {
            return $value * 100;
        }

        if (is_float($value)) {
            return (int) round($value * 100);
        }

        $clean = trim(str_replace(['R$', ' ', "\u{00A0}"], '', $value));

        if ($clean === '') {
            return 0;
        }

        // Formato brasileiro: "1.011,25" ou "30,50"
        if (str_contains($clean, ',')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }

        if (! is_numeric($clean)) {
            throw new InvalidArgumentException("Valor monetário inválido: {$value}");
        }

        return (int) round(((float) $clean) * 100);
    }

    /**
     * Formata centavos (int) para string BRL (ex: "R$ 1.011,25" ou "-R$ 499,65").
     */
    public static function format(int $cents, bool $withSymbol = true): string
    {
        $isNegative = $cents < 0;
        $abs = abs($cents) / 100;
        $formatted = number_format($abs, 2, ',', '.');

        if (! $withSymbol) {
            return ($isNegative ? '-' : '') . $formatted;
        }

        return ($isNegative ? '-R$ ' : 'R$ ') . $formatted;
    }

    /**
     * Converte centavos para decimal (ex: 101125 -> "1011.25") útil para inputs numéricos.
     */
    public static function toDecimalString(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
