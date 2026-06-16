@php
    $fmtData = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y H:i') : null;
@endphp
<div class="min-h-screen bg-gray-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        <div class="mb-4 sm:mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Privacidade e meus dados</h1>
            <p class="text-xs text-gray-500 mt-0.5">Seus direitos como titular (LGPD): ver consentimentos, revogar marketing, exportar e solicitar exclusão dos seus dados.</p>
        </div>

        @if(session('status'))
            <div class="bg-white rounded border border-gray-300 border-l-4 mb-4 p-3 text-sm text-gray-700" style="border-left-color: #047857">
                {{ session('status') }}
            </div>
        @endif

        {{-- Consentimentos --}}
        <div class="bg-white rounded border border-gray-300 mb-5 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-sm font-bold text-gray-900">Consentimentos</h2>
            </div>
            <div class="p-4 space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Conta</p>
                        <p class="text-[12px] text-gray-500">{{ $user->email }}</p>
                    </div>
                    <span class="text-[12px] text-gray-500">desde {{ $fmtData($user->created_at) ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                    <div>
                        <p class="font-medium text-gray-900">Termos e Política de Privacidade</p>
                        <p class="text-[12px] text-gray-500">{{ $fmtData($user->terms_accepted_at) ? 'Aceitos em '.$fmtData($user->terms_accepted_at) : 'Sem registro de aceite' }}</p>
                    </div>
                    <a href="/privacidade" data-link class="text-[12px] text-blue-600 hover:underline">Ver política →</a>
                </div>
                <div class="flex items-center justify-between border-t border-gray-100 pt-3">
                    <div>
                        <p class="font-medium text-gray-900">Comunicações de marketing</p>
                        <p class="text-[12px] text-gray-500">
                            @if($user->marketing_opt_in)
                                Autorizado{{ $fmtData($user->marketing_opt_in_at) ? ' em '.$fmtData($user->marketing_opt_in_at) : '' }}
                            @else
                                Não autorizado — você não recebe e-mails de marketing.
                            @endif
                        </p>
                    </div>
                    @if($user->marketing_opt_in)
                        <form method="POST" action="{{ route('app.privacidade.marketing.revogar') }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded text-[12px] font-semibold text-white hover:opacity-90" style="background-color: #b45309">Revogar</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Histórico de consentimentos (trilha auditável — fase 2.1) --}}
        @php
            $tipoLabels = [
                'termos' => 'Termos de Uso', 'privacidade' => 'Política de Privacidade',
                'marketing' => 'Marketing', 'exclusao' => 'Exclusão de conta',
            ];
            $acaoLabels = [
                'aceite' => 'Aceite', 'revogacao' => 'Revogação',
                'solicitacao' => 'Solicitação', 'cancelamento' => 'Cancelamento',
            ];
        @endphp
        @if($historico->isNotEmpty())
            <div class="bg-white rounded border border-gray-300 mb-5 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h2 class="text-sm font-bold text-gray-900">Histórico de consentimentos</h2>
                    <p class="text-[11px] text-gray-500 mt-0.5">Registro auditável de cada aceite, revogação e pedido — com data, versão do documento e IP.</p>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($historico as $log)
                        <div class="px-4 py-2.5 flex items-center justify-between text-sm">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ $tipoLabels[$log->tipo] ?? ucfirst($log->tipo) }}
                                    <span class="text-gray-400">·</span>
                                    <span class="text-gray-600">{{ $acaoLabels[$log->acao] ?? ucfirst($log->acao) }}</span>
                                    @if($log->versao)
                                        <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-semibold text-white align-middle" style="background-color: #6b7280">v{{ $log->versao }}</span>
                                    @endif
                                </p>
                                @if($log->ip)
                                    <p class="text-[11px] text-gray-400">{{ $log->ip }}</p>
                                @endif
                            </div>
                            <span class="text-[12px] text-gray-500 shrink-0">{{ $fmtData($log->created_at) ?? '—' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Exportar dados (DSAR) --}}
        <div class="bg-white rounded border border-gray-300 mb-5 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-sm font-bold text-gray-900">Exportar meus dados</h2>
            </div>
            <div class="p-4 flex items-center justify-between gap-4">
                <p class="text-sm text-gray-600">Baixe um arquivo JSON com seus dados de cadastro, consentimentos e histórico de créditos (art. 18, LGPD). Dados de clientes que você administra aparecem só como contagem.</p>
                <a href="{{ route('app.privacidade.exportar') }}" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded text-[12px] font-bold uppercase tracking-wide text-white hover:opacity-90" style="background-color: #0b1f3a">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Exportar JSON
                </a>
            </div>
            <div class="px-4 pb-4 grid grid-cols-2 sm:grid-cols-5 gap-2 text-center">
                @foreach([
                    ['Clientes', $titularidade['clientes']],
                    ['Participantes', $titularidade['participantes']],
                    ['Import. EFD', $titularidade['importacoes_efd']],
                    ['Import. XML', $titularidade['importacoes_xml']],
                    ['Consultas', $titularidade['consultas_lotes']],
                ] as [$rotulo, $valor])
                    <div class="bg-gray-50 rounded p-2">
                        <p class="text-base font-bold text-gray-900">{{ number_format($valor, 0, ',', '.') }}</p>
                        <p class="text-[10px] text-gray-500 uppercase tracking-wide">{{ $rotulo }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Exclusão de conta --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden" style="border-left: 4px solid #dc2626">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-sm font-bold text-gray-900">Exclusão de conta</h2>
            </div>
            <div class="p-4">
                @if($user->deletion_requested_at)
                    <p class="text-sm text-gray-700 mb-3">
                        <span class="font-semibold" style="color: #b45309">Pedido de exclusão registrado em {{ $fmtData($user->deletion_requested_at) }}.</span>
                        Nossa equipe processará respeitando a retenção fiscal obrigatória de documentos (SPED/XML). Você pode cancelar enquanto não for processado.
                    </p>
                    <form method="POST" action="{{ route('app.privacidade.exclusao.cancelar') }}">
                        @csrf
                        <button type="submit" class="px-3 py-2 rounded text-[12px] font-bold uppercase tracking-wide text-white hover:opacity-90" style="background-color: #374151">Cancelar pedido</button>
                    </form>
                @else
                    <p class="text-sm text-gray-600 mb-3">Você pode solicitar a exclusão da sua conta e dados pessoais. Documentos fiscais (SPED/XML) podem ser retidos pelo prazo legal antes da anonimização. O pedido não apaga nada imediatamente — é registrado para processamento.</p>
                    <form method="POST" action="{{ route('app.privacidade.exclusao.solicitar') }}" onsubmit="return confirm('Confirmar pedido de exclusão da sua conta? Você poderá cancelar enquanto não for processado.');">
                        @csrf
                        <button type="submit" class="px-3 py-2 rounded text-[12px] font-bold uppercase tracking-wide text-white hover:opacity-90" style="background-color: #dc2626">Solicitar exclusão da conta</button>
                    </form>
                @endif
            </div>
        </div>

    </div>
</div>
