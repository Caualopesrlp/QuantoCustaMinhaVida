<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MonthlySummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AnnualSummaryController extends Controller
{
    public function __construct(
        private readonly MonthlySummaryService $monthlySummaryService,
    ) {}

    public function index(Request $request, ?int $year = null): View
    {
        $resolvedYear = $year ?? (int) $request->query('year', (int) now()->format('Y'));
        $resolvedYear = max(2000, min(2100, $resolvedYear));

        $report = $this->monthlySummaryService->getAnnualInvestmentReport($request->user(), $resolvedYear);

        return view('annual.index', [
            'report' => $report,
        ]);
    }
}
