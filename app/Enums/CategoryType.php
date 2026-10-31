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
            self::Income => 'Receitas',
            self::FixedExpense => 'Despesas Fixas',
            self::VariableExpense => 'Despesas Variáveis',
            self::Investment => 'Investimentos',
        };
    }

    public function defaultGroupName(): string
    {
        return $this->label();
    }

    public function natureLabel(): string
    {
        return match ($this) {
            self::Income => 'Entrada (Soma na Receita)',
            self::FixedExpense => 'Saída Fixa (Despesa Fixa)',
            self::VariableExpense => 'Saída Variável (Despesa Variável)',
            self::Investment => 'Investimento (Aporte / Reserva)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Income => 'Entrada',
            self::FixedExpense => 'Fixa',
            self::VariableExpense => 'Variável',
            self::Investment => 'Investimento',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Income => 'emerald',
            self::FixedExpense => 'sky',
            self::VariableExpense => 'amber',
            self::Investment => 'teal',
        };
    }
}
