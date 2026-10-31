@php
    use App\Support\Money;

    $year = $report['year'];
    $months = $report['months'];
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Cabeçalho de Resumo Anual -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Resumo Anual ({{ $year }})</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Visão consolidada dos 12 meses: receitas, despesas fixas, despesas variáveis, investimentos e saldo mensal.
                    </p>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <a href="{{ route('annual.summary', ['year' => $year - 1]) }}"
                       class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-200 transition">
                        &larr; {{ $year - 1 }}
                    </a>
                    <span class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-bold">{{ $year }}</span>
                    <a href="{{ route('annual.summary', ['year' => $year + 1]) }}"
                       class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-200 transition">
                        {{ $year + 1 }} &rarr;
                    </a>
                </div>
            </div>

            <!-- Cards Consolidados do Ano -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Receita Acumulada ({{ $year }})</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">{{ Money::format($report['total_income_cents']) }}</div>
                    <div class="text-xs text-slate-400 dark:text-slate-500 mt-1.5">Total de entradas no ano</div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Despesas Fixas + Variáveis</div>
                    <div class="text-2xl font-bold text-slate-800 dark:text-slate-100 mt-2">
                        {{ Money::format($report['total_fixed_cents'] + $report['total_variable_cents']) }}
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">
                        Fixas: {{ Money::format($report['total_fixed_cents']) }} &bull; Variáveis: {{ Money::format($report['total_variable_cents']) }}
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Investido ({{ $year }})</div>
                    <div class="text-2xl font-bold text-teal-600 dark:text-teal-400 mt-2">{{ Money::format($report['total_invested_cents']) }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">
                        {{ number_format($report['investment_share_pct'] ?? 0, 1, ',', '.') }}% da receita anual
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Saldo Livre do Ano</div>
                    <div class="text-2xl font-bold {{ $report['total_balance_cents'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} mt-2">
                        {{ Money::format($report['total_balance_cents']) }}
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">
                        Resultado líquido após saídas e aportes
                    </div>
                </div>
            </div>

            <!-- Tabela de Balanço Mensal de Janeiro a Dezembro -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="px-5 py-4 bg-slate-900 dark:bg-slate-800/90 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <span class="font-bold text-sm uppercase tracking-wider">Balanço Mês a Mês — {{ $year }}</span>
                    <span class="text-xs text-slate-300">Clique no mês para abrir o Controle Mensal</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">
                            <tr>
                                <th class="px-5 py-3.5 text-left">Mês</th>
                                <th class="px-5 py-3.5 text-right">Receitas</th>
                                <th class="px-5 py-3.5 text-right">Despesas Fixas</th>
                                <th class="px-5 py-3.5 text-right">Despesas Variáveis</th>
                                <th class="px-5 py-3.5 text-right">Investimentos</th>
                                <th class="px-5 py-3.5 text-right">Saldo do Mês</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($months as $m)
                                @php
                                    $isEmptyMonth = $m['income_cents'] === 0 && $m['fixed_cents'] === 0 && $m['variable_cents'] === 0 && !$m['has_investment'];
                                @endphp
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                        <a href="{{ route('dashboard.monthly', ['year' => $year, 'month' => $m['month']]) }}"
                                           class="hover:text-emerald-600 dark:hover:text-emerald-400 hover:underline">
                                            {{ $m['month_name'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap {{ $m['income_cents'] > 0 ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-slate-300 dark:text-slate-700' }}">
                                        {{ $m['income_cents'] > 0 ? Money::format($m['income_cents']) : '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap {{ $m['fixed_cents'] > 0 ? 'text-sky-600 dark:text-sky-400 font-medium' : 'text-slate-300 dark:text-slate-700' }}">
                                        {{ $m['fixed_cents'] > 0 ? Money::format($m['fixed_cents']) : '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap {{ $m['variable_cents'] > 0 ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-slate-300 dark:text-slate-700' }}">
                                        {{ $m['variable_cents'] > 0 ? Money::format($m['variable_cents']) : '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap {{ $m['has_investment'] ? 'text-teal-600 dark:text-teal-400 font-medium' : 'text-slate-300 dark:text-slate-700' }}">
                                        {{ $m['has_investment'] ? Money::format($m['invested_cents']) : '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-bold whitespace-nowrap
                                        @if($isEmptyMonth) text-slate-300 dark:text-slate-700
                                        @elseif($m['balance_cents'] > 0) text-emerald-600 dark:text-emerald-400
                                        @elseif($m['balance_cents'] < 0) text-rose-600 dark:text-rose-400
                                        @else text-slate-600 dark:text-slate-400 @endif">
                                        {{ $isEmptyMonth ? '—' : Money::format($m['balance_cents']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-900 dark:bg-slate-950 text-white font-bold">
                            <tr>
                                <td class="px-5 py-3.5 uppercase text-xs tracking-wider">TOTAL NO ANO</td>
                                <td class="px-5 py-3.5 text-right">{{ Money::format($report['total_income_cents']) }}</td>
                                <td class="px-5 py-3.5 text-right">{{ Money::format($report['total_fixed_cents']) }}</td>
                                <td class="px-5 py-3.5 text-right">{{ Money::format($report['total_variable_cents']) }}</td>
                                <td class="px-5 py-3.5 text-right">{{ Money::format($report['total_invested_cents']) }}</td>
                                <td class="px-5 py-3.5 text-right">{{ Money::format($report['total_balance_cents']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
