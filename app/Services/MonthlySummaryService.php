<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Strategies\Contracts\BudgetRuleCalculatorInterface;
use Illuminate\Support\Collection;

class MonthlySummaryService
{
    public const MONTH_NAMES = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly MonthlyBudgetService $monthlyBudgetService,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly BudgetRuleCalculatorInterface $budgetRuleCalculator,
    ) {}

    /**
     * Retorna todos os dados consolidados do mês (Resumo, % da Renda, Regra 50-30-20, Saldo Acumulado e Grupos por Categoria).
     * Inicializa automaticamente o mês com o molde recorrente caso esteja vazio.
     *
     * @return array<string, mixed>
     */
    public function getMonthlyDashboard(User $user, int $year, int $month): array
    {
        $categories = $this->categoryService->listForUser($user);
        $autoInjectedCount = $this->monthlyBudgetService->ensureMonthInitialized((int) $user->id, $year, $month);
        $transactions = $this->transactionRepository->getByMonth((int) $user->id, $year, $month);

        $incomeCents = $this->sumByType($transactions, CategoryType::Income);
        $fixedCents = $this->sumByType($transactions, CategoryType::FixedExpense);
        $variableCents = $this->sumByType($transactions, CategoryType::VariableExpense);
        $investmentCents = $this->sumByType($transactions, CategoryType::Investment);

        $balanceCents = $incomeCents - $fixedCents - $variableCents - $investmentCents;

        $status = $this->resolveBalanceStatus($balanceCents);

        $fixedPct = $incomeCents > 0 ? round(($fixedCents / $incomeCents) * 100, 1) : 0.0;
        $variablePct = $incomeCents > 0 ? round(($variableCents / $incomeCents) * 100, 1) : 0.0;
        $investmentPct = $incomeCents > 0 ? round(($investmentCents / $incomeCents) * 100, 1) : 0.0;

        $budgetRule = $this->budgetRuleCalculator->calculate(
            user: $user,
            incomeCents: $incomeCents,
            fixedExpenseCents: $fixedCents,
            variableExpenseCents: $variableCents,
            investmentCents: $investmentCents
        );

        $accumulatedBalanceCents = $this->calculateAccumulatedBalanceUpTo((int) $user->id, $year, $month);

        $blocks = [
            CategoryType::Income->value => $this->buildBlockGroup($categories, $transactions, CategoryType::Income),
            CategoryType::FixedExpense->value => $this->buildBlockGroup($categories, $transactions, CategoryType::FixedExpense),
            CategoryType::VariableExpense->value => $this->buildBlockGroup($categories, $transactions, CategoryType::VariableExpense),
            CategoryType::Investment->value => $this->buildBlockGroup($categories, $transactions, CategoryType::Investment),
        ];

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => self::MONTH_NAMES[$month] ?? (string) $month,
            'auto_injected_count' => $autoInjectedCount,
            'navigation' => $this->buildMonthNavigation($year, $month),
            'totals' => [
                'income_cents' => $incomeCents,
                'fixed_cents' => $fixedCents,
                'variable_cents' => $variableCents,
                'investment_cents' => $investmentCents,
                'total_outflow_cents' => $fixedCents + $variableCents + $investmentCents,
                'balance_cents' => $balanceCents,
                'accumulated_balance_cents' => $accumulatedBalanceCents,
                'fixed_pct' => $fixedPct,
                'variable_pct' => $variablePct,
                'investment_pct' => $investmentPct,
                'status_badge' => $status['badge'],
                'status_color' => $status['color'],
            ],
            'budget_rule' => $budgetRule,
            'blocks' => $blocks,
            'categories' => $categories->where('is_active', true)->values(),
            'transactions_count' => $transactions->count(),
        ];
    }

    /**
     * Consolida a aba "Investimentos 📈" e o balanço anual de Janeiro a Dezembro.
     *
     * @return array<string, mixed>
     */
    public function getAnnualInvestmentReport(User $user, int $year): array
    {
        $yearTransactions = $this->transactionRepository->getByYear((int) $user->id, $year);

        $months = [];
        $accumulatedInvestedCents = 0;
        $totalAnnualIncomeCents = 0;
        $totalAnnualFixedCents = 0;
        $totalAnnualVariableCents = 0;

        for ($m = 1; $m <= 12; $m++) {
            $monthTxs = $yearTransactions->where('month', $m);

            $monthIncome = $this->sumByType($monthTxs, CategoryType::Income);
            $monthFixed = $this->sumByType($monthTxs, CategoryType::FixedExpense);
            $monthVariable = $this->sumByType($monthTxs, CategoryType::VariableExpense);

            $investmentTxs = $monthTxs->filter(
                fn (Transaction $tx) => $tx->category?->type === CategoryType::Investment
            )->values();

            $monthInvestedCents = (int) $investmentTxs->sum('amount_cents');
            $hasInvestment = $monthInvestedCents > 0;

            if ($hasInvestment) {
                $accumulatedInvestedCents += $monthInvestedCents;
            }

            $totalAnnualIncomeCents += $monthIncome;
            $totalAnnualFixedCents += $monthFixed;
            $totalAnnualVariableCents += $monthVariable;

            $notesSummary = $investmentTxs
                ->map(function (Transaction $tx): string {
                    if ($tx->notes && mb_strtolower($tx->description) === 'investimento') {
                        return $tx->notes;
                    }
                    if ($tx->notes) {
                        return "{$tx->description} ({$tx->notes})";
                    }

                    return $tx->description;
                })
                ->filter()
                ->unique()
                ->implode(', ');

            $months[$m] = [
                'month' => $m,
                'month_name' => self::MONTH_NAMES[$m],
                'invested_cents' => $monthInvestedCents,
                'accumulated_cents' => $hasInvestment ? $accumulatedInvestedCents : 0,
                'has_investment' => $hasInvestment,
                'notes_summary' => $notesSummary,
                'items' => $investmentTxs,
                'income_cents' => $monthIncome,
                'fixed_cents' => $monthFixed,
                'variable_cents' => $monthVariable,
                'balance_cents' => $monthIncome - $monthFixed - $monthVariable - $monthInvestedCents,
            ];
        }

        return [
            'year' => $year,
            'months' => $months,
            'total_invested_cents' => $accumulatedInvestedCents,
            'total_income_cents' => $totalAnnualIncomeCents,
            'total_fixed_cents' => $totalAnnualFixedCents,
            'total_variable_cents' => $totalAnnualVariableCents,
            'total_balance_cents' => $totalAnnualIncomeCents - $totalAnnualFixedCents - $totalAnnualVariableCents - $accumulatedInvestedCents,
        ];
    }

    /**
     * @param Collection<int, Transaction> $transactions
     */
    private function sumByType(Collection $transactions, CategoryType $type): int
    {
        return (int) $transactions
            ->filter(fn (Transaction $tx) => $tx->category?->type === $type)
            ->sum('amount_cents');
    }

    /**
     * @return array{badge: string, color: string}
     */
    public function resolveBalanceStatus(int $balanceCents): array
    {
        if ($balanceCents > 0) {
            return ['badge' => '✅ Sobrando 😊', 'color' => 'emerald'];
        }

        if ($balanceCents < 0) {
            return ['badge' => '⚠️ No vermelho', 'color' => 'rose'];
        }

        return ['badge' => '🟰 Zero a zero', 'color' => 'slate'];
    }

    private function calculateAccumulatedBalanceUpTo(int $userId, int $year, int $month): int
    {
        $allTransactions = $this->transactionRepository->getAllUpToMonth($userId, $year, $month);

        $income = $this->sumByType($allTransactions, CategoryType::Income);
        $fixed = $this->sumByType($allTransactions, CategoryType::FixedExpense);
        $variable = $this->sumByType($allTransactions, CategoryType::VariableExpense);
        $investment = $this->sumByType($allTransactions, CategoryType::Investment);

        return $income - $fixed - $variable - $investment;
    }

    /**
     * @param Collection<int, Category> $categories
     * @param Collection<int, Transaction> $transactions
     * @return array{
     *   type: CategoryType,
     *   label: string,
     *   subtotal_cents: int,
     *   categories: Collection<int, array{
     *     category: Category,
     *     subtotal_cents: int,
     *     notes_summary: string,
     *     transactions: Collection<int, Transaction>
     *   }>
     * }
     */
    private function buildBlockGroup(Collection $categories, Collection $transactions, CategoryType $type): array
    {
        $typeCategories = $categories->filter(fn (Category $cat) => $cat->type === $type)->values();

        $groupedCategories = $typeCategories->map(function (Category $category) use ($transactions): ?array {
            $catTransactions = $transactions
                ->where('category_id', $category->id)
                ->values();

            // Oculta categorias inativas que não possuem lançamentos neste mês
            if (! $category->is_active && $catTransactions->isEmpty()) {
                return null;
            }

            $subtotalCents = (int) $catTransactions->sum('amount_cents');

            $notesParts = $catTransactions->map(function (Transaction $tx) use ($category): ?string {
                $descDiffers = mb_strtolower(trim($tx->description)) !== mb_strtolower(trim($category->name));
                if ($descDiffers && $tx->notes) {
                    return "{$tx->description} ({$tx->notes})";
                }
                if ($descDiffers) {
                    return $tx->description;
                }

                return $tx->notes;
            })->filter()->unique()->values()->all();

            return [
                'category' => $category,
                'subtotal_cents' => $subtotalCents,
                'notes_summary' => implode(', ', $notesParts),
                'transactions' => $catTransactions,
            ];
        })->filter()->values();

        return [
            'type' => $type,
            'label' => $type->label(),
            'subtotal_cents' => (int) $groupedCategories->sum('subtotal_cents'),
            'categories' => $groupedCategories,
        ];
    }

    /**
     * @return array{prev_year: int, prev_month: int, next_year: int, next_month: int}
     */
    private function buildMonthNavigation(int $year, int $month): array
    {
        $prevMonth = $month === 1 ? 12 : $month - 1;
        $prevYear = $month === 1 ? $year - 1 : $year;

        $nextMonth = $month === 12 ? 1 : $month + 1;
        $nextYear = $month === 12 ? $year + 1 : $year;

        return [
            'prev_year' => $prevYear,
            'prev_month' => $prevMonth,
            'next_year' => $nextYear,
            'next_month' => $nextMonth,
        ];
    }
}
