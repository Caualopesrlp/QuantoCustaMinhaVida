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

            <!-- 📐 Configuração da Regra 50-30-20 -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
                <div class="mb-4">
                    <h1 class="text-xl font-bold text-slate-900">📐 Configurar Regra Orçamentária (50-30-20)</h1>
                    <p class="text-xs text-slate-500 mt-1">
                        💡 Dica da planilha: Se o salário for mais apertado no início, comece com 10% de investimento (ex: 60% / 30% / 10%) — o hábito vale mais que o valor! A soma deve ser 100%.
                    </p>
                </div>

                <form method="POST" action="{{ route('categories.budget-rule.update') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">🟢 Necessidades / Fixas (%)</label>
                        <input type="number" name="rule_needs_pct" min="0" max="100" value="{{ $user->rule_needs_pct }}" required
                               class="w-full rounded-xl border-slate-300 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">🟡 Desejos / Variáveis (%)</label>
                        <input type="number" name="rule_wants_pct" min="0" max="100" value="{{ $user->rule_wants_pct }}" required
                               class="w-full rounded-xl border-slate-300 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">🔵 Investimentos (%)</label>
                        <input type="number" name="rule_investments_pct" min="0" max="100" value="{{ $user->rule_investments_pct }}" required
                               class="w-full rounded-xl border-slate-300 text-sm">
                    </div>

                    <div>
                        <button type="submit" class="w-full py-2 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">
                            Salvar Percentuais
                        </button>
                    </div>
                </form>
            </div>

            <!-- ➕ Criar Nova Categoria -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-3">➕ Nova Categoria Personalizada</h2>
                <form method="POST" action="{{ route('categories.store') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    @csrf
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Emoji/Ícone</label>
                        <input type="text" name="icon" value="📌" maxlength="10"
                               class="w-full rounded-xl border-slate-300 text-sm text-center">
                    </div>
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nome da Categoria</label>
                        <input type="text" name="name" required placeholder="Ex: Faculdade, Academia, Pet..."
                               class="w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Bloco / Classificação</label>
                        <select name="type" required class="w-full rounded-xl border-slate-300 text-sm">
                            @foreach($categoryTypes as $typeCase)
                                <option value="{{ $typeCase->value }}">{{ $typeCase->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="w-full py-2 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold">
                            Criar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Listagem de Categorias por Bloco -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($categoryTypes as $typeCase)
                    @php
                        $cats = $categoriesByType->get($typeCase->value, collect());
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                        <div class="px-5 py-3.5 bg-slate-900 text-white font-bold text-sm">
                            {{ $typeCase->label() }}
                        </div>
                        <div class="divide-y divide-slate-100">
                            @forelse($cats as $cat)
                                <div x-data="{ editing: false }" class="p-3.5 hover:bg-slate-50 transition">
                                    <div x-show="!editing" class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">{{ $cat->icon }}</span>
                                            <span class="text-sm font-medium {{ $cat->is_active ? 'text-slate-900' : 'text-slate-400 line-through' }}">
                                                {{ $cat->name }}
                                            </span>
                                            @if(!$cat->is_active)
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">Arquivada</span>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-3 text-xs">
                                            <button type="button" @click="editing = true" class="text-indigo-600 font-semibold hover:underline">
                                                Editar
                                            </button>
                                            <form method="POST" action="{{ route('categories.destroy', $cat->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 font-semibold hover:underline">
                                                    Remover
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <form x-show="editing" x-cloak method="POST" action="{{ route('categories.update', $cat->id) }}"
                                          class="flex items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="type" value="{{ $cat->type->value }}">
                                        <input type="text" name="icon" value="{{ $cat->icon }}" class="w-14 rounded-lg border-slate-300 text-xs py-1 text-center">
                                        <input type="text" name="name" value="{{ $cat->name }}" required class="flex-1 rounded-lg border-slate-300 text-xs py-1">
                                        <button type="submit" class="px-2.5 py-1 bg-slate-900 text-white rounded-lg text-xs font-semibold">Salvar</button>
                                        <button type="button" @click="editing = false" class="text-xs text-slate-500">✕</button>
                                    </form>
                                </div>
                            @empty
                                <div class="p-4 text-xs text-slate-400">Nenhuma categoria neste bloco.</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
