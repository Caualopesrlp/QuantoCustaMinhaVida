<?php

declare(strict_types=1);

namespace App\Strategies;

use App\Models\User;
use App\Strategies\Contracts\BudgetRuleCalculatorInterface;

class CustomPercentageBudgetStrategy implements BudgetRuleCalculatorInterface
{
    public function calculate(
        User $user,
        int $incomeCents,
        int $fixedExpenseCents,
        int $variableExpenseCents,
        int $investmentCents
    ): array {
        $needsPct = (int) ($user->rule_needs_pct ?? 50);
        $wantsPct = (int) ($user->rule_wants_pct ?? 30);
        $investmentsPct = (int) ($user->rule_investments_pct ?? 20);

        $idealNeedsCents = (int) round(($incomeCents * $needsPct) / 100);
        $idealWantsCents = (int) round(($incomeCents * $wantsPct) / 100);
        // Garante fechamento exato dos centavos quando a soma dos percentuais é 100%
        $idealInvestmentsCents = ($needsPct + $wantsPct + $investmentsPct === 100)
            ? max(0, $incomeCents - $idealNeedsCents - $idealWantsCents)
            : (int) round(($incomeCents * $investmentsPct) / 100);

        return [
            'income_cents' => $incomeCents,
            'buckets' => [
                'needs' => $this->buildBucket(
                    key: 'needs',
                    label: "🟢 {$needsPct}% — Necessidades",
                    subtitle: 'Fixas: transporte, celular, assinaturas, moradia',
                    targetPct: $needsPct,
                    idealCents: $idealNeedsCents,
                    actualCents: $fixedExpenseCents,
                    incomeCents: $incomeCents,
                    isInvestment: false
                ),
                'wants' => $this->buildBucket(
                    key: 'wants',
                    label: "🟡 {$wantsPct}% — Desejos",
                    subtitle: 'Variáveis: alimentação fora, lazer, compras',
                    targetPct: $wantsPct,
                    idealCents: $idealWantsCents,
                    actualCents: $variableExpenseCents,
                    incomeCents: $incomeCents,
                    isInvestment: false
                ),
                'investments' => $this->buildBucket(
                    key: 'investments',
                    label: "🔵 {$investmentsPct}% — Investimentos",
                    subtitle: 'Poupança, CDB, Tesouro, Ações...',
                    targetPct: $investmentsPct,
                    idealCents: $idealInvestmentsCents,
                    actualCents: $investmentCents,
                    incomeCents: $incomeCents,
                    isInvestment: true
                ),
            ],
        ];
    }

    /**
     * @return array{
     *   key: string,
     *   label: string,
     *   subtitle: string,
     *   target_pct: int,
     *   ideal_cents: int,
     *   actual_cents: int,
     *   diff_cents: int,
     *   actual_pct_of_income: float,
     *   progress_pct: float,
     *   is_over_budget: bool,
     *   is_goal_met: bool
     * }
     */
    private function buildBucket(
        string $key,
        string $label,
        string $subtitle,
        int $targetPct,
        int $idealCents,
        int $actualCents,
        int $incomeCents,
        bool $isInvestment
    ): array {
        $actualPctOfIncome = $incomeCents > 0
            ? round(($actualCents / $incomeCents) * 100, 1)
            : 0.0;

        $progressPct = $idealCents > 0
            ? min(100.0, round(($actualCents / $idealCents) * 100, 1))
            : ($actualCents > 0 ? 100.0 : 0.0);

        return [
            'key' => $key,
            'label' => $label,
            'subtitle' => $subtitle,
            'target_pct' => $targetPct,
            'ideal_cents' => $idealCents,
            'actual_cents' => $actualCents,
            'diff_cents' => $idealCents - $actualCents,
            'actual_pct_of_income' => $actualPctOfIncome,
            'progress_pct' => $progressPct,
            'is_over_budget' => ! $isInvestment && $incomeCents > 0 && $actualCents > $idealCents,
            'is_goal_met' => $isInvestment && $incomeCents > 0 && $actualCents >= $idealCents,
        ];
    }
}
