<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\RecurringTemplateRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Eloquent\EloquentCategoryRepository;
use App\Repositories\Eloquent\EloquentRecurringTemplateRepository;
use App\Repositories\Eloquent\EloquentTransactionRepository;
use App\Services\CategoryService;
use App\Strategies\Contracts\BudgetRuleCalculatorInterface;
use App\Strategies\CustomPercentageBudgetStrategy;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(RecurringTemplateRepositoryInterface::class, EloquentRecurringTemplateRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, EloquentTransactionRepository::class);
        $this->app->bind(BudgetRuleCalculatorInterface::class, CustomPercentageBudgetStrategy::class);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole() && request()->header('x-forwarded-host')) {
            $forwardedHost = trim(explode(',', (string) request()->header('x-forwarded-host'))[0]);
            $scheme = str_contains($forwardedHost, 'cloudshell.dev')
                ? 'https'
                : (request()->header('x-forwarded-proto') ?: request()->getScheme());

            URL::forceRootUrl("{$scheme}://{$forwardedHost}");
            URL::forceScheme($scheme);
        }

        User::created(function (User $user): void {
            app(CategoryService::class)->ensureDefaultCategoriesForUser($user);
        });
    }
}
