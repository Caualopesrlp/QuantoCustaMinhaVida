<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use Illuminate\Support\Collection;

interface TransactionRepositoryInterface
{
    /**
     * @return Collection<int, Transaction>
     */
    public function getByMonth(int $userId, int $year, int $month): Collection;

    /**
     * @return Collection<int, Transaction>
     */
    public function getByYear(int $userId, int $year): Collection;

    /**
     * @return Collection<int, Transaction>
     */
    public function getAllUpToMonth(int $userId, int $year, int $month): Collection;

    public function findForUser(int $userId, int $transactionId): ?Transaction;

    public function existsForRecurringTemplate(int $userId, int $year, int $month, int $recurringTemplateId): bool;

    public function countForCategory(int $userId, int $categoryId): int;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Transaction;

    /**
     * @param array<string, mixed> $data
     */
    public function update(Transaction $transaction, array $data): Transaction;

    public function delete(Transaction $transaction): bool;

    /**
     * @return array<int, array{year: int, month: int}>
     */
    public function getAvailableMonths(int $userId): array;
}
