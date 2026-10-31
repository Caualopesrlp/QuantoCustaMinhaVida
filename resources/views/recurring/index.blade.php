@php
    use App\Support\Money;
@endphp

<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800">
                    {{ session('status') }}
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

            <!-- Cabeçalho Explicativo -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">📌 Modelo Fixo Mensal (Substituto da Aba X26)</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        💡 Cadastre seus valores fixos uma única vez (Salário, Celular, Corte de Cabelo, Transporte Público).
                        Ao iniciar um novo mês no Controle Mensal, clique em <strong>"Aplicar Modelo Fixo (X26)"</strong> para gerar todos de uma vez.
                    </p>
                </div>
                <div class="flex items-center gap-4 shrink-0 text-right">
                    <div>
                        <div class="text-xs text-slate-500">Receita Base</div>
                        <div class="text-base font-bold text-emerald-600">{{ Money::format($incomeTotalCents) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Fixas Base</div>
                        <div class="text-base font-bold text-blue-600">{{ Money::format($fixedTotalCents) }}</div>
                    </div>
                </div>
            </div>

            <!-- Formulário para Adicionar Item ao Modelo Fixo -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">➕ Adicionar Item ao Modelo Fixo</h2>
                <form method="POST" action="{{ route('recurring-templates.store') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    @csrf
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Categoria</label>
                        <select name="category_id" required class="w-full rounded-xl border-slate-300 text-sm">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">
                                    {{ $cat->icon }} {{ $cat->name }} ({{ $cat->type->shortLabel() }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Descrição</label>
                        <input type="text" name="description" required placeholder="Ex: Salário, SalvadorCard..."
                               class="w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Valor Base (R$)</label>
                        <input type="text" name="amount" required placeholder="180,00"
                               class="w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Obs.</label>
                        <input type="text" name="notes" placeholder="Ex: 5G, Barbearia"
                               class="w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="w-full py-2 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold">
                            Adicionar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de Itens do Modelo Fixo -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="px-5 py-4 bg-slate-900 text-white font-bold text-sm uppercase tracking-wider">
                    Itens Recorrentes Cadastrados ({{ $templates->count() }})
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($templates as $tpl)
                        <div x-data="{ editing: false }" class="p-4 hover:bg-slate-50 transition">
                            <div x-show="!editing" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl">{{ $tpl->category?->icon ?? '📌' }}</span>
                                    <div>
                                        <div class="font-semibold text-slate-900 text-sm">
                                            {{ $tpl->description }}
                                            <span class="text-xs font-normal text-slate-500">({{ $tpl->category?->name }})</span>
                                        </div>
                                        @if($tpl->notes)
                                            <div class="text-xs text-slate-500">Obs: {{ $tpl->notes }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <span class="font-bold text-base text-slate-900">{{ Money::format($tpl->amount_cents) }}</span>
                                    <button type="button" @click="editing = true" class="text-xs font-semibold text-indigo-600 hover:underline">
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('recurring-templates.destroy', $tpl->id) }}"
                                          onsubmit="return confirm('Remover este item do Modelo Fixo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-rose-600 hover:underline">
                                            Remover
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Edição Inline -->
                            <form x-show="editing" x-cloak method="POST" action="{{ route('recurring-templates.update', $tpl->id) }}"
                                  class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                @csrf
                                @method('PUT')
                                <div class="sm:col-span-3">
                                    <select name="category_id" required class="w-full rounded-lg border-slate-300 text-xs py-1.5">
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" @selected($cat->id === $tpl->category_id)>
                                                {{ $cat->icon }} {{ $cat->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-3">
                                    <input type="text" name="description" value="{{ $tpl->description }}" required
                                           class="w-full rounded-lg border-slate-300 text-xs py-1.5">
                                </div>
                                <div class="sm:col-span-2">
                                    <input type="text" name="amount" value="{{ Money::format($tpl->amount_cents, false) }}" required
                                           class="w-full rounded-lg border-slate-300 text-xs py-1.5">
                                </div>
                                <div class="sm:col-span-2">
                                    <input type="text" name="notes" value="{{ $tpl->notes }}"
                                           class="w-full rounded-lg border-slate-300 text-xs py-1.5" placeholder="Obs.">
                                </div>
                                <div class="sm:col-span-2 flex items-center justify-end gap-1">
                                    <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold">
                                        Salvar
                                    </button>
                                    <button type="button" @click="editing = false" class="px-2 py-1.5 text-slate-500 text-xs">
                                        ✕
                                    </button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-slate-500">
                            Nenhum item cadastrado no Modelo Fixo (X26) ainda. Adicione acima seu Salário e suas Despesas Fixas!
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
