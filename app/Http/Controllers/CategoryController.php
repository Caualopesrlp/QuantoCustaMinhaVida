<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Http\Requests\CategoryRequest;
use App\Http\Requests\UpdateBudgetRuleRequest;
use App\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    public function index(Request $request): View
    {
        $categories = $this->categoryService->listForUser($request->user());

        return view('categories.index', [
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
            ->with('status', '✅ Nova categoria criada!');
    }

    public function update(CategoryRequest $request, int $category): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->categoryService->updateForUser($request->user(), $category, $data);

        return redirect()
            ->route('categories.index')
            ->with('status', '✅ Categoria atualizada!');
    }

    public function destroy(Request $request, int $category): RedirectResponse
    {
        $result = $this->categoryService->deleteOrArchiveForUser($request->user(), $category);

        $msg = $result === 'archived'
            ? '📦 Categoria arquivada (possui lançamentos históricos).'
            : '🗑️ Categoria excluída.';

        return redirect()
            ->route('categories.index')
            ->with('status', $msg);
    }

    public function updateBudgetRule(UpdateBudgetRuleRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('status', '📐 Metas da Regra Orçamentária (50-30-20) atualizadas!');
    }
}
