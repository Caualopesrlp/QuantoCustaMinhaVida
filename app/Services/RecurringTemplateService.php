<?php

declare(strict_types=1);

namespace App\Services;

use App\Factories\RecurringTransactionFactory;
use App\Models\RecurringTemplate;
use App\Models\User;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\RecurringTemplateRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Support\Money;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RecurringTemplateService
{
    public function __construct(
        private readonly RecurringTemplateRepositoryInterface $templateRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly RecurringTransactionFactory $factory,
    ) {}

    /**
     * @return Collection<int, RecurringTemplate>
     */
    public function listForUser(User $user, bool $onlyActive = false): Collection
    {
        return $this->templateRepository->getAllByUser((int) $user->id, $onlyActive);
    }

    /**
     * @param array{category_id: int, expense_type_id?: ?int, description: string, amount: string|float|int, notes?: ?string, is_active?: bool} $data
     */
    public function createForUser(User $user, array $data): RecurringTemplate
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

        return $this->templateRepository->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'expense_type_id' => $expenseTypeId,
            'description' => trim($data['description']),
            'amount_cents' => Money::toCents($data['amount']),
            'notes' => isset($data['notes']) && trim((string) $data['notes']) !== '' ? trim((string) $data['notes']) : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * @param array{category_id: int, expense_type_id?: ?int, description: string, amount: string|float|int, notes?: ?string, is_active?: bool} $data
     */
    public function updateForUser(User $user, int $templateId, array $data): RecurringTemplate
    {
        $template = $this->templateRepository->findForUser((int) $user->id, $templateId);
        if (! $template) {
            throw new AccessDeniedHttpException('Item recorrente não encontrado.');
        }

        $category = $this->categoryRepository->findForUser((int) $user->id, (int) $data['category_id']);
        if (! $category) {
            throw new AccessDeniedHttpException('Categoria inválida para este usuário.');
        }

        $expenseTypeId = $template->expense_type_id;
        if (array_key_exists('expense_type_id', $data)) {
            $expenseTypeId = null;
            if (! empty($data['expense_type_id'])) {
                $expenseType = $this->categoryRepository->findExpenseTypeForUser((int) $user->id, (int) $data['expense_type_id']);
                if ($expenseType && (int) $expenseType->category_id === (int) $category->id) {
                    $expenseTypeId = $expenseType->id;
                }
            }
        }

        return $this->templateRepository->update($template, [
            'category_id' => $category->id,
            'expense_type_id' => $expenseTypeId,
            'description' => trim($data['description']),
            'amount_cents' => Money::toCents($data['amount']),
            'notes' => isset($data['notes']) && trim((string) $data['notes']) !== '' ? trim((string) $data['notes']) : null,
            'is_active' => (bool) ($data['is_active'] ?? $template->is_active),
        ]);
    }

    public function deleteForUser(User $user, int $templateId): void
    {
        $template = $this->templateRepository->findForUser((int) $user->id, $templateId);
        if (! $template) {
            throw new AccessDeniedHttpException('Item recorrente não encontrado.');
        }

        $this->templateRepository->delete($template);
    }

    /**
     * Instancia os templates ativos para o mês/ano especificado sem duplicar itens já gerados.
     *
     * @return int Quantidade de lançamentos gerados
     */
    public function generateForMonth(User $user, int $year, int $month): int
    {
        $activeTemplates = $this->templateRepository->getAllByUser((int) $user->id, onlyActive: true);
        $createdCount = 0;

        foreach ($activeTemplates as $template) {
            if ($template->amount_cents <= 0) {
                continue;
            }

            $alreadyExists = $this->transactionRepository->existsForRecurringTemplate(
                userId: (int) $user->id,
                year: $year,
                month: $month,
                recurringTemplateId: (int) $template->id
            );

            if ($alreadyExists) {
                continue;
            }

            $attributes = $this->factory->makeAttributesForMonth($template, $year, $month);
            $this->transactionRepository->create($attributes);
            $createdCount++;
        }

        return $createdCount;
    }
}
