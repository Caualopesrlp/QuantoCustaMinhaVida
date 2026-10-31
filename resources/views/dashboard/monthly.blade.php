@php
    use App\Support\Money;

    $year = $dashboard['year'];
    $month = $dashboard['month'];
    $monthName = $dashboard['month_name'];
    $autoInjectedCount = $dashboard['auto_injected_count'] ?? 0;
    $nav = $dashboard['navigation'];
    $totals = $dashboard['totals'];
    $budgetRule = $dashboard['budget_rule'];
    $blocks = $dashboard['blocks'];
    $categories = $dashboard['categories'];

    $categoriesByGroup = $categories->groupBy(fn ($cat) => $cat->resolvedGroupName());
    $categoryNamesMap = $categories->mapWithKeys(fn ($cat) => [(string) $cat->id => $cat->name])->all();
@endphp

<x-app-layout>
    <div x-data="{
        showCreateModal: false,
        selectedCategoryId: '{{ $categories->first()?->id ?? '' }}',
        defaultDescription: '{{ addslashes($categories->first()?->name ?? '') }}',
        categoryNames: @js($categoryNamesMap),
        openCreateForCategory(catId, catName) {
            this.selectedCategoryId = String(catId);
            this.defaultDescription = catName;
            this.showCreateModal = true;
        },
        onCategoryChange() {
            const name = this.categoryNames[String(this.selectedCategoryId)] || '';
            if (name) {
                this.defaultDescription = name;
            }
        }
    }" class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($autoInjectedCount > 0)
                <div class="rounded-xl bg-teal-50 dark:bg-teal-950/60 border border-teal-200 dark:border-teal-800 px-4 py-3 text-sm font-medium text-teal-800 dark:text-teal-200 flex items-center justify-between">
                    <span>Molde mensal aplicado automaticamente: <strong>{{ $autoInjectedCount }}</strong> lançamento(s) recorrente(s) inicializado(s) em {{ $monthName }}/{{ $year }}.</span>
                </div>
            @endif

            @if (session('status'))
                <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-200 flex items-center justify-between">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 px-4 py-3 text-sm text-rose-800 dark:text-rose-200">
                    <ul class="list-disc ms-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Cabeçalho de Navegação de Mês e Ações Rápidas -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xs border border-slate-200 dark:border-slate-800 p-5 sm:p-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div class="flex items-center justify-between sm:justify-start gap-3 sm:gap-4">
                    <a href="{{ route('dashboard.monthly', ['year' => $nav['prev_year'], 'month' => $nav['prev_month']]) }}"
                       class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold transition shrink-0"
                       title="Mês anterior">
                        &larr;
                    </a>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                                {{ $monthName }} / {{ $year }}
                            </h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                @if($totals['status_color'] === 'emerald') bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300
                                @elseif($totals['status_color'] === 'rose') bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-300
                                @else bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 @endif">
                                {{ $totals['status_badge'] }}
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Saldo acumulado até {{ $monthName }}/{{ $year }}:
                            <strong class="font-semibold {{ $totals['accumulated_balance_cents'] >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ Money::format($totals['accumulated_balance_cents']) }}
                            </strong>
                        </p>
                    </div>

                    <a href="{{ route('dashboard.monthly', ['year' => $nav['next_year'], 'month' => $nav['next_month']]) }}"
                       class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold transition shrink-0"
                       title="Próximo mês">
                        &rarr;
                    </a>
                </div>

                <!-- Seletor Rápido de Mês e Botão de Novo Lançamento -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-2 sm:flex items-center gap-2.5">
                        <select name="month" onchange="this.form.submit()" class="w-full sm:w-auto rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm py-2 ps-3 pe-8 focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach($monthNames as $mNum => $mLabel)
                                <option value="{{ $mNum }}" @selected($mNum === $month)>{{ $mLabel }}</option>
                            @endforeach
                        </select>
                        <select name="year" onchange="this.form.submit()" class="w-full sm:w-auto rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm py-2 ps-3 pe-8 focus:border-emerald-500 focus:ring-emerald-500">
                            @for($y = $year - 2; $y <= $year + 2; $y++)
                                <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                            @endfor
                        </select>
                    </form>

                    <button type="button"
                            @click="selectedCategoryId = '{{ $categories->first()?->id ?? '' }}'; onCategoryChange(); showCreateModal = true"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold shadow-xs transition">
                        <span>+ Novo Lançamento</span>
                    </button>
                </div>
            </div>

            <!-- RESUMO DO MÊS (5 Cards Principais) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Receita Total</div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">{{ Money::format($totals['income_cents']) }}</div>
                    <div class="text-xs text-slate-400 dark:text-slate-500 mt-1.5">100% da base mensal</div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">(-) Despesas Fixas</div>
                    <div class="text-2xl font-bold text-sky-600 dark:text-sky-400 mt-2">{{ Money::format($totals['fixed_cents']) }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">{{ number_format($totals['fixed_pct'], 1, ',', '.') }}% da renda</div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">(-) Despesas Variáveis</div>
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2">{{ Money::format($totals['variable_cents']) }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">{{ number_format($totals['variable_pct'], 1, ',', '.') }}% da renda</div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-xs">
                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">(-) Investimentos</div>
                    <div class="text-2xl font-bold text-teal-600 dark:text-teal-400 mt-2">{{ Money::format($totals['investment_cents']) }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">{{ number_format($totals['investment_pct'], 1, ',', '.') }}% da renda</div>
                </div>

                <div class="rounded-2xl p-5 border shadow-xs sm:col-span-2 lg:col-span-1
                    @if($totals['balance_cents'] > 0) bg-emerald-50/70 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800
                    @elseif($totals['balance_cents'] < 0) bg-rose-50/70 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800
                    @else bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 @endif">
                    <div class="text-xs font-semibold uppercase tracking-wider
                        @if($totals['balance_cents'] > 0) text-emerald-700 dark:text-emerald-300
                        @elseif($totals['balance_cents'] < 0) text-rose-700 dark:text-rose-300
                        @else text-slate-600 dark:text-slate-400 @endif">
                        (=) Saldo do Mês
                    </div>
                    <div class="text-2xl font-extrabold mt-2
                        @if($totals['balance_cents'] > 0) text-emerald-700 dark:text-emerald-400
                        @elseif($totals['balance_cents'] < 0) text-rose-700 dark:text-rose-400
                        @else text-slate-800 dark:text-slate-100 @endif">
                        {{ Money::format($totals['balance_cents']) }}
                    </div>
                    <div class="text-xs font-medium mt-1.5
                        @if($totals['balance_cents'] > 0) text-emerald-700 dark:text-emerald-300
                        @elseif($totals['balance_cents'] < 0) text-rose-600 dark:text-rose-300
                        @else text-slate-500 dark:text-slate-400 @endif">
                        {{ $totals['status_badge'] }}
                    </div>
                </div>
            </div>

            <!-- REGRA 50-30-20 (Ideal vs. Real no Mês) -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Regra Orçamentária (Ideal vs. Real no Mês)</h2>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            Comparativo calculado sobre a receita do mês ({{ Money::format($totals['income_cents']) }}).
                        </p>
                    </div>
                    <a href="{{ route('categories.index') }}" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                        Ajustar metas e categorias &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($budgetRule['buckets'] as $bucket)
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 p-4 bg-slate-50/50 dark:bg-slate-800/40 space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-sm text-slate-900 dark:text-white">{{ $bucket['label'] }}</span>
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full shrink-0
                                    @if($bucket['is_over_budget']) bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300
                                    @elseif($bucket['is_goal_met']) bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300
                                    @else bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 @endif">
                                    Real: {{ number_format($bucket['actual_pct_of_income'], 1, ',', '.') }}%
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $bucket['subtitle'] }}</p>

                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full transition-all
                                    @if($bucket['is_over_budget']) bg-rose-500
                                    @elseif($bucket['key'] === 'investments') bg-teal-600
                                    @elseif($bucket['key'] === 'needs') bg-emerald-500
                                    @else bg-amber-500 @endif"
                                     style="width: {{ $bucket['progress_pct'] }}%"></div>
                            </div>

                            <div class="flex items-center justify-between text-xs pt-1">
                                <span class="text-slate-600 dark:text-slate-300">
                                    Realizado: <strong class="text-slate-900 dark:text-white">{{ Money::format($bucket['actual_cents']) }}</strong>
                                </span>
                                <span class="text-slate-500 dark:text-slate-400">
                                    Ideal ({{ $bucket['target_pct'] }}%): <strong>{{ Money::format($bucket['ideal_cents']) }}</strong>
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- BLOCOS DE CATEGORIAS PRINCIPAIS (Padrão + Personalizadas) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($blocks as $blockKey => $block)
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden flex flex-col">
                        <div class="px-5 py-4 bg-slate-900 dark:bg-slate-800/90 text-white flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $block['color'] ?? '#0d9488' }}"></span>
                                <span class="font-bold text-sm tracking-wide uppercase truncate">{{ $block['label'] }}</span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-white/10 text-slate-200 font-medium shrink-0">
                                    {{ $block['type']->shortLabel() }}
                                </span>
                            </div>
                            <span class="font-bold text-base shrink-0">{{ Money::format($block['subtotal_cents']) }}</span>
                        </div>

                        <div class="divide-y divide-slate-100 dark:divide-slate-800 flex-1">
                            @forelse($block['categories'] as $catGroup)
                                @php
                                    $cat = $catGroup['category'];
                                    $txs = $catGroup['transactions'];
                                @endphp
                                <div x-data="{ expanded: false, editingTxId: null }" class="p-4 sm:px-5 hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0 flex-1 cursor-pointer" @click="expanded = !expanded">
                                            <div class="flex items-center gap-2.5">
                                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cat->color ?? '#0d9488' }}"></span>
                                                <span class="font-semibold text-slate-900 dark:text-white text-sm truncate">{{ $cat->name }}</span>
                                                @if($txs->count() > 0)
                                                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium shrink-0">
                                                        {{ $txs->count() }} {{ $txs->count() === 1 ? 'item' : 'itens' }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($catGroup['notes_summary'] !== '')
                                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-1 ps-5">
                                                    Obs: {{ $catGroup['notes_summary'] }}
                                                </p>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-2.5 shrink-0">
                                            <span class="font-bold text-sm {{ $catGroup['subtotal_cents'] > 0 ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500' }}">
                                                {{ Money::format($catGroup['subtotal_cents']) }}
                                            </span>

                                            <button type="button"
                                                    @click="openCreateForCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')"
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-900/50 hover:bg-emerald-100 dark:hover:bg-emerald-900 text-emerald-700 dark:text-emerald-300 font-bold text-sm transition"
                                                    title="Adicionar em {{ $cat->name }}">
                                                +
                                            </button>

                                            <button type="button"
                                                    @click="expanded = !expanded"
                                                    class="text-xs text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 px-1"
                                                    title="Ver lançamentos">
                                                <span x-text="expanded ? '▲' : '▼'">▼</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Lista de Lançamentos Individuais do Tipo -->
                                    <div x-show="expanded" x-cloak class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2">
                                        @forelse($txs as $tx)
                                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800/70 px-3.5 py-2.5 text-xs">
                                                <div x-show="editingTxId !== {{ $tx->id }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                    <div class="flex flex-wrap items-center gap-1.5 min-w-0">
                                                        <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $tx->description }}</span>
                                                        @if($tx->notes)
                                                            <span class="text-slate-500 dark:text-slate-400">&mdash; {{ $tx->notes }}</span>
                                                        @endif
                                                        @if($tx->occurred_on)
                                                            <span class="text-slate-400 dark:text-slate-500">({{ $tx->occurred_on->format('d/m') }})</span>
                                                        @endif
                                                    </div>

                                                    <div class="flex items-center justify-end gap-3 shrink-0">
                                                        <span class="font-bold text-slate-900 dark:text-white">{{ Money::format($tx->amount_cents) }}</span>
                                                        <button type="button"
                                                                @click="editingTxId = {{ $tx->id }}"
                                                                class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium">
                                                            Editar
                                                        </button>
                                                        <form method="POST" action="{{ route('transactions.destroy', $tx->id) }}"
                                                              onsubmit="return confirm('Remover este lançamento?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-rose-600 dark:text-rose-400 hover:underline font-medium">
                                                                Excluir
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>

                                                <!-- Formulário Inline de Edição -->
                                                <form x-show="editingTxId === {{ $tx->id }}" x-cloak
                                                      method="POST" action="{{ route('transactions.update', $tx->id) }}"
                                                      class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center pt-1">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="category_id" value="{{ $cat->id }}">
                                                    <div class="sm:col-span-4">
                                                        <input type="text" name="description" value="{{ $tx->description }}" required
                                                               class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs py-1.5" placeholder="Descrição">
                                                    </div>
                                                    <div class="sm:col-span-3">
                                                        <input type="text" name="amount" value="{{ Money::format($tx->amount_cents, false) }}" required
                                                               class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs py-1.5" placeholder="Valor (R$)">
                                                    </div>
                                                    <div class="sm:col-span-3">
                                                        <input type="text" name="notes" value="{{ $tx->notes }}"
                                                               class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs py-1.5" placeholder="Obs.">
                                                    </div>
                                                    <div class="sm:col-span-2 flex items-center gap-1.5 justify-end">
                                                        <button type="submit" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold">
                                                            Salvar
                                                        </button>
                                                        <button type="button" @click="editingTxId = null" class="px-2 py-1.5 text-slate-500 text-xs">
                                                            ✕
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        @empty
                                            <div class="text-xs text-slate-400 italic py-1">
                                                Nenhum lançamento neste tipo em {{ $monthName }}/{{ $year }}.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @empty
                                <div class="p-5 text-xs text-slate-400">Nenhum tipo cadastrado nesta categoria.</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Modal Rápido para Novo Lançamento -->
        <div x-show="showCreateModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
            <div @click.away="showCreateModal = false"
                 class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 max-w-md w-full p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Novo Lançamento ({{ $monthName }}/{{ $year }})</h3>
                    <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">✕</button>
                </div>

                <form method="POST" action="{{ route('transactions.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Categoria / Tipo</label>
                        <select name="category_id" x-model="selectedCategoryId" @change="onCategoryChange()" required
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach($categoriesByGroup as $groupName => $groupItems)
                                <optgroup label="{{ $groupName }}">
                                    @foreach($groupItems as $catOption)
                                        <option value="{{ $catOption->id }}">
                                            {{ $catOption->name }} ({{ $catOption->type->shortLabel() }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Descrição</label>
                        <input type="text" name="description" x-model="defaultDescription" required
                               placeholder="Ex: Salário, Aluguel, Mercado, CDB Nubank..."
                               class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Valor (R$)</label>
                            <input type="text" name="amount" required
                                   placeholder="Ex: 150,00"
                                   class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Data (Opcional)</label>
                            <input type="date" name="occurred_on"
                                   value="{{ sprintf('%04d-%02d-01', $year, $month) }}"
                                   class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase mb-1.5">Observação (Opcional)</label>
                        <input type="text" name="notes"
                               placeholder="Ex: CDB 100% CDI, Compra mensal..."
                               class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button type="button" @click="showCreateModal = false"
                                class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold">
                            Salvar Lançamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
