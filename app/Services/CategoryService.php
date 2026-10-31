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
     * Categorias iniciais e subcategorias (Tipos de Despesas) para novos usuários.
     *
     * @return array<int, array{name: string, color: string, icon: string, type: CategoryType, sort_order: int, expense_types: list<string>}>
     */
    public static function defaultCategoriesBlueprint(): array
    {
        return [
            // Receitas
            [
                'name' => 'Salário',
                'color' => '#10b981',
                'icon' => '💰',
                'type' => CategoryType::Income,
                'sort_order' => 1,
                'expense_types' => ['Salário Mensal', '13º Salário', 'Férias / Bônus'],
            ],
            [
                'name' => 'Outros / Extras',
                'color' => '#059669',
                'icon' => '➕',
                'type' => CategoryType::Income,
                'sort_order' => 2,
                'expense_types' => ['Freelance', 'Reembolso', 'Renda Extra'],
            ],

            // Despesas Fixas
            [
                'name' => 'Celular / Internet',
                'color' => '#3b82f6',
                'icon' => '📱',
                'type' => CategoryType::FixedExpense,
                'sort_order' => 1,
                'expense_types' => ['Plano 5G / Móvel', 'Fibra Residencial'],
            ],
            [
                'name' => 'Assinaturas',
                'color' => '#6366f1',
                'icon' => '🎧',
                'type' => CategoryType::FixedExpense,
                'sort_order' => 2,
                'expense_types' => ['Spotify / Música', 'Streaming de Vídeo', 'Nuvem / Software'],
            ],
            [
                'name' => 'Corte de Cabelo',
                'color' => '#8b5cf6',
                'icon' => '✂️',
                'type' => CategoryType::FixedExpense,
                'sort_order' => 3,
                'expense_types' => ['Barbearia / Salão', 'Cuidados Pessoais'],
            ],
            [
                'name' => 'Transporte Público',
                'color' => '#0ea5e9',
                'icon' => '🚌',
                'type' => CategoryType::FixedExpense,
                'sort_order' => 4,
                'expense_types' => ['SalvadorCard / Bilhete Único', 'Metrô / Ônibus'],
            ],

            // Despesas Variáveis
            [
                'name' => 'Alimentação fora',
                'color' => '#f59e0b',
                'icon' => '🍔',
                'type' => CategoryType::VariableExpense,
                'sort_order' => 1,
                'expense_types' => ['Restaurante / Almoço', 'Fast Food / Lanche', 'Delivery / iFood'],
            ],
            [
                'name' => 'App de transporte',
                'color' => '#eab308',
                'icon' => '🛵',
                'type' => CategoryType::VariableExpense,
                'sort_order' => 2,
                'expense_types' => ['Uber', '99 Moto / Carro'],
            ],
            [
                'name' => 'Lazer',
                'color' => '#ec4899',
                'icon' => '🎉',
                'type' => CategoryType::VariableExpense,
                'sort_order' => 3,
                'expense_types' => ['Cinema / Eventos', 'Passeios / Barzinho', 'Viagens'],
            ],
            [
                'name' => 'Compras online',
                'color' => '#f97316',
                'icon' => '🛒',
                'type' => CategoryType::VariableExpense,
                'sort_order' => 4,
                'expense_types' => ['Mercado Livre / Amazon', 'Shopee / Eletrônicos', 'Vestuário'],
            ],
            [
                'name' => 'Outros gastos variáveis',
                'color' => '#64748b',
                'icon' => '➕',
                'type' => CategoryType::VariableExpense,
                'sort_order' => 5,
                'expense_types' => ['Farmácia / Saúde', 'Presentes', 'Imprevistos'],
            ],

            // Investimentos
            [
                'name' => 'Investimento',
                'color' => '#4f46e5',
                'icon' => '📈',
                'type' => CategoryType::Investment,
                'sort_order' => 1,
                'expense_types' => ['CDB / Renda Fixa', 'Tesouro Direto', 'Reserva de Emergência', 'Ações / FIIs'],
            ],
        ];
    }

    public function ensureDefaultCategoriesForUser(User $user): void
    {
        $existing = $this->categoryRepository->getAllByUser((int) $user->id);
        if ($existing->isNotEmpty()) {
            return;
        }

        foreach (self::defaultCategoriesBlueprint() as $blueprint) {
            $category = $this->categoryRepository->create([
                'user_id' => $user->id,
                'name' => $blueprint['name'],
                'color' => $blueprint['color'],
                'icon' => $blueprint['icon'],
                'type' => $blueprint['type']->value,
                'sort_order' => $blueprint['sort_order'],
                'is_active' => true,
            ]);

            foreach ($blueprint['expense_types'] as $subName) {
                $this->categoryRepository->createExpenseType($category, [
                    'name' => $subName,
                ]);
            }
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
     * @param array{
     *   name: string,
     *   color?: string,
     *   icon?: string,
     *   type?: string,
     *   sort_order?: int,
     *   is_active?: bool,
     *   expense_types?: array<int, string>|string
     * } $data
     */
    public function createForUser(User $user, array $data): Category
    {
        $typeValue = isset($data['type']) && $data['type'] !== ''
            ? CategoryType::from((string) $data['type'])->value
            : CategoryType::VariableExpense->value;

        $category = $this->categoryRepository->create([
            'user_id' => $user->id,
            'name' => trim($data['name']),
            'color' => $this->normalizeColor($data['color'] ?? null),
            'icon' => trim($data['icon'] ?? '📌') ?: '📌',
            'type' => $typeValue,
            'sort_order' => (int) ($data['sort_order'] ?? 10),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        if (! empty($data['expense_types'])) {
            $rawSubtypes = is_array($data['expense_types'])
                ? $data['expense_types']
                : explode(',', (string) $data['expense_types']);

            foreach ($rawSubtypes as $subName) {
                $cleanSub = trim((string) $subName);
                if ($cleanSub !== '') {
                    $this->categoryRepository->createExpenseType($category, ['name' => $cleanSub]);
                }
            }
        }

        return $category->load('expenseTypes');
    }

    /**
     * @param array{name: string, color?: string, icon?: string, type?: string, sort_order?: int, is_active?: bool} $data
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

        return $this->categoryRepository->update($category, [
            'name' => trim($data['name']),
            'color' => isset($data['color']) && trim((string) $data['color']) !== ''
                ? $this->normalizeColor($data['color'])
                : ($category->color ?: '#6366f1'),
            'icon' => trim($data['icon'] ?? $category->icon) ?: '📌',
            'type' => $typeValue,
            'sort_order' => (int) ($data['sort_order'] ?? $category->sort_order),
            'is_active' => (bool) ($data['is_active'] ?? $category->is_active),
        ]);
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
     * Cria um novo Tipo de Despesa (Subcategoria) vinculado a uma Categoria do usuário.
     *
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
     * Atualiza um Tipo de Despesa (Subcategoria) pertencente ao usuário.
     *
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

    /**
     * Exclui um Tipo de Despesa (Subcategoria) pertencente ao usuário.
     */
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
            return '#6366f1';
        }

        return $trimmed;
    }
}
