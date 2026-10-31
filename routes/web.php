<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AnnualInvestmentController;
use App\Http\Controllers\AnnualSummaryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MonthlyDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecurringTemplateController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [MonthlyDashboardController::class, 'index'])->name('dashboard');
    Route::get('/financas/{year?}/{month?}', [MonthlyDashboardController::class, 'index'])
        ->whereNumber(['year', 'month'])
        ->name('dashboard.monthly');
    Route::post('/financas/{year}/{month}/gerar-fixas', [MonthlyDashboardController::class, 'generateRecurring'])
        ->whereNumber(['year', 'month'])
        ->name('dashboard.generate-recurring');

    // Lançamentos individuais (Transactions)
    Route::post('/lancamentos', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('/lancamentos/{transaction}', [TransactionController::class, 'update'])
        ->whereNumber('transaction')
        ->name('transactions.update');
    Route::delete('/lancamentos/{transaction}', [TransactionController::class, 'destroy'])
        ->whereNumber('transaction')
        ->name('transactions.destroy');

    // Tela Independente 1: Investimentos
    Route::get('/investimentos/{year?}', [AnnualInvestmentController::class, 'index'])
        ->whereNumber('year')
        ->name('investments.annual');

    // Tela Independente 2: Resumo Anual (Ano)
    Route::get('/ano/{year?}', [AnnualSummaryController::class, 'index'])
        ->whereNumber('year')
        ->name('annual.summary');

    // Molde Mensal (Recorrências)
    Route::get('/modelo-fixo', [RecurringTemplateController::class, 'index'])->name('recurring-templates.index');
    Route::post('/modelo-fixo', [RecurringTemplateController::class, 'store'])->name('recurring-templates.store');
    Route::put('/modelo-fixo/{recurringTemplate}', [RecurringTemplateController::class, 'update'])
        ->whereNumber('recurringTemplate')
        ->name('recurring-templates.update');
    Route::delete('/modelo-fixo/{recurringTemplate}', [RecurringTemplateController::class, 'destroy'])
        ->whereNumber('recurringTemplate')
        ->name('recurring-templates.destroy');

    // Categorias Principais, Tipos & Regra 50-30-20
    Route::get('/categorias', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categorias', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categorias/bloco', [CategoryController::class, 'updateGroup'])->name('categories.groups.update');
    Route::delete('/categorias/bloco', [CategoryController::class, 'destroyGroup'])->name('categories.groups.destroy');
    Route::put('/categorias/{category}', [CategoryController::class, 'update'])
        ->whereNumber('category')
        ->name('categories.update');
    Route::delete('/categorias/{category}', [CategoryController::class, 'destroy'])
        ->whereNumber('category')
        ->name('categories.destroy');
    Route::post('/categorias/{category}/tipos', [CategoryController::class, 'storeExpenseType'])
        ->whereNumber('category')
        ->name('categories.expense-types.store');
    Route::put('/tipos-despesa/{expenseType}', [CategoryController::class, 'updateExpenseType'])
        ->whereNumber('expenseType')
        ->name('categories.expense-types.update');
    Route::delete('/tipos-despesa/{expenseType}', [CategoryController::class, 'destroyExpenseType'])
        ->whereNumber('expenseType')
        ->name('categories.expense-types.destroy');
    Route::patch('/categorias/regra-orcamento', [CategoryController::class, 'updateBudgetRule'])
        ->name('categories.budget-rule.update');
});

// Painel Admin & Gestão de Usuários
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.list');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])
        ->whereNumber('user')
        ->name('users.reset-password');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
