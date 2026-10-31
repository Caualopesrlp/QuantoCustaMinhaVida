<x-app-layout>
    <div class="py-6 sm:py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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

            <!-- Cabeçalho do Painel Admin -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/70 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs font-semibold mb-2">
                        Acesso Restrito — Administrador
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Painel Admin &amp; Gestão de Usuários</h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Cadastre novos usuários no QuantoCustaMinhaVida e redefina senhas esquecidas para a senha temporária padrão (<code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 font-mono text-emerald-600 dark:text-emerald-400 font-semibold">{{ $defaultPassword }}</code>).
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0 sm:text-right">
                    <div class="px-4 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700">
                        <div class="text-xs text-slate-500 dark:text-slate-400">Total de Usuários</div>
                        <div class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $users->count() }}</div>
                    </div>
                    <div class="px-4 py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800">
                        <div class="text-xs text-emerald-600 dark:text-emerald-300">Administradores</div>
                        <div class="text-lg font-extrabold text-emerald-700 dark:text-emerald-300">{{ $users->filter(fn($u) => $u->isAdmin())->count() }}</div>
                    </div>
                </div>
            </div>

            <!-- Formulário de Cadastro de Novo Usuário -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs p-5 sm:p-6">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4">Cadastrar Novo Usuário</h2>

                <form method="POST" action="{{ route('admin.users.store') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3.5 items-end">
                    @csrf
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">Nome Completo</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ex: Maria Souza"
                               class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">E-mail</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="usuario@exemplo.com"
                               class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">Perfil (Role)</label>
                        <select name="role" class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="user" @selected(old('role') === 'user')>Usuário Padrão</option>
                            <option value="admin" @selected(old('role') === 'admin')>Administrador</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1.5">
                            Senha Inicial <span class="text-[10px] font-normal text-slate-400">(opcional)</span>
                        </label>
                        <input type="text" name="password" placeholder="Padrão: {{ $defaultPassword }}"
                               class="w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="sm:col-span-2">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold shadow-xs transition">
                            Criar Usuário
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de Usuários Cadastrados -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="px-5 py-4 bg-slate-900 dark:bg-slate-800/90 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <span class="font-bold text-sm uppercase tracking-wider">Usuários Cadastrados ({{ $users->count() }})</span>
                    <span class="text-xs text-slate-300">Senha padrão de reset: <code class="font-mono text-emerald-300">{{ $defaultPassword }}</code></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800 text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs font-semibold uppercase text-slate-600 dark:text-slate-400">
                            <tr>
                                <th class="px-5 py-3.5 text-left">ID</th>
                                <th class="px-5 py-3.5 text-left">Nome</th>
                                <th class="px-5 py-3.5 text-left">E-mail</th>
                                <th class="px-5 py-3.5 text-center">Perfil</th>
                                <th class="px-5 py-3.5 text-center">Criado em</th>
                                <th class="px-5 py-3.5 text-right">Ações de Segurança</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($users as $u)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-5 py-3.5 font-mono text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">#{{ $u->id }}</td>
                                    <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                        {{ $u->name }}
                                        @if ($u->id === auth()->id())
                                            <span class="ms-1.5 text-[10px] px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-semibold">Você</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600 dark:text-slate-300 whitespace-nowrap">{{ $u->email }}</td>
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        @if ($u->isAdmin())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                Admin
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                Usuário
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        {{ $u->created_at?->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                        <form method="POST" action="{{ route('admin.users.reset-password', $u->id) }}"
                                              onsubmit="return confirm('Redefinir a senha de {{ addslashes($u->name) }} ({{ $u->email }}) para a senha padrão {{ $defaultPassword }}?')"
                                              class="inline-block">
                                            @csrf
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 text-xs font-semibold transition">
                                                Resetar Senha ({{ $defaultPassword }})
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
