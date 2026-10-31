<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Http\Requests\RecurringTemplateRequest;
use App\Services\CategoryService;
use App\Services\RecurringTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RecurringTemplateController extends Controller
{
    public function __construct(
        private readonly RecurringTemplateService $recurringTemplateService,
        private readonly CategoryService $categoryService,
    ) {}

    public function index(Request $request): View
    {
        $templates = $this->recurringTemplateService->listForUser($request->user());
        $categories = $this->categoryService->listForUser($request->user(), onlyActive: true);
        $groupedCategories = $this->categoryService->listGroupedForUser($request->user(), onlyActive: true);

        $incomeTotalCents = (int) $templates
            ->where('is_active', true)
            ->filter(fn ($t) => $t->category?->type === CategoryType::Income)
            ->sum('amount_cents');

        $fixedTotalCents = (int) $templates
            ->where('is_active', true)
            ->filter(fn ($t) => $t->category?->type !== CategoryType::Income)
            ->sum('amount_cents');

        return view('recurring.index', [
            'templates' => $templates,
            'categories' => $categories,
            'groupedCategories' => $groupedCategories,
            'incomeTotalCents' => $incomeTotalCents,
            'fixedTotalCents' => $fixedTotalCents,
        ]);
    }

    public function store(RecurringTemplateRequest $request): RedirectResponse
    {
        $this->recurringTemplateService->createForUser($request->user(), $request->validated());

        return redirect()
            ->route('recurring-templates.index')
            ->with('status', 'Item adicionado ao Molde Mensal.');
    }

    public function update(RecurringTemplateRequest $request, int $recurringTemplate): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $this->recurringTemplateService->updateForUser($request->user(), $recurringTemplate, $data);

        return redirect()
            ->route('recurring-templates.index')
            ->with('status', 'Item do Molde Mensal atualizado.');
    }

    public function destroy(Request $request, int $recurringTemplate): RedirectResponse
    {
        $this->recurringTemplateService->deleteForUser($request->user(), $recurringTemplate);

        return redirect()
            ->route('recurring-templates.index')
            ->with('status', 'Item removido do Molde Mensal.');
    }
}
