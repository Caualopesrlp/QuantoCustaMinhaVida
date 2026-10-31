<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CategoryType;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinancialWorkflowAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_receives_default_categories_and_auto_injects_monthly_mold_even_on_non_empty_months(): void
    {
        $user = User::factory()->create();

        // 12 tipos padrão criados automaticamente pelo evento User::created
        $this->assertSame(12, $user->categories()->count());

        $salaryCategory = $user->categories()->where('name', 'Salário')->firstOrFail();
        $fixedCategory = $user->categories()->where('name', 'Transporte Público')->firstOrFail();
        $variableCategory = $user->categories()->where('name', 'Alimentação fora')->firstOrFail();
        $investmentCategory = $user->categories()->where('name', 'Investimento')->firstOrFail();

        // Cadastra Molde Mensal
        $this->actingAs($user)->post(route('recurring-templates.store'), [
            'category_id' => $salaryCategory->id,
            'description' => 'Salário',
            'amount' => '1.011,25',
            'notes' => 'Salário',
        ])->assertRedirect(route('recurring-templates.index'));

        // Automação de Molde Mensal: Ao abrir o Controle Mensal de Outubro/2026, injeta o molde automaticamente
        $firstLoad = $this->actingAs($user)->get(route('dashboard.monthly', ['year' => 2026, 'month' => 10]));
        $firstLoad->assertOk();
        $this->assertSame(1, $user->transactions()->where('year', 2026)->where('month', 10)->count());

        // Adiciona um segundo item no Molde Mensal depois que o mês já tem lançamentos
        $this->actingAs($user)->post(route('recurring-templates.store'), [
            'category_id' => $fixedCategory->id,
            'description' => 'Transporte Público',
            'amount' => '180,00',
            'notes' => 'SalvadorCard',
        ])->assertRedirect(route('recurring-templates.index'));

        // Reabrir o mês injeta o novo item faltante do molde sem duplicar o primeiro
        $this->actingAs($user)->get(route('dashboard.monthly', ['year' => 2026, 'month' => 10]))->assertOk();
        $this->assertSame(2, $user->transactions()->where('year', 2026)->where('month', 10)->count());

        // Registra gasto variável individual e aporte de investimento
        $this->actingAs($user)->post(route('transactions.store'), [
            'category_id' => $variableCategory->id,
            'year' => 2026,
            'month' => 10,
            'description' => 'BK',
            'amount' => '55,90',
            'notes' => 'Lanche',
        ])->assertRedirect(route('dashboard.monthly', ['year' => 2026, 'month' => 10]));

        $this->actingAs($user)->post(route('transactions.store'), [
            'category_id' => $investmentCategory->id,
            'year' => 2026,
            'month' => 10,
            'description' => 'CDB Nubank',
            'amount' => '333,00',
            'notes' => '100% CDI',
        ])->assertRedirect(route('dashboard.monthly', ['year' => 2026, 'month' => 10]));

        // Verifica tela do Controle Mensal (Receita 1011,25 - Fixas 180,00 - Variáveis 55,90 - Investimento 333,00 = Saldo 442,35)
        $response = $this->actingAs($user)->get(route('dashboard.monthly', ['year' => 2026, 'month' => 10]));
        $response->assertOk();
        $response->assertSee('R$ 1.011,25');
        $response->assertSee('R$ 180,00');
        $response->assertSee('R$ 55,90');
        $response->assertSee('R$ 333,00');
        $response->assertSee('R$ 442,35');
        $response->assertSee('Sobrando');
        $response->assertDontSee('Molde Automático');

        // Verifica tela independente de Investimentos (/investimentos)
        $investmentsResponse = $this->actingAs($user)->get(route('investments.annual', ['year' => 2026]));
        $investmentsResponse->assertOk();
        $investmentsResponse->assertSee('Carteira de Investimentos (2026)');
        $investmentsResponse->assertSee('R$ 333,00');
        $investmentsResponse->assertSee('CDB Nubank (100% CDI)');

        // Verifica tela independente de Resumo Anual (/ano)
        $annualSummaryResponse = $this->actingAs($user)->get(route('annual.summary', ['year' => 2026]));
        $annualSummaryResponse->assertOk();
        $annualSummaryResponse->assertSee('Resumo Anual (2026)');
        $annualSummaryResponse->assertSee('R$ 1.011,25');
        $annualSummaryResponse->assertSee('R$ 442,35');
    }

    public function test_user_can_create_custom_main_categories_and_types(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Acessa GET /categorias sem erro
        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Despesas Fixas');

        // Adiciona novo tipo "Gasolina Aditivada" dentro da categoria existente "Despesas Fixas"
        $this->actingAs($user)->post(route('categories.store'), [
            'group_name' => 'Despesas Fixas',
            'type' => CategoryType::FixedExpense->value,
            'color' => '#0284c7',
            'name' => 'Gasolina Aditivada',
        ])->assertRedirect(route('categories.index'));

        $this->assertTrue(
            $user->categories()
                ->where('group_name', 'Despesas Fixas')
                ->where('name', 'Gasolina Aditivada')
                ->exists()
        );

        // Cria nova Categoria Principal "Pet & Veterinário" com tipos "Ração, Consulta"
        $this->actingAs($user)->post(route('categories.store'), [
            'group_name' => 'Pet & Veterinário',
            'type' => CategoryType::FixedExpense->value,
            'color' => '#0d9488',
            'items' => 'Ração, Consulta',
        ])->assertRedirect(route('categories.index'));

        $this->assertSame(2, $user->categories()->where('group_name', 'Pet & Veterinário')->count());

        // Verifica que "Gasolina Aditivada" e "Pet & Veterinário" aparecem na tela /categorias e no Controle Mensal do usuário
        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Gasolina Aditivada')
            ->assertSee('Pet & Veterinário')
            ->assertSee('Ração');

        $this->actingAs($user)
            ->get(route('dashboard.monthly', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->assertSee('Gasolina Aditivada')
            ->assertSee('Pet & Veterinário')
            ->assertSee('Ração')
            ->assertSee('Consulta');

        // Isolamento estrito: outro usuário não vê os tipos nem categorias criados pelo primeiro usuário
        $this->actingAs($otherUser)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertDontSee('Gasolina Aditivada')
            ->assertDontSee('Pet & Veterinário');
    }

    public function test_enforces_strict_multi_user_isolation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $catA = $userA->categories()->where('type', CategoryType::VariableExpense->value)->firstOrFail();

        $this->actingAs($userA)->post(route('transactions.store'), [
            'category_id' => $catA->id,
            'year' => 2026,
            'month' => 10,
            'description' => 'Gasto Privado A',
            'amount' => '150,00',
        ]);

        $txA = $userA->transactions()->firstOrFail();

        // Usuário B não vê o lançamento de A e recebe 403 ao tentar editar ou excluir
        $this->actingAs($userB)
            ->get(route('dashboard.monthly', ['year' => 2026, 'month' => 10]))
            ->assertOk()
            ->assertDontSee('Gasto Privado A');

        $this->actingAs($userB)
            ->delete(route('transactions.destroy', $txA->id))
            ->assertForbidden();
    }

    public function test_admin_can_create_users_and_reset_passwords_to_12345678_while_non_admin_is_blocked(): void
    {
        $this->assertSame('12345678', AdminUserService::DEFAULT_TEMP_PASSWORD);

        $regularUser = User::factory()->create([
            'role' => 'user',
            'is_admin' => false,
        ]);

        $adminUser = User::factory()->create([
            'role' => 'admin',
            'is_admin' => true,
        ]);

        // Usuário comum recebe 403 ao tentar acessar /admin/users
        $this->actingAs($regularUser)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        // Admin acessa painel normalmente
        $this->actingAs($adminUser)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Painel Admin & Gestão de Usuários');

        // Admin cria novo usuário
        $this->actingAs($adminUser)
            ->post(route('admin.users.store'), [
                'name' => 'Novo Membro',
                'email' => 'novo@quantocustaminhavida.com',
                'role' => 'user',
            ])
            ->assertRedirect(route('admin.users.index'));

        $createdUser = User::where('email', 'novo@quantocustaminhavida.com')->firstOrFail();
        $this->assertTrue(Hash::check('12345678', $createdUser->password));

        // Altera a senha do usuário e depois usa o reset do Admin para voltar para 12345678
        $createdUser->update(['password' => Hash::make('OutraSenha999!')]);
        $this->assertFalse(Hash::check('12345678', $createdUser->fresh()->password));

        $this->actingAs($adminUser)
            ->post(route('admin.users.reset-password', $createdUser->id))
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('12345678', $createdUser->fresh()->password));
    }
}
