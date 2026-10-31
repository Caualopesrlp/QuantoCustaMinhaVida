<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Http\Requests\CategoryRequest;
use App\Http\Requests\ExpenseTypeRequest;
use App\Http\Requests\UpdateBudgetRuleRequest;
use App\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    public function index(Request $request): View
    {
        $categories = $this->categoryService->listForUser($request->user());
        $groupedCategories = $this->categoryService->listGroupedForUser($request->user());

        return view('categories.index', [
            'groups' => $groupedCategories,
            'groupedCategories' => $groupedCategories,
            'categoriesByType' => $categories->groupBy(fn ($cat) => $cat->type->value),
            'categoryTypes' => CategoryType::cases(),
            'user' => $request->user(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->categoryService->createForUser($request->user(), $request->validated());

        return redirect()
            ->route('categories.index')
            ->with('status', 'Categoria / Tipo salvo com sucesso.');
    }

    public function update(CategoryRequest $request, int $category): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->categoryService->updateForUser($request->user(), $category, $data);

        return redirect()
            ->route('categories.index')
            ->with('status', 'Tipo atualizado com sucesso.');
    }

    public function destroy(Request $request, int $category): RedirectResponse
    {
        $result = $this->categoryService->deleteOrArchiveForUser($request->user(), $category);

        $msg = $result === 'archived'
            ? 'Item arquivado (possui lançamentos históricos).'
            : 'Item removido.';

        return redirect()
            ->route('categories.index')
            ->with('status', $msg);
    }

    public function updateGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'old_group_name' => ['required', 'string', 'max:100'],
            'group_name' => ['nullable', 'string', 'max:100'],
            'new_group_name' => ['nullable', 'string', 'max:100'],
            'type' => ['required', new Enum(CategoryType::class)],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $newGroupName = trim((string) ($validated['new_group_name'] ?? $validated['group_name'] ?? $validated['old_group_name']));

        $this->categoryService->updateGroupForUser($request->user(), $validated['old_group_name'], [
            'new_group_name' => $newGroupName,
            'type' => $validated['type'],
            'color' => $validated['color'] ?? null,
        ]);

        return redirect()
            ->route('categories.index')
            ->with('status', 'Categoria principal atualizada.');
    }

    public function destroyGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_name' => ['required', 'string', 'max:100'],
        ]);

        $this->categoryService->deleteGroupForUser($request->user(), $validated['group_name']);

        return redirect()
            ->route('categories.index')
            ->with('status', 'Categoria principal removida.');
    }

    public function storeExpenseType(ExpenseTypeRequest $request, int $category): RedirectResponse
    {
        $this->categoryService->createExpenseTypeForUser($request->user(), $category, $request->validated());

        return redirect()
            ->route('categories.index')
            ->with('status', 'Tipo adicionado com sucesso.');
    }

    public function updateExpenseType(ExpenseTypeRequest $request, int $expenseType): RedirectResponse
    {
        $this->categoryService->updateExpenseTypeForUser($request->user(), $expenseType, $request->validated());

        return redirect()
            ->route('categories.index')
            ->with('status', 'Tipo atualizado com sucesso.');
    }

    public function destroyExpenseType(Request $request, int $expenseType): RedirectResponse
    {
        $this->categoryService->deleteExpenseTypeForUser($request->user(), $expenseType);

        return redirect()
            ->route('categories.index')
            ->with('status', 'Tipo removido.');
    }

    public function updateBudgetRule(UpdateBudgetRuleRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('status', 'Metas da regra orçamentária (50-30-20) atualizadas.');
    }
}
