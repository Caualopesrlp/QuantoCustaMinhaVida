<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CategoryType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialWorkflowAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_receives_default_categories_and_can_manage_monthly_finances(): void
    {
        $user = User::factory()->create();

        // 12 categorias padrão criadas automaticamente pelo evento User::created
        $this->assertSame(12, $user->categories()->count());

        $salaryCategory = $user->categories()->where('name', 'Salário')->firstOrFail();
        $fixedCategory = $user->categories()->where('name', 'Transporte Público')->firstOrFail();
        $variableCategory = $user->categories()->where('name', 'Alimentação fora')->firstOrFail();
        $investmentCategory = $user->categories()->where('name', 'Investimento')->firstOrFail();

        // Cadastra Modelo Fixo (X26)
        $this->actingAs($user)->post(route('recurring-templates.store'), [
            'category_id' => $salaryCategory->id,
            'description' => 'Salário',
            'amount' => '1.011,25',
            'notes' => 'Salário',
        ])->assertRedirect(route('recurring-templates.index'));

        $this->actingAs($user)->post(route('recurring-templates.store'), [
            'category_id' => $fixedCategory->id,
            'description' => 'Transporte Público',
            'amount' => '180,00',
            'notes' => 'SalvadorCard',
        ])->assertRedirect(route('recurring-templates.index'));

        // Gera fixas para Outubro/2026 (duas chamadas para provar idempotência da Factory)
        $this->actingAs($user)->post(route('dashboard.generate-recurring', ['year' => 2026, 'month' => 10]))
            ->assertRedirect(route('dashboard.monthly', ['year' => 2026, 'month' => 10]));
        $this->actingAs($user)->post(route('dashboard.generate-recurring', ['year' => 2026, 'month' => 10]));

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
        $response->assertSee('✅ Sobrando 😊');

        // Verifica sincronização automática na aba anual de Investimentos 📈
        $annualResponse = $this->actingAs($user)->get(route('investments.annual', ['year' => 2026]));
        $annualResponse->assertOk();
        $annualResponse->assertSee('R$ 333,00');
        $annualResponse->assertSee('CDB Nubank (100% CDI)');
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
}
