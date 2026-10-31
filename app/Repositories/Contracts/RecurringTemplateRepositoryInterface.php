<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\RecurringTemplate;
use Illuminate\Support\Collection;

interface RecurringTemplateRepositoryInterface
{
    /**
     * @return Collection<int, RecurringTemplate>
     */
    public function getAllByUser(int $userId, bool $onlyActive = false): Collection;

    public function findForUser(int $userId, int $templateId): ?RecurringTemplate;

    public function findByDescriptionAndCategory(int $userId, int $categoryId, string $description): ?RecurringTemplate;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): RecurringTemplate;

    /**
     * @param array<string, mixed> $data
     */
    public function update(RecurringTemplate $template, array $data): RecurringTemplate;

    public function delete(RecurringTemplate $template): bool;
}
