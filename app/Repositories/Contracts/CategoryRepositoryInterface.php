<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\ExpenseType;
use Illuminate\Support\Collection;

interface CategoryRepositoryInterface
{
    /**
     * @return Collection<int, Category>
     */
    public function getAllByUser(int $userId, bool $onlyActive = false): Collection;

    /**
     * @return Collection<int, Category>
     */
    public function getByType(int $userId, CategoryType $type, bool $onlyActive = true): Collection;

    public function findForUser(int $userId, int $categoryId): ?Category;

    public function findByNameAndType(int $userId, string $name, CategoryType $type): ?Category;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Category;

    /**
     * @param array<string, mixed> $data
     */
    public function update(Category $category, array $data): Category;

    public function delete(Category $category): bool;

    /**
     * @return Collection<int, ExpenseType>
     */
    public function getExpenseTypesByCategory(int $categoryId): Collection;

    public function findExpenseTypeForUser(int $userId, int $expenseTypeId): ?ExpenseType;

    /**
     * @param array<string, mixed> $data
     */
    public function createExpenseType(Category $category, array $data): ExpenseType;

    /**
     * @param array<string, mixed> $data
     */
    public function updateExpenseType(ExpenseType $expenseType, array $data): ExpenseType;

    public function deleteExpenseType(ExpenseType $expenseType): bool;
}
