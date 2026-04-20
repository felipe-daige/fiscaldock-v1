{{-- Dashboard - Hub Central --}}
<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        {{-- Header --}}
        <div class="mb-4 sm:mb-8">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Dashboard</h1>
            <p class="text-xs text-gray-500 mt-1">Visão geral do seu escritório</p>
        </div>

        @if(($trialResumo['is_active'] ?? false) || ($trialResumo['is_expired'] ?? false))
            <div class="bg-white rounded border border-gray-300 p-4 mb-6 border-l-4 {{ ($trialResumo['is_active'] ?? false) ? 'border-l-blue-500' : 'border-l-amber-500' }}">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Trial</p>
                @if($trialResumo['is_active'] ?? false)
                    <p class="mt-2 text-sm text-gray-700">
                        Seu trial está ativo com <strong>{{ number_format($trialResumo['remaining'] ?? 0, 0, ',', '.') }} créditos promocionais</strong>
                        e expira em <strong>{{ optional($trialResumo['expires_at'])->format('d/m/Y H:i') }}</strong>.
                    </p>
                @else
                    <p class="mt-2 text-sm text-gray-700">
                        Seu período promocional terminou em <strong>{{ optional($trialResumo['expires_at'])->format('d/m/Y H:i') }}</strong>.
                        Compre novos créditos para continuar usando as consultas pagas.
                    </p>
                @endif
            </div>
        @endif

        {{-- KPI Cards --}}
        @php
            $vol = $kpis['volume_total_notas'] ?? 0;
            $volValor = $kpis['volume_valor_total'] ?? 0;
            $partTotal = $kpis['participantes_total'] ?? 0;
            $partRisco = $kpis['participantes_risco'] ?? 0;
            $cred = $kpis['creditos'] ?? 0;
            $credMes = $kpis['creditos_usados_mes'] ?? 0;
            $alertTotal = $kpis['alertas_total'] ?? 0;
            $alertAlta = $kpis['alertas_alta'] ?? 0;
        @endphp

        {{-- KPIs (Resumo Fiscal) --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6 sm:mb-10">
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">
                
                {{-- KPI 1: Volume Processado --}}
                <div class="p-4 sm:p-6 hover:bg-gray-50/50 transition-colors">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Volume Processado</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($vol, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">R$ {{ number_format($volValor, 2, ',', '.') }}</p>
                </div>

                {{-- KPI 2: Participantes --}}
                <div class="p-4 sm:p-6 hover:bg-gray-50/50 transition-colors">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Participantes</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($partTotal, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $partRisco > 0 ? $partRisco . ' em risco alto/crítico' : 'Nenhum em risco' }}</p>
                </div>

                {{-- KPI 3: Créditos --}}
                <div class="p-4 sm:p-6 hover:bg-gray-50/50 transition-colors">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Créditos</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($cred, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $credMes }} usados este mês</p>
                </div>

                {{-- KPI 4: Alertas --}}
                <div class="p-4 sm:p-6 hover:bg-gray-50/50 transition-colors">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Alertas</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ $alertTotal }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $alertAlta > 0 ? $alertAlta . ' alta severidade' : 'Nenhum alerta' }}</p>
                </div>

            </div>
        </div>

        {{-- Cards de Módulos --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-6 mb-6 sm:mb-10">

            {{-- Notas Fiscais --}}
            <a href="/app/notas-fiscais" data-link class="bg-white rounded border border-gray-300 p-4 sm:p-6 hover:border-gray-400 transition-all group">
                <div class="flex items-center gap-3 mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 group-hover:text-gray-600 transition-colors uppercase tracking-wide">Notas Fiscais</h3>
                        <p class="text-[11px] text-gray-500 mt-1">Dashboard de notas</p>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-500 pt-2 border-t border-gray-100">
                    <span>{{ number_format($vol, 0, ',', '.') }} notas</span>
                    <span>R$ {{ number_format($volValor, 2, ',', '.') }}</span>
                </div>
            </a>

            {{-- BI Fiscal --}}
            <a href="/app/bi/dashboard" data-link class="bg-white rounded border border-gray-300 p-4 sm:p-6 hover:border-gray-400 transition-all group">
                <div class="flex items-center gap-3 mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 group-hover:text-gray-600 transition-colors uppercase tracking-wide">BI Fiscal</h3>
                        <p class="text-[11px] text-gray-500 mt-1">Dashboard analítico</p>
                    </div>
                </div>
                <div class="text-[11px] text-gray-500 pt-2 border-t border-gray-100">
                    <span>Faturamento, compras e tributos</span>
                </div>
            </a>

            {{-- Importar EFD --}}
            <a href="/app/importacao/efd" data-link class="bg-white rounded border border-gray-300 p-4 sm:p-6 hover:border-gray-400 transition-all group">
                <div class="flex items-center gap-3 mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 group-hover:text-gray-600 transition-colors uppercase tracking-wide">Importar EFD</h3>
                        <p class="text-[11px] text-gray-500 mt-1">Upload de arquivos</p>
                    </div>
                </div>
                <div class="text-[11px] text-gray-500 pt-2 border-t border-gray-100">
                    @if($ultimaImportacao)
                        <span class="inline-flex items-center gap-1">
                            @if($ultimaImportacao->status === 'concluido')
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: #047857"></span>
                            @elseif($ultimaImportacao->status === 'processando')
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: #d97706"></span>
                            @else
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: #374151"></span>
                            @endif
                            Última: {{ $ultimaImportacao->created_at->format('d/m/Y') }}
                        </span>
                    @else
                        <span>Nenhuma importação ainda</span>
                    @endif
                </div>
            </a>

        </div>

        {{-- Atividade Recente --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6 sm:mb-10">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <h2 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Atividade Recente</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($atividadeRecente as $atividade)
                    <div class="px-4 py-3 hover:bg-gray-50/50 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                @if($atividade['tipo'] === 'importacao')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #4338ca">Importação</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #0f766e">Consulta</span>
                                @endif
                                <span class="text-sm text-gray-700 truncate">{{ $atividade['descricao'] }}</span>
                                @if($atividade['tipo'] === 'importacao' && !empty($atividade['tipo_efd']))
                                    <span class="text-[11px] text-gray-400 capitalize">{{ $atividade['tipo_efd'] }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                @php
                                    $statusHex = match($atividade['status']) {
                                        'concluido' => '#047857',
                                        'processando' => '#d97706',
                                        'erro' => '#dc2626',
                                        default => '#9ca3af',
                                    };
                                    $statusLabel = match($atividade['status']) {
                                        'concluido' => 'Concluído',
                                        'processando' => 'Processando',
                                        'pendente' => 'Pendente',
                                        'erro' => 'Erro',
                                        default => ucfirst($atividade['status']),
                                    };
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $statusHex }}">{{ $statusLabel }}</span>
                                <span class="text-[11px] text-gray-400">{{ $atividade['data']->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 sm:py-8 text-center text-sm text-gray-500">
                        Nenhuma atividade recente
                        <br>
                        <a href="/app/importacao/efd" data-link class="mt-2 text-gray-900 hover:text-gray-600 hover:underline">
                            Fazer sua primeira importação
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Primeiros Passos (condicional) --}}
        @if($isUsuarioNovo)
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6 sm:mb-10">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <h2 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Primeiros Passos</h2>
                </div>
                <div class="p-4">
                    <ol class="space-y-4 text-sm text-gray-700">
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-5 h-5 flex items-center justify-center text-[10px] font-bold text-white rounded bg-gray-700 mt-0.5">1</span>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">Cadastre Clientes e Participantes</p>
                                <p class="text-xs text-gray-500 mt-1">Importe um arquivo EFD ou adicione os dados manualmente</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-5 h-5 flex items-center justify-center text-[10px] font-bold text-white rounded bg-gray-700 mt-0.5">2</span>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">Monitore sua Carteira</p>
                                <p class="text-xs text-gray-500 mt-1">Acompanhe a situação fiscal de clientes e participantes em tempo real</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-5 h-5 flex items-center justify-center text-[10px] font-bold text-white rounded bg-gray-700 mt-0.5">3</span>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">Analise o Score de Risco</p>
                                <p class="text-xs text-gray-500 mt-1">Identifique participantes com irregularidades cadastrais ou tributárias</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex-shrink-0 w-5 h-5 flex items-center justify-center text-[10px] font-bold text-white rounded bg-gray-700 mt-0.5">4</span>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">Explore o BI Fiscal</p>
                                <p class="text-xs text-gray-500 mt-1">Visualize faturamento, tributos e indicadores a partir dos seus EFDs</p>
                            </div>
                        </li>
                    </ol>
                </div>
            </div>
        @endif

        {{-- Suporte --}}
        <div class="bg-gray-50 rounded border border-gray-200 p-4">
            <div class="flex items-start gap-3">
                <div>
                    <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Precisa de ajuda?</p>
                    <p class="text-xs text-gray-600 mt-1">Entre em contato com nosso suporte através do email <a href="mailto:suporte@fiscaldock.com" class="text-gray-900 font-medium hover:text-gray-600 hover:underline">suporte@fiscaldock.com</a></p>
                </div>
            </div>
        </div>

    </div>
</div>
