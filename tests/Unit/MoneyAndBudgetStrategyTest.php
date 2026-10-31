<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Strategies\CustomPercentageBudgetStrategy;
use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyAndBudgetStrategyTest extends TestCase
{
    public function test_converts_monetary_formats_to_exact_cents(): void
    {
        $this->assertSame(101125, Money::toCents('1.011,25'));
        $this->assertSame(101125, Money::toCents('R$ 1.011,25'));
        $this->assertSame(101125, Money::toCents('1011.25'));
        $this->assertSame(101125, Money::toCents(1011.25));
        $this->assertSame(3000, Money::toCents(30));
        $this->assertSame(0, Money::toCents(''));
    }

    public function test_formats_cents_to_brl_string(): void
    {
        $this->assertSame('R$ 1.011,25', Money::format(101125));
        $this->assertSame('-R$ 499,65', Money::format(-49965));
        $this->assertSame('1.011,25', Money::format(101125, false));
    }

    public function test_calculates_50_30_20_rule_accurately_in_cents(): void
    {
        $user = new User([
            'rule_needs_pct' => 50,
            'rule_wants_pct' => 30,
            'rule_investments_pct' => 20,
        ]);

        $strategy = new CustomPercentageBudgetStrategy();

        // Outubro/26 da planilha: Receita 1.011,25 | Fixas 205,00 | Variáveis (sem invest.) 108,88 | Investimento 333,00
        $result = $strategy->calculate(
            user: $user,
            incomeCents: 101125,
            fixedExpenseCents: 20500,
            variableExpenseCents: 10888,
            investmentCents: 33300
        );

        $this->assertSame(101125, $result['income_cents']);
        $this->assertSame(50563, $result['buckets']['needs']['ideal_cents']);
        $this->assertSame(30338, $result['buckets']['wants']['ideal_cents']);
        $this->assertSame(20224, $result['buckets']['investments']['ideal_cents']);

        // Soma dos 3 blocos ideais fecha exatamente 100% dos centavos da receita
        $sumIdeal = $result['buckets']['needs']['ideal_cents']
            + $result['buckets']['wants']['ideal_cents']
            + $result['buckets']['investments']['ideal_cents'];
        $this->assertSame(101125, $sumIdeal);

        $this->assertFalse($result['buckets']['needs']['is_over_budget']);
        $this->assertFalse($result['buckets']['wants']['is_over_budget']);
        $this->assertTrue($result['buckets']['investments']['is_goal_met']);
    }
}
