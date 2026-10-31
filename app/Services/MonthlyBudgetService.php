<?php

declare(strict_types=1);

namespace App\Services;

use App\Factories\RecurringTransactionFactory;
use App\Repositories\Contracts\RecurringTemplateRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class MonthlyBudgetService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly RecurringTemplateRepositoryInterface $recurringTemplateRepository,
        private readonly RecurringTransactionFactory $recurringTransactionFactory,
    ) {}

    /**
     * Garante que todos os itens ativos do Molde Mensal existam no mês especificado ao abri-lo,
     * injetando automaticamente qualquer item recorrente ainda ausente no mês.
     *
     * @return int Quantidade de lançamentos injetados automaticamente
     */
    public function ensureMonthInitialized(int $userId, int $year, int $month): int
    {
        $activeTemplates = $this->recurringTemplateRepository->getAllByUser($userId, onlyActive: true);
        if ($activeTemplates->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($activeTemplates, $userId, $year, $month): int {
            $createdCount = 0;

            foreach ($activeTemplates as $template) {
                if ($template->amount_cents <= 0) {
                    continue;
                }

                if ($this->transactionRepository->existsForRecurringTemplate(
                    userId: $userId,
                    year: $year,
                    month: $month,
                    recurringTemplateId: (int) $template->id
                )) {
                    continue;
                }

                $attributes = $this->recurringTransactionFactory->makeAttributesForMonth($template, $year, $month);
                $this->transactionRepository->create($attributes);
                $createdCount++;
            }

            return $createdCount;
        });
    }
}
