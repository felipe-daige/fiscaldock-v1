<section id="login" class="bg-gray-100 min-h-[calc(100vh-80px)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="max-w-sm mx-auto">
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Acesso FiscalDock</p>
                            <h1 class="text-lg font-bold text-gray-900 uppercase tracking-wide mt-1">Entrar no painel</h1>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">
                            Seguro
                        </span>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    <div class="mb-5 text-center">
                        <img
                            src="{{ asset('binary_files/logo/logo-fiscaldock_whitebg-removebg.png') }}"
                            alt="FiscalDock"
                            class="h-12 mx-auto object-contain"
                        >
                        <p class="text-xs text-gray-500 mt-3">Informe seu e-mail corporativo e senha para acessar o ambiente autenticado.</p>
                    </div>

                    {{-- Erro de credencial (genérico, anti-enumeração) aparece neste alerta inline.
                         Erros de formato de campo aparecem abaixo de cada campo (.field-error). --}}
                    <div id="login-alert" class="mb-4 hidden bg-white rounded border border-gray-300 p-3 border-l-4 border-l-red-500 text-sm text-gray-700"></div>
                    @if ($errors->any())
                        <div class="mb-4 bg-white rounded border border-gray-300 p-3 border-l-4 border-l-red-500 text-sm text-gray-700">{{ $errors->first() }}</div>
                    @endif

                    <form id="login-form" class="space-y-4" method="POST" action="/login">
                        @csrf

                        <div>
                            <label for="email" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">E-mail</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="email"
                                oninvalid="this.setCustomValidity('Inclua um @ e insira um e-mail válido.')"
                                oninput="this.setCustomValidity('')"
                                class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="seu@empresa.com.br"
                            >
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-3 mb-1">
                                <label for="password" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Senha</label>
                                <a href="/esqueci-senha" data-link class="text-[10px] text-gray-500 hover:text-gray-800 hover:underline">
                                    Esqueceu sua senha?
                                </a>
                            </div>
                            <div class="relative">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    class="w-full border border-gray-300 rounded text-sm pl-3 pr-10 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                    style="padding-right:2.5rem"
                                    placeholder="Digite sua senha"
                                >
                                <button type="button" class="senha-toggle absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600" style="top:0;bottom:0" data-target="password" aria-label="Mostrar senha" tabindex="-1">
                                    <svg class="icon-eye w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="icon-eye-off w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.5 10.5 0 002.25 12s3.75 7.5 9.75 7.5c1.6 0 3.06-.38 4.35-1.01M9.88 5.09A10.6 10.6 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a17 17 0 01-2.83 3.74M9.9 9.9a3 3 0 104.2 4.2M3 3l18 18"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <label for="remember" class="inline-flex items-center gap-2 text-sm text-gray-600">
                                <input
                                    type="checkbox"
                                    id="remember"
                                    name="remember"
                                    class="h-4 w-4 border border-gray-300 rounded text-gray-800 focus:ring-1 focus:ring-gray-400"
                                >
                                <span>Lembrar-me</span>
                            </label>
                            <a href="/agendar" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">
                                Falar com especialista
                            </a>
                        </div>

                        <button
                            type="submit"
                            id="login-submit-btn"
                            class="w-full bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium px-4 py-2.5 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Entrar no painel
                        </button>

                        <div class="relative my-1">
                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center">
                                <span class="bg-white px-3 text-xs uppercase tracking-wider text-gray-500">novo no FiscalDock?</span>
                            </div>
                        </div>

                        <a
                            href="/criar-conta"
                            data-link
                            class="w-full inline-flex items-center justify-center gap-2 rounded border border-gray-300 bg-white text-gray-800 hover:bg-gray-50 hover:border-gray-400 text-sm font-medium px-4 py-2.5 transition-colors"
                        >
                            Criar conta grátis
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
