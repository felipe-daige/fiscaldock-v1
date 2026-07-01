<section id="esqueci-senha" class="bg-gray-100 min-h-[calc(100vh-80px)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="max-w-sm mx-auto">
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Acesso FiscalDock</p>
                            <h1 class="text-lg font-bold text-gray-900 uppercase tracking-wide mt-1">Esqueci minha senha</h1>
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
                        <p class="text-xs text-gray-500 mt-3">Informe o e-mail da sua conta e enviaremos um link para redefinir sua senha.</p>
                    </div>

                    <div id="esqueci-senha-alert" class="mb-4 hidden bg-white rounded border border-gray-300 p-3 border-l-4 border-l-red-500 text-sm text-gray-700"></div>
                    <div id="esqueci-senha-status" class="mb-4 hidden bg-white rounded border border-gray-300 p-3 border-l-4 text-sm text-gray-700" style="border-left-color: #047857"></div>
                    @if (session('status'))
                        <div class="mb-4 bg-white rounded border border-gray-300 p-3 border-l-4 text-sm text-gray-700" style="border-left-color: #047857">{{ session('status') }}</div>
                    @endif

                    <form id="esqueci-senha-form" class="space-y-4" method="POST" action="/esqueci-senha">
                        @csrf

                        <div>
                            <label for="email" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">E-mail</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                required
                                autocomplete="email"
                                class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="seu@empresa.com.br"
                            >
                        </div>

                        <button
                            type="submit"
                            id="esqueci-senha-submit-btn"
                            class="w-full bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium px-4 py-2.5 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Enviar link de redefinição
                        </button>

                        <a href="/login" data-link class="block text-center text-xs text-gray-600 hover:text-gray-900 hover:underline">
                            Voltar para o login
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
