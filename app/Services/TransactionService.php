<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Support\Money;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TransactionService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    /**
     * @param array{
     *   category_id: int,
     *   expense_type_id?: ?int,
     *   year: int,
     *   month: int,
     *   occurred_on?: ?string,
     *   description: string,
     *   amount: string|float|int,
     *   notes?: ?string
     * } $data
     */
    public function createForUser(User $user, array $data): Transaction
    {
        $category = $this->categoryRepository->findForUser((int) $user->id, (int) $data['category_id']);
        if (! $category) {
            throw new AccessDeniedHttpException('Categoria inválida para este usuário.');
        }

        $expenseTypeId = null;
        if (! empty($data['expense_type_id'])) {
            $expenseType = $this->categoryRepository->findExpenseTypeForUser((int) $user->id, (int) $data['expense_type_id']);
            if ($expenseType && (int) $expenseType->category_id === (int) $category->id) {
                $expenseTypeId = $expenseType->id;
            }
        }

        return $this->transactionRepository->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'expense_type_id' => $expenseTypeId,
            'year' => (int) $data['year'],
            'month' => (int) $data['month'],
            'occurred_on' => ! empty($data['occurred_on']) ? $data['occurred_on'] : null,
            'description' => trim($data['description']),
            'amount_cents' => Money::toCents($data['amount']),
            'notes' => isset($data['notes']) && trim((string) $data['notes']) !== '' ? trim((string) $data['notes']) : null,
        ]);
    }

    /**
     * @param array{
     *   category_id: int,
     *   expense_type_id?: ?int,
     *   occurred_on?: ?string,
     *   description: string,
     *   amount: string|float|int,
     *   notes?: ?string
     * } $data
     */
    public function updateForUser(User $user, int $transactionId, array $data): Transaction
    {
        $transaction = $this->transactionRepository->findForUser((int) $user->id, $transactionId);
        if (! $transaction) {
            throw new AccessDeniedHttpException('Lançamento não encontrado.');
        }

        $category = $this->categoryRepository->findForUser((int) $user->id, (int) $data['category_id']);
        if (! $category) {
            throw new AccessDeniedHttpException('Categoria inválida para este usuário.');
        }

        $expenseTypeId = $transaction->expense_type_id;
        if (array_key_exists('expense_type_id', $data)) {
            $expenseTypeId = null;
            if (! empty($data['expense_type_id'])) {
                $expenseType = $this->categoryRepository->findExpenseTypeForUser((int) $user->id, (int) $data['expense_type_id']);
                if ($expenseType && (int) $expenseType->category_id === (int) $category->id) {
                    $expenseTypeId = $expenseType->id;
                }
            }
        }

        return $this->transactionRepository->update($transaction, [
            'category_id' => $category->id,
            'expense_type_id' => $expenseTypeId,
            'occurred_on' => ! empty($data['occurred_on']) ? $data['occurred_on'] : null,
            'description' => trim($data['description']),
            'amount_cents' => Money::toCents($data['amount']),
            'notes' => isset($data['notes']) && trim((string) $data['notes']) !== '' ? trim((string) $data['notes']) : null,
        ]);
    }

    public function deleteForUser(User $user, int $transactionId): void
    {
        $transaction = $this->transactionRepository->findForUser((int) $user->id, $transactionId);
        if (! $transaction) {
            throw new AccessDeniedHttpException('Lançamento não encontrado.');
        }

        $this->transactionRepository->delete($transaction);
    }
}
