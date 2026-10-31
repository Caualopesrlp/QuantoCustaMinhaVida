@php
    use App\Support\Money;

    $year = $report['year'];
    $months = $report['months'];
    $investmentTransactions = $report['investment_transactions'] ?? collect();
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Cabeçalho de Investimentos -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Carteira de Investimentos ({{ $year }})</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Acompanhamento exclusivo dos seus aportes mensais e evolução acumulada no ano.
                    </p>
                </div>

                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <a href="{{ route('investments.annual', ['year' => $year - 1]) }}"
                       class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-200 transition">
                        &larr; {{ $year - 1 }}
                    </a>
                    <span class="px-4 py-2 rounded-xl bg-teal-600 text-white text-sm font-bold">{{ $year }}</span>
                    <a href="{{ route('investments.annual', ['year' => $year + 1]) }}"
                       class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-sm font-semibold text-slate-700 dark:text-slate-200 transition">
                        {{ $year + 1 }} &rarr;
                    </a>
                </div>
            </div>

            <!-- KPIs Exclusivos de Investimentos -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-teal-600 text-white rounded-2xl p-5 shadow-xs">
                    <div class="text-xs font-semibold uppercase tracking-wider text-teal-100">Total Investido em {{ $year }}</div>
                    <div class="text-2xl font-extrabold mt-2">{{ Money::format($report['total_invested_cents']) }}</div>
                    <div class="text-xs text-teal-200 mt-1.5">Patrimônio aportado no ano</div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Média por Mês Aportado</div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-2">
                        {{ Money::format($report['average_monthly_invested_cents'] ?? 0) }}
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">
                        Considerando meses com aporte ativo
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Taxa de Investimento</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">
                        {{ number_format($report['investment_share_pct'] ?? 0, 1, ',', '.') }}%
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">
                        Percentual sobre a receita do ano
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Constância no Ano</div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white mt-2">
                        {{ $report['months_with_investment'] ?? 0 }} / 12 meses
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">
                        Meses com pelo menos 1 aporte
                    </div>
                </div>
            </div>

            <!-- Evolução Mensal de Aportes -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="px-5 py-4 bg-slate-900 dark:bg-slate-800/90 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <span class="font-bold text-sm uppercase tracking-wider">Evolução Mensal de Aportes — {{ $year }}</span>
                    <span class="text-xs text-slate-300">Clique no mês para gerenciar os lançamentos</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">
                            <tr>
                                <th class="px-5 py-3.5 text-left">Mês</th>
                                <th class="px-5 py-3.5 text-right">Aporte no Mês (R$)</th>
                                <th class="px-5 py-3.5 text-right">Acumulado no Ano (R$)</th>
                                <th class="px-5 py-3.5 text-left">Detalhes / Ativos Aportados</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($months as $m)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                        <a href="{{ route('dashboard.monthly', ['year' => $year, 'month' => $m['month']]) }}"
                                           class="hover:text-teal-600 dark:hover:text-teal-400 hover:underline">
                                            {{ $m['month_name'] }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-bold whitespace-nowrap {{ $m['has_investment'] ? 'text-teal-600 dark:text-teal-400' : 'text-slate-300 dark:text-slate-700' }}">
                                        {{ $m['has_investment'] ? Money::format($m['invested_cents']) : '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-semibold whitespace-nowrap {{ $m['has_investment'] ? 'text-slate-900 dark:text-slate-100' : 'text-slate-300 dark:text-slate-700' }}">
                                        {{ $m['has_investment'] ? Money::format($m['accumulated_cents']) : '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600 dark:text-slate-400 text-xs">
                                        {{ $m['notes_summary'] !== '' ? $m['notes_summary'] : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-900 dark:bg-slate-950 text-white font-bold">
                            <tr>
                                <td class="px-5 py-3.5 uppercase text-xs tracking-wider">TOTAL INVESTIDO NO ANO</td>
                                <td class="px-5 py-3.5 text-right text-base">{{ Money::format($report['total_invested_cents']) }}</td>
                                <td class="px-5 py-3.5 text-right text-base">{{ Money::format($report['total_invested_cents']) }}</td>
                                <td class="px-5 py-3.5"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Lista Detalhada de Aportes do Ano -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="px-5 py-4 bg-slate-900 dark:bg-slate-800/90 text-white flex items-center justify-between">
                    <span class="font-bold text-sm uppercase tracking-wider">Lançamentos de Investimento em {{ $year }}</span>
                    <span class="text-xs text-slate-300">{{ $investmentTransactions->count() }} {{ $investmentTransactions->count() === 1 ? 'aporte' : 'aportes' }}</span>
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($investmentTransactions as $tx)
                        <div class="px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $tx->category?->color ?? '#0d9488' }}"></span>
                                <span class="font-semibold text-sm text-slate-900 dark:text-white">{{ $tx->description }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-teal-50 dark:bg-teal-950/70 text-teal-700 dark:text-teal-300 font-medium">
                                    {{ $tx->category?->name }}
                                </span>
                                @if($tx->notes)
                                    <span class="text-xs text-slate-500 dark:text-slate-400">&mdash; {{ $tx->notes }}</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0">
                                <span class="text-xs text-slate-400 dark:text-slate-500">
                                    {{ $tx->occurred_on ? $tx->occurred_on->format('d/m/Y') : sprintf('%02d/%04d', $tx->month, $tx->year) }}
                                </span>
                                <span class="font-bold text-sm text-teal-600 dark:text-teal-400">
                                    {{ Money::format($tx->amount_cents) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">
                            Nenhum aporte registrado em {{ $year }}.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
