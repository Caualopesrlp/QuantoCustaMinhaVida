<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\RecurringTemplate;
use App\Repositories\Contracts\RecurringTemplateRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentRecurringTemplateRepository implements RecurringTemplateRepositoryInterface
{
    public function getAllByUser(int $userId, bool $onlyActive = false): Collection
    {
        $query = RecurringTemplate::query()
            ->with(['category', 'expenseType'])
            ->where('user_id', $userId)
            ->orderBy('id');

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function findForUser(int $userId, int $templateId): ?RecurringTemplate
    {
        return RecurringTemplate::query()
            ->with(['category', 'expenseType'])
            ->where('user_id', $userId)
            ->whereKey($templateId)
            ->first();
    }

    public function findByDescriptionAndCategory(int $userId, int $categoryId, string $description): ?RecurringTemplate
    {
        return RecurringTemplate::query()
            ->where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->whereRaw('LOWER(description) = ?', [mb_strtolower(trim($description))])
            ->first();
    }

    public function create(array $data): RecurringTemplate
    {
        return RecurringTemplate::query()->create($data);
    }

    public function update(RecurringTemplate $template, array $data): RecurringTemplate
    {
        $template->update($data);

        return $template->refresh();
    }

    public function delete(RecurringTemplate $template): bool
    {
        return (bool) $template->delete();
    }
}
