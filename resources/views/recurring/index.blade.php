@php
    use App\Support\Money;

    $categoriesByGroup = $categories->groupBy(fn ($cat) => $cat->resolvedGroupName());
@endphp

<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-300">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 px-4 py-3 text-sm text-rose-800 dark:text-rose-300">
                    <ul class="list-disc ms-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Cabeçalho Explicativo -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Molde Mensal</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        Cadastre seus valores recorrentes e receitas base uma única vez (Salário, Aluguel, Internet, Transporte).
                        Ao abrir qualquer mês no <strong>Controle Mensal</strong>, o QuantoCustaMinhaVida garante automaticamente que todos os itens ativos deste molde estejam lançados no mês.
                    </p>
                </div>
                <div class="flex items-center gap-6 shrink-0 sm:text-right border-t sm:border-t-0 border-slate-100 dark:border-slate-800 pt-3 sm:pt-0">
                    <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Receita Base</div>
                        <div class="text-base font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ Money::format($incomeTotalCents) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 dark:text-slate-400">Fixas Base</div>
                        <div class="text-base font-bold text-sky-600 dark:text-sky-400 mt-0.5">{{ Money::format($fixedTotalCents) }}</div>
                    </div>
                </div>
            </div>

            <!-- Formulário para Adicionar Item ao Molde Mensal -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 sm:p-6">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4">Adicionar Item ao Molde Mensal</h2>
                <form method="POST" action="{{ route('recurring-templates.store') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3.5 items-end">
                    @csrf
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">Categoria / Tipo</label>
                        <select name="category_id" required
                                class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            @foreach($categoriesByGroup as $groupName => $groupItems)
                                <optgroup label="{{ $groupName }}">
                                    @foreach($groupItems as $cat)
                                        <option value="{{ $cat->id }}">
                                            {{ $cat->name }} ({{ $cat->type->shortLabel() }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">Descrição</label>
                        <input type="text" name="description" required placeholder="Ex: Salário, Aluguel..."
                               class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">Valor Base (R$)</label>
                        <input type="text" name="amount" required placeholder="180,00"
                               class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">Observação</label>
                        <input type="text" name="notes" placeholder="Opcional"
                               class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold transition">
                            Adicionar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de Itens do Molde Mensal -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="px-5 py-4 bg-slate-900 dark:bg-slate-800/90 text-white font-bold text-sm uppercase tracking-wider">
                    Itens Recorrentes Cadastrados ({{ $templates->count() }})
                </div>

                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($templates as $tpl)
                        <div x-data="{ editing: false }" class="p-4 sm:px-5 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <div x-show="!editing" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                                <div class="flex items-start sm:items-center gap-3">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0 mt-1.5 sm:mt-0" style="background-color: {{ $tpl->category?->color ?? '#0d9488' }}"></span>
                                    <div>
                                        <div class="font-semibold text-slate-900 dark:text-slate-100 text-sm flex flex-wrap items-center gap-2">
                                            <span>{{ $tpl->description }}</span>
                                            <span class="text-xs font-normal text-slate-500 dark:text-slate-400">
                                                ({{ $tpl->category?->resolvedGroupName() }} &bull; {{ $tpl->category?->name }})
                                            </span>
                                        </div>
                                        @if($tpl->notes)
                                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Obs: {{ $tpl->notes }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-4 shrink-0">
                                    <span class="font-bold text-base text-slate-900 dark:text-slate-100">{{ Money::format($tpl->amount_cents) }}</span>
                                    <button type="button" @click="editing = true" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('recurring-templates.destroy', $tpl->id) }}"
                                          onsubmit="return confirm('Remover este item do Molde Mensal?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline">
                                            Remover
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Edição Inline -->
                            <form x-show="editing" x-cloak method="POST" action="{{ route('recurring-templates.update', $tpl->id) }}"
                                  class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
                                @csrf
                                @method('PUT')
                                <div class="sm:col-span-3">
                                    <select name="category_id" required class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs py-1.5">
                                        @foreach($categoriesByGroup as $groupName => $groupItems)
                                            <optgroup label="{{ $groupName }}">
                                                @foreach($groupItems as $cat)
                                                    <option value="{{ $cat->id }}" @selected($cat->id === $tpl->category_id)>
                                                        {{ $cat->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-3">
                                    <input type="text" name="description" value="{{ $tpl->description }}" required
                                           class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs py-1.5">
                                </div>
                                <div class="sm:col-span-2">
                                    <input type="text" name="amount" value="{{ Money::format($tpl->amount_cents, false) }}" required
                                           class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs py-1.5">
                                </div>
                                <div class="sm:col-span-2">
                                    <input type="text" name="notes" value="{{ $tpl->notes }}"
                                           class="w-full rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-xs py-1.5" placeholder="Obs.">
                                </div>
                                <div class="sm:col-span-2 flex items-center justify-end gap-1.5">
                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold">
                                        Salvar
                                    </button>
                                    <button type="button" @click="editing = false" class="px-2 py-1.5 text-slate-500 text-xs">
                                        ✕
                                    </button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">
                            Nenhum item cadastrado no Molde Mensal ainda. Adicione acima seu Salário e suas Despesas Fixas.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
