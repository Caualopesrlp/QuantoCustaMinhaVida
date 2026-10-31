<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\ExpenseType;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function getAllByUser(int $userId, bool $onlyActive = false): Collection
    {
        $query = Category::query()
            ->with('expenseTypes')
            ->where('user_id', $userId)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function getByType(int $userId, CategoryType $type, bool $onlyActive = true): Collection
    {
        $query = Category::query()
            ->with('expenseTypes')
            ->where('user_id', $userId)
            ->where('type', $type->value)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function findForUser(int $userId, int $categoryId): ?Category
    {
        return Category::query()
            ->with('expenseTypes')
            ->where('user_id', $userId)
            ->whereKey($categoryId)
            ->first();
    }

    public function findByNameAndType(int $userId, string $name, CategoryType $type): ?Category
    {
        return Category::query()
            ->with('expenseTypes')
            ->where('user_id', $userId)
            ->where('type', $type->value)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->first();
    }

    public function create(array $data): Category
    {
        return Category::query()->create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->refresh()->load('expenseTypes');
    }

    public function delete(Category $category): bool
    {
        return (bool) $category->delete();
    }

    public function getExpenseTypesByCategory(int $categoryId): Collection
    {
        return ExpenseType::query()
            ->where('category_id', $categoryId)
            ->orderBy('name')
            ->get();
    }

    public function findExpenseTypeForUser(int $userId, int $expenseTypeId): ?ExpenseType
    {
        return ExpenseType::query()
            ->with('category')
            ->whereKey($expenseTypeId)
            ->whereHas('category', fn ($q) => $q->where('user_id', $userId))
            ->first();
    }

    public function createExpenseType(Category $category, array $data): ExpenseType
    {
        /** @var ExpenseType $expenseType */
        $expenseType = $category->expenseTypes()->create([
            'name' => trim((string) $data['name']),
        ]);

        return $expenseType;
    }

    public function updateExpenseType(ExpenseType $expenseType, array $data): ExpenseType
    {
        $expenseType->update([
            'name' => trim((string) $data['name']),
        ]);

        return $expenseType->refresh();
    }

    public function deleteExpenseType(ExpenseType $expenseType): bool
    {
        return (bool) $expenseType->delete();
    }
}
