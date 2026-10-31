<?php

declare(strict_types=1);

namespace App\Strategies\Contracts;

use App\Models\User;

interface BudgetRuleCalculatorInterface
{
    /**
     * Calcula o comparativo Ideal vs Real da regra orçamentária (ex: 50-30-20) em centavos.
     *
     * @return array{
     *   income_cents: int,
     *   buckets: array<string, array{
     *     key: string,
     *     label: string,
     *     subtitle: string,
     *     target_pct: int,
     *     ideal_cents: int,
     *     actual_cents: int,
     *     diff_cents: int,
     *     actual_pct_of_income: float,
     *     progress_pct: float,
     *     is_over_budget: bool,
     *     is_goal_met: bool
     *   }>
     * }
     */
    public function calculate(
        User $user,
        int $incomeCents,
        int $fixedExpenseCents,
        int $variableExpenseCents,
        int $investmentCents
    ): array;
}
