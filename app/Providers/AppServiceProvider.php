<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\RecurringTemplateRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentRecurringTemplateRepository;
use App\Repositories\Eloquent\EloquentTransactionRepository;
use App\Repositories\UserRepository;
use App\Services\CategoryService;
use App\Strategies\Contracts\BudgetRuleCalculatorInterface;
use App\Strategies\CustomPercentageBudgetStrategy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(RecurringTemplateRepositoryInterface::class, EloquentRecurringTemplateRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, EloquentTransactionRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
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

        Gate::define('admin', static fn (User $user): bool => $user->isAdmin());

        User::created(function (User $user): void {
            app(CategoryService::class)->ensureDefaultCategoriesForUser($user);
        });
    }
}
