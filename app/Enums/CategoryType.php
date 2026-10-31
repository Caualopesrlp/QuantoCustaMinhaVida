<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryType: string
{
    case Income = 'income';
    case FixedExpense = 'fixed_expense';
    case VariableExpense = 'variable_expense';
    case Investment = 'investment';

    public function label(): string
    {
        return match ($this) {
            self::Income => '💰 Receita',
            self::FixedExpense => '📌 Despesa Fixa (Necessidades)',
            self::VariableExpense => '🔄 Despesa Variável (Desejos)',
            self::Investment => '📈 Investimento (Futuro)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Income => 'Receita',
            self::FixedExpense => 'Fixas',
            self::VariableExpense => 'Variáveis',
            self::Investment => 'Investimentos',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Income => 'emerald',
            self::FixedExpense => 'blue',
            self::VariableExpense => 'amber',
            self::Investment => 'indigo',
        };
    }
}
