<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-200">
                    {{ session('status') }}
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

            <!-- Ações Rápidas: Adicionar Tipo em Categoria Existente OU Criar Nova Categoria -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Card 1: Adicionar Novo Tipo em Categoria Existente (ex: Gasolina em Despesas Fixas) -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 sm:p-6 flex flex-col justify-between">
                    <div class="mb-4">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">
                                Adicionar Tipo em Categoria Existente
                            </h2>
                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                Apenas no seu perfil
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Inclua novos tipos dentro de qualquer categoria atual (ex: adicionar <strong>Gasolina</strong> dentro de <strong>Despesas Fixas</strong>).
                        </p>
                    </div>

                    <form method="POST" action="{{ route('categories.store') }}"
                          x-data="{
                              selectedIndex: 0,
                              groups: {{ Js::from(collect($groupedCategories)->map(fn($g) => ['group_name' => $g['group_name'], 'type' => $g['type']->value, 'color' => $g['color'] ?? '#0d9488'])->values()) }}
                          }"
                          class="space-y-3.5">
                        @csrf
                        <input type="hidden" name="group_name" :value="groups[selectedIndex]?.group_name || 'Despesas Fixas'">
                        <input type="hidden" name="type" :value="groups[selectedIndex]?.type || 'fixed_expense'">
                        <input type="hidden" name="color" :value="groups[selectedIndex]?.color || '#0d9488'">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Categoria Destino</label>
                                <select x-model="selectedIndex" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    @foreach($groupedCategories as $idx => $group)
                                        <option value="{{ $idx }}">{{ $group['group_name'] }} ({{ $group['type']->shortLabel() }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Nome do Novo Tipo</label>
                                <input type="text" name="name" required placeholder="Ex: Gasolina, Seguro, Academia..."
                                       class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold transition shadow-xs">
                            + Adicionar Tipo na Categoria
                        </button>
                    </form>
                </div>

                <!-- Card 2: Criar Nova Categoria Principal & Seus Tipos -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 sm:p-6 flex flex-col justify-between">
                    <div class="mb-4">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">
                                Criar Nova Categoria Principal
                            </h2>
                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                                Personalizada
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Crie categorias próprias além das 4 padrão (ex: <strong>Veículo</strong>, <strong>Moradia</strong>, <strong>Educação</strong>) e defina seus tipos iniciais.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('categories.store') }}" class="space-y-3.5">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Cor</label>
                                <input type="color" name="color" value="#0d9488"
                                       class="w-full h-10 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-1 cursor-pointer">
                            </div>
                            <div class="sm:col-span-5">
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Nome da Categoria</label>
                                <input type="text" name="group_name" required placeholder="Ex: Veículo, Moradia..."
                                       class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div class="sm:col-span-5">
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Natureza no Saldo</label>
                                <select name="type" required class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    @foreach($categoryTypes as $typeCase)
                                        <option value="{{ $typeCase->value }}">{{ $typeCase->natureLabel() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1.5">Tipos Iniciais (separados por vírgula)</label>
                            <input type="text" name="items" required placeholder="Ex: Gasolina, IPVA, Manutenção"
                                   class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-900 dark:bg-teal-600 hover:bg-slate-800 dark:hover:bg-teal-500 text-white text-sm font-semibold transition shadow-xs">
                            Criar Nova Categoria
                        </button>
                    </form>
                </div>
            </div>

            <!-- Listagem de Categorias Principais e Seus Tipos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($groupedCategories as $groupKey => $group)
                    @php
                        $groupName = $group['group_name'];
                        $groupType = $group['type'];
                        $groupColor = $group['color'] ?? '#0d9488';
                        $items = $group['items'];
                    @endphp
                    <div x-data="{ editingGroup: false }" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden flex flex-col">
                        <!-- Cabeçalho da Categoria Principal -->
                        <div class="px-5 py-4 bg-slate-900 dark:bg-slate-800/90 text-white">
                            <div x-show="!editingGroup" class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $groupColor }}"></span>
                                    <span class="font-bold text-sm tracking-wide uppercase truncate">{{ $groupName }}</span>
                                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-white/10 text-slate-200 font-medium shrink-0">
                                        {{ $groupType->shortLabel() }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 shrink-0 text-xs">
                                    <span class="text-slate-300">{{ $items->count() }} {{ $items->count() === 1 ? 'tipo' : 'tipos' }}</span>
                                    <button type="button" @click="editingGroup = true" class="text-emerald-300 hover:text-white font-semibold">
                                        Editar
                                    </button>
                                </div>
                            </div>

                            <!-- Edição da Categoria Principal -->
                            <form x-show="editingGroup" x-cloak method="POST" action="{{ route('categories.groups.update') }}" class="space-y-2.5">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="old_group_name" value="{{ $groupName }}">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
                                    <div class="sm:col-span-2">
                                        <input type="color" name="color" value="{{ $groupColor }}" class="w-full h-8 rounded-lg border border-slate-700 bg-slate-800 p-0.5 cursor-pointer">
                                    </div>
                                    <div class="sm:col-span-5">
                                        <input type="text" name="group_name" value="{{ $groupName }}" required
                                               class="w-full rounded-lg border-slate-700 bg-slate-800 text-white text-xs py-1.5">
                                    </div>
                                    <div class="sm:col-span-5">
                                        <select name="type" class="w-full rounded-lg border-slate-700 bg-slate-800 text-white text-xs py-1.5">
                                            @foreach($categoryTypes as $typeOpt)
                                                <option value="{{ $typeOpt->value }}" @selected($typeOpt === $groupType)>
                                                    {{ $typeOpt->natureLabel() }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click="editingGroup = false" class="px-2.5 py-1 text-xs text-slate-300 hover:text-white">
                                        Cancelar
                                    </button>
                                    <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold">
                                        Salvar Categoria
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Lista de Tipos da Categoria -->
                        <div class="divide-y divide-slate-100 dark:divide-slate-800 flex-1">
                            @forelse($items as $cat)
                                <div x-data="{ editing: false }" class="px-5 py-3.5 hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                    <div x-show="!editing" class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $cat->color ?? $groupColor }}"></span>
                                            <span class="text-sm font-medium truncate {{ $cat->is_active ? 'text-slate-800 dark:text-slate-100' : 'text-slate-400 line-through' }}">
                                                {{ $cat->name }}
                                            </span>
                                            @if(!$cat->is_active)
                                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">Arquivado</span>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-3 text-xs shrink-0">
                                            <button type="button" @click="editing = true" class="text-emerald-600 dark:text-emerald-400 font-semibold hover:underline">
                                                Renomear
                                            </button>
                                            <form method="POST" action="{{ route('categories.destroy', $cat->id) }}"
                                                  onsubmit="return confirm('Remover ou arquivar este tipo?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 dark:text-rose-400 font-semibold hover:underline">
                                                    Remover
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Formulário de Edição do Tipo -->
                                    <form x-show="editing" x-cloak method="POST" action="{{ route('categories.update', $cat->id) }}"
                                          class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="group_name" value="{{ $groupName }}">
                                        <input type="hidden" name="type" value="{{ $cat->type->value }}">
                                        <input type="color" name="color" value="{{ $cat->color ?? $groupColor }}" class="w-8 h-8 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 p-0.5 cursor-pointer">
                                        <input type="text" name="name" value="{{ $cat->name }}" required class="flex-1 min-w-[140px] rounded-lg border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs py-1.5">
                                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold">Salvar</button>
                                        <button type="button" @click="editing = false" class="text-xs text-slate-500 px-1.5">✕</button>
                                    </form>
                                </div>
                            @empty
                                <div class="p-5 text-xs text-slate-400">Nenhum tipo cadastrado nesta categoria.</div>
                            @endforelse
                        </div>

                        <!-- Adicionar Novo Tipo diretamente no Card desta Categoria -->
                        <div class="p-4 bg-slate-50/70 dark:bg-slate-950/40 border-t border-slate-100 dark:border-slate-800">
                            <form method="POST" action="{{ route('categories.store') }}" class="flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="group_name" value="{{ $groupName }}">
                                <input type="hidden" name="type" value="{{ $groupType->value }}">
                                <input type="hidden" name="color" value="{{ $groupColor }}">
                                <input type="text" name="name" required
                                       placeholder="Novo tipo em {{ $groupName }} (ex: Gasolina)..."
                                       class="flex-1 rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500">
                                <button type="submit"
                                        class="px-3.5 py-2 rounded-xl bg-slate-900 dark:bg-emerald-600 hover:bg-slate-800 dark:hover:bg-emerald-500 text-white text-xs font-semibold transition shrink-0">
                                    + Adicionar
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Configuração da Regra 50-30-20 -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 sm:p-6">
                <div class="mb-4">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Configurar Regra Orçamentária (50-30-20)</h2>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Defina a distribuição ideal da sua receita mensal entre Necessidades, Desejos e Investimentos. A soma deve ser 100%.
                    </p>
                </div>

                <form method="POST" action="{{ route('categories.budget-rule.update') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Necessidades / Fixas (%)</label>
                        <input type="number" name="rule_needs_pct" min="0" max="100" value="{{ $user->rule_needs_pct }}" required
                               class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Desejos / Variáveis (%)</label>
                        <input type="number" name="rule_wants_pct" min="0" max="100" value="{{ $user->rule_wants_pct }}" required
                               class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Investimentos (%)</label>
                        <input type="number" name="rule_investments_pct" min="0" max="100" value="{{ $user->rule_investments_pct }}" required
                               class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div>
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold transition">
                            Salvar Percentuais
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
