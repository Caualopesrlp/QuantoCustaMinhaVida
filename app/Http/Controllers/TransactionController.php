<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $tx = $this->transactionService->createForUser($request->user(), $request->validated());

        return redirect()
            ->route('dashboard.monthly', ['year' => $tx->year, 'month' => $tx->month])
            ->with('status', 'Lançamento registrado com sucesso.');
    }

    public function update(UpdateTransactionRequest $request, int $transaction): RedirectResponse
    {
        $tx = $this->transactionService->updateForUser($request->user(), $transaction, $request->validated());

        return redirect()
            ->route('dashboard.monthly', ['year' => $tx->year, 'month' => $tx->month])
            ->with('status', 'Lançamento atualizado.');
    }

    public function destroy(Request $request, int $transaction): RedirectResponse
    {
        $this->transactionService->deleteForUser($request->user(), $transaction);

        return back()->with('status', 'Lançamento removido.');
    }
}
