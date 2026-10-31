<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MonthlySummaryService;
use App\Services\RecurringTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MonthlyDashboardController extends Controller
{
    public function __construct(
        private readonly MonthlySummaryService $monthlySummaryService,
        private readonly RecurringTemplateService $recurringTemplateService,
    ) {}

    public function index(Request $request, ?int $year = null, ?int $month = null): View
    {
        $resolvedYear = $year ?? (int) $request->query('year', (int) now()->format('Y'));
        $resolvedMonth = $month ?? (int) $request->query('month', (int) now()->format('n'));

        $resolvedYear = max(2000, min(2100, $resolvedYear));
        $resolvedMonth = max(1, min(12, $resolvedMonth));

        $dashboard = $this->monthlySummaryService->getMonthlyDashboard(
            user: $request->user(),
            year: $resolvedYear,
            month: $resolvedMonth
        );

        return view('dashboard.monthly', [
            'dashboard' => $dashboard,
            'monthNames' => MonthlySummaryService::MONTH_NAMES,
        ]);
    }

    public function generateRecurring(Request $request, int $year, int $month): RedirectResponse
    {
        $created = $this->recurringTemplateService->generateForMonth(
            user: $request->user(),
            year: $year,
            month: $month
        );

        $message = $created > 0
            ? "{$created} lançamento(s) do Molde Mensal aplicado(s) no mês."
            : 'Todos os lançamentos do Molde Mensal já estavam aplicados neste mês.';

        return redirect()
            ->route('dashboard.monthly', ['year' => $year, 'month' => $month])
            ->with('status', $message);
    }
}
