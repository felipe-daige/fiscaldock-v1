@php
    $tipoEfdBadgeMap = [
        'fiscal' => ['label' => 'EFD ICMS/IPI', 'hex' => '#4338ca'],
        'contrib' => ['label' => 'EFD PIS/COFINS', 'hex' => '#0f766e'],
    ];
@endphp

{{-- Monitoramento - Importar EFD --}}
<div class="min-h-screen bg-gray-100" id="importacao-efd-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        <div class="mb-4 sm:mb-8 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Importação EFD</h1>
                <p class="text-xs text-gray-500 mt-1">Envio operacional de arquivos SPED para extrair participantes, notas, catálogo e apurações.</p>
            </div>
            <a
                href="/app/dashboard"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium"
                data-link
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar
            </a>
        </div>

        <div id="efd-upload-workspace" class="space-y-4 sm:space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 items-start">
                <div class="space-y-4 lg:col-span-2">
                    <div class="bg-white rounded border border-gray-300 overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Upload do Arquivo</span>
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">TXT até 10 MB</span>
                        </div>
                        <div class="p-4 sm:p-5">
                            <div class="mb-4">
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Tipo de EFD</label>
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                    <label class="tipo-efd-label flex items-start gap-3 px-4 py-3 border border-gray-300 rounded cursor-pointer transition-colors bg-white hover:bg-gray-50" data-tipo="efd-fiscal">
                                        <input type="radio" name="tipo-efd" value="efd-fiscal" class="mt-0.5 w-4 h-4 text-gray-600 border-gray-300 flex-shrink-0">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-sm font-semibold text-gray-900">EFD Fiscal</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Grátis</span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">ICMS/IPI para escrituração fiscal digital.</p>
                                        </div>
                                    </label>
                                    <label class="tipo-efd-label flex items-start gap-3 px-4 py-3 border border-gray-300 rounded cursor-pointer transition-colors bg-white hover:bg-gray-50" data-tipo="efd-contrib">
                                        <input type="radio" name="tipo-efd" value="efd-contrib" class="mt-0.5 w-4 h-4 text-gray-600 border-gray-300 flex-shrink-0">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-sm font-semibold text-gray-900">EFD Contribuições</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Grátis</span>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">PIS/COFINS para contribuições federais.</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div id="efd-extraction-info" class="mb-4 hidden">
                                <div class="bg-gray-50 border border-gray-200 rounded p-4">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Escopo da Extração</p>
                                    <div id="info-efd-fiscal" class="hidden flex flex-wrap gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">Participantes</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">NF-e Mercadorias</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">CT-e</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #4338ca">Catálogo</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">Apuração ICMS</span>
                                    </div>
                                    <div id="info-efd-contrib" class="hidden flex flex-wrap gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">Participantes</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">Notas Serviço</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">NF-e Mercadorias</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #4338ca">Catálogo</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">Apuração PIS/COFINS</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">Retenções</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div id="txt-dropzone" class="border-2 border-dashed border-gray-300 rounded p-6 min-h-[180px] flex flex-col items-center justify-center text-center transition-colors cursor-not-allowed bg-gray-100 opacity-60 pointer-events-none" role="button" tabindex="0" aria-disabled="true">
                                    <div class="mb-4">
                                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-gray-500" id="txt-dropzone-main-text">Selecione o tipo de EFD primeiro</p>
                                        <p class="text-xs text-gray-400" id="txt-dropzone-sub-text">Depois arraste o arquivo .txt aqui ou clique para selecionar</p>
                                        <p class="text-[11px] text-gray-400 mt-2">Formato .txt | Limite operacional de 10 MB</p>
                                    </div>
                                    <input
                                        type="file"
                                        id="txt-file-input"
                                        name="txt_file"
                                        accept=".txt"
                                        class="hidden"
                                        disabled
                                    >
                                </div>
                            </div>

                            <div id="txt-file-meta" class="mb-4 hidden">
                                <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200 rounded">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-gray-900 truncate max-w-[280px]" id="txt-file-name">arquivo.txt</div>
                                            <div class="text-[11px] text-gray-500" id="txt-file-size">0 MB</div>
                                        </div>
                                    </div>
                                    <button type="button" id="txt-change-file" class="text-gray-500 hover:text-gray-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div id="txt-error-message" class="mb-4 hidden">
                                <div class="bg-white rounded border border-gray-300 p-4 border-l-4 border-l-red-500">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-sm text-gray-700" id="txt-error-text"></p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="button"
                                    id="txt-importar-btn"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                    </svg>
                                    Importar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded border border-gray-300 overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Últimas Importações</span>
                                <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ min($importacoes->count(), 4) }}</span>
                            </div>
                            <a href="/app/importacao/historico" data-link class="text-[10px] font-semibold text-gray-600 hover:text-gray-900 hover:underline uppercase tracking-wide">Abrir histórico</a>
                        </div>
                        @if($importacoes->isEmpty())
                            <div class="p-4 sm:p-5">
                                <p class="text-sm text-gray-700">Nenhuma importação registrada ainda.</p>
                                <p class="text-xs text-gray-500 mt-1">As próximas operações concluídas aparecerão aqui.</p>
                            </div>
                        @else
                            <div class="divide-y divide-gray-100">
                                @foreach($importacoes->take(4) as $recentImp)
                                    @php
                                        $recentTipoKey = $recentImp->tipo_efd === 'EFD PIS/COFINS' ? 'contrib' : 'fiscal';
                                        $recentTipoBadge = $tipoEfdBadgeMap[$recentTipoKey];
                                        $recentStatus = match($recentImp->status) {
                                            'concluido' => ['label' => 'Concluído', 'hex' => '#047857'],
                                            'processando' => ['label' => 'Processando', 'hex' => '#d97706'],
                                            'erro' => ['label' => 'Erro', 'hex' => '#dc2626'],
                                            default => ['label' => 'Pendente', 'hex' => '#9ca3af'],
                                        };
                                    @endphp
                                    <a href="/app/importacao/efd/{{ $recentImp->id }}" data-link class="block px-4 py-3 hover:bg-gray-50/50 transition-colors">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $recentTipoBadge['hex'] }}">{{ $recentTipoBadge['label'] }}</span>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $recentStatus['hex'] }}">{{ $recentStatus['label'] }}</span>
                                                </div>
                                                <p class="text-sm text-gray-900 mt-2 truncate">{{ $recentImp->arquivo ?? ('Importação #' . $recentImp->id) }}</p>
                                                <p class="text-[11px] text-gray-500 mt-1">
                                                    {{ optional($recentImp->created_at)->format('d/m/Y H:i') }}
                                                    @if($recentImp->cliente?->razao_social)
                                                        · {{ $recentImp->cliente->razao_social }}
                                                    @endif
                                                </p>
                                            </div>
                                            <span class="text-[10px] text-gray-500 uppercase tracking-wide whitespace-nowrap">{{ $recentImp->created_at->diffForHumans() }}</span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded border border-gray-300 overflow-hidden lg:max-w-sm">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Fluxo de Processamento</span>
                    </div>
                    <div class="p-3 sm:p-4 grid grid-cols-1 gap-2.5">
                        <div class="rounded border border-gray-200 bg-gray-50 p-3">
                            <div class="flex items-start gap-2.5">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded text-[11px] font-bold text-white flex-shrink-0" style="background-color: #1f2937">1</span>
                                <div>
                                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Seleção</p>
                                    <p class="text-xs font-semibold text-gray-900">Escolha o tipo de EFD</p>
                                    <p class="text-xs text-gray-700 mt-0.5">Defina ICMS/IPI ou Contribuições para habilitar o envio.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded border border-gray-200 bg-gray-50 p-3">
                            <div class="flex items-start gap-2.5">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded text-[11px] font-bold text-white flex-shrink-0" style="background-color: #1f2937">2</span>
                                <div>
                                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Envio</p>
                                    <p class="text-xs font-semibold text-gray-900">Anexe o arquivo `.txt`</p>
                                    <p class="text-xs text-gray-700 mt-0.5">Use o dropzone e respeite o limite de 10 MB.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded border border-gray-200 bg-gray-50 p-3">
                            <div class="flex items-start gap-2.5">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded text-[11px] font-bold text-white flex-shrink-0" style="background-color: #1f2937">3</span>
                                <div>
                                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Extração</p>
                                    <p class="text-xs font-semibold text-gray-900">Consolide os blocos fiscais</p>
                                    <p class="text-xs text-gray-700 mt-0.5">A rotina extrai participantes, notas, catálogo e apurações.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded border border-gray-200 bg-gray-50 p-3">
                            <div class="flex items-start gap-2.5">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded text-[11px] font-bold text-white flex-shrink-0" style="background-color: #1f2937">4</span>
                                <div>
                                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Conferência</p>
                                    <p class="text-xs font-semibold text-gray-900">Revise a importação</p>
                                    <p class="text-xs text-gray-700 mt-0.5">Confira o período e priorize o arquivo mais recente.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded border border-gray-200 bg-gray-50 p-3">
                            <div class="flex items-start gap-2.5">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded text-[11px] font-bold text-white flex-shrink-0" style="background-color: #1f2937">5</span>
                                <div>
                                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Monitoramento</p>
                                    <p class="text-xs font-semibold text-gray-900">Acompanhe os dados</p>
                                    <p class="text-xs text-gray-700 mt-0.5">Abra a importação para navegar pelos dados extraídos.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Seção de Progresso de Importação (inicialmente oculta) --}}
        <div id="importacao-progresso" class="hidden">
            <div id="progresso-card" class="bg-white rounded border border-gray-300 p-4">
                {{-- Header: Empresa e documento --}}
                <div class="flex items-start gap-3 mb-4">
                    <div id="progresso-icon" class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-700 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 id="progresso-empresa" class="font-semibold text-gray-900 truncate">
                            Aguardando dados...
                        </h3>
                        <p id="progresso-documento" class="text-sm text-gray-500 hidden">
                            {{-- Tipo EFD • Período --}}
                        </p>
                        <p id="progresso-timer" class="text-xs font-mono text-gray-400 mt-0.5 hidden">
                            <svg class="w-3 h-3 inline-block mr-0.5 -mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span id="timer-display">0:00</span>
                        </p>
                    </div>
                </div>

                {{-- Barra de progresso --}}
                <div class="mb-3">
                    <div class="flex justify-between text-sm mb-1">
                        <span id="progresso-mensagem" class="text-gray-600">Iniciando...</span>
                        <span id="progresso-porcentagem" class="font-medium text-gray-900">0%</span>
                    </div>
                    <div class="bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div id="barra-progresso" class="bg-gray-800 h-full rounded-full transition-all duration-500 ease-out" style="width: 0%"></div>
                    </div>
                </div>

                {{-- Mensagem de erro (só aparece em caso de erro) --}}
                <div id="progresso-erro" class="hidden pt-3 border-t border-red-100">
                    <p id="progresso-erro-msg" class="text-sm text-gray-700 mb-3">
                        Ocorreu um erro interno durante o processamento.
                    </p>
                    <p class="text-sm text-gray-600 mb-4">
                        Por favor, tente novamente mais tarde.<br>
                        Se o erro persistir, entre em contato com o suporte:
                    </p>
                    <a href="https://wa.me/5567999844366"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-3 py-2 rounded text-white text-xs font-semibold transition mb-3" style="background-color: #047857">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        WhatsApp: (67) 99984-4366
                    </a>
                    <div>
                        <button type="button"
                                id="btn-tentar-novamente"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Tentar Novamente
                        </button>
                    </div>
                </div>
            </div>

            {{-- Strip horizontal de etapas EFD --}}
            <div id="etapas-notas-card" class="hidden mt-3 flex items-center gap-1.5 flex-wrap">
                <div class="etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400" data-etapa="participantes">
                    <span class="etapa-icon flex items-center justify-center w-3.5 h-3.5"></span>
                    <span>Participantes</span>
                </div>
                <svg class="etapa-sep hidden w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <div class="etapa-item hidden inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400" data-etapa="notas_servicos">
                    <span class="etapa-icon flex items-center justify-center w-3.5 h-3.5"></span>
                    <span>Notas Serviço</span>
                </div>
                <svg class="etapa-sep hidden w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <div class="etapa-item hidden inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400" data-etapa="notas_mercadorias">
                    <span class="etapa-icon flex items-center justify-center w-3.5 h-3.5"></span>
                    <span>NF-e Mercadorias</span>
                </div>
                <svg class="etapa-sep hidden w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <div class="etapa-item hidden inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400" data-etapa="notas_transportes">
                    <span class="etapa-icon flex items-center justify-center w-3.5 h-3.5"></span>
                    <span>CT-e</span>
                </div>
                <svg class="etapa-sep etapa-sep-catalogo hidden w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <div class="etapa-item etapa-catalogo hidden inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400" data-etapa="catalogo">
                    <span class="etapa-icon flex items-center justify-center w-3.5 h-3.5"></span>
                    <span>Catálogo</span>
                </div>
                <svg class="etapa-sep hidden w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <div class="etapa-item hidden inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400" data-etapa="apuracao_icms">
                    <span class="etapa-icon flex items-center justify-center w-3.5 h-3.5"></span>
                    <span>Apuração ICMS</span>
                </div>
                <svg class="etapa-sep hidden w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <div class="etapa-item hidden inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400" data-etapa="retencoes_fonte">
                    <span class="etapa-icon flex items-center justify-center w-3.5 h-3.5"></span>
                    <span>Retenções</span>
                </div>
                <svg class="etapa-sep hidden w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <div class="etapa-item hidden inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400" data-etapa="apuracao_pis_cofins">
                    <span class="etapa-icon flex items-center justify-center w-3.5 h-3.5"></span>
                    <span>Apuração PIS/COFINS</span>
                </div>
            </div>

            {{-- Seção de Resultados da Importação (aparece após importação concluída) --}}
            <div id="resultado-importacao" class="hidden mt-4">
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    {{-- Header dos Resultados --}}
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Importação Concluída</h3>
                                    <p class="text-sm text-gray-600" id="resultado-empresa">-</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                id="btn-nova-importacao"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Nova Importação
                            </button>
                        </div>
                    </div>

                    {{-- Estatísticas da Importação --}}
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="p-4 bg-gray-50 rounded border border-gray-200 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Total CNPJs</p>
                                <p class="text-2xl font-bold text-gray-900" id="resultado-total-participantes">0</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded border border-gray-200 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Novos</p>
                                <p class="text-2xl font-bold text-gray-900" id="resultado-novos">0</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded border border-gray-200 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Duplicados</p>
                                <p class="text-2xl font-bold text-gray-900" id="resultado-duplicados">0</p>
                            </div>
                        </div>
                        {{-- Notas Fiscais Extraídas (aparece apenas se extrair_notas=true) --}}
                        <div id="resultado-notas" class="hidden mt-4 pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-2xl font-bold text-gray-900" id="notas-extraidas-count">0</p>
                                        <p class="text-xs text-gray-500">Notas Fiscais Extraídas</p>
                                    </div>
                                </div>
                                <a href="/app/bi/dashboard" class="text-sm text-gray-600 hover:text-gray-900 font-medium hover:underline" data-link>
                                    Ver no BI Fiscal
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Mini-painel Resumo Final de Notas EFD (aparece quando n8n envia resumo_final) --}}
                    <div id="resumo-final-notas" class="hidden px-6 py-4 border-b border-gray-200">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Resumo de Notas Importadas</p>
                        <div id="resumo-final-notas-content" class="space-y-1 text-sm font-mono bg-gray-50 rounded p-3 border border-gray-200">
                            {{-- Preenchido via JS --}}
                        </div>
                    </div>

                    {{-- Cliente Associado (visível apenas quando cliente_id é informado via API) --}}
                    <div id="resultado-cliente" class="hidden px-6 py-4 border-b border-gray-200">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Cliente Associado</p>
                        <div class="flex items-center gap-6 flex-wrap">
                            <div>
                                <p class="text-xs text-gray-500 mb-0.5">Razão Social</p>
                                <a id="resultado-cliente-nome-link" href="#" data-link
                                   class="text-sm font-semibold text-gray-900 hover:text-gray-600 hover:underline cursor-pointer">
                                    <span id="resultado-cliente-nome">—</span>
                                </a>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-0.5" id="resultado-cliente-doc-label">Documento</p>
                                <p class="text-sm font-mono text-gray-900" id="resultado-cliente-doc">—</p>
                            </div>
                            <div class="ml-auto">
                                <a id="resultado-cliente-link" href="#" data-link
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-xs font-semibold">
                                    Ver no cadastro
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Lista de Participantes Importados --}}
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900">Participantes Importados</h4>
                            <button
                                type="button"
                                id="btn-carregar-participantes"
                                class="text-sm text-gray-600 hover:text-gray-900 font-medium hover:underline"
                            >
                                Carregar lista
                            </button>
                        </div>

                        {{-- Container da lista (inicialmente mostra placeholder) --}}
                        <div id="lista-participantes-container">
                            <div class="text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <p class="text-sm">Clique em "Carregar lista" para ver os participantes importados</p>
                            </div>
                        </div>

                        {{-- Loading state --}}
                        <div id="lista-participantes-loading" class="hidden text-center py-8">
                            <svg class="w-8 h-8 mx-auto text-gray-700 animate-spin mb-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            <p class="text-sm text-gray-500">Carregando participantes...</p>
                        </div>

                        {{-- Tabela de participantes (preenchida via JS) --}}
                        <div id="lista-participantes-tabela" class="hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">CNPJ</th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Razão Social</th>
                                            <th class="px-2 py-2 text-center text-xs font-semibold text-gray-500 uppercase w-12">UF</th>
                                            <th class="px-2 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Notas</th>
                                            <th class="px-2 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Entradas</th>
                                            <th class="px-2 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Saídas</th>
                                            <th class="px-2 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody id="participantes-tbody-resultado" class="divide-y divide-gray-200">
                                        {{-- Preenchido via JS --}}
                                    </tbody>
                                </table>
                            </div>
                            <div id="participantes-pagination" class="mt-6 py-2 flex items-center justify-between text-sm text-gray-500">
                                <span id="participantes-info">Mostrando 0 de 0</span>
                                <div class="flex gap-3">
                                    <button type="button" id="btn-prev-page" class="px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-50 disabled:opacity-50" disabled>Anterior</button>
                                    <button type="button" id="btn-next-page" class="px-3 py-1 rounded border border-gray-300 bg-white hover:bg-gray-50 disabled:opacity-50" disabled>Próximo</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Ações --}}
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex flex-col sm:flex-row gap-3 sm:justify-end">
                            <a
                                href="/app/dashboard"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium"
                                data-link
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                </svg>
                                Ver Todos os Participantes
                            </a>
                            <a
                                id="link-filtrar-importacao"
                                href="/app/importacao/efd/"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium"
                                data-link
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Ver Detalhes da Importação
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Modal Monitorar Participante Individual (EFD) --}}
<div id="modal-monitorar-individual-efd" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded border border-gray-300 max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Configurar Monitoramento</h3>
                <button type="button" class="modal-close text-gray-400 hover:text-gray-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="px-6 py-4">
            {{-- Info do participante --}}
            <div class="mb-4 p-3 bg-gray-50 rounded border border-gray-200">
                <p class="text-xs text-gray-500 mb-1">Participante</p>
                <p class="text-sm font-mono font-semibold text-gray-900" id="modal-monitorar-cnpj-efd">00.000.000/0001-00</p>
                <p class="text-sm text-gray-600" id="modal-monitorar-razao-efd">Razão Social</p>
            </div>

            {{-- Selecao de plano --}}
            <p class="text-sm font-medium text-gray-700 mb-3">Selecione o plano de monitoramento:</p>
            <div class="space-y-2">
                @php
                    $planosDisponiveis = [
                        ['id' => 'basico', 'nome' => 'Básico', 'creditos' => 0, 'gratuito' => true, 'descricao' => 'Dados cadastrais + Simples/MEI'],
                        ['id' => 'cadastral_plus', 'nome' => 'Cadastral+', 'creditos' => 3, 'gratuito' => false, 'descricao' => 'Básico + SINTEGRA + TCU Consolidada'],
                        ['id' => 'fiscal_federal', 'nome' => 'Fiscal Federal', 'creditos' => 6, 'gratuito' => false, 'descricao' => 'Cadastral+ + CND Federal + CRF FGTS'],
                        ['id' => 'fiscal_completo', 'nome' => 'Fiscal Completo', 'creditos' => 12, 'gratuito' => false, 'descricao' => 'Fiscal Federal + CND Estadual + CNDT'],
                        ['id' => 'due_diligence', 'nome' => 'Due Diligence', 'creditos' => 16, 'gratuito' => false, 'descricao' => 'Fiscal Completo + Lista Devedores PGFN'],
                        ['id' => 'esg', 'nome' => 'ESG', 'creditos' => 6, 'gratuito' => false, 'descricao' => 'Trabalho Escravo + IBAMA Autuações'],
                        ['id' => 'completo', 'nome' => 'Completo', 'creditos' => 22, 'gratuito' => false, 'descricao' => 'Todas as consultas disponíveis'],
                    ];
                @endphp
                @foreach($planosDisponiveis as $plano)
                    <label class="plano-option flex items-center gap-3 p-3 rounded border border-gray-300 cursor-pointer hover:bg-gray-50 transition-colors has-[:checked]:border-gray-500 has-[:checked]:bg-gray-50">
                        <input type="radio" name="plano_selecionado_efd" value="{{ $plano['id'] }}" data-creditos="{{ $plano['creditos'] }}" class="text-gray-600 focus:ring-gray-500">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900">{{ $plano['nome'] }}</span>
                                @if($plano['gratuito'])
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Grátis</span>
                                @else
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ $plano['creditos'] }} cred.</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $plano['descricao'] }}</p>
                        </div>
                    </label>
                @endforeach
            </div>

            {{-- Resumo --}}
            <div class="mt-4 p-3 bg-gray-50 rounded border border-gray-200">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Frequência:</span>
                    <span class="font-medium text-gray-900">Mensal (30 dias)</span>
                </div>
                <div class="flex items-center justify-between text-sm mt-1">
                    <span class="text-gray-600">Custo por consulta:</span>
                    <span class="font-semibold text-gray-900" id="modal-monitorar-custo-efd">0 créditos</span>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
            <button type="button" class="modal-close px-4 py-2 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-semibold">
                Cancelar
            </button>
            <button type="button" id="btn-confirmar-monitorar-efd" class="px-4 py-2 bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                Ativar Monitoramento
            </button>
        </div>
    </div>
</div>
<input type="hidden" id="modal-monitorar-participante-id-efd" value="">

<style>
    .etapa-item {
        transition: opacity 300ms ease;
    }
    .etapa-icon {
        transition: background-color 300ms ease, color 300ms ease;
    }
</style>

<script>
(function() {
    'use strict';

    function initImportacaoEfd() {
        const container = document.getElementById('importacao-efd-container');
        if (!container) return;

        if (container.dataset.initialized === '1') return;
        container.dataset.initialized = '1';

        console.log('[Monitoramento EFD] Inicializando...');

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Identificador único por aba para isolar notificações SSE
        // Usa 'let' para permitir regeneração ao tentar novamente
        let tabId = crypto.randomUUID ? crypto.randomUUID() :
            (Date.now().toString(36) + Math.random().toString(36).substr(2));

        // Função para gerar novo tabId
        function regenerarTabId() {
            tabId = crypto.randomUUID ? crypto.randomUUID() :
                (Date.now().toString(36) + Math.random().toString(36).substr(2));
            console.log('[Monitoramento EFD] Novo tabId gerado:', tabId);
        }

        // ===== Funcionalidade de Upload de Arquivo .txt =====
        const txtDropzone = document.getElementById('txt-dropzone');
        const txtFileInput = document.getElementById('txt-file-input');
        const txtFileMeta = document.getElementById('txt-file-meta');
        const txtFileName = document.getElementById('txt-file-name');
        const txtFileSize = document.getElementById('txt-file-size');
        const txtChangeFile = document.getElementById('txt-change-file');
        const txtImportarBtn = document.getElementById('txt-importar-btn');
        const txtErrorMessage = document.getElementById('txt-error-message');
        const txtErrorText = document.getElementById('txt-error-text');
        const tipoEfdRadios = document.querySelectorAll('input[name="tipo-efd"]');

        const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

        // Função para obter tipo EFD selecionado
        function getSelectedTipoEfd() {
            const selected = Array.from(tipoEfdRadios).find(radio => radio.checked);
            return selected ? selected.value : '';
        }

        // Função para atualizar visual dos labels do tipo EFD
        function updateTipoEfdLabels() {
            const selectedValue = getSelectedTipoEfd();
            document.querySelectorAll('.tipo-efd-label').forEach(function(label) {
                const radio = label.querySelector('input[type="radio"]');
                if (radio && radio.value === selectedValue) {
                    label.classList.remove('border-gray-300', 'hover:bg-gray-50');
                    label.classList.add('border-gray-500', 'bg-gray-50', 'ring-1', 'ring-gray-300');
                } else {
                    label.classList.remove('border-gray-500', 'bg-gray-50', 'ring-1', 'ring-gray-300');
                    label.classList.add('border-gray-300', 'hover:bg-gray-50');
                }
            });

            // Mostrar painel informativo conforme tipo selecionado
            const infoPanel = document.getElementById('efd-extraction-info');
            const infoFiscal = document.getElementById('info-efd-fiscal');
            const infoContrib = document.getElementById('info-efd-contrib');
            if (infoPanel && infoFiscal && infoContrib) {
                if (selectedValue) {
                    infoPanel.classList.remove('hidden');
                    infoFiscal.classList.toggle('hidden', selectedValue !== 'efd-fiscal');
                    infoContrib.classList.toggle('hidden', selectedValue !== 'efd-contrib');
                } else {
                    infoPanel.classList.add('hidden');
                }
            }
        }

        // Função para atualizar estado do dropzone
        function updateDropzoneState() {
            const hasTipoEfd = getSelectedTipoEfd() !== '';
            const dropzoneMainText = document.getElementById('txt-dropzone-main-text');
            const dropzoneSubText = document.getElementById('txt-dropzone-sub-text');
            
            if (txtDropzone && txtFileInput) {
                if (hasTipoEfd) {
                    // Habilitar dropzone
                    txtDropzone.classList.remove('border-gray-300', 'bg-gray-100', 'opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                    txtDropzone.classList.add('border-gray-300', 'bg-gray-50', 'hover:bg-gray-50', 'cursor-pointer');
                    txtDropzone.setAttribute('aria-disabled', 'false');
                    txtFileInput.disabled = false;
                    
                    // Atualizar ícone
                    const svg = txtDropzone.querySelector('svg');
                    if (svg) {
                        svg.classList.remove('text-gray-400');
                        svg.classList.add('text-gray-500');
                    }
                    
                    // Atualizar textos
                    if (dropzoneMainText) {
                        dropzoneMainText.textContent = 'Arraste o arquivo .txt aqui';
                        dropzoneMainText.classList.remove('text-gray-500');
                        dropzoneMainText.classList.add('text-gray-700', 'font-medium');
                    }
                    if (dropzoneSubText) {
                        dropzoneSubText.textContent = 'ou clique para selecionar';
                        dropzoneSubText.classList.remove('text-gray-400');
                        dropzoneSubText.classList.add('text-gray-500');
                    }
                } else {
                    // Desabilitar dropzone
                    txtDropzone.classList.remove('border-gray-300', 'bg-gray-50', 'hover:bg-gray-50', 'cursor-pointer');
                    txtDropzone.classList.add('border-gray-300', 'bg-gray-100', 'opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                    txtDropzone.setAttribute('aria-disabled', 'true');
                    txtFileInput.disabled = true;
                    
                    // Atualizar ícone
                    const svg = txtDropzone.querySelector('svg');
                    if (svg) {
                        svg.classList.remove('text-gray-500');
                        svg.classList.add('text-gray-400');
                    }
                    
                    // Atualizar textos
                    if (dropzoneMainText) {
                        dropzoneMainText.textContent = 'Selecione o tipo de EFD primeiro';
                        dropzoneMainText.classList.remove('text-gray-700', 'font-medium');
                        dropzoneMainText.classList.add('text-gray-500');
                    }
                    if (dropzoneSubText) {
                        dropzoneSubText.textContent = 'Depois arraste o arquivo .txt aqui ou clique para selecionar';
                        dropzoneSubText.classList.remove('text-gray-500');
                        dropzoneSubText.classList.add('text-gray-400');
                    }
                }
            }
        }

        // Função para atualizar habilitação do botão
        function updateImportButtonState() {
            const hasTipoEfd = getSelectedTipoEfd() !== '';
            const hasFile = txtFileInput && txtFileInput.files && txtFileInput.files.length > 0;
            
            if (txtImportarBtn) {
                txtImportarBtn.disabled = !(hasTipoEfd && hasFile);
            }
        }

        // Função para validar arquivo
        function validarArquivoTxt(file) {
            if (!file) return false;

            // Validar extensão
            const fileName = file.name.toLowerCase();
            const isTxt = fileName.endsWith('.txt') || file.type === 'text/plain';
            if (!isTxt) {
                mostrarErroTxt('Apenas arquivos .txt são permitidos. Por favor, selecione um arquivo .txt.');
                return false;
            }

            // Validar tamanho
            if (file.size > MAX_FILE_SIZE) {
                mostrarErroTxt('O arquivo excede o limite de 10MB. Por favor, selecione um arquivo menor.');
                return false;
            }

            return true;
        }

        // Função para mostrar erro
        function mostrarErroTxt(mensagem) {
            if (txtErrorText) txtErrorText.textContent = mensagem;
            if (txtErrorMessage) txtErrorMessage.classList.remove('hidden');
        }

        // Função para ocultar erro
        function ocultarErroTxt() {
            if (txtErrorMessage) txtErrorMessage.classList.add('hidden');
        }

        // Função para atualizar UI do arquivo
        function atualizarUITxt(file) {
            if (!file) {
                if (txtFileMeta) txtFileMeta.classList.add('hidden');
                updateImportButtonState();
                return;
            }

            if (txtFileName) txtFileName.textContent = file.name;
            if (txtFileSize) {
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                txtFileSize.textContent = sizeMB + ' MB';
            }
            if (txtFileMeta) txtFileMeta.classList.remove('hidden');
            updateImportButtonState();
        }

        // Função para limpar arquivo
        function limparArquivoTxt() {
            if (txtFileInput) txtFileInput.value = '';
            atualizarUITxt(null);
            ocultarErroTxt();
            updateImportButtonState();
        }

        // Função para processar arquivo selecionado
        function processarArquivoTxt(file) {
            ocultarErroTxt();

            if (!validarArquivoTxt(file)) {
                limparArquivoTxt();
                return;
            }

            atualizarUITxt(file);
        }

        // Click no dropzone
        if (txtDropzone && txtFileInput) {
            txtDropzone.addEventListener('click', function() {
                if (txtFileInput.disabled) return;
                txtFileInput.click();
            });

            // Drag and drop
            txtDropzone.addEventListener('dragover', function(e) {
                if (txtFileInput.disabled) return;
                e.preventDefault();
                txtDropzone.classList.remove('border-gray-300', 'bg-gray-50', 'hover:bg-gray-50');
                txtDropzone.classList.add('border-gray-500', 'bg-gray-50');
            });

            txtDropzone.addEventListener('dragleave', function() {
                if (txtFileInput.disabled) return;
                txtDropzone.classList.remove('border-gray-500', 'bg-gray-50');
                txtDropzone.classList.add('border-gray-300', 'bg-gray-50', 'hover:bg-gray-50');
            });

            txtDropzone.addEventListener('drop', function(e) {
                if (txtFileInput.disabled) return;
                e.preventDefault();
                txtDropzone.classList.remove('border-gray-500', 'bg-gray-50');
                txtDropzone.classList.add('border-gray-300', 'bg-gray-50', 'hover:bg-gray-50');

                const file = e.dataTransfer?.files?.[0];
                if (file) {
                    processarArquivoTxt(file);
                    // Atualizar input file
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    txtFileInput.files = dt.files;
                }
            });
        }

        // Change no input file
        if (txtFileInput) {
            txtFileInput.addEventListener('change', function(e) {
                const file = e.target.files?.[0];
                if (file) {
                    processarArquivoTxt(file);
                } else {
                    limparArquivoTxt();
                }
            });
        }

        // Botão trocar arquivo
        if (txtChangeFile) {
            txtChangeFile.addEventListener('click', function(e) {
                e.stopPropagation();
                limparArquivoTxt();
                if (txtFileInput) txtFileInput.click();
            });
        }

        // Event listeners para radio buttons do tipo EFD
        if (tipoEfdRadios && tipoEfdRadios.length > 0) {
            tipoEfdRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    updateTipoEfdLabels();
                    updateDropzoneState();
                    updateImportButtonState();
                });
            });
        }

        // Timer de processamento
        let timerInterval = null;
        let timerStartTime = null;

        function iniciarTimer() {
            pararTimer();
            timerStartTime = Date.now();
            const timerEl = document.getElementById('progresso-timer');
            const displayEl = document.getElementById('timer-display');
            if (timerEl) timerEl.classList.remove('hidden');
            timerInterval = setInterval(function() {
                if (!timerStartTime || !displayEl) return;
                const elapsed = Math.floor((Date.now() - timerStartTime) / 1000);
                const m = Math.floor(elapsed / 60);
                const s = elapsed % 60;
                displayEl.textContent = m + ':' + String(s).padStart(2, '0');
            }, 1000);
        }

        function pararTimer() {
            if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        }

        function formatarTempoFinal(segundos) {
            const h = Math.floor(segundos / 3600);
            const m = Math.floor((segundos % 3600) / 60);
            const s = segundos % 60;
            if (h > 0) return h + 'h ' + m + 'm';
            if (m > 0) return m + 'm ' + s + 's';
            if (s > 0) return s + 's';
            return '< 1s';
        }

        // Variáveis para controle de importação
        let eventSourceTxt = null;
        let importacaoEmAndamento = false;
        let importandoComNotas = true;
        let importandoComCatalogo = true;
        let tipoEfdAtual = null; // 'efd-fiscal' ou 'efd-contrib'
        let reconnectTimer = null;
        let reconnectAttempts = 0;
        let toastConcluidoMostrado = false; // evita toast duplicado quando SSE mantido aberto
        const MAX_RECONEXOES = 3;
        const DELAY_RECONEXAO_BASE = 3000;

        // Animação suave da barra de progresso
        let currentProgress = 0;   // valor atualmente exibido na barra
        let targetProgress  = 0;   // valor alvo recebido do SSE
        let animFrameId     = null; // handle do requestAnimationFrame ativo
        let blocoAtualProgresso = null; // rastreia mudança de bloco para reset do contador

        function animarProgresso() {
            if (currentProgress < targetProgress) {
                currentProgress = Math.min(currentProgress + 0.4, targetProgress);
                const pct = Math.round(currentProgress);
                if (barraProgresso)       barraProgresso.style.width = pct + '%';
                if (progressoPorcentagem) progressoPorcentagem.textContent = pct + '%';
                animFrameId = requestAnimationFrame(animarProgresso);
            } else if (currentProgress > targetProgress) {
                // Snap imediato para baixo (não animar regressão)
                currentProgress = targetProgress;
                const pct = Math.round(currentProgress);
                if (barraProgresso)       barraProgresso.style.width = pct + '%';
                if (progressoPorcentagem) progressoPorcentagem.textContent = pct + '%';
                animFrameId = null;
            } else {
                animFrameId = null;
            }
        }

        // Elementos de progresso (nova UI minimalista)
        const progressoContainer = document.getElementById('importacao-progresso');
        const progressoCard = document.getElementById('progresso-card');
        const barraProgresso = document.getElementById('barra-progresso');
        const progressoPorcentagem = document.getElementById('progresso-porcentagem');
        const progressoMensagem = document.getElementById('progresso-mensagem');
        const progressoEmpresa = document.getElementById('progresso-empresa');
        const progressoDocumento = document.getElementById('progresso-documento');
        const progressoIcon = document.getElementById('progresso-icon');

        // Elementos de erro
        const progressoErro = document.getElementById('progresso-erro');
        const progressoErroMsg = document.getElementById('progresso-erro-msg');

        // Função para atualizar ícone de status
        function atualizarIconeStatus(status, errorMessage) {
            if (!progressoIcon || !progressoCard) return;

            // Mapeia o status recebido para um estado visual estável
            const visualState = status === 'concluido'
                ? 'ok'
                : (status === 'erro' || status === 'timeout') ? 'err' : 'loading';

            const previousState = progressoIcon.dataset.visualState || null;
            const stateChanged = visualState !== previousState;

            // Enquanto o estado visual não muda, NÃO reescrever innerHTML/className/style
            // (isso reiniciaria a animação CSS animate-spin a cada mensagem SSE).
            // Exceção: no estado de erro, permitimos atualizar a mensagem de erro.
            if (!stateChanged && visualState !== 'err') return;

            if (stateChanged) {
                progressoCard.className = 'bg-white rounded border p-4';

                switch (visualState) {
                    case 'ok':
                        progressoIcon.className = 'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0';
                        progressoIcon.style.backgroundColor = '#d1fae5';
                        progressoIcon.innerHTML = '<svg class="w-5 h-5" style="color: #047857" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                        progressoCard.classList.add('border-gray-300');
                        if (barraProgresso) {
                            barraProgresso.className = 'h-full rounded-full transition-all duration-500 ease-out';
                            barraProgresso.style.backgroundColor = '#047857';
                        }
                        if (progressoErro) progressoErro.classList.add('hidden');
                        break;
                    case 'err':
                        progressoIcon.className = 'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0';
                        progressoIcon.style.backgroundColor = '#fee2e2';
                        progressoIcon.innerHTML = '<svg class="w-5 h-5" style="color: #b91c1c" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
                        progressoCard.classList.add('border-gray-300');
                        if (barraProgresso) {
                            barraProgresso.className = 'h-full rounded-full transition-all duration-500 ease-out';
                            barraProgresso.style.backgroundColor = '#b91c1c';
                        }
                        if (progressoErro) progressoErro.classList.remove('hidden');
                        break;
                    default: // 'loading'
                        progressoIcon.className = 'w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center flex-shrink-0';
                        progressoIcon.innerHTML = '<svg class="w-5 h-5 text-gray-700 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>';
                        progressoCard.classList.add('border-gray-200');
                        if (barraProgresso) barraProgresso.className = 'bg-gray-800 h-full rounded-full transition-all duration-500 ease-out';
                        if (progressoErro) progressoErro.classList.add('hidden');
                }

                progressoIcon.dataset.visualState = visualState;
            }

            // Atualização da mensagem de erro (pode chegar depois da primeira transição para 'err')
            if (visualState === 'err' && progressoErroMsg) {
                if (errorMessage) {
                    progressoErroMsg.textContent = errorMessage;
                } else if (stateChanged) {
                    progressoErroMsg.textContent = status === 'timeout'
                        ? 'O processamento demorou mais do que o esperado.'
                        : 'Ocorreu um erro interno durante o processamento.';
                }
            }
        }

        // Função para atualizar UI de progresso
        function atualizarProgresso(payload) {
            const dados = payload.dados || {};
            const progresso = parseInt(payload.progresso) || 0;
            const status = payload.status || 'processando';
            const mensagem = payload.mensagem || 'Processando...';
            const errorMessage = payload.error_message || payload.mensagem || null;

            // Reset do contador ao mudar de bloco
            const blocoPayload = payload.bloco || null;
            if (blocoPayload !== blocoAtualProgresso) {
                blocoAtualProgresso = blocoPayload;
                // Reset imediato (sem animação de retrocesso)
                if (animFrameId !== null) { cancelAnimationFrame(animFrameId); animFrameId = null; }
                currentProgress = 0;
                if (barraProgresso)       barraProgresso.style.width = '0%';
                if (progressoPorcentagem) progressoPorcentagem.textContent = '0%';
            }

            // Barra de progresso (animação suave)
            targetProgress = progresso;
            if (animFrameId === null) {
                animFrameId = requestAnimationFrame(animarProgresso);
            }
            if (progressoMensagem) progressoMensagem.textContent = mensagem;

            // Empresa
            if (progressoEmpresa && dados.nome_empresa) {
                progressoEmpresa.textContent = dados.nome_empresa;
            }

            // Documento (tipo e período)
            if (progressoDocumento) {
                const tipo = dados.tipo_documento || '';
                const periodo = dados.data_inicial_do_documento && dados.data_final_do_documento
                    ? dados.data_inicial_do_documento + ' - ' + dados.data_final_do_documento
                    : '';
                const docText = [tipo, periodo].filter(Boolean).join(' • ');
                if (docText) {
                    progressoDocumento.textContent = docText;
                    progressoDocumento.classList.remove('hidden');
                }
            }

            // Status visual (passa mensagem de erro se for erro/timeout)
            const isError = status === 'erro' || status === 'timeout';
            atualizarIconeStatus(status, isError ? errorMessage : null);

            // Etapas de notas
            atualizarEtapasNotas(payload);
        }

        // Blocos permitidos por tipo de EFD (mesma ordem do n8n)
        function getBlocosPermitidos() {
            if (tipoEfdAtual === 'efd-fiscal')  return ['participantes', 'notas_mercadorias', 'notas_transportes', 'catalogo', 'apuracao_icms'];
            if (tipoEfdAtual === 'efd-contrib') return ['participantes', 'notas_servicos', 'notas_mercadorias', 'catalogo', 'apuracao_pis_cofins', 'retencoes_fonte'];
            // Fallback: todos (retrocompatibilidade com importações sem tipo definido)
            return ['participantes', 'notas_servicos', 'notas_mercadorias', 'notas_transportes', 'catalogo', 'apuracao_icms', 'retencoes_fonte', 'apuracao_pis_cofins'];
        }

        function atualizarEtapasNotas(payload) {
            const card = document.getElementById('etapas-notas-card');
            if (!card) return;

            card.classList.remove('hidden');

            const blocos = Object.assign({}, payload.notas_blocos || {});

            // Retrocompatibilidade: aceitar chaves antigas (0, 0200, A, C, D)
            if (blocos['0'] && !blocos.participantes)          blocos.participantes = blocos['0'];
            if (blocos['0200'] && !blocos.catalogo)            blocos.catalogo = blocos['0200'];
            if (blocos['A'] && !blocos.notas_servicos)         blocos.notas_servicos = blocos['A'];
            if (blocos['C'] && !blocos.notas_mercadorias)      blocos.notas_mercadorias = blocos['C'];
            if (blocos['D'] && !blocos.notas_transportes)      blocos.notas_transportes = blocos['D'];

            const isFinalConcluido = payload.status === 'concluido';
            const permitidos = getBlocosPermitidos();

            // Atualizar apenas blocos permitidos para o tipo de EFD atual
            permitidos.forEach(function(b) {
                if (b === 'participantes') {
                    // Participantes: inferir concluido se outro bloco já está ativo
                    const bp = blocos.participantes;
                    if (bp) {
                        const s = (bp.status === 'concluido' || parseInt(bp.progresso) === 100 || isFinalConcluido)
                            ? 'concluido' : bp.status;
                        renderEtapa('participantes', s, null);
                    } else {
                        const temOutro = Object.keys(blocos).some(function(k) { return k !== 'participantes' && permitidos.indexOf(k) !== -1; });
                        renderEtapa('participantes', temOutro ? 'concluido' : 'processando', null);
                    }
                    return;
                }

                // Demais blocos: atualizar status se SSE trouxe dados
                if (blocos[b]) {
                    const statusEfetivo = (blocos[b].status !== 'skip' && (isFinalConcluido || blocos[b].status === 'concluido' || parseInt(blocos[b].progresso) === 100))
                        ? 'concluido'
                        : blocos[b].status;
                    renderEtapa(b, statusEfetivo, null);
                } else if (isFinalConcluido) {
                    // Importação concluída mas bloco sem dados no SSE (chegou após o payload final)
                    renderEtapa(b, 'concluido', null);
                }
            });
        }

        function renderEtapa(etapa, status, mensagem) {
            const item = document.querySelector('.etapa-item[data-etapa="' + etapa + '"]');
            if (!item) return;

            // Normaliza 'inicio' e 'processando' para a mesma chave visual ('loading'),
            // evitando re-render do SVG quando o backend alterna entre esses dois valores.
            const visualStatus = (status === 'processando' || status === 'inicio')
                ? 'loading'
                : (status || 'pendente');

            // Se o estado visual não mudou, não tocar no DOM — preserva a animação CSS.
            if (item.dataset.renderedStatus === visualStatus) return;

            const iconEl = item.querySelector('.etapa-icon');

            const svgSpinner = '<svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>';
            const svgCheck   = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
            const svgDash    = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>';

            const estados = {
                pendente:  { pill: 'bg-gray-100 text-gray-400', icon: svgDash },
                loading:   { pill: 'bg-gray-200 text-gray-700', icon: svgSpinner },
                concluido: { pill: 'text-white',                icon: svgCheck, style: 'background-color: #047857' },
                skip:      { pill: 'bg-gray-100 text-gray-400', icon: svgDash },
            };

            const estado = estados[visualStatus] || estados.pendente;
            item.className = 'etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium ' + estado.pill;
            item.style.cssText = estado.style || '';
            iconEl.innerHTML = estado.icon;
            item.dataset.renderedStatus = visualStatus;
        }

        // Função para mostrar UI de progresso
        function mostrarProgresso() {
            if (progressoContainer) progressoContainer.classList.remove('hidden');
            // Ocultar cards de upload
            const uploadSection = document.getElementById('efd-upload-workspace');
            if (uploadSection) uploadSection.classList.add('hidden');
            document.getElementById('historico-importacoes')?.classList.add('hidden');
        }

        // Função para ocultar UI de progresso
        function ocultarProgresso() {
            if (progressoContainer) progressoContainer.classList.add('hidden');
            // Mostrar cards de upload
            const uploadSection = document.getElementById('efd-upload-workspace');
            if (uploadSection) uploadSection.classList.remove('hidden');
            document.getElementById('historico-importacoes')?.classList.remove('hidden');
        }

        // Função para resetar UI de progresso
        function resetarProgresso() {
            // Resetar timer
            pararTimer();
            timerStartTime = null;
            const timerEl = document.getElementById('progresso-timer');
            if (timerEl) timerEl.classList.add('hidden');

            // Cancelar animação em andamento e resetar estado
            if (animFrameId !== null) { cancelAnimationFrame(animFrameId); animFrameId = null; }
            currentProgress = 0;
            targetProgress  = 0;
            // Resetar barra de progresso
            if (barraProgresso) {
                barraProgresso.style.width = '0%';
                barraProgresso.className = 'bg-gray-800 h-full rounded-full transition-all duration-500 ease-out';
            }
            if (progressoPorcentagem) progressoPorcentagem.textContent = '0%';
            if (progressoMensagem) progressoMensagem.textContent = 'Iniciando...';

            // Resetar header
            if (progressoEmpresa) progressoEmpresa.textContent = 'Aguardando dados...';
            if (progressoDocumento) {
                progressoDocumento.textContent = '';
                progressoDocumento.classList.add('hidden');
            }

            // Resetar ícone e card para estado inicial (processando)
            atualizarIconeStatus('processando');

            // Ocultar seção de erro
            if (progressoErro) progressoErro.classList.add('hidden');

            // Ocultar seção de resultados
            const resultadoImportacao = document.getElementById('resultado-importacao');
            if (resultadoImportacao) resultadoImportacao.classList.add('hidden');

            // Resetar card de etapas de notas
            const etapasCard = document.getElementById('etapas-notas-card');
            if (etapasCard) etapasCard.classList.add('hidden');
            document.querySelectorAll('.etapa-item').forEach(function(item) {
                item.className = 'etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400';
                const iconEl = item.querySelector('.etapa-icon');
                if (iconEl) iconEl.innerHTML = '';
            });
            importandoComNotas = true;
            importandoComCatalogo = true;
            // Ocultar etapa 0200 e seu separador
            document.querySelectorAll('.etapa-0200, .etapa-sep-0200').forEach(function(el) {
                el.classList.add('hidden');
            });
            blocoAtualProgresso = null;
        }

        // Template SVG separador (reutilizado ao reordenar)
        const sepSvgTemplate = '<svg class="etapa-sep w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';

        // Mostra apenas os badges de etapa relevantes para o tipo de EFD selecionado,
        // reordenando no DOM para que a sequência visual siga a ordem do n8n.
        function mostrarEtapasPorTipoEfd(tipo) {
            const blocosFiscal  = ['participantes', 'notas_mercadorias', 'notas_transportes', 'catalogo', 'apuracao_icms'];
            const blocosContrib = ['participantes', 'notas_servicos', 'notas_mercadorias', 'catalogo', 'apuracao_pis_cofins', 'retencoes_fonte'];
            const blocosAtivos = tipo === 'efd-fiscal' ? blocosFiscal : blocosContrib;

            const card = document.getElementById('etapas-notas-card');
            if (!card) return;

            // Guardar todos os badge elements e limpar o card
            const allBadges = {};
            card.querySelectorAll('.etapa-item').forEach(function(item) {
                allBadges[item.dataset.etapa] = item;
                item.classList.add('hidden');
            });
            // Limpar card completamente (remove separadores e badges do DOM)
            card.innerHTML = '';

            // Reinserir apenas os ativos na ordem correta
            blocosAtivos.forEach(function(b, idx) {
                var el = allBadges[b];
                if (!el) return;
                if (idx > 0) {
                    card.insertAdjacentHTML('beforeend', sepSvgTemplate);
                }
                card.appendChild(el);
                el.classList.remove('hidden');
            });

            card.classList.remove('hidden');
        }

        // Elementos da seção de resultados
        const resultadoContainer = document.getElementById('resultado-importacao');
        const resultadoEmpresa = document.getElementById('resultado-empresa');
        const resultadoTotalParticipantes = document.getElementById('resultado-total-participantes');
        const resultadoNovos = document.getElementById('resultado-novos');
        const resultadoDuplicados = document.getElementById('resultado-duplicados');
        const btnNovaImportacao = document.getElementById('btn-nova-importacao');
        const btnCarregarParticipantes = document.getElementById('btn-carregar-participantes');
        const linkFiltrarImportacao = document.getElementById('link-filtrar-importacao');
        const listaParticipantesContainer = document.getElementById('lista-participantes-container');
        const listaParticipantesLoading = document.getElementById('lista-participantes-loading');
        const listaParticipantesTabela = document.getElementById('lista-participantes-tabela');
        const participantesTbody = document.getElementById('participantes-tbody-resultado');

        // Variável para guardar o ID da importação atual e IDs dos participantes
        let importacaoAtualId = null;
        let participanteIdsFromSSE = null; // Array de IDs recebidos do n8n via SSE
        let novosIdsFromSSE = null; // Array de IDs dos participantes NOVOS
        let duplicadosIdsFromSSE = null; // Array de IDs dos participantes DUPLICADOS/ATUALIZADOS
        let participantesPage = 1;
        let participantesTotal = 0;
        // Mapa participante_id → dados de resumo (nota_ids, total_notas, entradas, saidas, bi)
        let participantesResumoMap = {};
        // Cache de notas carregadas por participante (participante_id → array de notas)
        let notasCache = {};

        // Função para mostrar seção de resultados após importação concluída
        function mostrarResultadoImportacao(dados) {
            console.log('[Monitoramento EFD] mostrarResultadoImportacao - dados recebidos:', dados);
            const resultadoEl = resultadoContainer || document.getElementById('resultado-importacao');
            console.log('[Monitoramento EFD] resultadoContainer existe?', !!resultadoEl);

            if (!resultadoEl) {
                console.error('[Monitoramento EFD] resultadoContainer NAO ENCONTRADO no DOM!');
                return;
            }

            // Preencher dados
            console.log('[Monitoramento EFD] Preenchendo cards...');
            console.log('[Monitoramento EFD] total_participantes:', dados.total_participantes);
            console.log('[Monitoramento EFD] duplicados_identificados:', dados.duplicados_identificados);
            console.log('[Monitoramento EFD] participante_ids:', dados.participante_ids);

            if (resultadoEmpresa) {
                resultadoEmpresa.textContent = dados.cliente_nome || 'Importação concluída';
            }
            if (resultadoTotalParticipantes) {
                const valor = dados.total_participantes || dados.total_processados || 0;
                console.log('[Monitoramento EFD] Setando Total Participantes para:', valor);
                resultadoTotalParticipantes.textContent = valor;
            }
            if (resultadoNovos) {
                const valor = dados.novos_salvos || dados.novos || 0;
                console.log('[Monitoramento EFD] Setando Novos para:', valor);
                resultadoNovos.textContent = valor;
            }
            if (resultadoDuplicados) {
                const valor = dados.duplicados_identificados || 0;
                console.log('[Monitoramento EFD] Setando Duplicados para:', valor);
                resultadoDuplicados.textContent = valor;
            }

            // Exibir notas fiscais extraídas (se houver)
            const resultadoNotas = document.getElementById('resultado-notas');
            const notasExtraidasCount = document.getElementById('notas-extraidas-count');
            const totalNotas = dados.notas_extraidas || dados.total_notas || 0;

            if (totalNotas > 0 && resultadoNotas && notasExtraidasCount) {
                notasExtraidasCount.textContent = totalNotas;
                resultadoNotas.classList.remove('hidden');
                console.log('[Monitoramento EFD] Notas extraídas:', totalNotas);
            } else if (resultadoNotas) {
                resultadoNotas.classList.add('hidden');
            }

            // Guardar ID da importação se disponível nos dados do SSE
            if (dados.importacao_id) {
                importacaoAtualId = dados.importacao_id;
                console.log('[Monitoramento EFD] importacaoAtualId setado para:', importacaoAtualId);
            }

            // Guardar IDs dos participantes se disponível (enviados pelo n8n)
            // Aceita participante_lita_geral_ids (novo) ou participante_ids (legado)
            // Aceita tanto array quanto string separada por vírgulas
            const idsGeral = dados.participante_lita_geral_ids || dados.participante_ids;
            if (idsGeral) {
                if (Array.isArray(idsGeral)) {
                    participanteIdsFromSSE = idsGeral;
                } else if (typeof idsGeral === 'string') {
                    participanteIdsFromSSE = idsGeral.split(',').map(id => parseInt(id.trim(), 10)).filter(id => !isNaN(id));
                }
                if (participanteIdsFromSSE && participanteIdsFromSSE.length > 0) {
                    console.log('[Monitoramento EFD] participanteIdsFromSSE setado, total:', participanteIdsFromSSE.length);
                }
            }

            // Guardar IDs dos participantes NOVOS (pode ser null)
            if (dados.participante_novos_ids) {
                if (Array.isArray(dados.participante_novos_ids)) {
                    novosIdsFromSSE = dados.participante_novos_ids;
                } else if (typeof dados.participante_novos_ids === 'string') {
                    novosIdsFromSSE = dados.participante_novos_ids.split(',').map(id => parseInt(id.trim(), 10)).filter(id => !isNaN(id));
                }
                if (novosIdsFromSSE && novosIdsFromSSE.length > 0) {
                    console.log('[Monitoramento EFD] novosIdsFromSSE setado, total:', novosIdsFromSSE.length);
                }
            }

            // Guardar IDs dos participantes DUPLICADOS/ATUALIZADOS
            if (dados.participante_repetido_ids) {
                if (Array.isArray(dados.participante_repetido_ids)) {
                    duplicadosIdsFromSSE = dados.participante_repetido_ids;
                } else if (typeof dados.participante_repetido_ids === 'string') {
                    duplicadosIdsFromSSE = dados.participante_repetido_ids.split(',').map(id => parseInt(id.trim(), 10)).filter(id => !isNaN(id));
                }
                if (duplicadosIdsFromSSE && duplicadosIdsFromSSE.length > 0) {
                    console.log('[Monitoramento EFD] duplicadosIdsFromSSE setado, total:', duplicadosIdsFromSSE.length);
                }
            }

            // Cliente Associado
            const resultadoCliente         = document.getElementById('resultado-cliente');
            const resultadoClienteNome     = document.getElementById('resultado-cliente-nome');
            const resultadoClienteDocLabel = document.getElementById('resultado-cliente-doc-label');
            const resultadoClienteDoc      = document.getElementById('resultado-cliente-doc');
            const resultadoClienteLink     = document.getElementById('resultado-cliente-link');

            if (dados.cliente_id && resultadoCliente) {
                if (resultadoClienteNome)     resultadoClienteNome.textContent     = dados.cliente_nome || '—';
                if (resultadoClienteDocLabel) resultadoClienteDocLabel.textContent = dados.cliente_tipo_pessoa === 'PJ' ? 'CNPJ' : 'CPF';
                if (resultadoClienteDoc)      resultadoClienteDoc.textContent      = dados.cliente_documento || '—';

                // Link direto ao perfil do cliente
                const clientePerfilUrl = '/app/cliente/' + dados.cliente_id;
                const resultadoClienteNomeLink = document.getElementById('resultado-cliente-nome-link');
                if (resultadoClienteNomeLink) {
                    resultadoClienteNomeLink.href = clientePerfilUrl;
                }
                if (resultadoClienteLink) {
                    resultadoClienteLink.href = clientePerfilUrl;
                }
                resultadoCliente.classList.remove('hidden');
            } else if (resultadoCliente) {
                resultadoCliente.classList.add('hidden');
            }

            // Atualizar link para detalhes da importação
            if (importacaoAtualId && linkFiltrarImportacao) {
                linkFiltrarImportacao.href = '/app/importacao/efd/' + importacaoAtualId;
            }

            // Mini-painel Resumo Final de Notas
            const resumoFinalEl = document.getElementById('resumo-final-notas');
            const resumoFinalContent = document.getElementById('resumo-final-notas-content');
            // dados pode ser o próprio resumo_final (n8n envia JSON.stringify) ou conter subchave
            const rf = dados.resumo_final || (dados.blocos ? dados : null);
            if (rf && resumoFinalEl && resumoFinalContent) {
                resumoFinalContent.innerHTML = renderResumoFinal(rf);
                resumoFinalEl.classList.remove('hidden');

                // Indexar participantes_resumo por participante_id
                if (Array.isArray(rf.participantes_resumo)) {
                    participantesResumoMap = {};
                    rf.participantes_resumo.forEach(pr => {
                        participantesResumoMap[pr.participante_id] = pr;
                    });
                }
            } else if (resumoFinalEl) {
                resumoFinalEl.classList.add('hidden');
            }

            // Resetar lista de participantes
            if (listaParticipantesContainer) listaParticipantesContainer.classList.remove('hidden');
            if (listaParticipantesLoading) listaParticipantesLoading.classList.add('hidden');
            if (listaParticipantesTabela) listaParticipantesTabela.classList.add('hidden');
            participantesPage = 1;

            // Mostrar seção de resultados
            resultadoEl.classList.remove('hidden');

            // Scroll para a seção de resultados
            resultadoEl.scrollIntoView({ behavior: 'smooth', block: 'start' });

            // Atualizar histórico de importações após conclusão
            fetch('/monitoramento/efd', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const novaSecao = doc.getElementById('historico-importacoes');
                const secaoAtual = document.getElementById('historico-importacoes');
                if (novaSecao && secaoAtual) {
                    secaoAtual.replaceWith(novaSecao);
                }
            })
            .catch(() => {});

            // Carregar participantes automaticamente (carregarParticipantes tem guard próprio)
            carregarParticipantes();
        }

        // Função para carregar lista de participantes
        async function carregarParticipantes() {
            // Verificar se temos IDs dos participantes (via SSE) ou ID da importação
            if (!participanteIdsFromSSE && !importacaoAtualId) {
                console.warn('[Monitoramento EFD] Nenhum ID disponível para carregar participantes');
                return;
            }

            // Mostrar loading
            if (listaParticipantesContainer) listaParticipantesContainer.classList.add('hidden');
            if (listaParticipantesLoading) listaParticipantesLoading.classList.remove('hidden');
            if (listaParticipantesTabela) listaParticipantesTabela.classList.add('hidden');

            try {
                let response;

                // Priorizar uso de participante_ids se disponível (mais direto)
                if (participanteIdsFromSSE && participanteIdsFromSSE.length > 0) {
                    response = await fetch('/app/participantes/por-ids', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({
                            ids: participanteIdsFromSSE,
                            page: participantesPage,
                            importacao_id: importacaoAtualId,
                        }),
                    });
                } else {
                    // Fallback: buscar por ID da importação
                    response = await fetch('/app/participantes/por-importacao/' + importacaoAtualId + '?page=' + participantesPage, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                }

                if (!response.ok) {
                    throw new Error('Erro ao carregar participantes: HTTP ' + response.status);
                }

                const data = await response.json();

                // Preencher tabela
                preencherTabelaParticipantes(data.participantes || []);
                participantesTotal = data.total || 0;

                // Retry após 800ms se não encontrou nada na primeira chamada (absorver race condition)
                if (participantesTotal === 0 && participantesPage === 1 && !participanteIdsFromSSE) {
                    setTimeout(() => {
                        if (participantesTotal === 0) carregarParticipantes();
                    }, 800);
                }

                // Atualizar paginação
                atualizarPaginacao(data);

                // Mostrar tabela
                if (listaParticipantesLoading) listaParticipantesLoading.classList.add('hidden');
                if (listaParticipantesTabela) listaParticipantesTabela.classList.remove('hidden');

            } catch (err) {
                console.error('[Monitoramento EFD] Erro ao carregar participantes:', err);
                if (listaParticipantesLoading) listaParticipantesLoading.classList.add('hidden');
                if (listaParticipantesContainer) {
                    listaParticipantesContainer.classList.remove('hidden');
                    listaParticipantesContainer.innerHTML = '<div class="text-center py-8 text-red-500"><p class="text-sm">Erro ao carregar participantes. Tente novamente.</p></div>';
                }
            }
        }

        // Helper: formata valor em BRL
        function formatBRL(valor) {
            return 'R$ ' + Number(valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatDateBR(val) {
            if (!val) return '—';
            var p = val.split('T')[0].split('-');
            return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : val;
        }

        // Helper: renderiza o mini-painel resumo_final
        function renderResumoFinal(rf) {
            if (!rf) return '';
            const blocosRaw = rf.blocos || {};
            // Retrocompatibilidade: aceitar chaves antigas
            const blocos = {};
            blocos.notas_servicos     = blocosRaw.notas_servicos     || blocosRaw.A || null;
            blocos.notas_mercadorias  = blocosRaw.notas_mercadorias  || blocosRaw.C || null;
            blocos.notas_transportes  = blocosRaw.notas_transportes  || blocosRaw.D || null;
            blocos.apuracao_icms      = blocosRaw.apuracao_icms      || null;
            blocos.apuracao_pis_cofins= blocosRaw.apuracao_pis_cofins|| null;
            blocos.retencoes_fonte    = blocosRaw.retencoes_fonte    || null;
            const ordemBlocos = ['notas_servicos', 'notas_mercadorias', 'notas_transportes', 'apuracao_icms', 'apuracao_pis_cofins', 'retencoes_fonte', 'A', 'C', 'D'];
            const nomeBloco = { 
                notas_servicos: 'Notas de Serviço (PIS/COFINS)', 
                notas_mercadorias: 'NF-e Mercadorias (ICMS/IPI)', 
                notas_transportes: 'CT-e Transportes',
                apuracao_icms: 'Apuração ICMS/IPI',
                apuracao_pis_cofins: 'Apuração PIS/COFINS',
                retencoes_fonte: 'Retenções na Fonte',
                A: 'Notas de Serviço (PIS/COFINS)',
                C: 'NF-e Mercadorias (ICMS/IPI)',
                D: 'CT-e Transportes'
            };

            let html = '<div class="space-y-1">';

            // Participantes — normaliza tanto rf.participantes (spec) quanto rf.estatisticas (n8n atual)
            const partRaw = rf.participantes || rf.estatisticas || {};
            const part = {
                total:      partRaw.total      ?? (partRaw.total_participantes_processados ?? 0),
                novos:      partRaw.novos      ?? (partRaw.participantes_novos      ?? 0),
                duplicados: partRaw.duplicados ?? (partRaw.participantes_repetidos  ?? 0),
            };
            html += `<div class="flex items-center justify-between py-1.5 border-b border-gray-100 last:border-0">
                <div class="flex items-center gap-2">
                    <span class="text-green-600 font-bold w-4 text-center">✓</span>
                    <span class="text-gray-700">Participantes</span>
                </div>
                <div class="text-right flex items-center justify-end flex-wrap gap-1 sm:gap-2">
                    <span class="text-gray-900 font-medium">${part.total || 0} registros</span>
                    <span class="text-gray-500 text-xs bg-gray-200/50 px-2 py-0.5 rounded-full">${part.novos || 0} novos · ${part.duplicados || 0} exist.</span>
                </div>
            </div>`;

            // Produtos e Serviços (catálogo 0200)
            if (rf.produtos_servicos) {
                const ps = rf.produtos_servicos;
                html += `<div class="flex items-center justify-between py-1.5 border-b border-gray-100 last:border-0">
                    <div class="flex items-center gap-2">
                        <span class="text-green-600 font-bold w-4 text-center">✓</span>
                        <span class="text-gray-700">Produtos e Serviços</span>
                    </div>
                    <div class="text-right flex items-center justify-end flex-wrap gap-1 sm:gap-2">
                        <span class="text-gray-900 font-medium">${ps.total || 0} itens</span>
                        <span class="text-gray-500 text-xs bg-gray-200/50 px-2 py-0.5 rounded-full">${ps.novos || 0} novos · ${ps.existentes || 0} exist.</span>
                    </div>
                </div>`;
            }

            // Blocos
            ordemBlocos.forEach(b => {
                const bd = blocos[b];
                if (!bd) return;
                const isSkip = bd.total_notas === 0 && bd.valor_total === 0;
                const icon = isSkip ? '<span class="text-gray-400 w-4 text-center">—</span>' : '<span class="text-green-600 font-bold w-4 text-center">✓</span>';
                const labelCount = bd.label_count || 'notas';
                const valor = isSkip ? '<span class="text-gray-400 text-xs">Vazio</span>' : `<span class="text-gray-900 font-medium text-right sm:text-left">${(bd.total_notas || 0)} ${labelCount}</span><span class="text-gray-600 font-mono text-xs sm:ml-2 text-right">R$ ${Number(bd.valor_total||0).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})}</span>`;
                html += `<div class="flex items-center justify-between py-1.5 border-b border-gray-100 last:border-0">
                    <div class="flex items-center gap-2">
                        ${icon}
                        <span class="text-gray-700 truncate">${nomeBloco[b] || 'Bloco ' + b}</span>
                    </div>
                    <div class="text-right flex flex-col sm:flex-row sm:items-center justify-end sm:gap-2 min-w-[120px]">
                        ${valor}
                    </div>
                </div>`;
            });

            // Separador + Totais
            const tot = rf.totais || {};
            html += `<div class="flex items-center justify-between py-2 border-t border-gray-300 mt-1">
                <div class="flex items-center gap-2">
                    <span class="w-4"></span>
                    <span class="text-gray-800 font-bold">Total</span>
                </div>
                <div class="text-right flex flex-col sm:flex-row sm:items-center justify-end sm:gap-2">
                    <span class="text-gray-900 font-bold text-right sm:text-left">${(tot.notas || 0)} notas</span>
                    <span class="text-gray-800 font-mono text-xs sm:ml-2 text-right border-t sm:border-0 border-gray-200 pt-0.5 sm:pt-0 mt-0.5 sm:mt-0">R$ ${Number(tot.valor||0).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})}</span>
                </div>
            </div>`;

            html += '</div>';
            return html;
        }

        // Função para preencher tabela de participantes
        function preencherTabelaParticipantes(participantes) {
            if (!participantesTbody) return;

            participantesTbody.innerHTML = '';

            if (participantes.length === 0) {
                participantesTbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500 text-sm">Nenhum participante encontrado.</td></tr>';
                return;
            }

            // Helper para badge de status da importação (Novo/Atualizado)
            function getStatusImportacaoBadge(participanteId) {
                if (novosIdsFromSSE && novosIdsFromSSE.includes(participanteId)) {
                    return '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white ml-2" style="background-color: #047857">Novo</span>';
                }
                if (duplicadosIdsFromSSE && duplicadosIdsFromSSE.includes(participanteId)) {
                    return '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700 ml-2">Já Registrado</span>';
                }
                return '';
            }

            // Ordenar por volume de valor (entradas + saídas) desc, com qtd de notas como tiebreaker
            participantes.sort((a, b) => {
                const ra = participantesResumoMap[a.id] || {};
                const rb = participantesResumoMap[b.id] || {};
                const aValor = (Number((ra.entradas || {}).valor) || 0) + (Number((ra.saidas || {}).valor) || 0);
                const bValor = (Number((rb.entradas || {}).valor) || 0) + (Number((rb.saidas || {}).valor) || 0);
                if (bValor !== aValor) return bValor - aValor;
                return (rb.total_notas || 0) - (ra.total_notas || 0);
            });

            participantes.forEach(p => {
                const cnpjFormatado = p.cnpj ? p.cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5') : '-';
                const resumo = participantesResumoMap[p.id] || null;
                const temNotas = resumo && resumo.nota_ids && resumo.nota_ids.length > 0;

                const totalNotas = resumo ? (resumo.total_notas || 0) : null;
                const entradas = resumo ? (resumo.entradas || {}) : null;
                const saidas = resumo ? (resumo.saidas || {}) : null;

                const tdNotas    = totalNotas !== null ? `<span class="font-medium text-gray-900">${totalNotas}</span>` : '<span class="text-gray-400">—</span>';
                const tdEntradas = entradas   !== null ? `<span class="text-green-700">${entradas.count || 0}</span><span class="text-xs text-gray-400 ml-1">${formatBRL(entradas.valor)}</span>` : '<span class="text-gray-400">—</span>';
                const tdSaidas   = saidas     !== null ? `<span class="text-amber-700">${saidas.count || 0}</span><span class="text-xs text-gray-400 ml-1">${formatBRL(saidas.valor)}</span>` : '<span class="text-gray-400">—</span>';

                const btnExpand = temNotas
                    ? `<button type="button" class="btn-expand-notas text-gray-600 hover:text-gray-900 text-xs font-medium px-1.5 py-0.5 rounded border border-gray-300 hover:bg-gray-50 transition" data-participante-id="${p.id}" data-expanded="0" title="Ver notas">▶</button>`
                    : '';

                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';
                tr.dataset.participanteId = p.id;
                tr.innerHTML = `
                    <td class="px-3 py-2 text-xs font-mono text-gray-900 whitespace-nowrap">${cnpjFormatado}${getStatusImportacaoBadge(p.id)}</td>
                    <td class="px-3 py-2 text-sm text-gray-900 max-w-[200px] truncate" title="${p.razao_social || ''}">${p.razao_social || '-'}</td>
                    <td class="px-2 py-2 text-center text-xs text-gray-600 w-12">${p.uf || '-'}</td>
                    <td class="px-2 py-2 text-right text-xs">${tdNotas}</td>
                    <td class="px-2 py-2 text-right text-xs">${tdEntradas}</td>
                    <td class="px-2 py-2 text-right text-xs">${tdSaidas}</td>
                    <td class="px-2 py-2 text-right">
                        <div class="flex items-center justify-end gap-2">
                            ${btnExpand}
                            <button
                                type="button"
                                class="btn-monitorar-participante inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors"
                                data-participante-id="${p.id}"
                                data-participante-cnpj="${p.cnpj || ''}"
                                data-tem-plano="${p.monitoramento_ativo ? '1' : '0'}"
                                title="${p.monitoramento_ativo ? 'Consultar agora' : 'Configurar monitoramento'}"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                ${p.monitoramento_ativo ? 'Consultar' : 'Monitorar'}
                            </button>
                            <a href="/app/participante/${p.id}" class="text-xs text-gray-600 hover:text-gray-900 hover:underline" data-link>Ver</a>
                        </div>
                    </td>
                `;
                participantesTbody.appendChild(tr);
            });

            // Handler de expansão inline
            participantesTbody.querySelectorAll('.btn-expand-notas').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const pid = parseInt(this.dataset.participanteId);
                    const expanded = this.dataset.expanded === '1';
                    const parentTr = this.closest('tr');

                    // Fechar se já aberto
                    const existingExpandRow = parentTr.nextElementSibling;
                    if (existingExpandRow && existingExpandRow.classList.contains('expand-notas-row')) {
                        existingExpandRow.remove();
                        this.textContent = '▶';
                        this.dataset.expanded = '0';
                        return;
                    }

                    this.textContent = '▼';
                    this.dataset.expanded = '1';

                    const resumo = participantesResumoMap[pid];
                    if (!resumo) return;

                    const expandTr = document.createElement('tr');
                    expandTr.className = 'expand-notas-row bg-gray-50';
                    expandTr.innerHTML = `<td colspan="7" class="px-4 py-3">
                        <div class="expand-notas-content text-sm">
                            <div class="text-gray-500 text-xs">Carregando notas...</div>
                        </div>
                    </td>`;
                    parentTr.after(expandTr);

                    const contentDiv = expandTr.querySelector('.expand-notas-content');

                    // Dados BI
                    let biHtml = '';
                    if (resumo.bi && Object.keys(resumo.bi).length > 0) {
                        biHtml = '<div class="flex flex-wrap gap-4 mb-2">' +
                            Object.entries(resumo.bi).map(([k, v]) =>
                                `<span class="text-xs text-gray-600"><span class="font-medium text-gray-700">${k.replace(/_/g,' ')}:</span> ${v}</span>`
                            ).join('') + '</div>';
                    }

                    // Carregar notas (com cache)
                    if (!notasCache[pid] && resumo.nota_ids && resumo.nota_ids.length > 0) {
                        try {
                            const params = resumo.nota_ids.map(id => 'ids[]=' + id).join('&');
                            const resp = await fetch('/app/importacao/efd/notas?' + params, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            notasCache[pid] = resp.ok ? await resp.json() : [];
                        } catch (e) {
                            notasCache[pid] = [];
                        }
                    }

                    const notas = notasCache[pid] || [];
                    let notasHtml = '';
                    if (notas.length > 0) {
                        notasHtml = `<div class="overflow-x-auto mt-2">
                            <table class="w-full text-xs border border-gray-200 rounded">
                                <thead class="bg-gray-100"><tr>
                                    <th class="px-2 py-1 text-left text-gray-500">Nº Doc</th>
                                    <th class="px-2 py-1 text-left text-gray-500">Série</th>
                                    <th class="px-2 py-1 text-left text-gray-500">Modelo</th>
                                    <th class="px-2 py-1 text-left text-gray-500">Emissão</th>
                                    <th class="px-2 py-1 text-center text-gray-500">Tipo</th>
                                    <th class="px-2 py-1 text-right text-gray-500">Valor</th>
                                </tr></thead>
                                <tbody class="divide-y divide-gray-200">` +
                                notas.slice(0, 50).map(n => `<tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='/app/notas-fiscais/efd/${n.id}'">
                                    <td class="px-2 py-1 font-mono">${n.numero || '—'}</td>
                                    <td class="px-2 py-1">${n.serie || '—'}</td>
                                    <td class="px-2 py-1">${n.modelo || '—'}</td>
                                    <td class="px-2 py-1">${formatDateBR(n.data_emissao)}</td>
                                    <td class="px-2 py-1 text-center">${n.tipo_operacao === '1' ? '<span class="text-green-700">E</span>' : '<span class="text-amber-700">S</span>'}</td>
                                    <td class="px-2 py-1 text-right">${formatBRL(n.valor_total)}</td>
                                </tr>`).join('') +
                                `</tbody></table>` +
                                (notas.length > 50 ? `<p class="text-xs text-gray-400 mt-1">Mostrando 50 de ${notas.length} notas.</p>` : '') +
                            `</div>`;
                    } else {
                        notasHtml = '<p class="text-xs text-gray-400 mt-2">Nenhuma nota disponível.</p>';
                    }

                    contentDiv.innerHTML = biHtml + notasHtml;
                });
            });
        }

        // Função para atualizar paginação
        function atualizarPaginacao(data) {
            const infoEl = document.getElementById('participantes-info');
            const btnPrev = document.getElementById('btn-prev-page');
            const btnNext = document.getElementById('btn-next-page');

            if (infoEl) {
                const start = ((data.current_page || 1) - 1) * (data.per_page || 10) + 1;
                const end = Math.min(start + (data.participantes?.length || 0) - 1, data.total || 0);
                infoEl.textContent = 'Mostrando ' + start + '-' + end + ' de ' + (data.total || 0);
            }

            if (btnPrev) {
                btnPrev.disabled = !data.prev_page_url;
                btnPrev.onclick = function() {
                    if (data.prev_page_url) {
                        participantesPage--;
                        carregarParticipantes();
                    }
                };
            }

            if (btnNext) {
                btnNext.disabled = !data.next_page_url;
                btnNext.onclick = function() {
                    if (data.next_page_url) {
                        participantesPage++;
                        carregarParticipantes();
                    }
                };
            }
        }

        // Event listeners para seção de resultados
        if (btnNovaImportacao) {
            btnNovaImportacao.addEventListener('click', function() {
                // Resetar flag de importação em andamento (CRÍTICO)
                importacaoEmAndamento = false;
                // Fechar SSE se ainda estiver aberto
                if (eventSourceTxt) {
                    eventSourceTxt.close();
                    eventSourceTxt = null;
                }
                // Ocultar seção de resultados
                if (resultadoContainer) resultadoContainer.classList.add('hidden');
                // Ocultar seção de progresso
                ocultarProgresso();
                // Resetar formulário
                resetarProgresso();
                // Limpar IDs armazenados
                importacaoAtualId = null;
                participanteIdsFromSSE = null;
                novosIdsFromSSE = null;
                duplicadosIdsFromSSE = null;
                // Limpar arquivo selecionado
                if (txtFileInput) txtFileInput.value = '';
                const txtFileMeta = document.getElementById('txt-file-meta');
                if (txtFileMeta) txtFileMeta.classList.add('hidden');
                const txtDropzone = document.getElementById('txt-dropzone');
                if (txtDropzone) txtDropzone.classList.remove('hidden');
                // Habilitar botão importar
                if (txtImportarBtn) {
                    txtImportarBtn.disabled = true;
                    txtImportarBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg> Importar';
                }
            });
        }

        if (btnCarregarParticipantes) {
            btnCarregarParticipantes.addEventListener('click', carregarParticipantes);
        }

        // Função para conectar ao SSE (novo formato com tab_id)
        function conectarSSE() {
            if (eventSourceTxt) {
                eventSourceTxt.close();
            }

            const sseUrl = '/app/importacao/efd/progresso/stream?tab_id=' + encodeURIComponent(tabId);
            console.log('[Monitoramento EFD] Conectando ao SSE:', sseUrl);
            eventSourceTxt = new EventSource(sseUrl);

            eventSourceTxt.onopen = function() {
                reconnectAttempts = 0;
                console.log('[Monitoramento EFD] SSE conectado');
            };

            eventSourceTxt.onmessage = function(event) {
                let statusConcluido = false;
                try {
                    const dados = JSON.parse(event.data);
                    console.log('[Monitoramento EFD] Dados SSE:', dados);
                    atualizarProgresso(dados);

                    if (dados.status === 'concluido') {
                        // Usa mensagem do n8n ou monta mensagem com dados
                        const dadosN8n = dados.dados || {};
                        // resumo_final vem no top-level do SSE (gravado por receiveNotasEfdProgress)
                        if (dados.resumo_final && !dadosN8n.resumo_final) {
                            dadosN8n.resumo_final = dados.resumo_final;
                        }

                        const temResumoFinal = !!(dados.resumo_final || dadosN8n.resumo_final);

                        // Sem resumo_final: fase 1 concluída mas notas ainda em andamento.
                        // NÃO fechar SSE, NÃO setar importacaoEmAndamento=false, NÃO mostrar resultados.
                        if (!temResumoFinal) {
                            console.log('[Monitoramento EFD] Status concluido sem resumo_final — aguardando fase de notas');
                            return;
                        }

                        // Conclusão real — temos todos os dados
                        statusConcluido = true;
                        importacaoEmAndamento = false;

                        // Parar timer e exibir tempo final formatado
                        pararTimer();
                        const timerDisplayEl = document.getElementById('timer-display');
                        if (timerDisplayEl && timerStartTime) {
                            const totalSec = Math.floor((Date.now() - timerStartTime) / 1000);
                            timerDisplayEl.textContent = formatarTempoFinal(totalSec);
                        }

                        // Popular campos flat de stats a partir de resumo_final.estatisticas
                        // quando os campos diretos estão ausentes (fase 2 é a fonte de verdade)
                        if (dadosN8n.resumo_final?.estatisticas) {
                            const s = dadosN8n.resumo_final.estatisticas;
                            if (!dadosN8n.total_participantes && !dadosN8n.total_processados) {
                                dadosN8n.total_participantes = s.total_participantes_processados || 0;
                            }
                            if (!dadosN8n.novos_salvos && !dadosN8n.novos) {
                                dadosN8n.novos_salvos = s.participantes_novos || 0;
                            }
                            if (!dadosN8n.duplicados_identificados) {
                                dadosN8n.duplicados_identificados = s.participantes_repetidos || 0;
                            }
                        }
                        // Fallback: stats via resumo_final.participantes (spec canônico)
                        if (dadosN8n.resumo_final?.participantes) {
                            const p = dadosN8n.resumo_final.participantes;
                            if (!dadosN8n.total_participantes && !dadosN8n.total_processados) {
                                dadosN8n.total_participantes = p.total || 0;
                            }
                            if (!dadosN8n.novos_salvos && !dadosN8n.novos) {
                                dadosN8n.novos_salvos = p.novos || 0;
                            }
                            if (!dadosN8n.duplicados_identificados) {
                                dadosN8n.duplicados_identificados = p.duplicados || 0;
                            }
                        }
                        // Fallback: estatisticas no nível do payload SSE (não dentro de resumo_final)
                        if (!dadosN8n.resumo_final?.estatisticas && dadosN8n.estatisticas) {
                            const s = dadosN8n.estatisticas;
                            if (!dadosN8n.total_participantes && !dadosN8n.total_processados) {
                                dadosN8n.total_participantes = s.total_participantes_processados || 0;
                            }
                            if (!dadosN8n.novos_salvos && !dadosN8n.novos) {
                                dadosN8n.novos_salvos = s.participantes_novos || 0;
                            }
                            if (!dadosN8n.duplicados_identificados) {
                                dadosN8n.duplicados_identificados = s.participantes_repetidos || 0;
                            }
                        }
                        // Fallback: notas extraídas a partir de resumo_final.totais
                        if (!dadosN8n.notas_extraidas && !dadosN8n.total_notas && dadosN8n.resumo_final?.totais?.notas) {
                            dadosN8n.notas_extraidas = dadosN8n.resumo_final.totais.notas;
                        }

                        // Fechar SSE — conclusão real com resumo_final
                        if (eventSourceTxt) {
                            eventSourceTxt.close();
                            eventSourceTxt = null;
                        }

                        console.log('[Monitoramento EFD] Status concluido - dadosN8n:', dadosN8n);

                        // Toast apenas na primeira vez (SSE pode enviar vários concluido)
                        if (!toastConcluidoMostrado) {
                            toastConcluidoMostrado = true;
                            const totalImportados = dadosN8n.novos_salvos || dadosN8n.total_a_analisar || 0;
                            const mensagemSucesso = dados.mensagem || ('Importação concluída! ' + totalImportados + ' novos participantes adicionados.');
                            if (window.showToast) {
                                window.showToast(mensagemSucesso, 'success');
                            }
                        }

                        // Mostrar seção de resultados em vez de redirecionar
                        console.log('[Monitoramento EFD] Chamando mostrarResultadoImportacao com:', dadosN8n);
                        mostrarResultadoImportacao(dadosN8n);
                    } else if (dados.status === 'erro' || dados.status === 'timeout') {
                        if (eventSourceTxt) {
                            eventSourceTxt.close();
                            eventSourceTxt = null;
                        }
                        importacaoEmAndamento = false;
                        pararTimer();

                        // Erro/timeout é tratado pelo atualizarProgresso que mostra a seção de erro
                        // Não redireciona automaticamente - usuário decide via botão "Tentar Novamente"
                    }
                } catch (e) {
                    console.error('[Monitoramento EFD] Erro ao parsear SSE:', e);
                }
                // Safety net FORA do try/catch — garante exibição mesmo se mostrarResultadoImportacao lançar exceção
                if (statusConcluido) {
                    const safeResult = document.getElementById('resultado-importacao');
                    if (safeResult) safeResult.classList.remove('hidden');
                }
            };

            eventSourceTxt.onerror = function() {
                const tentativas = reconnectAttempts;

                eventSourceTxt.close();
                eventSourceTxt = null;

                if (!importacaoEmAndamento) return;

                if (tentativas < MAX_RECONEXOES) {
                    reconnectAttempts++;
                    const delay = DELAY_RECONEXAO_BASE * Math.pow(2, tentativas);
                    console.warn('[EFD] SSE desconectado, tentativa ' + reconnectAttempts + '/' + MAX_RECONEXOES + ' em ' + delay + 'ms');

                    reconnectTimer = setTimeout(() => {
                        reconnectTimer = null;
                        if (importacaoEmAndamento) conectarSSE();
                    }, delay);
                } else {
                    reconnectAttempts = 0;
                    importacaoEmAndamento = false;
                    atualizarProgresso({
                        status: 'erro',
                        progresso: 0,
                        mensagem: 'Erro na conexão',
                        error_message: 'Não foi possível manter conexão com o servidor após ' + MAX_RECONEXOES + ' tentativas. Verifique sua internet.'
                    });
                }
            };
        }

        // Reconectar SSE ao voltar à aba se importação ainda estiver em andamento
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && importacaoEmAndamento) {
                if (!eventSourceTxt || eventSourceTxt.readyState === EventSource.CLOSED) {
                    console.log('[Monitoramento EFD] Aba reativada — reconectando SSE');
                    reconnectAttempts = 0;
                    conectarSSE();
                }
            }
        });

        // Botão importar - funcionalidade real
        if (txtImportarBtn) {
            txtImportarBtn.addEventListener('click', async function() {
                const tipoEfd = getSelectedTipoEfd();
                if (!tipoEfd) {
                    if (window.showToast) {
                        window.showToast('Selecione o tipo de EFD antes de importar.', 'error');
                    } else {
                        alert('Selecione o tipo de EFD antes de importar.');
                    }
                    return;
                }

                if (!txtFileInput || !txtFileInput.files || txtFileInput.files.length === 0) {
                    if (window.showToast) {
                        window.showToast('Selecione um arquivo .txt para importar.', 'error');
                    } else {
                        alert('Selecione um arquivo .txt para importar.');
                    }
                    return;
                }

                if (importacaoEmAndamento) {
                    if (window.showToast) {
                        window.showToast('Aguarde a importação em andamento terminar.', 'warning');
                    }
                    return;
                }

                // Desabilitar botão e mostrar loading
                txtImportarBtn.disabled = true;
                txtImportarBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg> Enviando...';

                try {
                    const formData = new FormData();
                    formData.append('arquivo', txtFileInput.files[0]);
                    formData.append('tipo_efd', tipoEfd === 'efd-fiscal' ? 'EFD ICMS/IPI' : 'EFD PIS/COFINS');
                    formData.append('tab_id', tabId);

                    // Extração completa sempre
                    importandoComNotas = true;
                    importandoComCatalogo = true;
                    tipoEfdAtual = tipoEfd;

                    const response = await fetch('/app/importacao/efd/importar-txt', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.error || data.message || 'Erro ao enviar arquivo');
                    }

                    console.log('[Monitoramento EFD] Arquivo enviado com tab_id:', tabId);

                    // Guardar ID da importação retornado pelo n8n
                    if (data.importacao_id) {
                        importacaoAtualId = data.importacao_id;
                        console.log('[Monitoramento EFD] Importação ID:', importacaoAtualId);
                    }

                    // Marcar como em andamento
                    importacaoEmAndamento = true;
                    toastConcluidoMostrado = false;

                    // Mostrar UI de progresso
                    resetarProgresso();
                    // Mostrar badges filtrados por tipo EFD (DEPOIS do reset que oculta tudo)
                    mostrarEtapasPorTipoEfd(tipoEfdAtual);
                    mostrarProgresso();
                    iniciarTimer();

                    // Conectar ao SSE para receber atualizações (usa tabId do escopo)
                    conectarSSE();

                } catch (err) {
                    console.error('[Monitoramento EFD] Erro ao enviar arquivo:', err);
                    if (window.showToast) {
                        window.showToast(err.message || 'Erro ao enviar arquivo.', 'error');
                    } else {
                        alert(err.message || 'Erro ao enviar arquivo.');
                    }
                } finally {
                    // Restaurar botão
                    txtImportarBtn.disabled = false;
                    txtImportarBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg> Importar';
                    updateImportButtonState();
                }
            });
        }

        // Cleanup ao sair da página (para SPA)
        window._cleanupFunctions = window._cleanupFunctions || {};
        window._cleanupFunctions.initImportacaoEfd = function() {
            pararTimer();
            if (reconnectTimer !== null) {
                clearTimeout(reconnectTimer);
                reconnectTimer = null;
            }
            if (eventSourceTxt) {
                eventSourceTxt.close();
                eventSourceTxt = null;
            }
        };

        // Botão "Tentar Novamente" na seção de erro
        const btnTentarNovamente = document.getElementById('btn-tentar-novamente');
        if (btnTentarNovamente) {
            btnTentarNovamente.addEventListener('click', function() {
                // Resetar flag de importação em andamento (CRÍTICO)
                importacaoEmAndamento = false;
                // Cancelar timer de reconexão pendente
                if (reconnectTimer !== null) {
                    clearTimeout(reconnectTimer);
                    reconnectTimer = null;
                }
                reconnectAttempts = 0;
                // Fechar SSE se ainda estiver aberto
                if (eventSourceTxt) {
                    eventSourceTxt.close();
                    eventSourceTxt = null;
                }
                // CRÍTICO: Regenerar tabId para evitar receber dados de erro do cache anterior
                regenerarTabId();
                ocultarProgresso();
                limparArquivoTxt();
                resetarProgresso();
                // Limpar IDs armazenados
                importacaoAtualId = null;
                participanteIdsFromSSE = null;
                novosIdsFromSSE = null;
                duplicadosIdsFromSSE = null;
            });
        }

        // Inicializar estado inicial
        updateTipoEfdLabels();
        updateDropzoneState();
        updateImportButtonState();

        // =====================================================
        // MONITORAR PARTICIPANTE INDIVIDUAL (delegação de eventos)
        // =====================================================

        const modalMonitorarIndividualEfd = document.getElementById('modal-monitorar-individual-efd');

        // Event delegation para botões dinâmicos
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-monitorar-participante');
            if (!btn) return;

            const participanteId = btn.dataset.participanteId;
            const cnpj = btn.dataset.participanteCnpj;
            const temPlano = btn.dataset.temPlano === '1';
            const row = btn.closest('tr');
            const razaoSocial = row ? row.querySelector('td:nth-child(2)')?.textContent?.trim() : '';

            // Formatar CNPJ
            const cnpjFormatado = cnpj ? cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5') : '';

            if (temPlano) {
                // Já tem plano - executar consulta
                if (confirm('Executar consulta agora para este participante?\n\nCNPJ: ' + cnpjFormatado)) {
                    executarConsultaEfd(participanteId);
                }
            } else {
                // Não tem plano - abrir modal
                if (modalMonitorarIndividualEfd) {
                    document.getElementById('modal-monitorar-cnpj-efd').textContent = cnpjFormatado;
                    document.getElementById('modal-monitorar-razao-efd').textContent = razaoSocial || '-';
                    document.getElementById('modal-monitorar-participante-id-efd').value = participanteId;
                    document.getElementById('modal-monitorar-custo-efd').textContent = '0 créditos';
                    document.getElementById('btn-confirmar-monitorar-efd').disabled = true;

                    // Limpar seleção anterior
                    document.querySelectorAll('input[name="plano_selecionado_efd"]').forEach(r => r.checked = false);

                    modalMonitorarIndividualEfd.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            }
        });

        // Atualizar custo quando selecionar plano
        document.querySelectorAll('input[name="plano_selecionado_efd"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.getElementById('modal-monitorar-custo-efd').textContent = radio.dataset.creditos + ' créditos';
                document.getElementById('btn-confirmar-monitorar-efd').disabled = false;
            });
        });

        // Fechar modal
        if (modalMonitorarIndividualEfd) {
            modalMonitorarIndividualEfd.addEventListener('click', function(e) {
                if (e.target === modalMonitorarIndividualEfd || e.target.closest('.modal-close')) {
                    modalMonitorarIndividualEfd.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        }

        // Confirmar monitoramento
        const btnConfirmarMonitorarEfd = document.getElementById('btn-confirmar-monitorar-efd');
        if (btnConfirmarMonitorarEfd) {
            btnConfirmarMonitorarEfd.addEventListener('click', async function() {
                const participanteId = document.getElementById('modal-monitorar-participante-id-efd').value;
                const planoSelecionado = document.querySelector('input[name="plano_selecionado_efd"]:checked');

                if (!participanteId || !planoSelecionado) {
                    alert('Selecione um plano de monitoramento.');
                    return;
                }

                try {
                    btnConfirmarMonitorarEfd.disabled = true;
                    btnConfirmarMonitorarEfd.textContent = 'Ativando...';

                    const response = await fetch('/app/participante/' + participanteId + '/ativar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ plano: planoSelecionado.value }),
                    });

                    const data = await response.json();

                    if (data.success) {
                        modalMonitorarIndividualEfd.classList.add('hidden');
                        document.body.style.overflow = '';
                        alert('Monitoramento ativado com sucesso!');
                        // Atualizar botão na tabela
                        const btn = document.querySelector('.btn-monitorar-participante[data-participante-id="' + participanteId + '"]');
                        if (btn) {
                            btn.dataset.temPlano = '1';
                            btn.title = 'Consultar agora';
                            btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg> Consultar';
                        }
                    } else {
                        alert(data.error || 'Erro ao ativar monitoramento.');
                    }
                } catch (error) {
                    console.error('Erro ao ativar monitoramento:', error);
                    alert('Erro ao ativar monitoramento. Tente novamente.');
                } finally {
                    btnConfirmarMonitorarEfd.disabled = false;
                    btnConfirmarMonitorarEfd.textContent = 'Ativar Monitoramento';
                }
            });
        }

        // Função para executar consulta
        async function executarConsultaEfd(participanteId) {
            try {
                const response = await fetch('/app/participante/' + participanteId + '/consultar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();

                if (data.success) {
                    alert('Consulta iniciada com sucesso! Os resultados serão atualizados em breve.');
                } else {
                    alert(data.error || 'Erro ao executar consulta.');
                }
            } catch (error) {
                console.error('Erro ao executar consulta:', error);
                alert('Erro ao executar consulta. Tente novamente.');
            }
        }

        console.log('[Monitoramento EFD] Inicializacao concluida');
    }

    // Auto-inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            try { initImportacaoEfd(); } catch(e) { console.error('[EFD] Erro init:', e); }
        }, { once: true });
    } else {
        try { initImportacaoEfd(); } catch(e) { console.error('[EFD] Erro init:', e); }
    }

    // Expor globalmente para SPA
    window.initImportacaoEfd = function() {
        try { initImportacaoEfd(); } catch(e) { console.error('[EFD] Erro init:', e); }
    };
})();
</script>
