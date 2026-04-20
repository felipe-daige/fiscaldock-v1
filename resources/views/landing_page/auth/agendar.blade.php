@push('structured-data')
    @include('landing_page.partials.breadcrumb-schema', [
        'trail' => [
            ['name' => 'Início', 'url' => url('/')],
            ['name' => 'Contato Comercial', 'url' => url('/agendar')],
        ],
    ])
@endpush

<section id="contato-comercial" class="bg-gray-100 min-h-[calc(100vh-80px)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="max-w-4xl mx-auto">
            @if(session('contact_notice'))
                <div class="mb-4 bg-white rounded border border-gray-300 p-4 border-l-4 border-l-amber-500">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Atualização do fluxo</p>
                    <p class="mt-2 text-sm text-gray-700">{{ session('contact_notice') }}</p>
                </div>
            @endif

            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Contato FiscalDock</p>
                            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide mt-1">Fale com nosso time</h1>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">
                            Comercial
                        </span>
                    </div>
                </div>

                <div class="p-4 sm:p-6">
                    <div class="mb-6">
                        <p class="text-sm text-gray-700">
                            Se você quer tirar dúvidas, falar com o comercial ou combinar uma conversa, use nossos canais diretos.
                            Esta página não agenda horário automaticamente e não cria conta.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <a href="{{ $whatsAppUrl ?? 'https://wa.me/5567999844366' }}"
                           target="_blank"
                           rel="noopener"
                           class="bg-white rounded border border-gray-300 p-4 hover:bg-gray-50 transition-colors">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">WhatsApp</p>
                            <p class="text-sm font-semibold text-gray-900">(67) 99984-4366</p>
                            <p class="text-xs text-gray-500 mt-2">Canal principal para contato comercial e dúvidas rápidas.</p>
                        </a>

                        <a href="mailto:contato@fiscaldock.com.br"
                           class="bg-white rounded border border-gray-300 p-4 hover:bg-gray-50 transition-colors">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">E-mail</p>
                            <p class="text-sm font-semibold text-gray-900">contato@fiscaldock.com.br</p>
                            <p class="text-xs text-gray-500 mt-2">Envie detalhes da sua operação e retornamos pelo canal informado.</p>
                        </a>
                    </div>

                    <div class="bg-white rounded border border-gray-300 p-4 mb-6 border-l-4 border-l-blue-500">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Quando falar com a FiscalDock</p>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li>Entender quantos créditos e qual faixa combinam com o seu volume e rotina fiscal.</li>
                            <li>Tirar dúvidas sobre SPED, monitoramento, consultas e compliance.</li>
                            <li>Solicitar uma conversa comercial por WhatsApp.</li>
                        </ul>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('signup') }}"
                           class="btn-cta justify-center text-center">
                            Criar conta grátis
                        </a>
                        <a href="{{ $whatsAppUrl ?? 'https://wa.me/5567999844366' }}"
                           target="_blank"
                           rel="noopener"
                           class="bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium px-4 py-3 text-center">
                            Falar no WhatsApp
                        </a>
                        <a href="{{ route('precos') }}"
                           class="bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium px-4 py-3 text-center">
                            Ver créditos e faixas
                        </a>
                        <a href="{{ route('duvidas') }}"
                           class="bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium px-4 py-3 text-center">
                            Ler dúvidas frequentes
                        </a>
                    </div>

                    <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t border-gray-200">
                        <p class="text-xs text-gray-500">
                            Ao entrar em contato, você concorda com nossos
                            <a href="{{ route('termos') }}" class="hover:underline" style="color: #1e4fa0">Termos de Uso</a>
                            e
                            <a href="{{ route('privacidade') }}" class="hover:underline" style="color: #1e4fa0">Política de Privacidade</a>.
                        </p>
                        <a href="{{ route('login') }}" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">
                            Já tem conta? Entrar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
