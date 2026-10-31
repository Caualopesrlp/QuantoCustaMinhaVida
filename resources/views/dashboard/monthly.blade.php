@php
    use App\Support\Money;

    $year = $dashboard['year'];
    $month = $dashboard['month'];
    $monthName = $dashboard['month_name'];
    $nav = $dashboard['navigation'];
    $totals = $dashboard['totals'];
    $budgetRule = $dashboard['budget_rule'];
    $blocks = $dashboard['blocks'];
    $categories = $dashboard['categories'];
@endphp

<x-app-layout>
    <div x-data="{
        showCreateModal: false,
        selectedCategoryId: '{{ $categories->first()?->id ?? '' }}',
        defaultDescription: '',
        openCreateForCategory(catId, catName) {
            this.selectedCategoryId = String(catId);
            this.defaultDescription = catName;
            this.showCreateModal = true;
        }
    }" class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center justify-between">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                    <ul class="list-disc ms-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Cabeçalho de Navegação de Mês e Ações Rápidas -->
            <div class="bg-white rounded-2xl shadow-xs border border-slate-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('dashboard.monthly', ['year' => $nav['prev_year'], 'month' => $nav['prev_month']]) }}"
                       class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold transition"
                       title="Mês anterior">
                        ←
                    </a>

                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold text-slate-900">
                                {{ $monthName }} / {{ $year }}
                            </h1>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                @if($totals['status_color'] === 'emerald') bg-emerald-100 text-emerald-800
                                @elseif($totals['status_color'] === 'rose') bg-rose-100 text-rose-800
                                @else bg-slate-100 text-slate-800 @endif">
                                {{ $totals['status_badge'] }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Saldo Acumulado Geral até {{ $monthName }}/{{ $year }}:
                            <strong class="{{ $totals['accumulated_balance_cents'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                {{ Money::format($totals['accumulated_balance_cents']) }}
                            </strong>
                        </p>
                    </div>

                    <a href="{{ route('dashboard.monthly', ['year' => $nav['next_year'], 'month' => $nav['next_month']]) }}"
                       class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 font-semibold transition"
                       title="Próximo mês">
                        →
                    </a>
                </div>

                <!-- Seletor Rápido de Mês e Botões de Ação -->
                <div class="flex flex-wrap items-center gap-2">
                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <select name="month" onchange="this.form.submit()" class="rounded-xl border-slate-300 text-sm py-2 ps-3 pe-8 focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($monthNames as $mNum => $mLabel)
                                <option value="{{ $mNum }}" @selected($mNum === $month)>{{ $mLabel }}</option>
                            @endforeach
                        </select>
                        <select name="year" onchange="this.form.submit()" class="rounded-xl border-slate-300 text-sm py-2 ps-3 pe-8 focus:border-indigo-500 focus:ring-indigo-500">
                            @for($y = $year - 2; $y <= $year + 2; $y++)
                                <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                            @endfor
                        </select>
                    </form>

                    @if($recurringTemplatesCount > 0)
                        <form method="POST" action="{{ route('dashboard.generate-recurring', ['year' => $year, 'month' => $month]) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-medium transition">
                                📌 Aplicar Modelo Fixo (X26)
                            </button>
                        </form>
                    @endif

                    <button type="button"
                            @click="selectedCategoryId = '{{ $categories->first()?->id ?? '' }}'; defaultDescription = ''; showCreateModal = true"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold shadow-xs transition">
                        ➕ Novo Lançamento
                    </button>
                </div>
            </div>

            <!-- 📊 RESUMO DO MÊS (5 Cards Principais) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">💰 Receita Total</div>
                    <div class="text-2xl font-bold text-emerald-600 mt-1">{{ Money::format($totals['income_cents']) }}</div>
                    <div class="text-xs text-slate-400 mt-1">100% da base mensal</div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">📌 (-) Despesas Fixas</div>
                    <div class="text-2xl font-bold text-blue-600 mt-1">{{ Money::format($totals['fixed_cents']) }}</div>
                    <div class="text-xs text-slate-500 mt-1">{{ number_format($totals['fixed_pct'], 1, ',', '.') }}% da renda</div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">🔄 (-) Variáveis</div>
                    <div class="text-2xl font-bold text-amber-600 mt-1">{{ Money::format($totals['variable_cents']) }}</div>
                    <div class="text-xs text-slate-500 mt-1">{{ number_format($totals['variable_pct'], 1, ',', '.') }}% da renda</div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">📈 (-) Investimentos</div>
                    <div class="text-2xl font-bold text-indigo-600 mt-1">{{ Money::format($totals['investment_cents']) }}</div>
                    <div class="text-xs text-slate-500 mt-1">{{ number_format($totals['investment_pct'], 1, ',', '.') }}% da renda</div>
                </div>

                <div class="rounded-2xl p-4 border shadow-xs
                    @if($totals['balance_cents'] > 0) bg-emerald-50/70 border-emerald-200
                    @elseif($totals['balance_cents'] < 0) bg-rose-50/70 border-rose-200
                    @else bg-slate-50 border-slate-200 @endif">
                    <div class="text-xs font-semibold uppercase tracking-wider
                        @if($totals['balance_cents'] > 0) text-emerald-700
                        @elseif($totals['balance_cents'] < 0) text-rose-700
                        @else text-slate-600 @endif">
                        💡 (=) Saldo do Mês
                    </div>
                    <div class="text-2xl font-extrabold mt-1
                        @if($totals['balance_cents'] > 0) text-emerald-700
                        @elseif($totals['balance_cents'] < 0) text-rose-700
                        @else text-slate-800 @endif">
                        {{ Money::format($totals['balance_cents']) }}
                    </div>
                    <div class="text-xs font-medium mt-1
                        @if($totals['balance_cents'] > 0) text-emerald-700
                        @elseif($totals['balance_cents'] < 0) text-rose-600
                        @else text-slate-500 @endif">
                        {{ $totals['status_badge'] }}
                    </div>
                </div>
            </div>

            <!-- 📐 REGRA 50-30-20 (Ideal vs. Real no Mês) -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">📐 Regra Orçamentária (Ideal vs. Real no Mês)</h2>
                        <p class="text-xs text-slate-500">
                            Comparativo calculado automaticamente sobre a receita do mês ({{ Money::format($totals['income_cents']) }}).
                        </p>
                    </div>
                    <a href="{{ route('categories.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        Ajustar metas percentuais →
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($budgetRule['buckets'] as $bucket)
                        <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/50 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-slate-900">{{ $bucket['label'] }}</span>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full
                                    @if($bucket['is_over_budget']) bg-rose-100 text-rose-700
                                    @elseif($bucket['is_goal_met']) bg-emerald-100 text-emerald-700
                                    @else bg-slate-200 text-slate-700 @endif">
                                    Real: {{ number_format($bucket['actual_pct_of_income'], 1, ',', '.') }}%
                                </span>
                            </div>
                            <p class="text-xs text-slate-500">{{ $bucket['subtitle'] }}</p>

                            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full transition-all
                                    @if($bucket['is_over_budget']) bg-rose-500
                                    @elseif($bucket['key'] === 'investments') bg-indigo-600
                                    @elseif($bucket['key'] === 'needs') bg-emerald-500
                                    @else bg-amber-500 @endif"
                                     style="width: {{ $bucket['progress_pct'] }}%"></div>
                            </div>

                            <div class="flex items-center justify-between text-xs pt-1">
                                <span class="text-slate-600">
                                    Gasto/Aporte: <strong class="text-slate-900">{{ Money::format($bucket['actual_cents']) }}</strong>
                                </span>
                                <span class="text-slate-500">
                                    Ideal ({{ $bucket['target_pct'] }}%): <strong>{{ Money::format($bucket['ideal_cents']) }}</strong>
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 4 BLOCOS DE CONTROLE MENSAL (Fiel à Planilha, mas expansível por lançamento) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($blocks as $blockKey => $block)
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden flex flex-col">
                        <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between">
                            <span class="font-bold text-sm tracking-wide uppercase">{{ $block['label'] }}</span>
                            <span class="font-bold text-base">{{ Money::format($block['subtotal_cents']) }}</span>
                        </div>

                        <div class="divide-y divide-slate-100 flex-1">
                            @forelse($block['categories'] as $catGroup)
                                @php
                                    $cat = $catGroup['category'];
                                    $txs = $catGroup['transactions'];
                                @endphp
                                <div x-data="{ expanded: false, editingTxId: null }" class="p-4 hover:bg-slate-50/70 transition">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0 flex-1 cursor-pointer" @click="expanded = !expanded">
                                            <div class="flex items-center gap-2">
                                                <span class="text-lg">{{ $cat->icon }}</span>
                                                <span class="font-semibold text-slate-900 text-sm">{{ $cat->name }}</span>
                                                @if($txs->count() > 0)
                                                    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium">
                                                        {{ $txs->count() }} {{ $txs->count() === 1 ? 'item' : 'itens' }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($catGroup['notes_summary'] !== '')
                                                <p class="text-xs text-slate-500 truncate mt-0.5">
                                                    Obs: {{ $catGroup['notes_summary'] }}
                                                </p>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-3 shrink-0">
                                            <span class="font-bold text-sm {{ $catGroup['subtotal_cents'] > 0 ? 'text-slate-900' : 'text-slate-400' }}">
                                                {{ Money::format($catGroup['subtotal_cents']) }}
                                            </span>

                                            <button type="button"
                                                    @click="openCreateForCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}')"
                                                    class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-sm transition"
                                                    title="Adicionar em {{ $cat->name }}">
                                                +
                                            </button>

                                            <button type="button"
                                                    @click="expanded = !expanded"
                                                    class="text-xs text-slate-400 hover:text-slate-700 px-1"
                                                    title="Ver lançamentos">
                                                <span x-text="expanded ? '▲' : '▼'">▼</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Lista de Lançamentos Individuais da Categoria -->
                                    <div x-show="expanded" x-cloak class="mt-3 pt-3 border-t border-slate-100 space-y-2">
                                        @forelse($txs as $tx)
                                            <div class="rounded-xl bg-slate-50 px-3 py-2 text-xs">
                                                <div x-show="editingTxId !== {{ $tx->id }}" class="flex items-center justify-between gap-2">
                                                    <div>
                                                        <span class="font-semibold text-slate-800">{{ $tx->description }}</span>
                                                        @if($tx->notes)
                                                            <span class="text-slate-500"> — {{ $tx->notes }}</span>
                                                        @endif
                                                        @if($tx->occurred_on)
                                                            <span class="text-slate-400 ms-1">({{ $tx->occurred_on->format('d/m') }})</span>
                                                        @endif
                                                    </div>

                                                    <div class="flex items-center gap-2 shrink-0">
                                                        <span class="font-bold text-slate-900">{{ Money::format($tx->amount_cents) }}</span>
                                                        <button type="button"
                                                                @click="editingTxId = {{ $tx->id }}"
                                                                class="text-indigo-600 hover:underline font-medium">
                                                            Editar
                                                        </button>
                                                        <form method="POST" action="{{ route('transactions.destroy', $tx->id) }}"
                                                              onsubmit="return confirm('Remover este lançamento?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-rose-600 hover:underline font-medium">
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
                                                               class="w-full rounded-lg border-slate-300 text-xs py-1.5" placeholder="Descrição">
                                                    </div>
                                                    <div class="sm:col-span-3">
                                                        <input type="text" name="amount" value="{{ Money::format($tx->amount_cents, false) }}" required
                                                               class="w-full rounded-lg border-slate-300 text-xs py-1.5" placeholder="Valor (R$)">
                                                    </div>
                                                    <div class="sm:col-span-3">
                                                        <input type="text" name="notes" value="{{ $tx->notes }}"
                                                               class="w-full rounded-lg border-slate-300 text-xs py-1.5" placeholder="Obs.">
                                                    </div>
                                                    <div class="sm:col-span-2 flex items-center gap-1 justify-end">
                                                        <button type="submit" class="px-2.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold">
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
                                                Nenhum lançamento nesta categoria em {{ $monthName }}/{{ $year }}.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-xs text-slate-400">Nenhuma categoria cadastrada neste bloco.</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Modal Rápido para Novo Lançamento -->
        <div x-show="showCreateModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4">
            <div @click.away="showCreateModal = false"
                 class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">➕ Novo Lançamento ({{ $monthName }}/{{ $year }})</h3>
                    <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <form method="POST" action="{{ route('transactions.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Categoria</label>
                        <select name="category_id" x-model="selectedCategoryId" required
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($categories as $catOption)
                                <option value="{{ $catOption->id }}">
                                    {{ $catOption->icon }} {{ $catOption->name }} ({{ $catOption->type->shortLabel() }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Descrição</label>
                        <input type="text" name="description" x-model="defaultDescription" required
                               placeholder="Ex: BK, Uber, Salário, CDB Nubank..."
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Valor (R$)</label>
                            <input type="text" name="amount" required
                                   placeholder="Ex: 45,90"
                                   class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Data (Opcional)</label>
                            <input type="date" name="occurred_on"
                                   value="{{ sprintf('%04d-%02d-01', $year, $month) }}"
                                   class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Observação (Opcional)</label>
                        <input type="text" name="notes"
                               placeholder="Ex: CDB 100% CDI, Lanche com amigos, SalvadorCard..."
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showCreateModal = false"
                                class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                            Salvar Lançamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
