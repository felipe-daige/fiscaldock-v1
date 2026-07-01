<section id="redefinir-senha" class="bg-gray-100 min-h-[calc(100vh-80px)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="max-w-sm mx-auto">
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Acesso FiscalDock</p>
                            <h1 class="text-lg font-bold text-gray-900 uppercase tracking-wide mt-1">Redefinir senha</h1>
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
                        <p class="text-xs text-gray-500 mt-3">Escolha sua nova senha.</p>
                    </div>

                    <div id="redefinir-senha-alert" class="mb-4 hidden bg-white rounded border border-gray-300 p-3 border-l-4 border-l-red-500 text-sm text-gray-700"></div>
                    @if ($errors->any())
                        <div class="mb-4 bg-white rounded border border-gray-300 p-3 border-l-4 border-l-red-500 text-sm text-gray-700">{{ $errors->first() }}</div>
                    @endif

                    <form id="redefinir-senha-form" class="space-y-4" method="POST" action="/redefinir-senha">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token ?? '' }}">

                        <div>
                            <label for="email" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">E-mail</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ $email ?? old('email') }}"
                                required
                                autocomplete="email"
                                class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                            >
                        </div>

                        <div>
                            <label for="password" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Nova senha</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                autocomplete="new-password"
                                class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                                placeholder="Mínimo de 8 caracteres, com letra e número"
                            >
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Confirmar nova senha</label>
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                                class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                            >
                        </div>

                        <button
                            type="submit"
                            id="redefinir-senha-submit-btn"
                            class="w-full bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium px-4 py-2.5 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Redefinir senha
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
