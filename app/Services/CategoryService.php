<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\ExpenseType;
use App\Models\User;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
    ) {}

    /**
     * Categorias iniciais (4 Blocos padrão e seus respectivos Tipos) para novos usuários.
     *
     * @return array<int, array{group_name: string, name: string, color: string, icon: string, type: CategoryType, sort_order: int}>
     */
    public static function defaultCategoriesBlueprint(): array
    {
        return [
            // Receitas
            [
                'group_name' => 'Receitas',
                'name' => 'Salário',
                'color' => '#10b981',
                'icon' => '',
                'type' => CategoryType::Income,
                'sort_order' => 1,
            ],
            [
                'group_name' => 'Receitas',
                'name' => 'Outros / Extras',
                'color' => '#059669',
                'icon' => '',
                'type' => CategoryType::Income,
                'sort_order' => 2,
            ],

            // Despesas Fixas
            [
                'group_name' => 'Despesas Fixas',
                'name' => 'Celular / Internet',
                'color' => '#0284c7',
                'icon' => '',
                'type' => CategoryType::FixedExpense,
                'sort_order' => 1,
            ],
            [
                'group_name' => 'Despesas Fixas',
                'name' => 'Assinaturas',
                'color' => '#0369a1',
                'icon' => '',
                'type' => CategoryType::FixedExpense,
                'sort_order' => 2,
            ],
            [
                'group_name' => 'Despesas Fixas',
                'name' => 'Corte de Cabelo',
                'color' => '#0891b2',
                'icon' => '',
                'type' => CategoryType::FixedExpense,
                'sort_order' => 3,
            ],
            [
                'group_name' => 'Despesas Fixas',
                'name' => 'Transporte Público',
                'color' => '#0ea5e9',
                'icon' => '',
                'type' => CategoryType::FixedExpense,
                'sort_order' => 4,
            ],

            // Despesas Variáveis
            [
                'group_name' => 'Despesas Variáveis',
                'name' => 'Alimentação fora',
                'color' => '#f59e0b',
                'icon' => '',
                'type' => CategoryType::VariableExpense,
                'sort_order' => 1,
            ],
            [
                'group_name' => 'Despesas Variáveis',
                'name' => 'App de transporte',
                'color' => '#eab308',
                'icon' => '',
                'type' => CategoryType::VariableExpense,
                'sort_order' => 2,
            ],
            [
                'group_name' => 'Despesas Variáveis',
                'name' => 'Lazer',
                'color' => '#ea580c',
                'icon' => '',
                'type' => CategoryType::VariableExpense,
                'sort_order' => 3,
            ],
            [
                'group_name' => 'Despesas Variáveis',
                'name' => 'Compras online',
                'color' => '#f97316',
                'icon' => '',
                'type' => CategoryType::VariableExpense,
                'sort_order' => 4,
            ],
            [
                'group_name' => 'Despesas Variáveis',
                'name' => 'Outros gastos variáveis',
                'color' => '#64748b',
                'icon' => '',
                'type' => CategoryType::VariableExpense,
                'sort_order' => 5,
            ],

            // Investimentos
            [
                'group_name' => 'Investimentos',
                'name' => 'Investimento',
                'color' => '#0d9488',
                'icon' => '',
                'type' => CategoryType::Investment,
                'sort_order' => 1,
            ],
        ];
    }

    public function ensureDefaultCategoriesForUser(User $user): void
    {
        $existing = $this->categoryRepository->getAllByUser((int) $user->id);
        if ($existing->isNotEmpty()) {
            foreach ($existing as $cat) {
                if (empty(trim((string) $cat->group_name))) {
                    $this->categoryRepository->update($cat, [
                        'group_name' => $cat->type->defaultGroupName(),
                    ]);
                }
            }

            return;
        }

        foreach (self::defaultCategoriesBlueprint() as $blueprint) {
            $this->categoryRepository->create([
                'user_id' => $user->id,
                'group_name' => $blueprint['group_name'],
                'name' => $blueprint['name'],
                'color' => $blueprint['color'],
                'icon' => $blueprint['icon'],
                'type' => $blueprint['type']->value,
                'sort_order' => $blueprint['sort_order'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return Collection<int, Category>
     */
    public function listForUser(User $user, bool $onlyActive = false): Collection
    {
        $this->ensureDefaultCategoriesForUser($user);

        return $this->categoryRepository->getAllByUser((int) $user->id, $onlyActive);
    }

    /**
     * Retorna os blocos/categorias principais agrupados por group_name, mantendo
     * os 4 blocos padrão na ordem clássica seguidos pelas categorias personalizadas criadas pelo usuário.
     *
     * @return Collection<string, array{
     *   group_name: string,
     *   type: CategoryType,
     *   color: string,
     *   is_default: bool,
     *   items: Collection<int, Category>
     * }>
     */
    public function listGroupedForUser(User $user, bool $onlyActive = false): Collection
    {
        $categories = $this->listForUser($user, $onlyActive);
        $grouped = $categories->groupBy(fn (Category $c) => $c->resolvedGroupName());

        $defaultOrder = [
            'Receitas' => CategoryType::Income,
            'Despesas Fixas' => CategoryType::FixedExpense,
            'Despesas Variáveis' => CategoryType::VariableExpense,
            'Investimentos' => CategoryType::Investment,
        ];

        $result = collect();

        foreach ($defaultOrder as $defaultGroupName => $defaultType) {
            $items = $grouped->pull($defaultGroupName, collect());
            $firstColor = $items->first()?->color ?? match ($defaultType) {
                CategoryType::Income => '#10b981',
                CategoryType::FixedExpense => '#0284c7',
                CategoryType::VariableExpense => '#f59e0b',
                CategoryType::Investment => '#0d9488',
            };

            $result->put($defaultGroupName, [
                'group_name' => $defaultGroupName,
                'type' => $defaultType,
                'color' => $firstColor,
                'is_default' => true,
                'items' => $items->values(),
            ]);
        }

        foreach ($grouped as $customGroupName => $items) {
            /** @var Category $first */
            $first = $items->first();
            $result->put($customGroupName, [
                'group_name' => (string) $customGroupName,
                'type' => $first->type,
                'color' => $first->color ?? '#0d9488',
                'is_default' => false,
                'items' => $items->values(),
            ]);
        }

        return $result;
    }

    /**
     * Cria uma nova Categoria Principal (Bloco) com seus Tipos iniciais,
     * ou adiciona um novo Tipo dentro de uma Categoria Principal existente.
     *
     * @param array{
     *   group_name?: string,
     *   name: string,
     *   color?: string,
     *   icon?: string,
     *   type?: string,
     *   sort_order?: int,
     *   is_active?: bool,
     *   items?: array<int, string>|string,
     *   expense_types?: array<int, string>|string
     * } $data
     */
    public function createForUser(User $user, array $data): Category
    {
        $typeValue = isset($data['type']) && $data['type'] !== ''
            ? CategoryType::from((string) $data['type'])->value
            : CategoryType::VariableExpense->value;

        $groupName = isset($data['group_name']) && trim((string) $data['group_name']) !== ''
            ? trim((string) $data['group_name'])
            : trim($data['name']);

        $color = $this->normalizeColor($data['color'] ?? null);
        $icon = trim((string) ($data['icon'] ?? ''));

        $rawItemsSource = $data['items'] ?? null;
        $parsedItems = [];
        if (! empty($rawItemsSource)) {
            $rawList = is_array($rawItemsSource)
                ? $rawItemsSource
                : (preg_split('/[\r\n,]+/', (string) $rawItemsSource) ?: []);

            foreach ($rawList as $item) {
                $clean = trim((string) $item);
                if ($clean !== '') {
                    $parsedItems[] = $clean;
                }
            }
        }

        $firstCategory = null;

        if (! empty($parsedItems)) {
            foreach ($parsedItems as $index => $itemName) {
                $created = $this->categoryRepository->create([
                    'user_id' => $user->id,
                    'group_name' => $groupName,
                    'name' => $itemName,
                    'color' => $color,
                    'icon' => $icon,
                    'type' => $typeValue,
                    'sort_order' => (int) ($data['sort_order'] ?? ($index + 1)),
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ]);

                if ($firstCategory === null) {
                    $firstCategory = $created;
                }
            }
        } else {
            $firstCategory = $this->categoryRepository->create([
                'user_id' => $user->id,
                'group_name' => $groupName,
                'name' => trim($data['name']),
                'color' => $color,
                'icon' => $icon,
                'type' => $typeValue,
                'sort_order' => (int) ($data['sort_order'] ?? 10),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);
        }

        if (! empty($data['expense_types'])) {
            $rawSubtypes = is_array($data['expense_types'])
                ? $data['expense_types']
                : (preg_split('/[\r\n,]+/', (string) $data['expense_types']) ?: []);

            foreach ($rawSubtypes as $subName) {
                $cleanSub = trim((string) $subName);
                if ($cleanSub !== '') {
                    $this->categoryRepository->createExpenseType($firstCategory, ['name' => $cleanSub]);
                }
            }
        }

        return $firstCategory->load('expenseTypes');
    }

    /**
     * @param array{group_name?: string, name: string, color?: string, icon?: string, type?: string, sort_order?: int, is_active?: bool} $data
     */
    public function updateForUser(User $user, int $categoryId, array $data): Category
    {
        $category = $this->categoryRepository->findForUser((int) $user->id, $categoryId);
        if (! $category) {
            throw new AccessDeniedHttpException('Categoria não encontrada para este usuário.');
        }

        $typeValue = isset($data['type']) && $data['type'] !== ''
            ? CategoryType::from((string) $data['type'])->value
            : $category->type->value;

        $groupName = isset($data['group_name']) && trim((string) $data['group_name']) !== ''
            ? trim((string) $data['group_name'])
            : $category->resolvedGroupName();

        return $this->categoryRepository->update($category, [
            'group_name' => $groupName,
            'name' => trim($data['name']),
            'color' => isset($data['color']) && trim((string) $data['color']) !== ''
                ? $this->normalizeColor($data['color'])
                : ($category->color ?: '#0d9488'),
            'icon' => isset($data['icon']) ? trim((string) $data['icon']) : $category->icon,
            'type' => $typeValue,
            'sort_order' => (int) ($data['sort_order'] ?? $category->sort_order),
            'is_active' => (bool) ($data['is_active'] ?? $category->is_active),
        ]);
    }

    /**
     * Atualiza o nome, cor e/ou natureza financeira de todo um Bloco / Categoria Principal.
     *
     * @param array{new_group_name: string, type: string, color?: string} $data
     */
    public function updateGroupForUser(User $user, string $oldGroupName, array $data): void
    {
        $categories = $this->categoryRepository->getAllByUser((int) $user->id)
            ->filter(fn (Category $c) => $c->resolvedGroupName() === $oldGroupName);

        if ($categories->isEmpty()) {
            throw new AccessDeniedHttpException('Categoria principal não encontrada.');
        }

        $newGroupName = trim($data['new_group_name']);
        $typeValue = CategoryType::from($data['type'])->value;
        $color = isset($data['color']) && trim((string) $data['color']) !== ''
            ? $this->normalizeColor($data['color'])
            : null;

        foreach ($categories as $category) {
            $payload = [
                'group_name' => $newGroupName,
                'type' => $typeValue,
            ];
            if ($color !== null) {
                $payload['color'] = $color;
            }
            $this->categoryRepository->update($category, $payload);
        }
    }

    /**
     * Remove ou arquiva todos os itens de uma Categoria Principal personalizada.
     */
    public function deleteGroupForUser(User $user, string $groupName): void
    {
        $categories = $this->categoryRepository->getAllByUser((int) $user->id)
            ->filter(fn (Category $c) => $c->resolvedGroupName() === $groupName);

        foreach ($categories as $category) {
            $this->deleteOrArchiveForUser($user, (int) $category->id);
        }
    }

    /**
     * Se a categoria possuir transações vinculadas, apenas a desativa (arquiva) para preservar o histórico.
     */
    public function deleteOrArchiveForUser(User $user, int $categoryId): string
    {
        $category = $this->categoryRepository->findForUser((int) $user->id, $categoryId);
        if (! $category) {
            throw new AccessDeniedHttpException('Categoria não encontrada para este usuário.');
        }

        $txCount = $this->transactionRepository->countForCategory((int) $user->id, $categoryId);
        if ($txCount > 0) {
            $this->categoryRepository->update($category, ['is_active' => false]);

            return 'archived';
        }

        $this->categoryRepository->delete($category);

        return 'deleted';
    }

    /**
     * @param array{name: string} $data
     */
    public function createExpenseTypeForUser(User $user, int $categoryId, array $data): ExpenseType
    {
        $category = $this->categoryRepository->findForUser((int) $user->id, $categoryId);
        if (! $category) {
            throw new AccessDeniedHttpException('Categoria não encontrada para este usuário.');
        }

        return $this->categoryRepository->createExpenseType($category, [
            'name' => trim($data['name']),
        ]);
    }

    /**
     * @param array{name: string} $data
     */
    public function updateExpenseTypeForUser(User $user, int $expenseTypeId, array $data): ExpenseType
    {
        $expenseType = $this->categoryRepository->findExpenseTypeForUser((int) $user->id, $expenseTypeId);
        if (! $expenseType) {
            throw new AccessDeniedHttpException('Tipo de despesa não encontrado para este usuário.');
        }

        return $this->categoryRepository->updateExpenseType($expenseType, [
            'name' => trim($data['name']),
        ]);
    }

    public function deleteExpenseTypeForUser(User $user, int $expenseTypeId): void
    {
        $expenseType = $this->categoryRepository->findExpenseTypeForUser((int) $user->id, $expenseTypeId);
        if (! $expenseType) {
            throw new AccessDeniedHttpException('Tipo de despesa não encontrado para este usuário.');
        }

        $this->categoryRepository->deleteExpenseType($expenseType);
    }

    private function normalizeColor(?string $color): string
    {
        $trimmed = trim((string) $color);
        if ($trimmed === '') {
            return '#0d9488';
        }

        return $trimmed;
    }
}
