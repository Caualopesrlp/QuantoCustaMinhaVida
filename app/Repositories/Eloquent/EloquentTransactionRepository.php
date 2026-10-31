<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    public function getByMonth(int $userId, int $year, int $month): Collection
    {
        return Transaction::query()
            ->with(['category', 'expenseType'])
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->orderByRaw('occurred_on IS NULL ASC')
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get();
    }

    public function getByYear(int $userId, int $year): Collection
    {
        return Transaction::query()
            ->with(['category', 'expenseType'])
            ->where('user_id', $userId)
            ->where('year', $year)
            ->orderBy('month')
            ->orderBy('id')
            ->get();
    }

    public function getAllUpToMonth(int $userId, int $year, int $month): Collection
    {
        return Transaction::query()
            ->with(['category', 'expenseType'])
            ->where('user_id', $userId)
            ->where(function ($query) use ($year, $month) {
                $query->where('year', '<', $year)
                    ->orWhere(function ($q) use ($year, $month) {
                        $q->where('year', $year)
                            ->where('month', '<=', $month);
                    });
            })
            ->get();
    }

    public function findForUser(int $userId, int $transactionId): ?Transaction
    {
        return Transaction::query()
            ->with(['category', 'expenseType'])
            ->where('user_id', $userId)
            ->whereKey($transactionId)
            ->first();
    }

    public function existsForRecurringTemplate(int $userId, int $year, int $month, int $recurringTemplateId): bool
    {
        return Transaction::query()
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('recurring_template_id', $recurringTemplateId)
            ->exists();
    }

    public function countForCategory(int $userId, int $categoryId): int
    {
        return Transaction::query()
            ->where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->count();
    }

    public function create(array $data): Transaction
    {
        return Transaction::query()->create($data);
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);

        return $transaction->refresh();
    }

    public function delete(Transaction $transaction): bool
    {
        return (bool) $transaction->delete();
    }

    public function getAvailableMonths(int $userId): array
    {
        return Transaction::query()
            ->select(['year', 'month'])
            ->where('user_id', $userId)
            ->distinct()
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'year' => (int) $row->year,
                'month' => (int) $row->month,
            ])
            ->values()
            ->all();
    }
}
