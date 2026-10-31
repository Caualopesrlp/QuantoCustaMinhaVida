@php
    use App\Support\Money;

    $year = $report['year'];
    $months = $report['months'];
@endphp

<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Cabeçalho de Ano e Resumo Anual -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">📈 Investimentos & Balanço Anual ({{ $year }})</h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Alimentado automaticamente pelos aportes lançados na categoria de Investimento em cada mês.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('investments.annual', ['year' => $year - 1]) }}"
                       class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-sm font-semibold text-slate-700">
                        ← {{ $year - 1 }}
                    </a>
                    <span class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-bold">{{ $year }}</span>
                    <a href="{{ route('investments.annual', ['year' => $year + 1]) }}"
                       class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-sm font-semibold text-slate-700">
                        {{ $year + 1 }} →
                    </a>
                </div>
            </div>

            <!-- Cards Consolidados do Ano -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-indigo-600 text-white rounded-2xl p-5 shadow-xs">
                    <div class="text-xs font-semibold uppercase tracking-wider text-indigo-100">📈 Total Investido em {{ $year }}</div>
                    <div class="text-2xl font-extrabold mt-1">{{ Money::format($report['total_invested_cents']) }}</div>
                    <div class="text-xs text-indigo-200 mt-1">Soma acumulada no ano</div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
                    <div class="text-xs font-medium uppercase tracking-wider text-slate-500">💰 Receita Acumulada ({{ $year }})</div>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ Money::format($report['total_income_cents']) }}</div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
                    <div class="text-xs font-medium uppercase tracking-wider text-slate-500">📌 + 🔄 Fixas e Variáveis ({{ $year }})</div>
                    <div class="text-2xl font-bold text-slate-800 mt-1">
                        {{ Money::format($report['total_fixed_cents'] + $report['total_variable_cents']) }}
                    </div>
                    <div class="text-xs text-slate-500 mt-1">
                        Fixas: {{ Money::format($report['total_fixed_cents']) }} | Variáveis: {{ Money::format($report['total_variable_cents']) }}
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
                    <div class="text-xs font-medium uppercase tracking-wider text-slate-500">💡 Saldo Livre do Ano</div>
                    <div class="text-2xl font-bold {{ $report['total_balance_cents'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }} mt-1">
                        {{ Money::format($report['total_balance_cents']) }}
                    </div>
                </div>
            </div>

            <!-- Tabela Fiel à Aba "Investimentos 📈" + Visão Mensal -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between">
                    <span class="font-bold text-sm uppercase tracking-wider">📅 Histórico de Aportes & Resumo Mensal — {{ $year }}</span>
                    <span class="text-xs text-slate-300">Clique no mês para abrir o controle detalhado</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left">Mês</th>
                                <th class="px-4 py-3 text-right">Valor Investido (R$)</th>
                                <th class="px-4 py-3 text-right">Acumulado no Ano (R$)</th>
                                <th class="px-4 py-3 text-left">Obs. / Detalhes do Aporte</th>
                                <th class="px-4 py-3 text-right">Receita do Mês</th>
                                <th class="px-4 py-3 text-right">Saldo do Mês</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($months as $m)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 font-semibold text-slate-900">
                                        <a href="{{ route('dashboard.monthly', ['year' => $year, 'month' => $m['month']]) }}"
                                           class="hover:text-indigo-600 hover:underline">
                                            {{ $m['month_name'] }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold {{ $m['has_investment'] ? 'text-indigo-600' : 'text-slate-300' }}">
                                        {{ $m['has_investment'] ? Money::format($m['invested_cents']) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $m['has_investment'] ? 'text-slate-900' : 'text-slate-300' }}">
                                        {{ $m['has_investment'] ? Money::format($m['accumulated_cents']) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 text-xs">
                                        {{ $m['notes_summary'] !== '' ? $m['notes_summary'] : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600">
                                        {{ $m['income_cents'] > 0 ? Money::format($m['income_cents']) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold
                                        @if($m['income_cents'] === 0 && $m['fixed_cents'] === 0 && $m['variable_cents'] === 0 && !$m['has_investment']) text-slate-300
                                        @elseif($m['balance_cents'] > 0) text-emerald-600
                                        @elseif($m['balance_cents'] < 0) text-rose-600
                                        @else text-slate-600 @endif">
                                        @if($m['income_cents'] === 0 && $m['fixed_cents'] === 0 && $m['variable_cents'] === 0 && !$m['has_investment'])
                                            —
                                        @else
                                            {{ Money::format($m['balance_cents']) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-900 text-white font-bold">
                            <tr>
                                <td class="px-4 py-3 uppercase text-xs tracking-wider">TOTAL INVESTIDO NO ANO</td>
                                <td class="px-4 py-3 text-right text-base">{{ Money::format($report['total_invested_cents']) }}</td>
                                <td class="px-4 py-3 text-right text-base">{{ Money::format($report['total_invested_cents']) }}</td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3 text-right">{{ Money::format($report['total_income_cents']) }}</td>
                                <td class="px-4 py-3 text-right">{{ Money::format($report['total_balance_cents']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
