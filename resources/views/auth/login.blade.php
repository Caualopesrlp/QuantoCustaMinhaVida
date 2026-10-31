<x-guest-layout>
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-slate-200/80 dark:border-slate-800 overflow-hidden transition-colors duration-200">
        <!-- Cabeçalho de Branding -->
        <div class="px-6 sm:px-8 pt-8 pb-6 bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 text-white text-center relative">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg mb-3 font-extrabold text-base tracking-tight">
                QC
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight">QuantoCustaMinhaVida</h1>
            <p class="text-xs text-emerald-200/80 mt-1.5 leading-relaxed">
                Controle financeiro pessoal, metas 50-30-20 e acompanhamento de investimentos
            </p>
        </div>

        <div class="p-6 sm:p-8">
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">Acesse sua conta</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                    Informe seu e-mail e senha para abrir seu painel financeiro.
                </p>
            </div>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- Email Address -->
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                        E-mail
                    </label>
                    <input id="email"
                           type="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           autofocus
                           autocomplete="username"
                           placeholder="voce@exemplo.com"
                           class="block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white text-sm py-2.5 px-3.5 focus:border-emerald-500 focus:ring-emerald-500 transition" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
                </div>

                <!-- Password -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                            Senha
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline">
                                Esqueceu a senha?
                            </a>
                        @endif
                    </div>

                    <input id="password"
                           type="password"
                           name="password"
                           required
                           autocomplete="current-password"
                           placeholder="••••••••"
                           class="block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white text-sm py-2.5 px-3.5 focus:border-emerald-500 focus:ring-emerald-500 transition" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between pt-1">
                    <label for="remember_me" class="inline-flex items-center cursor-pointer">
                        <input id="remember_me"
                               type="checkbox"
                               name="remember"
                               class="rounded border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-emerald-600 shadow-xs focus:ring-emerald-500">
                        <span class="ms-2 text-xs font-medium text-slate-600 dark:text-slate-300">Manter conectado</span>
                    </label>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">
                            Criar nova conta
                        </a>
                    @endif
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 text-white font-semibold text-sm shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-2">
                        <span>Entrar no Painel</span>
                        <span>&rarr;</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="px-6 sm:px-8 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200/80 dark:border-slate-800 text-center">
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                QuantoCustaMinhaVida &bull; Planejamento Financeiro Pessoal
            </p>
        </div>
    </div>
</x-guest-layout>
