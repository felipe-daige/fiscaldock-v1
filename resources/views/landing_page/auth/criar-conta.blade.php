@push('structured-data')
    @include('landing_page.partials.breadcrumb-schema', [
        'trail' => [
            ['name' => 'Início', 'url' => url('/')],
            ['name' => 'Criar Conta Grátis', 'url' => url('/criar-conta')],
        ],
    ])
@endpush

<section id="criar-conta" class="bg-gray-100 min-h-[calc(100vh-80px)]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-7">
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Trial FiscalDock</p>
                                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide mt-1">Criar conta grátis</h1>
                                <p class="text-xs text-gray-500 mt-1">Receba 100 créditos para usar em até 30 dias.</p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">
                                100 créditos
                            </span>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5">
                        @if ($errors->any())
                            <div class="mb-4 bg-white rounded border border-gray-300 p-4 border-l-4 border-l-red-500">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Falha no cadastro</p>
                                <ul class="mt-2 space-y-1 text-sm text-gray-700">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form id="signup-form" class="space-y-5" method="POST" action="{{ route('signup.post') }}">
                            @csrf

                            <div>
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-3">Dados do usuário</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="nome" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Nome</label>
                                        <input type="text" id="nome" name="nome" value="{{ old('nome') }}" required class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400" placeholder="João">
                                    </div>
                                    <div>
                                        <label for="sobrenome" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Sobrenome</label>
                                        <input type="text" id="sobrenome" name="sobrenome" value="{{ old('sobrenome') }}" required class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400" placeholder="Silva">
                                    </div>
                                    <div>
                                        <label for="signup-email" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">E-mail corporativo</label>
                                        <input type="email" id="signup-email" name="email" value="{{ old('email') }}" required class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400" placeholder="seu@empresa.com.br">
                                    </div>
                                    <div>
                                        <label for="telefone" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Telefone</label>
                                        <input type="tel" id="telefone" name="telefone" value="{{ old('telefone') }}" required class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400" placeholder="(67) 99984-4366">
                                    </div>
                                    <div>
                                        <label for="senha" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Senha</label>
                                        <input type="password" id="senha" name="senha" required minlength="8" class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400" placeholder="Mínimo 8 caracteres">
                                    </div>
                                    <div>
                                        <label for="senha_confirmation" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Confirmar senha</label>
                                        <input type="password" id="senha_confirmation" name="senha_confirmation" required minlength="8" class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400" placeholder="Repita a senha">
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-5">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-3">Dados da empresa</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="empresa" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Empresa</label>
                                        <input type="text" id="empresa" name="empresa" value="{{ old('empresa') }}" required class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400" placeholder="Nome da empresa">
                                    </div>
                                    <div>
                                        <label for="cargo" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Cargo</label>
                                        <input type="text" id="cargo" name="cargo" value="{{ old('cargo') }}" required class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400" placeholder="Ex: Contador, Financeiro, Sócio">
                                    </div>
                                    <div>
                                        <label for="documento" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CPF ou CNPJ</label>
                                        <input type="text" id="documento" name="documento" value="{{ old('documento') }}" required class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400" placeholder="000.000.000-00 ou 00.000.000/0000-00">
                                    </div>
                                    <div>
                                        <label for="faturamento" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Faturamento anual</label>
                                        <select id="faturamento" name="faturamento" required class="w-full border border-gray-300 rounded text-sm px-3 py-2.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                            <option value="">Selecione</option>
                                            <option value="ate-360k" @selected(old('faturamento') === 'ate-360k')>Até R$ 360 mil</option>
                                            <option value="360k-4.8m" @selected(old('faturamento') === '360k-4.8m')>R$ 360 mil a R$ 4,8 milhões</option>
                                            <option value="4.8m-300m" @selected(old('faturamento') === '4.8m-300m')>R$ 4,8 milhões a R$ 300 milhões</option>
                                            <option value="acima-300m" @selected(old('faturamento') === 'acima-300m')>Acima de R$ 300 milhões</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-5">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-3">Principal desafio</p>
                                <div class="space-y-2 text-sm text-gray-700">
                                    @php
                                        $desafios = [
                                            'documentos_espalhados' => 'Documentos espalhados sem histórico',
                                            'pendencias_fim_mes' => 'Corrida no fim do mês com pendências',
                                            'comunicacao_manual' => 'Comunicação manual sem rastreabilidade',
                                            'falta_visao' => 'Falta de visão do que está certo ou falta',
                                        ];
                                    @endphp
                                    @foreach($desafios as $valor => $label)
                                        <label class="flex items-center gap-2">
                                            <input type="radio" name="desafio_principal" value="{{ $valor }}" @checked(old('desafio_principal') === $valor) required class="text-gray-700 focus:ring-gray-500">
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-5 space-y-3">
                                <label for="terms_aceitos" class="flex items-start gap-2 text-sm text-gray-600">
                                    <input type="checkbox" id="terms_aceitos" name="terms_aceitos" value="1" @checked(old('terms_aceitos')) required class="mt-0.5 h-4 w-4 border border-gray-300 rounded text-gray-800 focus:ring-1 focus:ring-gray-400">
                                    <span>
                                        Concordo com os
                                        <a href="{{ route('termos') }}" class="hover:underline" style="color: #1e4fa0">Termos de Uso</a>
                                        e a
                                        <a href="{{ route('privacidade') }}" class="hover:underline" style="color: #1e4fa0">Política de Privacidade</a>.
                                    </span>
                                </label>

                                <label for="marketing_opt_in" class="flex items-start gap-2 text-sm text-gray-600">
                                    <input type="checkbox" id="marketing_opt_in" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in')) class="mt-0.5 h-4 w-4 border border-gray-300 rounded text-gray-800 focus:ring-1 focus:ring-gray-400">
                                    <span>Quero receber novidades e conteúdos sobre FiscalDock por e-mail.</span>
                                </label>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                <button type="submit" id="signup-submit-btn" class="bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium px-4 py-3 text-center">
                                    Criar conta grátis
                                </button>
                                <a href="{{ route('agendar') }}" class="bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium px-4 py-3 text-center">
                                    Falar com especialista
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">O que você recebe</span>
                    </div>
                    <div class="p-4 space-y-4 text-sm text-gray-700">
                        <div class="border border-gray-200 rounded p-4">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Bônus inicial</p>
                            <p class="text-lg font-bold text-gray-900">100 créditos grátis</p>
                            <p class="text-xs text-gray-500 mt-1">Liberados automaticamente na criação da conta.</p>
                        </div>
                        <div class="border border-gray-200 rounded p-4">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Validade</p>
                            <p class="text-lg font-bold text-gray-900">30 dias</p>
                            <p class="text-xs text-gray-500 mt-1">O saldo promocional restante expira ao fim do período.</p>
                        </div>
                        <div class="border border-gray-200 rounded p-4">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Próximo passo</p>
                            <p class="text-sm text-gray-700">Depois do trial, você pode comprar pacotes de créditos e continuar usando a plataforma no seu ritmo.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Preferir ajuda humana?</span>
                    </div>
                    <div class="p-4 space-y-3 text-sm text-gray-700">
                        <p>Se quiser confirmar aderência, pacotes de créditos, faixas ou regras de uso antes de criar a conta, fale direto com nosso time comercial.</p>
                        <a href="{{ route('agendar') }}" class="inline-flex items-center justify-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Falar com especialista
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
