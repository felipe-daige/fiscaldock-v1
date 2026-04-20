{{-- Monitoramento - Importar XMLs --}}
<div class="min-h-screen bg-gray-100" id="importacao-xml-container" data-em-breve="1">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        {{-- Page Header --}}
        <div class="mb-4 sm:mb-6 flex items-center justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Importação de XMLs</h1>
                <p class="text-xs text-gray-500 mt-0.5">Extraia CNPJs de NF-e, NFS-e ou CT-e e adicione à sua lista de monitoramento.</p>
            </div>
            <a
                href="/app/dashboard"
                class="inline-flex items-center gap-2 px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-600 text-xs font-medium hover:bg-gray-50 transition"
                data-link
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar
            </a>
        </div>

        <div class="mb-6">
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="border-l-4 border-l-amber-500 px-4 py-4 sm:px-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded border border-amber-200 bg-amber-50 text-amber-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"></path>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Alerta Operacional</p>
                            <h2 class="mt-1 text-base font-bold text-gray-900 uppercase tracking-wide">Importação XML indisponível</h2>
                            <p class="mt-1 text-sm text-gray-700">Esta tela está marcada como <strong>Em Breve</strong>. A funcionalidade ainda não está em desenvolvimento para uso operacional e todos os botões de importação permanecem desabilitados.</p>
                            <p class="mt-1 text-xs text-gray-500">Você ainda pode consultar o histórico e voltar para outras áreas do painel normalmente.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="importacao-xml-error-region" class="mb-6"></div>

        {{-- Upload Section --}}
        <div id="upload-section" class="mb-6 space-y-4 sm:space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded border border-gray-300 overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Upload dos Arquivos</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">Em Breve</span>
                        </div>
                        <div class="p-4 sm:p-5">
                            <div class="mb-4">
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Tipo de Documento</label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <label class="flex flex-col items-start gap-1 p-3 border border-gray-300 rounded cursor-pointer transition tipo-doc-label has-[:checked]:border-gray-700 has-[:checked]:bg-gray-50 hover:border-gray-400" data-tipo="NFE">
                                        <input type="radio" name="tipo-documento" value="NFE" class="sr-only" disabled>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #0f766e">NF-e</span>
                                        <span class="text-[11px] text-gray-500 leading-tight">Nota Fiscal Eletrônica</span>
                                    </label>
                                    <label class="flex flex-col items-start gap-1 p-3 border border-gray-300 rounded cursor-pointer transition tipo-doc-label has-[:checked]:border-gray-700 has-[:checked]:bg-gray-50 hover:border-gray-400" data-tipo="NFSE">
                                        <input type="radio" name="tipo-documento" value="NFSE" class="sr-only" disabled>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #4338ca">NFS-e</span>
                                        <span class="text-[11px] text-gray-500 leading-tight">Nota Fiscal de Serviços</span>
                                    </label>
                                    <label class="flex flex-col items-start gap-1 p-3 border border-gray-300 rounded cursor-pointer transition tipo-doc-label has-[:checked]:border-gray-700 has-[:checked]:bg-gray-50 hover:border-gray-400" data-tipo="CTE">
                                        <input type="radio" name="tipo-documento" value="CTE" class="sr-only" disabled>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">CT-e</span>
                                        <span class="text-[11px] text-gray-500 leading-tight">Conhecimento de Transporte</span>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Modo de Envio</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label class="flex flex-col items-start gap-1 p-3 border border-gray-300 rounded cursor-pointer transition modo-envio-label has-[:checked]:border-gray-700 has-[:checked]:bg-gray-50 hover:border-gray-400" data-modo="zip">
                                        <input type="radio" name="modo-envio" value="zip" class="sr-only" disabled>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-gray-900">Arquivo ZIP</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">50 MB</span>
                                        </div>
                                        <span class="text-[11px] text-gray-500 leading-tight">Até 5.000 XMLs em um ZIP</span>
                                    </label>
                                    <label class="flex flex-col items-start gap-1 p-3 border border-gray-300 rounded cursor-pointer transition modo-envio-label has-[:checked]:border-gray-700 has-[:checked]:bg-gray-50 hover:border-gray-400" data-modo="xml">
                                        <input type="radio" name="modo-envio" value="xml" class="sr-only" disabled>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-gray-900">XMLs Avulsos</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">100 arq</span>
                                        </div>
                                        <span class="text-[11px] text-gray-500 leading-tight">Até 100 arquivos por lote</span>
                                    </label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div id="xml-dropzone" class="border-2 border-dashed border-gray-300 rounded p-8 min-h-[180px] flex flex-col items-center justify-center transition-colors cursor-not-allowed bg-gray-50 opacity-60 pointer-events-none" role="button" tabindex="0" aria-disabled="true">
                                    <div class="mb-3" id="xml-dropzone-icon">
                                        <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                    </div>
                                    <div class="space-y-1 text-center">
                                        <p class="text-sm font-medium text-gray-600" id="xml-dropzone-main-text">Importação XML temporariamente indisponível</p>
                                        <p class="text-[11px] text-gray-400" id="xml-dropzone-sub-text">Esta área será liberada quando o desenvolvimento operacional estiver concluído</p>
                                        <p class="text-[11px] text-gray-400 mt-2">Nenhum arquivo pode ser enviado no momento</p>
                                    </div>
                                    <input
                                        type="file"
                                        id="xml-file-input"
                                        name="xml_files"
                                        accept=".xml,.zip"
                                        multiple
                                        class="hidden"
                                        disabled
                                    >
                                </div>
                            </div>

                            <div id="xml-files-list" class="mb-4 hidden">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Arquivos selecionados</span>
                                    <button type="button" id="xml-clear-all" class="text-[11px] text-gray-500 hover:text-gray-700 hover:underline">Limpar todos</button>
                                </div>
                                <div id="xml-files-container" class="space-y-1 max-h-[200px] overflow-y-auto"></div>
                                <div class="mt-2 pt-2 border-t border-gray-200 flex items-center justify-between text-[11px] text-gray-500">
                                    <span id="xml-files-count">0 arquivos</span>
                                    <span id="xml-files-size">0 MB</span>
                                </div>
                            </div>

                            <div id="xml-error-message" class="mb-4 hidden">
                                <div class="bg-white border border-gray-300 border-l-4 border-l-red-500 p-3">
                                    <p class="text-xs text-gray-700" id="xml-error-text"></p>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="button"
                                    id="xml-importar-btn"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded bg-gray-800 text-white text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                    </svg>
                                    <span class="btn-text">Em Breve</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-white rounded border border-gray-300 overflow-hidden h-full">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Como Funciona</span>
                        </div>
                        <div class="p-4 sm:p-5 space-y-3">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-6 h-6 rounded border border-gray-300 bg-gray-50 text-gray-700 flex items-center justify-center text-xs font-bold">1</div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Selecione o Tipo</p>
                                    <p class="text-[11px] text-gray-500">Escolha entre NF-e, NFS-e ou CT-e conforme o lote que será importado.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-6 h-6 rounded border border-gray-300 bg-gray-50 text-gray-700 flex items-center justify-center text-xs font-bold">2</div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Escolha o Modo</p>
                                    <p class="text-[11px] text-gray-500">Envie um ZIP consolidado ou um conjunto de XMLs avulsos.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-6 h-6 rounded border border-gray-300 bg-gray-50 text-gray-700 flex items-center justify-center text-xs font-bold">3</div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Envie os Arquivos</p>
                                    <p class="text-[11px] text-gray-500">Arraste ou selecione os arquivos para iniciar a validação do lote.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-6 h-6 rounded border border-gray-300 bg-gray-50 text-gray-700 flex items-center justify-center text-xs font-bold">4</div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Extração Fiscal</p>
                                    <p class="text-[11px] text-gray-500">O sistema identifica participantes, notas e movimentações compatíveis com o documento enviado.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-6 h-6 rounded border border-gray-300 text-white flex items-center justify-center text-xs font-bold" style="background-color: #047857">5</div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Revisão Operacional</p>
                                    <p class="text-[11px] text-gray-500">Ao concluir, revise os resultados e avance com monitoramento e consultas dos participantes importados.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Últimas Importações</span>
                        <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ min(($importacoes ?? collect())->count(), 4) }}</span>
                    </div>
                    <a href="/app/importacao/historico" data-link class="text-[10px] font-semibold text-gray-600 hover:text-gray-900 hover:underline uppercase tracking-wide">Abrir histórico</a>
                </div>
                @if(($importacoes ?? collect())->isEmpty())
                    <div class="p-4 sm:p-5">
                        <p class="text-sm text-gray-700">Nenhuma importação registrada ainda.</p>
                        <p class="text-xs text-gray-500 mt-1">As próximas operações concluídas aparecerão aqui.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach(($importacoes ?? collect())->take(4) as $recentImp)
                            @php
                                $recentTipoBadge = match(strtoupper($recentImp->tipo_documento ?? '')) {
                                    'NFE' => ['label' => 'NF-e', 'hex' => '#0f766e'],
                                    'NFSE' => ['label' => 'NFS-e', 'hex' => '#4338ca'],
                                    'CTE' => ['label' => 'CT-e', 'hex' => '#374151'],
                                    default => ['label' => 'XML', 'hex' => '#374151'],
                                };
                                $recentStatus = match($recentImp->status) {
                                    'concluido' => ['label' => 'Concluído', 'hex' => '#047857'],
                                    'processando' => ['label' => 'Processando', 'hex' => '#d97706'],
                                    'erro' => ['label' => 'Erro', 'hex' => '#dc2626'],
                                    default => ['label' => 'Pendente', 'hex' => '#9ca3af'],
                                };
                            @endphp
                            <a href="/app/importacao/xml/{{ $recentImp->id }}" data-link class="block px-4 py-3 hover:bg-gray-50/50 transition-colors">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $recentTipoBadge['hex'] }}">{{ $recentTipoBadge['label'] }}</span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $recentStatus['hex'] }}">{{ $recentStatus['label'] }}</span>
                                        </div>
                                        <p class="text-sm text-gray-900 mt-2 truncate">{{ $recentImp->arquivo ?? ('Importação #' . $recentImp->id) }}</p>
                                        <p class="text-[11px] text-gray-500 mt-1">
                                            {{ optional($recentImp->created_at)->format('d/m/Y H:i') }}
                                            @if(!empty($recentImp->total_xmls))
                                                · {{ number_format($recentImp->total_xmls, 0, ',', '.') }} XMLs
                                            @endif
                                        </p>
                                    </div>
                                    <span class="text-[10px] text-gray-500 uppercase tracking-wide whitespace-nowrap">{{ optional($recentImp->created_at)->diffForHumans() }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Secao de Progresso (inicialmente oculta) --}}
        <div id="importacao-progresso" class="hidden">
            <div id="progresso-card" class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Processamento</span>
                </div>
                <div class="p-4">
                    {{-- Header --}}
                    <div class="flex items-start gap-3 mb-4">
                        <div id="progresso-icon" class="w-9 h-9 rounded border border-gray-300 bg-gray-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-700 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 id="progresso-titulo" class="text-sm font-bold text-gray-900 uppercase tracking-wide truncate">
                                Processando XMLs...
                            </h3>
                            <p id="progresso-subtitulo" class="text-[11px] text-gray-500">
                                Aguarde enquanto os arquivos são processados.
                            </p>
                        </div>
                    </div>

                    {{-- Barra de progresso --}}
                    <div class="mb-4">
                        <div class="flex justify-between text-[11px] mb-1">
                            <span id="progresso-mensagem" class="text-gray-600">Iniciando...</span>
                            <span id="progresso-porcentagem" class="font-semibold text-gray-900">0%</span>
                        </div>
                        <div class="bg-gray-200 rounded h-2 overflow-hidden">
                            <div id="barra-progresso" class="bg-gray-800 h-full transition-all duration-500 ease-out" style="width: 0%"></div>
                        </div>
                    </div>

                    {{-- Stats em tempo real --}}
                    <div id="progresso-stats" class="grid grid-cols-5 divide-x divide-gray-200 border border-gray-200 rounded mb-4 hidden">
                        <div class="text-center px-2 py-3">
                            <p class="text-lg font-bold text-gray-900" id="stat-xmls-processados">0</p>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Processados</p>
                        </div>
                        <div class="text-center px-2 py-3">
                            <p class="text-lg font-bold text-gray-900" id="stat-notas-novas">0</p>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Novas</p>
                        </div>
                        <div class="text-center px-2 py-3">
                            <p class="text-lg font-bold text-gray-900" id="stat-notas-duplicadas">0</p>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Duplicadas</p>
                        </div>
                        <div class="text-center px-2 py-3">
                            <p class="text-lg font-bold text-gray-900" id="stat-participantes-novos">0</p>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Part. Novos</p>
                        </div>
                        <div class="text-center px-2 py-3">
                            <p class="text-lg font-bold text-gray-900" id="stat-erros">0</p>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Erros</p>
                        </div>
                    </div>

                    {{-- Nota atual sendo processada --}}
                    <div id="progresso-nota-atual" class="hidden mb-4 bg-white border border-gray-300 border-l-4 border-l-gray-400 p-3">
                        <div class="flex items-center gap-2 text-xs">
                            <svg class="w-3.5 h-3.5 text-gray-500 animate-pulse flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-gray-700 font-medium" id="nota-atual-info">-</span>
                        </div>
                    </div>

                    {{-- Secao de Erro --}}
                    <div id="progresso-erro" class="hidden pt-3 border-t border-gray-200">
                        <div class="bg-white border border-gray-300 border-l-4 border-l-red-500 p-3 mb-3">
                            <p id="progresso-erro-msg" class="text-xs text-gray-700">
                                Ocorreu um erro durante o processamento.
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button type="button"
                                    id="btn-tentar-novamente"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-600 text-xs font-medium hover:bg-gray-50 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Tentar Novamente
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Secao de Resultados (aparece apos importacao concluida) --}}
            <div id="resultado-importacao" class="hidden mt-4">
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    {{-- Header dos Resultados --}}
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Importação Concluída</span>
                            <span class="text-[10px] text-gray-400" id="resultado-resumo"></span>
                        </div>
                        <button
                            type="button"
                            id="btn-nova-importacao"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded text-[11px] font-medium bg-gray-800 text-white hover:bg-gray-700 transition"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nova Importação
                        </button>
                    </div>

                    {{-- Metricas Principais --}}
                    <div class="grid grid-cols-2 sm:grid-cols-5 divide-x divide-y sm:divide-y-0 divide-gray-200 border-b border-gray-200">
                        <div class="px-4 py-3 text-center">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">XMLs Proc.</p>
                            <p class="text-lg font-bold text-gray-900 mt-0.5" id="resultado-xmls">0</p>
                        </div>
                        <div class="px-4 py-3 text-center">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas Novas</p>
                            <p class="text-lg font-bold text-gray-900 mt-0.5" id="resultado-notas-novas">0</p>
                        </div>
                        <div class="px-4 py-3 text-center">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Duplicadas</p>
                            <p class="text-lg font-bold text-gray-900 mt-0.5" id="resultado-notas-duplicadas">0</p>
                        </div>
                        <div class="px-4 py-3 text-center">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participantes</p>
                            <p class="text-lg font-bold text-gray-900 mt-0.5" id="resultado-total-participantes">0</p>
                        </div>
                        <div class="px-4 py-3 text-center col-span-2 sm:col-span-1">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Com Erro</p>
                            <p class="text-lg font-bold text-gray-900 mt-0.5" id="resultado-xmls-erro">0</p>
                        </div>
                    </div>

                    {{-- CNPJs Novos --}}
                    <div id="resultado-cnpjs-novos-container" class="hidden px-4 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between mb-3 gap-3">
                            <div class="flex items-center gap-2">
                                <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">CNPJs Novos Encontrados</h4>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706" id="cnpjs-novos-count-badge">0</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="btn-selecionar-todos-cnpjs" class="text-[11px] text-gray-600 hover:text-gray-900 hover:underline font-medium">
                                    Selecionar todos
                                </button>
                                <button type="button" id="btn-salvar-cnpjs-selecionados"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded text-[11px] font-medium bg-gray-800 text-white hover:bg-gray-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span id="btn-salvar-cnpjs-text">Salvar Selecionados</span>
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mb-3">
                            Estes CNPJs não existiam no sistema. Selecione quais deseja salvar como participante ou cliente.
                        </p>
                        <div id="cnpjs-novos-lista" class="overflow-x-auto max-h-[280px] overflow-y-auto border border-gray-200 rounded">
                            <table class="min-w-full text-xs">
                                <thead class="sticky top-0">
                                    <tr class="border-b border-gray-300">
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50 w-8">
                                            <input type="checkbox" id="cnpjs-novos-check-all" class="w-3.5 h-3.5 text-gray-700 rounded border-gray-300">
                                        </th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNPJ</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Razão Social</th>
                                        <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">UF</th>
                                        <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Polo</th>
                                        <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Notas</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Salvar como</th>
                                    </tr>
                                </thead>
                                <tbody id="cnpjs-novos-tabela-body" class="divide-y divide-gray-100"></tbody>
                            </table>
                        </div>
                        <div id="cnpjs-novos-truncated-msg" class="hidden mt-2 text-[11px] text-gray-500">
                            Exibindo 500 de <span id="cnpjs-novos-total-count">0</span> CNPJs novos.
                        </div>
                        <div id="cnpjs-novos-salvos-msg" class="hidden mt-3 bg-white border border-gray-300 border-l-4 border-l-gray-400 p-3">
                            <p class="text-xs text-gray-700" id="cnpjs-novos-salvos-text">CNPJs salvos com sucesso!</p>
                        </div>
                    </div>

                    {{-- Duplicadas Detectadas --}}
                    <div id="resultado-duplicadas-container" class="hidden px-4 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between mb-3 gap-3">
                            <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas Duplicadas Ignoradas</h4>
                            <button type="button" id="btn-toggle-duplicadas" class="text-[11px] text-gray-600 hover:text-gray-900 hover:underline font-medium">
                                Ver detalhes
                            </button>
                        </div>
                        <p class="text-xs text-gray-600 mb-2">
                            <span id="duplicadas-count" class="font-bold text-gray-900">0</span> notas já existiam no sistema e foram ignoradas.
                        </p>
                        <div id="duplicadas-lista" class="hidden overflow-x-auto max-h-[180px] overflow-y-auto border border-gray-200 rounded">
                            <table class="min-w-full text-xs">
                                <thead class="sticky top-0">
                                    <tr class="border-b border-gray-300">
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">N/Série</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Emitente</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Data</th>
                                        <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Importada em</th>
                                    </tr>
                                </thead>
                                <tbody id="duplicadas-tabela-body" class="divide-y divide-gray-100"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Resumo Financeiro --}}
                    <div id="resultado-financeiro" class="hidden px-4 py-4 border-b border-gray-200">
                        <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-3">Resumo Financeiro</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-5 divide-x divide-y sm:divide-y-0 divide-gray-200 border border-gray-200 rounded mb-3">
                            <div class="px-4 py-3 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor Total</p>
                                <p class="text-sm font-bold text-gray-900 font-mono mt-0.5" id="resultado-valor-total">R$ 0,00</p>
                            </div>
                            <div class="px-4 py-3 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Entradas</p>
                                <p class="text-sm font-bold text-gray-900 font-mono mt-0.5" id="resultado-qtd-entradas">0</p>
                            </div>
                            <div class="px-4 py-3 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Saídas</p>
                                <p class="text-sm font-bold text-gray-900 font-mono mt-0.5" id="resultado-qtd-saidas">0</p>
                            </div>
                            <div class="px-4 py-3 text-center">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Devoluções</p>
                                <p class="text-sm font-bold text-gray-900 font-mono mt-0.5" id="resultado-qtd-devolucoes">0</p>
                            </div>
                            <div class="px-4 py-3 text-center col-span-2 sm:col-span-1">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Ticket Médio</p>
                                <p class="text-sm font-bold text-gray-900 font-mono mt-0.5" id="resultado-ticket-medio">R$ 0,00</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-500 bg-gray-50 border border-gray-200 rounded px-4 py-2">
                            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Tributos</span>
                            <span>ICMS <span class="font-mono font-semibold text-gray-900" id="resultado-icms-total">R$ 0</span></span>
                            <span class="text-gray-300">|</span>
                            <span>PIS/COFINS <span class="font-mono font-semibold text-gray-900" id="resultado-pis-cofins">R$ 0</span></span>
                            <span class="text-gray-300">|</span>
                            <span>IPI <span class="font-mono font-semibold text-gray-900" id="resultado-ipi">R$ 0</span></span>
                            <span class="text-gray-300">|</span>
                            <span><span class="font-mono font-semibold text-gray-900" id="resultado-ufs-count">0</span> UFs</span>
                        </div>
                    </div>

                    {{-- Notas Fiscais --}}
                    <div id="resultado-notas-container" class="hidden px-4 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between mb-3 gap-3 flex-wrap">
                            <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Notas Fiscais</h4>
                            <div class="flex gap-0 border border-gray-300 rounded overflow-hidden" id="notas-filtros">
                                <button type="button" class="notas-filtro-btn px-2.5 py-1 text-[11px] bg-gray-800 text-white font-medium" data-filtro="todas">
                                    Todas <span id="notas-count-todas">0</span>
                                </button>
                                <button type="button" class="notas-filtro-btn px-2.5 py-1 text-[11px] bg-white text-gray-600 hover:bg-gray-50 border-l border-gray-300" data-filtro="entradas">
                                    Entrada <span id="notas-count-entradas">0</span>
                                </button>
                                <button type="button" class="notas-filtro-btn px-2.5 py-1 text-[11px] bg-white text-gray-600 hover:bg-gray-50 border-l border-gray-300" data-filtro="saidas">
                                    Saída <span id="notas-count-saidas">0</span>
                                </button>
                                <button type="button" class="notas-filtro-btn px-2.5 py-1 text-[11px] bg-white text-gray-600 hover:bg-gray-50 border-l border-gray-300" data-filtro="devolucoes">
                                    Dev <span id="notas-count-devolucoes">0</span>
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto max-h-[220px] overflow-y-auto border border-gray-200 rounded">
                            <table class="min-w-full text-xs">
                                <thead class="sticky top-0">
                                    <tr class="border-b border-gray-300">
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">N/Série</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Emissão</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Emitente</th>
                                        <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th>
                                        <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">ICMS</th>
                                        <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">PIS</th>
                                        <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">COFINS</th>
                                        <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">IPI</th>
                                        <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>
                                    </tr>
                                </thead>
                                <tbody id="notas-tabela-body" class="divide-y divide-gray-100"></tbody>
                            </table>
                        </div>
                        <p id="notas-empty" class="hidden text-center text-gray-400 text-xs py-4">Nenhuma nota fiscal encontrada.</p>
                    </div>

                    {{-- Participantes Importados --}}
                    <div id="resultado-participantes-container" class="hidden px-4 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between mb-3 gap-3 flex-wrap">
                            <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participantes</h4>
                            <div class="flex gap-0 border border-gray-300 rounded overflow-hidden" id="participantes-filtros">
                                <button type="button" class="part-filtro-btn px-2.5 py-1 text-[11px] bg-gray-800 text-white font-medium" data-filtro="todos">
                                    Todos <span id="part-count-todos">0</span>
                                </button>
                                <button type="button" class="part-filtro-btn px-2.5 py-1 text-[11px] bg-white text-gray-600 hover:bg-gray-50 border-l border-gray-300" data-filtro="novos">
                                    Novos <span id="part-count-novos">0</span>
                                </button>
                                <button type="button" class="part-filtro-btn px-2.5 py-1 text-[11px] bg-white text-gray-600 hover:bg-gray-50 border-l border-gray-300" data-filtro="atualizados">
                                    Já Reg. <span id="part-count-atualizados">0</span>
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto max-h-[220px] overflow-y-auto border border-gray-200 rounded">
                            <table class="min-w-full text-xs">
                                <thead class="sticky top-0">
                                    <tr class="border-b border-gray-300">
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Razão Social</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNPJ</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Endereço</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">IE</th>
                                        <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="participantes-tabela-body" class="divide-y divide-gray-100"></tbody>
                            </table>
                        </div>
                        <p id="participantes-empty" class="hidden text-center text-gray-400 text-xs py-4">Nenhum participante encontrado.</p>
                        <p id="participantes-loading" class="hidden text-center text-gray-400 text-xs py-4">
                            <svg class="inline w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Carregando...
                        </p>
                    </div>

                    {{-- Erros detalhados --}}
                    <div id="resultado-erros-container" class="hidden px-4 py-4 border-b border-gray-200">
                        <h4 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Erros</h4>
                        <div id="resultado-erros-lista" class="max-h-[120px] overflow-y-auto space-y-1 text-xs text-gray-700 bg-white border border-gray-300 border-l-4 border-l-red-500 p-3"></div>
                    </div>

                    {{-- Acoes --}}
                    <div class="px-4 py-3 bg-gray-50 flex justify-end">
                        <a
                            href="/app/dashboard"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded bg-gray-800 text-white text-sm font-medium hover:bg-gray-700 transition"
                            data-link
                        >
                            Ver Participantes
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="xml-em-breve-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="xml-em-breve-modal-title" role="dialog" aria-modal="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div id="xml-em-breve-modal-overlay" class="fixed inset-0 bg-black/50 transition-opacity"></div>
        <div class="relative w-full max-w-lg overflow-hidden rounded border border-gray-300 bg-white shadow-xl">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Aviso de Desenvolvimento</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">Em Breve</span>
            </div>
            <div class="p-5">
                <h3 id="xml-em-breve-modal-title" class="text-base font-bold text-gray-900 uppercase tracking-wide">Esta view ainda não está funcionando</h3>
                <p class="mt-3 text-sm text-gray-700">A importação de XMLs continua indisponível para uso. Os controles exibidos nesta tela estão apenas como referência visual e não executam validação, upload ou processamento.</p>
                <p class="mt-2 text-sm text-gray-700">Enquanto o desenvolvimento não é retomado, utilize outras áreas do painel normalmente. O histórico permanece acessível para consulta.</p>
                <div class="mt-5 flex justify-end gap-3">
                    <a href="/app/dashboard" data-link class="inline-flex items-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                        Voltar ao painel
                    </a>
                    <button type="button" id="xml-em-breve-modal-close" class="inline-flex items-center gap-2 rounded bg-gray-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700">
                        Entendi
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    function initMonitoramentoXml() {
        const container = document.getElementById('importacao-xml-container');
        if (!container) return;

        if (container.dataset.initialized === '1') return;
        container.dataset.initialized = '1';

        console.log('[Monitoramento XML] Inicializando...');

        // Funcao para obter CSRF token atualizado
        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        // Funcao para atualizar o CSRF token na meta tag
        async function refreshCsrfToken() {
            try {
                const response = await fetch('/api/csrf-token', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.csrf_token) {
                        const meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) {
                            meta.setAttribute('content', data.csrf_token);
                            console.log('[Monitoramento XML] CSRF token atualizado');
                        }
                    }
                }
            } catch (e) {
                console.error('[Monitoramento XML] Erro ao atualizar CSRF token:', e);
            }
        }

        // Atualizar CSRF token ao inicializar (caso tenha ficado stale durante navegacao SPA)
        refreshCsrfToken();

        let csrf = getCsrfToken();

        // Identificador unico por aba
        // Usa 'let' para permitir regeneração ao tentar novamente
        let tabId = crypto.randomUUID ? crypto.randomUUID() :
            (Date.now().toString(36) + Math.random().toString(36).substr(2));

        // Função para gerar novo tabId
        function regenerarTabId() {
            tabId = crypto.randomUUID ? crypto.randomUUID() :
                (Date.now().toString(36) + Math.random().toString(36).substr(2));
            console.log('[XML Import] Novo tabId gerado:', tabId);
        }

        // Elementos
        const dropzone = document.getElementById('xml-dropzone');
        const fileInput = document.getElementById('xml-file-input');
        const filesList = document.getElementById('xml-files-list');
        const filesContainer = document.getElementById('xml-files-container');
        const filesCount = document.getElementById('xml-files-count');
        const filesSize = document.getElementById('xml-files-size');
        const clearAllBtn = document.getElementById('xml-clear-all');
        const importarBtn = document.getElementById('xml-importar-btn');
        const errorMessage = document.getElementById('xml-error-message');
        const errorText = document.getElementById('xml-error-text');
        const tipoDocRadios = document.querySelectorAll('input[name="tipo-documento"]');
        const modoEnvioRadios = document.querySelectorAll('input[name="modo-envio"]');
        const uploadSection = document.getElementById('upload-section');
        const progressoContainer = document.getElementById('importacao-progresso');
        const xmlEmBreveModal = document.getElementById('xml-em-breve-modal');
        const xmlEmBreveModalClose = document.getElementById('xml-em-breve-modal-close');
        const xmlEmBreveModalOverlay = document.getElementById('xml-em-breve-modal-overlay');
        const viewEmBreve = container.dataset.emBreve === '1';

        // Limites
        const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
        const MAX_TOTAL_SIZE = 200 * 1024 * 1024; // 200MB
        const MAX_FILES = 100;

        // Estado
        // File object structure: { file: File, status: 'pending'|'validating'|'valid'|'error', totalXmls: number|null, tipoDoc: string|null, error: string|null, hint: string|null }
        let selectedFiles = [];
        let eventSource = null;
        let importacaoEmAndamento = false;
        let currentImportacaoId = null;
        let reconnectTimer = null;
        let reconnectAttempts = 0;
        const MAX_RECONEXOES = 3;
        const DELAY_RECONEXAO_BASE = 3000;
        let dadosParticipantes = [];
        let dadosNotasFiscais = [];
        let dadosCnpjsNovos = [];
        let salvarMovimentacoesAtivo = false;

        function closeEmBreveModal() {
            if (xmlEmBreveModal) {
                xmlEmBreveModal.classList.add('hidden');
            }
        }

        function openEmBreveModal() {
            if (xmlEmBreveModal) {
                xmlEmBreveModal.classList.remove('hidden');
            }
        }

        function bloquearViewEmBreve() {
            tipoDocRadios.forEach(radio => {
                radio.checked = false;
                radio.disabled = true;
            });

            modoEnvioRadios.forEach(radio => {
                radio.checked = false;
                radio.disabled = true;
            });

            if (dropzone) {
                dropzone.classList.remove('bg-white', 'hover:border-gray-500', 'hover:bg-gray-50', 'cursor-pointer');
                dropzone.classList.add('bg-gray-50', 'opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                dropzone.setAttribute('aria-disabled', 'true');
            }

            if (fileInput) {
                fileInput.disabled = true;
                fileInput.value = '';
            }

            if (importarBtn) {
                importarBtn.disabled = true;
                const btnText = importarBtn.querySelector('.btn-text');
                if (btnText) btnText.textContent = 'Em Breve';
            }

            if (filesList) {
                filesList.classList.add('hidden');
            }

            if (errorMessage) {
                errorMessage.classList.add('hidden');
            }

            selectedFiles = [];
        }

        // Funcao para obter tipo de documento selecionado
        function getSelectedTipoDoc() {
            const selected = Array.from(tipoDocRadios).find(radio => radio.checked);
            return selected ? selected.value : '';
        }

        // Funcao para obter modo de envio selecionado
        function getSelectedModoEnvio() {
            const selected = Array.from(modoEnvioRadios).find(radio => radio.checked);
            return selected ? selected.value : '';
        }

        // Atualizar visual dos labels de tipo documento
        function updateTipoDocLabels() {
            // Visual toggle handled via has-[:checked] in markup — no runtime class swap needed.
        }

        // Atualizar visual dos labels de modo de envio
        function updateModoEnvioLabels() {
            // Visual toggle handled via has-[:checked] in markup — no runtime class swap needed.
        }

        // Atualizar estado do dropzone
        function updateDropzoneState() {
            if (viewEmBreve) {
                bloquearViewEmBreve();
                return;
            }

            const hasTipoDoc = getSelectedTipoDoc() !== '';
            const modoEnvio = getSelectedModoEnvio();
            const hasModoEnvio = modoEnvio !== '';
            const isReady = hasTipoDoc && hasModoEnvio;
            const mainText = document.getElementById('xml-dropzone-main-text');
            const subText = document.getElementById('xml-dropzone-sub-text');
            const iconContainer = document.getElementById('xml-dropzone-icon');

            if (dropzone && fileInput) {
                if (isReady) {
                    // Habilitar dropzone
                    dropzone.classList.remove('bg-gray-50', 'opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                    dropzone.classList.add('bg-white', 'hover:border-gray-500', 'hover:bg-gray-50', 'cursor-pointer');
                    dropzone.setAttribute('aria-disabled', 'false');
                    fileInput.disabled = false;

                    // Configurar accept e multiple baseado no modo
                    if (modoEnvio === 'zip') {
                        fileInput.accept = '.zip';
                        fileInput.multiple = false;

                        if (iconContainer) {
                            iconContainer.innerHTML = '<svg class="mx-auto h-10 w-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>';
                        }
                        if (mainText) {
                            mainText.textContent = 'Arraste seu arquivo ZIP aqui';
                            mainText.className = 'text-sm font-semibold text-gray-900';
                        }
                        if (subText) {
                            subText.textContent = 'ou clique para selecionar (1 arquivo ZIP)';
                        }
                    } else {
                        fileInput.accept = '.xml';
                        fileInput.multiple = true;

                        if (iconContainer) {
                            iconContainer.innerHTML = '<svg class="mx-auto h-10 w-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                        }
                        if (mainText) {
                            mainText.textContent = 'Arraste seus arquivos XML aqui';
                            mainText.className = 'text-sm font-semibold text-gray-900';
                        }
                        if (subText) {
                            subText.textContent = 'ou clique para selecionar (multiplos arquivos)';
                        }
                    }
                } else {
                    // Desabilitar dropzone
                    dropzone.classList.remove('bg-white', 'hover:border-gray-500', 'hover:bg-gray-50', 'cursor-pointer');
                    dropzone.classList.add('bg-gray-50', 'opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                    dropzone.setAttribute('aria-disabled', 'true');
                    fileInput.disabled = true;
                    fileInput.accept = '.xml,.zip';
                    fileInput.multiple = true;

                    if (iconContainer) {
                        iconContainer.innerHTML = '<svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>';
                    }
                    if (mainText) {
                        mainText.textContent = 'Selecione o tipo e modo de envio';
                        mainText.className = 'text-sm font-medium text-gray-600';
                    }
                    if (subText) {
                        subText.textContent = 'Escolha as opcoes acima para habilitar o envio';
                    }
                }
            }
        }

        // Atualizar botao importar
        function updateImportButtonState() {
            if (viewEmBreve) {
                if (importarBtn) {
                    importarBtn.disabled = true;
                    const btnText = importarBtn.querySelector('.btn-text');
                    if (btnText) btnText.textContent = 'Em Breve';
                }
                return;
            }

            const hasTipoDoc = getSelectedTipoDoc() !== '';
            const hasModoEnvio = getSelectedModoEnvio() !== '';
            const hasFiles = selectedFiles.length > 0;
            const validFiles = selectedFiles.filter(f => f.status === 'valid').length;
            const isValidating = selectedFiles.some(f => f.status === 'validating');

            if (importarBtn) {
                // Disable if: no tipo doc, no modo envio, no files, still validating, or no valid files
                importarBtn.disabled = !(hasTipoDoc && hasModoEnvio && hasFiles && validFiles > 0 && !isValidating);
                const btnText = importarBtn.querySelector('.btn-text');
                if (btnText) {
                    if (isValidating) {
                        btnText.textContent = 'Validando...';
                    } else {
                        btnText.textContent = 'Importar';
                    }
                }
            }
        }

        // Mostrar erro
        function mostrarErro(mensagem) {
            if (errorText) errorText.textContent = mensagem;
            if (errorMessage) errorMessage.classList.remove('hidden');
        }

        // Ocultar erro
        function ocultarErro() {
            if (errorMessage) errorMessage.classList.add('hidden');
        }

        // Formatar tamanho
        function formatSize(bytes) {
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return bytes + ' bytes';
        }

        // Validar tipo de arquivo (verificacao local rapida)
        function validarTipoArquivo(file) {
            const fileName = file.name.toLowerCase();
            const isValid = fileName.endsWith('.xml') || fileName.endsWith('.zip');

            if (!isValid) {
                return { valid: false, error: 'Tipo de arquivo nao permitido: ' + file.name };
            }

            if (file.size > MAX_FILE_SIZE) {
                return { valid: false, error: 'Arquivo muito grande: ' + file.name + ' (' + formatSize(file.size) + ')' };
            }

            return { valid: true };
        }

        // Converter File para base64
        function fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => {
                    const base64 = reader.result.split(',')[1];
                    resolve(base64);
                };
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        // Validar arquivo via API (conta XMLs em ZIPs, detecta tipo)
        async function validarArquivoApi(fileObj, index, retryCount = 0) {
            if (viewEmBreve) {
                fileObj.status = 'error';
                fileObj.error = 'Importacao XML indisponivel nesta fase.';
                fileObj.totalXmls = 0;
                renderFilesList();
                updateImportButtonState();
                return;
            }

            fileObj.status = 'validating';
            renderFilesList();
            updateImportButtonState();

            try {
                const base64 = await fileToBase64(fileObj.file);

                const response = await fetch('/app/importacao/xml/validar', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        arquivo: {
                            nome: fileObj.file.name,
                            conteudo_base64: base64
                        }
                    })
                });

                // Handle CSRF token mismatch (419)
                if (response.status === 419 && retryCount < 1) {
                    console.log('[Monitoramento XML] CSRF token expirado, atualizando...');
                    await refreshCsrfToken();
                    return validarArquivoApi(fileObj, index, retryCount + 1);
                }

                // Handle auth error
                if (response.status === 401) {
                    fileObj.status = 'error';
                    fileObj.error = 'Sessao expirada. Recarregue a pagina.';
                    fileObj.totalXmls = 0;
                    renderFilesList();
                    updateImportButtonState();
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    fileObj.status = 'valid';
                    fileObj.totalXmls = data.total_xmls === -1 ? -1 : (data.total_xmls || 1);
                    fileObj.tipoDoc = data.tipo_documento || null;
                    fileObj.validacaoRelaxada = data.validacao_relaxada || false;

                    // Warn if ZIP has 0 XMLs
                    if (data.tipo === 'zip' && data.total_xmls === 0) {
                        fileObj.error = null; // Still valid but will show 0 XMLs
                    }
                } else {
                    fileObj.status = 'error';
                    fileObj.error = data.error || 'Erro na validacao';
                    fileObj.hint = data.hint || null;
                    fileObj.totalXmls = 0;
                }
            } catch (err) {
                console.error('[Monitoramento XML] Erro ao validar arquivo:', err);
                fileObj.status = 'error';
                fileObj.error = 'Erro de conexao';
                fileObj.totalXmls = 0;
            }

            renderFilesList();
            updateImportButtonState();
        }

        // Renderizar lista de arquivos
        function renderFilesList() {
            if (!filesContainer) return;

            if (selectedFiles.length === 0) {
                filesList.classList.add('hidden');
                return;
            }

            filesList.classList.remove('hidden');
            filesContainer.innerHTML = '';

            let totalSize = 0;
            let totalXmls = 0;
            let hasUnknownCount = false; // Indica se algum arquivo tem contagem indisponível (-1)

            selectedFiles.forEach((fileObj, index) => {
                const file = fileObj.file;
                totalSize += file.size;
                if (fileObj.status === 'valid') {
                    if (fileObj.totalXmls === -1) {
                        hasUnknownCount = true;
                    } else {
                        totalXmls += fileObj.totalXmls || 0;
                    }
                }

                const div = document.createElement('div');
                const isError = fileObj.status === 'error';
                div.className = 'flex items-center justify-between p-2 rounded border ' +
                    (isError ? 'bg-white border-gray-300 border-l-4 border-l-red-500' : 'bg-white border-gray-200');

                const badgeBase = 'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white flex-shrink-0';

                // Build status indicator HTML
                let statusHtml = '';
                if (fileObj.status === 'validating') {
                    statusHtml = `
                        <svg class="w-4 h-4 text-gray-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>`;
                } else if (fileObj.status === 'valid') {
                    const isZip = file.name.toLowerCase().endsWith('.zip');
                    if (isZip) {
                        const xmlCount = fileObj.totalXmls;
                        if (xmlCount === -1) {
                            statusHtml = `<span class="${badgeBase}" style="background-color: #374151" title="A contagem será feita durante o processamento">ZIP aceito</span>`;
                        } else if (xmlCount === 0) {
                            statusHtml = `<span class="${badgeBase}" style="background-color: #d97706">0 XMLs</span>`;
                        } else {
                            statusHtml = `<span class="${badgeBase}" style="background-color: #374151">${xmlCount} XML${xmlCount > 1 ? 's' : ''}</span>`;
                        }
                    } else if (fileObj.tipoDoc) {
                        const tipoLabels = { 'NFE': 'NF-e', 'NFSE': 'NFS-e', 'CTE': 'CT-e' };
                        const tipoHex = { 'NFE': '#0f766e', 'NFSE': '#4338ca', 'CTE': '#374151' };
                        statusHtml = `<span class="${badgeBase}" style="background-color: ${tipoHex[fileObj.tipoDoc] || '#374151'}">${tipoLabels[fileObj.tipoDoc] || fileObj.tipoDoc}</span>`;
                    } else {
                        statusHtml = `<span class="${badgeBase}" style="background-color: #047857">XML</span>`;
                    }
                } else if (fileObj.status === 'error') {
                    let errorDisplay = fileObj.error || 'Erro';
                    if (fileObj.hint) {
                        errorDisplay += `<span class="block text-[11px] text-gray-500 font-normal mt-0.5">Dica: ${fileObj.hint}</span>`;
                    }
                    statusHtml = `<span class="text-xs text-gray-700 font-medium flex-shrink-0">${errorDisplay}</span>`;
                }

                const fileIconHtml = `<svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>`;

                div.innerHTML = `
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                        ${fileIconHtml}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-gray-800 truncate">${file.name}</span>
                                ${statusHtml}
                            </div>
                            <div class="text-[11px] text-gray-500">${formatSize(file.size)}</div>
                        </div>
                    </div>
                    <button type="button" class="remove-file text-gray-400 hover:text-gray-700 p-1 flex-shrink-0" data-index="${index}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                `;
                filesContainer.appendChild(div);
            });

            // Atualizar contadores
            const validCount = selectedFiles.filter(f => f.status === 'valid').length;
            if (filesCount) {
                if (hasUnknownCount) {
                    // Tem arquivos com contagem indisponível
                    const xmlText = totalXmls > 0 ? totalXmls + '+ XMLs' : 'XMLs a contar';
                    filesCount.textContent = selectedFiles.length + ' arquivo(s) · ' + xmlText;
                } else if (totalXmls > 0) {
                    filesCount.textContent = selectedFiles.length + ' arquivo(s) · ' + totalXmls + ' XMLs';
                } else {
                    filesCount.textContent = selectedFiles.length + ' arquivo(s)';
                }
            }
            if (filesSize) filesSize.textContent = formatSize(totalSize);

            // Event listeners para remover
            filesContainer.querySelectorAll('.remove-file').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idx = parseInt(this.dataset.index);
                    selectedFiles.splice(idx, 1);
                    renderFilesList();
                    updateImportButtonState();
                });
            });
        }

        // Adicionar arquivos
        function addFiles(files) {
            if (viewEmBreve) {
                mostrarErro('Importacao XML indisponivel. Nenhum arquivo pode ser enviado nesta tela.');
                return;
            }

            ocultarErro();

            let totalSize = selectedFiles.reduce((sum, f) => sum + f.file.size, 0);
            let errors = [];
            const filesToValidate = [];

            for (const file of files) {
                // Verificar limite de arquivos
                if (selectedFiles.length >= MAX_FILES) {
                    errors.push('Limite de ' + MAX_FILES + ' arquivos atingido.');
                    break;
                }

                // Validar tipo de arquivo localmente
                const validation = validarTipoArquivo(file);
                if (!validation.valid) {
                    errors.push(validation.error);
                    continue;
                }

                // Verificar limite total
                if (totalSize + file.size > MAX_TOTAL_SIZE) {
                    errors.push('Limite de 200MB total excedido.');
                    break;
                }

                // Verificar duplicata
                const exists = selectedFiles.some(f => f.file.name === file.name && f.file.size === file.size);
                if (!exists) {
                    const fileObj = {
                        file: file,
                        status: 'pending',
                        totalXmls: null,
                        tipoDoc: null,
                        error: null,
                        hint: null
                    };
                    selectedFiles.push(fileObj);
                    filesToValidate.push(fileObj);
                    totalSize += file.size;
                }
            }

            if (errors.length > 0) {
                mostrarErro(errors[0]);
            }

            renderFilesList();
            updateImportButtonState();

            // Trigger validation for new files
            filesToValidate.forEach((fileObj, idx) => {
                const fileIndex = selectedFiles.indexOf(fileObj);
                validarArquivoApi(fileObj, fileIndex);
            });
        }

        // Limpar todos os arquivos
        function clearFiles() {
            selectedFiles = [];
            if (fileInput) fileInput.value = '';
            renderFilesList();
            updateImportButtonState();
            ocultarErro();
        }

        // Event listeners tipo documento
        tipoDocRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                updateTipoDocLabels();
                updateDropzoneState();
                updateImportButtonState();
            });
        });

        // Event listeners modo de envio
        modoEnvioRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                updateModoEnvioLabels();
                // Limpar arquivos ao trocar modo (ZIP vs XML sao incompativeis)
                clearFiles();
                updateDropzoneState();
                updateImportButtonState();
            });
        });

        // Dropzone click
        if (dropzone && fileInput) {
            dropzone.addEventListener('click', function() {
                if (viewEmBreve) {
                    openEmBreveModal();
                    return;
                }
                if (!fileInput.disabled) fileInput.click();
            });

            dropzone.addEventListener('dragover', function(e) {
                if (viewEmBreve) return;
                if (fileInput.disabled) return;
                e.preventDefault();
                dropzone.classList.remove('border-gray-300', 'bg-gray-50');
                dropzone.classList.add('border-gray-500', 'bg-gray-50');
            });

            dropzone.addEventListener('dragleave', function() {
                if (viewEmBreve) return;
                if (fileInput.disabled) return;
                dropzone.classList.remove('border-gray-500', 'bg-gray-50');
                dropzone.classList.add('border-gray-300', 'bg-gray-50');
            });

            dropzone.addEventListener('drop', function(e) {
                if (viewEmBreve) return;
                if (fileInput.disabled) return;
                e.preventDefault();
                dropzone.classList.remove('border-gray-500', 'bg-gray-50');
                dropzone.classList.add('border-gray-300', 'bg-gray-50');

                if (e.dataTransfer?.files) {
                    addFiles(Array.from(e.dataTransfer.files));
                }
            });
        }

        // File input change
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                if (viewEmBreve) return;
                if (e.target.files) {
                    addFiles(Array.from(e.target.files));
                }
            });
        }

        // Clear all
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', clearFiles);
        }

        // Elementos de progresso
        const barraProgresso = document.getElementById('barra-progresso');
        const progressoPorcentagem = document.getElementById('progresso-porcentagem');
        const progressoMensagem = document.getElementById('progresso-mensagem');
        const progressoTitulo = document.getElementById('progresso-titulo');
        const progressoIcon = document.getElementById('progresso-icon');
        const progressoStats = document.getElementById('progresso-stats');
        const progressoErro = document.getElementById('progresso-erro');
        const progressoErroMsg = document.getElementById('progresso-erro-msg');
        const resultadoContainer = document.getElementById('resultado-importacao');

        function buildBadgeHtml(label, hexColor, extraClasses) {
            const classes = extraClasses ? ' ' + extraClasses : '';
            return '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white' + classes + '" style="background-color: ' + hexColor + '">' + label + '</span>';
        }

        // Atualizar icone de status
        function atualizarIconeStatus(status) {
            const card = document.getElementById('progresso-card');
            if (!progressoIcon || !card) return;

            card.className = 'bg-white rounded border border-gray-300 overflow-hidden';

            switch (status) {
                case 'concluido':
                    progressoIcon.className = 'w-9 h-9 rounded border border-gray-300 bg-gray-50 flex items-center justify-center flex-shrink-0';
                    progressoIcon.innerHTML = '<svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                    if (barraProgresso) barraProgresso.className = 'h-full transition-all duration-500 ease-out';
                    if (barraProgresso) barraProgresso.style.backgroundColor = '#047857';
                    if (progressoErro) progressoErro.classList.add('hidden');
                    break;
                case 'erro':
                case 'timeout':
                    progressoIcon.className = 'w-9 h-9 rounded border border-gray-300 bg-gray-50 flex items-center justify-center flex-shrink-0';
                    progressoIcon.innerHTML = '<svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
                    if (barraProgresso) barraProgresso.className = 'h-full transition-all duration-500 ease-out';
                    if (barraProgresso) barraProgresso.style.backgroundColor = '#dc2626';
                    if (progressoErro) progressoErro.classList.remove('hidden');
                    break;
                default:
                    progressoIcon.className = 'w-9 h-9 rounded border border-gray-300 bg-gray-50 flex items-center justify-center flex-shrink-0';
                    progressoIcon.innerHTML = '<svg class="w-4 h-4 text-gray-700 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>';
                    if (barraProgresso) barraProgresso.className = 'h-full transition-all duration-500 ease-out';
                    if (barraProgresso) barraProgresso.style.backgroundColor = '#374151';
                    if (progressoErro) progressoErro.classList.add('hidden');
            }
        }

        // Atualizar progresso
        function atualizarProgresso(payload) {
            const dados = payload.dados || {};
            const progresso = parseInt(payload.progresso) || 0;
            const status = payload.status || 'processando';
            const mensagem = payload.mensagem || 'Processando...';

            if (barraProgresso) barraProgresso.style.width = progresso + '%';
            if (progressoPorcentagem) progressoPorcentagem.textContent = progresso + '%';
            if (progressoMensagem) progressoMensagem.textContent = mensagem;

            // Stats em tempo real durante processamento
            if (dados.total_xmls !== undefined || dados.xml_atual !== undefined) {
                if (progressoStats) progressoStats.classList.remove('hidden');

                const statXmls = document.getElementById('stat-xmls-processados');
                const statNotasNovas = document.getElementById('stat-notas-novas');
                const statNotasDuplicadas = document.getElementById('stat-notas-duplicadas');
                const statPartNovos = document.getElementById('stat-participantes-novos');
                const statErros = document.getElementById('stat-erros');

                // Contadores em tempo real (campos novos)
                if (statXmls) statXmls.textContent = dados.xml_atual || dados.xmls_processados || 0;
                if (statNotasNovas) statNotasNovas.textContent = dados.novas || dados.xmls_novos || 0;
                if (statNotasDuplicadas) statNotasDuplicadas.textContent = dados.duplicadas || dados.xmls_duplicados || 0;
                if (statPartNovos) statPartNovos.textContent = dados.participantes_novos || 0;
                if (statErros) statErros.textContent = dados.erros || dados.xmls_com_erro || 0;
            }

            // Exibir nota atual sendo processada
            const notaAtualContainer = document.getElementById('progresso-nota-atual');
            const notaAtualInfo = document.getElementById('nota-atual-info');
            if (dados.nota_atual && notaAtualContainer && notaAtualInfo) {
                notaAtualContainer.classList.remove('hidden');
                const nota = dados.nota_atual;
                const cnpjFormatado = nota.emit_cnpj ?
                    nota.emit_cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5') :
                    '';
                notaAtualInfo.textContent = 'NF ' + (nota.numero || '-') + ' - ' +
                    (nota.emit_razao || 'Emitente') +
                    (cnpjFormatado ? ' (' + cnpjFormatado + ')' : '') +
                    (nota.valor ? ' - R$ ' + nota.valor.toLocaleString('pt-BR', {minimumFractionDigits: 2}) : '');
            } else if (notaAtualContainer && status === 'concluido') {
                notaAtualContainer.classList.add('hidden');
            }

            atualizarIconeStatus(status);

            if (status === 'erro' && progressoErroMsg) {
                progressoErroMsg.textContent = payload.error_message || payload.mensagem || 'Erro durante o processamento.';
            }
        }

        // Mostrar progresso
        function mostrarProgresso() {
            if (progressoContainer) progressoContainer.classList.remove('hidden');
            if (uploadSection) uploadSection.classList.add('hidden');
        }

        // Ocultar progresso e voltar ao upload
        function voltarAoUpload() {
            if (progressoContainer) progressoContainer.classList.add('hidden');
            if (uploadSection) uploadSection.classList.remove('hidden');
            if (resultadoContainer) resultadoContainer.classList.add('hidden');
        }

        // Resetar progresso
        function resetarProgresso() {
            if (barraProgresso) {
                barraProgresso.style.width = '0%';
                barraProgresso.className = 'h-full transition-all duration-500 ease-out';
                barraProgresso.style.backgroundColor = '#374151';
            }
            if (progressoPorcentagem) progressoPorcentagem.textContent = '0%';
            if (progressoMensagem) progressoMensagem.textContent = 'Iniciando...';
            if (progressoTitulo) progressoTitulo.textContent = 'Processando XMLs...';
            if (progressoStats) progressoStats.classList.add('hidden');
            if (progressoErro) progressoErro.classList.add('hidden');
            if (resultadoContainer) resultadoContainer.classList.add('hidden');

            // Resetar stats em tempo real
            const statXmls = document.getElementById('stat-xmls-processados');
            const statNotasNovas = document.getElementById('stat-notas-novas');
            const statNotasDuplicadas = document.getElementById('stat-notas-duplicadas');
            const statPartNovos = document.getElementById('stat-participantes-novos');
            const statErros = document.getElementById('stat-erros');
            if (statXmls) statXmls.textContent = '0';
            if (statNotasNovas) statNotasNovas.textContent = '0';
            if (statNotasDuplicadas) statNotasDuplicadas.textContent = '0';
            if (statPartNovos) statPartNovos.textContent = '0';
            if (statErros) statErros.textContent = '0';

            // Ocultar nota atual
            const notaAtualContainer = document.getElementById('progresso-nota-atual');
            if (notaAtualContainer) notaAtualContainer.classList.add('hidden');

            // Resetar secoes de resultado
            const financeiroContainer = document.getElementById('resultado-financeiro');
            const notasContainer = document.getElementById('resultado-notas-container');
            const participantesContainer = document.getElementById('resultado-participantes-container');
            const errosContainer = document.getElementById('resultado-erros-container');
            const duplicadasContainer = document.getElementById('resultado-duplicadas-container');
            const duplicadasLista = document.getElementById('duplicadas-lista');

            if (financeiroContainer) financeiroContainer.classList.add('hidden');
            if (notasContainer) notasContainer.classList.add('hidden');
            if (participantesContainer) participantesContainer.classList.add('hidden');
            if (errosContainer) errosContainer.classList.add('hidden');
            if (duplicadasContainer) duplicadasContainer.classList.add('hidden');
            if (duplicadasLista) duplicadasLista.classList.add('hidden');

            // Resetar CNPJs novos
            const cnpjsNovosContainer = document.getElementById('resultado-cnpjs-novos-container');
            const cnpjsNovosSalvosMsg = document.getElementById('cnpjs-novos-salvos-msg');
            if (cnpjsNovosContainer) cnpjsNovosContainer.classList.add('hidden');
            if (cnpjsNovosSalvosMsg) cnpjsNovosSalvosMsg.classList.add('hidden');
            dadosCnpjsNovos = [];

            // Limpar dados
            dadosParticipantes = [];
            dadosNotasFiscais = [];

            atualizarIconeStatus('processando');
        }

        // Formatar valor em BRL
        function formatarBRL(valor) {
            return 'R$ ' + (valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Mostrar resultados
        function mostrarResultados(dados) {
            if (!resultadoContainer) return;

            // Elementos principais
            const resumo = document.getElementById('resultado-resumo');
            const xmls = document.getElementById('resultado-xmls');
            const notasNovas = document.getElementById('resultado-notas-novas');
            const notasDuplicadas = document.getElementById('resultado-notas-duplicadas');
            const totalParticipantes = document.getElementById('resultado-total-participantes');
            const xmlsErro = document.getElementById('resultado-xmls-erro');
            const errosContainer = document.getElementById('resultado-erros-container');
            const errosLista = document.getElementById('resultado-erros-lista');

            // Extrair contadores do payload (novos campos)
            const xmlsCount = dados.xmls_processados || 0;
            const novasCount = dados.xmls_novos || 0;
            const duplicadasCount = dados.xmls_duplicados || 0;
            const errosCount = dados.xmls_com_erro || 0;
            const partNovos = dados.participantes_novos || 0;
            const partAtualizados = dados.participantes_atualizados || 0;
            const totalPartCount = partNovos + partAtualizados;

            if (resumo) resumo.textContent = '';
            if (xmls) xmls.textContent = xmlsCount;
            if (notasNovas) notasNovas.textContent = novasCount;
            if (notasDuplicadas) notasDuplicadas.textContent = duplicadasCount;
            if (totalParticipantes) totalParticipantes.textContent = totalPartCount;
            if (xmlsErro) xmlsErro.textContent = errosCount;

            // Duplicadas detectadas
            const duplicadasContainer = document.getElementById('resultado-duplicadas-container');
            const duplicadasLista = document.getElementById('duplicadas-lista');
            const duplicadasBody = document.getElementById('duplicadas-tabela-body');
            const duplicadasCountEl = document.getElementById('duplicadas-count');
            const btnToggleDuplicadas = document.getElementById('btn-toggle-duplicadas');

            if (dados.duplicadas_detectadas && dados.duplicadas_detectadas.length > 0 && duplicadasContainer) {
                duplicadasContainer.classList.remove('hidden');
                if (duplicadasCountEl) duplicadasCountEl.textContent = dados.duplicadas_detectadas.length;

                // Renderizar tabela de duplicadas
                if (duplicadasBody) {
                    duplicadasBody.innerHTML = dados.duplicadas_detectadas.slice(0, 100).map(dup => {
                        const cnpjFmt = dup.emit_cnpj ?
                            dup.emit_cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5') : '-';
                        return '<tr class="hover:bg-gray-50">' +
                            '<td class="px-3 py-2 text-gray-900 whitespace-nowrap">' + (dup.numero_nota || '-') + '</td>' +
                            '<td class="px-3 py-2 text-gray-900 max-w-[180px] truncate" title="' + (dup.emit_razao || '') + '">' +
                                (dup.emit_razao || cnpjFmt) +
                            '</td>' +
                            '<td class="px-3 py-2 text-gray-600 whitespace-nowrap">' + (dup.data_emissao || '-') + '</td>' +
                            '<td class="px-3 py-2 text-right text-gray-900 whitespace-nowrap">' + formatarBRL(dup.valor) + '</td>' +
                            '<td class="px-3 py-2 text-gray-500 whitespace-nowrap text-xs">' +
                                (dup.existente_importado_em ? dup.existente_importado_em.substring(0, 10) : '-') +
                            '</td>' +
                        '</tr>';
                    }).join('');

                    // Adicionar aviso se houver mais de 100
                    if (dados.duplicadas_detectadas.length > 100) {
                        duplicadasBody.innerHTML += '<tr><td colspan="5" class="px-3 py-2 text-center text-xs text-gray-500">... e mais ' + (dados.duplicadas_detectadas.length - 100) + ' duplicadas</td></tr>';
                    }
                }

                // Toggle para mostrar/ocultar lista
                if (btnToggleDuplicadas && duplicadasLista) {
                    btnToggleDuplicadas.onclick = function() {
                        duplicadasLista.classList.toggle('hidden');
                        this.textContent = duplicadasLista.classList.contains('hidden') ? 'Ver detalhes' : 'Ocultar';
                    };
                }
            } else if (duplicadasContainer) {
                duplicadasContainer.classList.add('hidden');
            }

            // Erros detalhados
            const errosDetectados = dados.erros_detectados || dados.erros || [];
            if (errosDetectados.length > 0 && errosContainer && errosLista) {
                errosContainer.classList.remove('hidden');
                errosLista.innerHTML = errosDetectados.map(e =>
                    '<div class="bg-white border border-gray-300 border-l-4 border-l-red-500 rounded px-3 py-2">' +
                    '<span class="font-medium text-gray-900">' + (e.arquivo || 'XML') + ':</span> ' +
                    '<span class="text-gray-700"> ' + (e.erro || e.motivo || 'Erro desconhecido') + '</span>' +
                    (e.detalhe ? '<span class="block text-xs text-gray-500 mt-0.5">' + e.detalhe + '</span>' : '') +
                    '</div>'
                ).join('');
            } else if (errosContainer) {
                errosContainer.classList.add('hidden');
            }

            // CNPJs novos (preview para usuario decidir)
            mostrarCnpjsNovos(dados);

            resultadoContainer.classList.remove('hidden');

            // Carregar detalhes dos participantes e notas se temos importacao_id
            if (currentImportacaoId) {
                carregarDetalhesImportacao(currentImportacaoId);
            }

            resultadoContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Mostrar CNPJs novos encontrados durante importacao
        function mostrarCnpjsNovos(dados) {
            const container = document.getElementById('resultado-cnpjs-novos-container');
            const tbody = document.getElementById('cnpjs-novos-tabela-body');
            const countBadge = document.getElementById('cnpjs-novos-count-badge');
            const truncatedMsg = document.getElementById('cnpjs-novos-truncated-msg');
            const totalCountEl = document.getElementById('cnpjs-novos-total-count');
            const salvosMsg = document.getElementById('cnpjs-novos-salvos-msg');

            if (!container || !tbody) return;

            const cnpjsNovos = dados.cnpjs_novos || [];
            dadosCnpjsNovos = cnpjsNovos;

            if (cnpjsNovos.length === 0) {
                container.classList.add('hidden');
                return;
            }

            container.classList.remove('hidden');
            if (salvosMsg) salvosMsg.classList.add('hidden');
            if (countBadge) countBadge.textContent = dados.cnpjs_novos_total || cnpjsNovos.length;

            // Mostrar aviso se truncado
            if (dados.cnpjs_novos_truncated && truncatedMsg && totalCountEl) {
                truncatedMsg.classList.remove('hidden');
                totalCountEl.textContent = dados.cnpjs_novos_total || '500+';
            } else if (truncatedMsg) {
                truncatedMsg.classList.add('hidden');
            }

            // Formatar CNPJ
            function fmtCnpj(c) {
                if (!c || c.length !== 14) return c || '-';
                return c.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
            }

            // Renderizar tabela
            tbody.innerHTML = cnpjsNovos.map(function(cnpj, idx) {
                const polos = (cnpj.visto_como || []).map(function(p) {
                    return p === 'emit'
                        ? buildBadgeHtml('Emit', '#047857')
                        : buildBadgeHtml('Dest', '#4338ca');
                }).join(' ');

                return '<tr class="hover:bg-gray-50" data-cnpj="' + cnpj.cnpj + '">' +
                    '<td class="px-3 py-2"><input type="checkbox" class="cnpj-novo-check w-3.5 h-3.5 text-gray-700 rounded border-gray-300" data-idx="' + idx + '"></td>' +
                    '<td class="px-3 py-2 text-gray-900 whitespace-nowrap font-mono">' + fmtCnpj(cnpj.cnpj) + '</td>' +
                    '<td class="px-3 py-2 text-gray-900 max-w-[200px] truncate" title="' + (cnpj.razao_social || '') + '">' + (cnpj.razao_social || '-') + '</td>' +
                    '<td class="px-3 py-2 text-center text-gray-600">' + (cnpj.uf || '-') + '</td>' +
                    '<td class="px-3 py-2 text-center">' + polos + '</td>' +
                    '<td class="px-3 py-2 text-center text-gray-600">' + (cnpj.contagem_notas || 0) + '</td>' +
                    '<td class="px-3 py-2">' +
                        '<select class="cnpj-novo-tipo text-xs border border-gray-300 rounded px-1.5 py-1" data-idx="' + idx + '">' +
                            '<option value="participante">Participante</option>' +
                            '<option value="cliente">Cliente</option>' +
                        '</select>' +
                    '</td>' +
                '</tr>';
            }).join('');

            // Configurar event listeners
            configurarCnpjsNovosEventos();
        }

        // Configurar eventos de selecao e salvamento de CNPJs novos
        function configurarCnpjsNovosEventos() {
            const checkAll = document.getElementById('cnpjs-novos-check-all');
            const btnSelecionarTodos = document.getElementById('btn-selecionar-todos-cnpjs');
            const btnSalvar = document.getElementById('btn-salvar-cnpjs-selecionados');
            const btnSalvarText = document.getElementById('btn-salvar-cnpjs-text');

            function getCheckboxes() {
                return document.querySelectorAll('.cnpj-novo-check');
            }

            function getSelectedCount() {
                return document.querySelectorAll('.cnpj-novo-check:checked').length;
            }

            function updateSalvarBtn() {
                const count = getSelectedCount();
                if (btnSalvar) btnSalvar.disabled = count === 0;
                if (btnSalvarText) btnSalvarText.textContent = count > 0 ? 'Salvar ' + count + ' Selecionado' + (count > 1 ? 's' : '') : 'Salvar Selecionados';
            }

            // Check all
            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    getCheckboxes().forEach(function(cb) { cb.checked = checkAll.checked; });
                    updateSalvarBtn();
                });
            }

            // Selecionar todos button
            if (btnSelecionarTodos) {
                btnSelecionarTodos.addEventListener('click', function() {
                    const allChecked = getSelectedCount() === getCheckboxes().length;
                    getCheckboxes().forEach(function(cb) { cb.checked = !allChecked; });
                    if (checkAll) checkAll.checked = !allChecked;
                    btnSelecionarTodos.textContent = allChecked ? 'Selecionar todos' : 'Desmarcar todos';
                    updateSalvarBtn();
                });
            }

            // Individual checkboxes
            getCheckboxes().forEach(function(cb) {
                cb.addEventListener('change', function() {
                    updateSalvarBtn();
                    if (checkAll) {
                        checkAll.checked = getSelectedCount() === getCheckboxes().length;
                    }
                });
            });

            // Salvar selecionados
            if (btnSalvar) {
                btnSalvar.addEventListener('click', async function() {
                    const selecionados = [];
                    getCheckboxes().forEach(function(cb) {
                        if (!cb.checked) return;
                        const idx = parseInt(cb.dataset.idx);
                        const cnpjData = dadosCnpjsNovos[idx];
                        if (!cnpjData) return;

                        const selectEl = document.querySelector('.cnpj-novo-tipo[data-idx="' + idx + '"]');
                        const salvarComo = selectEl ? selectEl.value : 'participante';

                        selecionados.push({
                            cnpj: cnpjData.cnpj,
                            salvar_como: salvarComo,
                            razao_social: cnpjData.razao_social || null,
                            nome_fantasia: cnpjData.nome_fantasia || null,
                            uf: cnpjData.uf || null,
                            cep: cnpjData.cep || null,
                            municipio: cnpjData.municipio || null,
                            telefone: cnpjData.telefone || null,
                            crt: cnpjData.crt || null,
                        });
                    });

                    if (selecionados.length === 0) return;

                    btnSalvar.disabled = true;
                    if (btnSalvarText) btnSalvarText.textContent = 'Salvando...';

                    try {
                        let response = await fetch('/app/importacao/xml/importacao/' + currentImportacaoId + '/salvar-cnpjs', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ cnpjs: selecionados })
                        });

                        // Handle CSRF token mismatch
                        if (response.status === 419) {
                            await refreshCsrfToken();
                            response = await fetch('/app/importacao/xml/importacao/' + currentImportacaoId + '/salvar-cnpjs', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': getCsrfToken(),
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json'
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({ cnpjs: selecionados })
                            });
                        }

                        const data = await response.json();

                        if (data.success) {
                            const salvosMsg = document.getElementById('cnpjs-novos-salvos-msg');
                            const salvosMsgText = document.getElementById('cnpjs-novos-salvos-text');
                            if (salvosMsg) salvosMsg.classList.remove('hidden');
                            if (salvosMsgText) {
                                const msg = data.total_criados + ' CNPJ' + (data.total_criados > 1 ? 's' : '') + ' salvo' + (data.total_criados > 1 ? 's' : '') + ' com sucesso!';
                                salvosMsgText.textContent = data.total_erros > 0
                                    ? msg + ' (' + data.total_erros + ' com erro)'
                                    : msg;
                            }

                            // Desabilitar checkboxes salvos
                            getCheckboxes().forEach(function(cb) {
                                if (cb.checked) {
                                    cb.disabled = true;
                                    const tr = cb.closest('tr');
                                    if (tr) tr.classList.add('opacity-50');
                                }
                            });
                            if (btnSalvarText) btnSalvarText.textContent = 'Salvos!';

                            // Recarregar detalhes da importacao para refletir novos participantes
                            if (currentImportacaoId) {
                                carregarDetalhesImportacao(currentImportacaoId);
                            }

                            if (window.showToast) {
                                window.showToast(data.total_criados + ' CNPJs salvos com sucesso!', 'success');
                            }
                        } else {
                            throw new Error(data.error || 'Erro ao salvar CNPJs');
                        }
                    } catch (err) {
                        console.error('[Monitoramento XML] Erro ao salvar CNPJs novos:', err);
                        if (window.showErrorAlert) {
                            window.showErrorAlert('Erro ao salvar CNPJs: ' + err.message);
                        }
                        btnSalvar.disabled = false;
                        if (btnSalvarText) btnSalvarText.textContent = 'Salvar Selecionados';
                    }
                });
            }
        }

        // Carregar detalhes da importacao (participantes e notas)
        async function carregarDetalhesImportacao(importacaoId) {
            const participantesContainer = document.getElementById('resultado-participantes-container');
            const participantesLoading = document.getElementById('participantes-loading');
            const participantesEmpty = document.getElementById('participantes-empty');
            const participantesBody = document.getElementById('participantes-tabela-body');

            // Mostrar container de participantes e loading
            if (participantesContainer) participantesContainer.classList.remove('hidden');
            if (participantesLoading) participantesLoading.classList.remove('hidden');
            if (participantesEmpty) participantesEmpty.classList.add('hidden');
            if (participantesBody) participantesBody.innerHTML = '';

            try {
                const response = await fetch('/app/importacao/xml/importacao/' + importacaoId + '/participantes', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error('Erro ao carregar detalhes');
                }

                const data = await response.json();

                if (participantesLoading) participantesLoading.classList.add('hidden');

                if (data.success) {
                    // Armazenar dados
                    dadosParticipantes = data.participantes || [];
                    dadosNotasFiscais = data.notas_fiscais || [];

                    // Atualizar totais
                    const novosEl = document.getElementById('resultado-novos');
                    const atualizadosEl = document.getElementById('resultado-atualizados');
                    const totalPartEl = document.getElementById('resultado-total-participantes');
                    if (data.totais) {
                        const novosCount = data.totais.participantes_novos || 0;
                        const atualizadosCount = data.totais.participantes_atualizados || 0;
                        if (novosEl) novosEl.textContent = novosCount;
                        if (atualizadosEl) atualizadosEl.textContent = atualizadosCount;
                        if (totalPartEl) totalPartEl.textContent = novosCount + atualizadosCount;
                    }

                    // Calcular estatisticas adicionais
                    const ufsUnicas = new Set(dadosParticipantes.map(p => p.uf).filter(Boolean));
                    const qtdDevolucoes = dadosNotasFiscais.filter(n => n.finalidade === 4).length;

                    if (data.resumo_financeiro) {
                        data.resumo_financeiro.ufs_count = ufsUnicas.size;
                        data.resumo_financeiro.qtd_devolucoes = qtdDevolucoes;
                    }

                    // Mostrar resumo financeiro e notas se houver notas (independente de salvarMovimentacoesAtivo)
                    if (dadosNotasFiscais.length > 0) {
                        mostrarResumoFinanceiro(data.resumo_financeiro);
                        mostrarNotasFiscais(dadosNotasFiscais);
                        configurarFiltrosNotas();
                    }

                    // Renderizar participantes
                    renderizarParticipantes(dadosParticipantes, 'todos');

                    // Configurar filtros de participantes
                    configurarFiltrosParticipantes();
                } else {
                    if (participantesEmpty) {
                        participantesEmpty.textContent = data.error || 'Erro ao carregar participantes';
                        participantesEmpty.classList.remove('hidden');
                    }
                }
            } catch (err) {
                console.error('[Monitoramento XML] Erro ao carregar detalhes:', err);
                if (participantesLoading) participantesLoading.classList.add('hidden');
                if (participantesEmpty) {
                    participantesEmpty.textContent = 'Erro ao carregar participantes';
                    participantesEmpty.classList.remove('hidden');
                }
            }
        }

        // Mostrar resumo financeiro
        function mostrarResumoFinanceiro(resumo) {
            const container = document.getElementById('resultado-financeiro');
            if (!container || !resumo) return;

            container.classList.remove('hidden');

            const valorTotal = document.getElementById('resultado-valor-total');
            const icmsTotal = document.getElementById('resultado-icms-total');
            const pisCofins = document.getElementById('resultado-pis-cofins');
            const ipi = document.getElementById('resultado-ipi');
            const qtdEntradas = document.getElementById('resultado-qtd-entradas');
            const qtdSaidas = document.getElementById('resultado-qtd-saidas');
            const qtdDevolucoes = document.getElementById('resultado-qtd-devolucoes');
            const ticketMedio = document.getElementById('resultado-ticket-medio');
            const ufsCount = document.getElementById('resultado-ufs-count');

            const icmsValue = (resumo.icms_total || 0) + (resumo.icms_st_total || 0);
            const totalNotas = (resumo.qtd_entradas || 0) + (resumo.qtd_saidas || 0);
            const ticketMedioValue = totalNotas > 0 ? (resumo.valor_total || 0) / totalNotas : 0;

            if (valorTotal) valorTotal.textContent = formatarBRL(resumo.valor_total);
            if (icmsTotal) icmsTotal.textContent = formatarBRLCompacto(icmsValue);
            if (pisCofins) pisCofins.textContent = formatarBRLCompacto(resumo.pis_cofins_total);
            if (ipi) ipi.textContent = formatarBRLCompacto(resumo.ipi_total);
            if (qtdEntradas) qtdEntradas.textContent = resumo.qtd_entradas || 0;
            if (qtdSaidas) qtdSaidas.textContent = resumo.qtd_saidas || 0;
            if (qtdDevolucoes) qtdDevolucoes.textContent = resumo.qtd_devolucoes || 0;
            if (ticketMedio) ticketMedio.textContent = formatarBRL(ticketMedioValue);
            if (ufsCount) ufsCount.textContent = resumo.ufs_count || 0;
        }

        // Formatar BRL compacto (sem centavos para valores grandes)
        function formatarBRLCompacto(valor) {
            valor = valor || 0;
            if (valor >= 1000) {
                return 'R$ ' + Math.round(valor).toLocaleString('pt-BR');
            }
            return formatarBRL(valor);
        }

        // Mostrar notas fiscais
        function mostrarNotasFiscais(notas) {
            const container = document.getElementById('resultado-notas-container');
            if (!container) return;

            container.classList.remove('hidden');

            // Atualizar contadores
            const todas = notas.length;
            const entradas = notas.filter(n => n.tipo_nota === 0).length;
            const saidas = notas.filter(n => n.tipo_nota === 1).length;
            const devolucoes = notas.filter(n => n.finalidade === 4).length;

            const countTodas = document.getElementById('notas-count-todas');
            const countEntradas = document.getElementById('notas-count-entradas');
            const countSaidas = document.getElementById('notas-count-saidas');
            const countDevolucoes = document.getElementById('notas-count-devolucoes');

            if (countTodas) countTodas.textContent = todas;
            if (countEntradas) countEntradas.textContent = entradas;
            if (countSaidas) countSaidas.textContent = saidas;
            if (countDevolucoes) countDevolucoes.textContent = devolucoes;

            renderizarNotas(notas, 'todas');
        }

        // Renderizar tabela de notas
        function renderizarNotas(notas, filtro) {
            const tbody = document.getElementById('notas-tabela-body');
            const emptyMsg = document.getElementById('notas-empty');
            if (!tbody) return;

            let notasFiltradas = notas;
            if (filtro === 'entradas') {
                notasFiltradas = notas.filter(n => n.tipo_nota === 0);
            } else if (filtro === 'saidas') {
                notasFiltradas = notas.filter(n => n.tipo_nota === 1);
            } else if (filtro === 'devolucoes') {
                notasFiltradas = notas.filter(n => n.finalidade === 4);
            }

            if (notasFiltradas.length === 0) {
                tbody.innerHTML = '';
                if (emptyMsg) emptyMsg.classList.remove('hidden');
                return;
            }

            if (emptyMsg) emptyMsg.classList.add('hidden');

            tbody.innerHTML = notasFiltradas.map(nota => {
                const tipoIcon = nota.tipo_nota === 0 ? '&#8595;' : '&#8593;';
                const tipoText = nota.tipo_nota === 0 ? 'Entrada' : 'Saída';
                const tipoBadge = nota.tipo_nota === 0
                    ? buildBadgeHtml(tipoIcon + ' ' + tipoText, '#047857', 'inline-flex items-center')
                    : buildBadgeHtml(tipoIcon + ' ' + tipoText, '#d97706', 'inline-flex items-center');

                return '<tr class="hover:bg-gray-50" data-tipo="' + nota.tipo_nota + '" data-finalidade="' + nota.finalidade + '">' +
                    '<td class="px-3 py-2 text-gray-900 whitespace-nowrap">' + (nota.numero_nota || '-') + '/' + (nota.serie || 1) + '</td>' +
                    '<td class="px-3 py-2 text-gray-600 whitespace-nowrap">' + (nota.data_emissao || '-') + '</td>' +
                    '<td class="px-3 py-2 text-gray-900 max-w-[200px] truncate" title="' + (nota.emit_razao_social || '') + '">' +
                        (nota.emit_razao_social || nota.emit_cnpj_formatado || '-') +
                    '</td>' +
                    '<td class="px-3 py-2 text-right text-gray-900 font-mono whitespace-nowrap">' + (nota.valor_formatado || formatarBRL(nota.valor_total)) + '</td>' +
                    '<td class="px-3 py-2 text-right text-gray-600 font-mono whitespace-nowrap">' + formatarBRL(nota.icms_valor) + '</td>' +
                    '<td class="px-3 py-2 text-right text-gray-600 font-mono whitespace-nowrap">' + formatarBRL(nota.pis_valor) + '</td>' +
                    '<td class="px-3 py-2 text-right text-gray-600 font-mono whitespace-nowrap">' + formatarBRL(nota.cofins_valor) + '</td>' +
                    '<td class="px-3 py-2 text-right text-gray-600 font-mono whitespace-nowrap">' + formatarBRL(nota.ipi_valor) + '</td>' +
                    '<td class="px-3 py-2 text-center whitespace-nowrap">' +
                        tipoBadge +
                    '</td>' +
                '</tr>';
            }).join('');
        }

        // Renderizar tabela de participantes
        function renderizarParticipantes(participantes, filtro) {
            const tbody = document.getElementById('participantes-tabela-body');
            const emptyMsg = document.getElementById('participantes-empty');
            if (!tbody) return;

            let partsFiltrados = participantes;
            if (filtro === 'novos') {
                partsFiltrados = participantes.filter(p => p.is_novo);
            } else if (filtro === 'atualizados') {
                partsFiltrados = participantes.filter(p => !p.is_novo);
            }

            // Atualizar contadores
            const countTodos = document.getElementById('part-count-todos');
            const countNovos = document.getElementById('part-count-novos');
            const countAtualizados = document.getElementById('part-count-atualizados');

            if (countTodos) countTodos.textContent = participantes.length;
            if (countNovos) countNovos.textContent = participantes.filter(p => p.is_novo).length;
            if (countAtualizados) countAtualizados.textContent = participantes.filter(p => !p.is_novo).length;

            if (partsFiltrados.length === 0) {
                tbody.innerHTML = '';
                if (emptyMsg) {
                    emptyMsg.textContent = filtro === 'todos' ? 'Nenhum participante encontrado.' : 'Nenhum participante nesta categoria.';
                    emptyMsg.classList.remove('hidden');
                }
                return;
            }

            if (emptyMsg) emptyMsg.classList.add('hidden');

            tbody.innerHTML = partsFiltrados.map(p => {
                const statusBadge = p.is_novo
                    ? buildBadgeHtml('Novo', '#047857')
                    : buildBadgeHtml('Já Reg.', '#374151');

                return '<tr class="hover:bg-gray-50" data-novo="' + (p.is_novo ? '1' : '0') + '">' +
                    '<td class="px-3 py-2 text-gray-900 max-w-[200px] truncate" title="' + (p.razao_social || '') + '">' + (p.razao_social || '-') + '</td>' +
                    '<td class="px-3 py-2 text-gray-900 whitespace-nowrap font-mono text-xs">' + (p.cnpj_formatado || p.cnpj || '-') + '</td>' +
                    '<td class="px-3 py-2 text-gray-600 max-w-[180px] truncate" title="' + (p.endereco || '') + '">' + (p.endereco || '-') + '</td>' +
                    '<td class="px-3 py-2 text-gray-600 whitespace-nowrap">' + (p.inscricao_estadual || '-') + '</td>' +
                    '<td class="px-3 py-2 text-center">' +
                        statusBadge +
                    '</td>' +
                '</tr>';
            }).join('');
        }

        // Configurar filtros de participantes
        function configurarFiltrosParticipantes() {
            const filtros = document.querySelectorAll('.part-filtro-btn');
            filtros.forEach(btn => {
                btn.addEventListener('click', function() {
                    const filtro = this.dataset.filtro;

                    // Atualizar visual dos botoes (tab style)
                    filtros.forEach(b => {
                        b.classList.remove('bg-gray-800', 'text-white', 'font-medium');
                        b.classList.add('bg-white', 'text-gray-600', 'hover:bg-gray-50');
                    });
                    this.classList.remove('bg-white', 'text-gray-600', 'hover:bg-gray-50');
                    this.classList.add('bg-gray-800', 'text-white', 'font-medium');

                    // Re-renderizar
                    renderizarParticipantes(dadosParticipantes, filtro);
                });
            });
        }

        // Configurar filtros de notas
        function configurarFiltrosNotas() {
            const filtros = document.querySelectorAll('.notas-filtro-btn');
            filtros.forEach(btn => {
                btn.addEventListener('click', function() {
                    const filtro = this.dataset.filtro;

                    // Atualizar visual dos botoes (tab style)
                    filtros.forEach(b => {
                        b.classList.remove('bg-gray-800', 'text-white', 'font-medium');
                        b.classList.add('bg-white', 'text-gray-600', 'hover:bg-gray-50');
                    });
                    this.classList.remove('bg-white', 'text-gray-600', 'hover:bg-gray-50');
                    this.classList.add('bg-gray-800', 'text-white', 'font-medium');

                    // Re-renderizar
                    renderizarNotas(dadosNotasFiscais, filtro);
                });
            });
        }

        // Conectar SSE
        function conectarSSE() {
            if (eventSource) eventSource.close();

            const sseUrl = '/app/importacao/xml/progresso/stream?tab_id=' + encodeURIComponent(tabId);
            console.log('[Monitoramento XML] Conectando ao SSE:', sseUrl);
            eventSource = new EventSource(sseUrl);

            eventSource.onopen = function() {
                reconnectAttempts = 0;
                console.log('[Monitoramento XML] SSE conectado');
            };

            let rafPendente = null;

            eventSource.onmessage = function(event) {
                const rawData = event.data;

                if (rafPendente !== null) return;

                rafPendente = requestAnimationFrame(() => {
                    rafPendente = null;
                    try {
                        const dados = JSON.parse(rawData);
                        console.log('[Monitoramento XML] Dados SSE:', dados);
                        atualizarProgresso(dados);

                        if (dados.status === 'concluido') {
                            eventSource.close();
                            eventSource = null;
                            importacaoEmAndamento = false;

                            if (window.showToast) {
                                window.showToast(dados.mensagem || 'Importacao concluida!', 'success');
                            }

                            mostrarResultados(dados.dados || {});
                        } else if (dados.status === 'erro' || dados.status === 'timeout') {
                            eventSource.close();
                            eventSource = null;
                            importacaoEmAndamento = false;
                        }
                    } catch (e) {
                        console.error('[Monitoramento XML] Erro ao parsear SSE:', e);
                    }
                });
            };

            eventSource.onerror = function() {
                const tentativas = reconnectAttempts;

                eventSource.close();
                eventSource = null;

                if (!importacaoEmAndamento) return;

                if (tentativas < MAX_RECONEXOES) {
                    reconnectAttempts++;
                    const delay = DELAY_RECONEXAO_BASE * Math.pow(2, tentativas);
                    console.warn('[XML] SSE desconectado, tentativa ' + reconnectAttempts + '/' + MAX_RECONEXOES + ' em ' + delay + 'ms');

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

        // Botao importar
        if (importarBtn) {
            importarBtn.addEventListener('click', async function() {
                if (viewEmBreve) {
                    openEmBreveModal();
                    return;
                }

                const tipoDoc = getSelectedTipoDoc();
                if (!tipoDoc) {
                    if (window.showToast) window.showToast('Selecione o tipo de documento.', 'warning');
                    return;
                }

                const modoEnvioSelecionado = getSelectedModoEnvio();
                if (!modoEnvioSelecionado) {
                    if (window.showToast) window.showToast('Selecione o modo de envio.', 'warning');
                    return;
                }

                if (selectedFiles.length === 0) {
                    if (window.showToast) window.showToast('Selecione ao menos um arquivo.', 'warning');
                    return;
                }

                if (importacaoEmAndamento) {
                    if (window.showToast) window.showToast('Aguarde a importacao em andamento.', 'warning');
                    return;
                }

                // Desabilitar botao
                importarBtn.disabled = true;
                const btnText = importarBtn.querySelector('.btn-text');
                if (btnText) btnText.textContent = 'Enviando...';

                try {
                    // Filter only valid files
                    const validFileObjs = selectedFiles.filter(f => f.status === 'valid');

                    if (validFileObjs.length === 0) {
                        throw new Error('Nenhum arquivo valido para importar.');
                    }

                    // Converter arquivos para base64
                    const arquivos = await Promise.all(validFileObjs.map(async fileObj => {
                        const file = fileObj.file;
                        const buffer = await file.arrayBuffer();
                        const base64 = btoa(String.fromCharCode(...new Uint8Array(buffer)));
                        return {
                            nome: file.name,
                            tipo: file.type || (file.name.endsWith('.zip') ? 'application/zip' : 'application/xml'),
                            conteudo_base64: base64
                        };
                    }));

                    const modoEnvio = getSelectedModoEnvio();
                    const payload = {
                        tipo_documento: tipoDoc,
                        modo_envio: modoEnvio,
                        tab_id: tabId,
                        salvar_movimentacoes: false,
                        arquivos: arquivos
                    };

                    let response = await fetch('/app/importacao/xml/importar', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload)
                    });

                    // Handle CSRF token mismatch (419) - retry once
                    if (response.status === 419) {
                        console.log('[Monitoramento XML] CSRF token expirado no import, atualizando...');
                        await refreshCsrfToken();
                        response = await fetch('/app/importacao/xml/importar', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify(payload)
                        });
                    }

                    // Handle auth error
                    if (response.status === 401) {
                        throw new Error('Sessao expirada. Recarregue a pagina para continuar.');
                    }

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.error || data.message || 'Erro ao enviar arquivos');
                    }

                    console.log('[Monitoramento XML] Importacao iniciada:', data);

                    // Salvar dados da importacao
                    currentImportacaoId = data.importacao_id || null;
                    salvarMovimentacoesAtivo = payload.salvar_movimentacoes || false;

                    importacaoEmAndamento = true;
                    resetarProgresso();
                    mostrarProgresso();
                    conectarSSE();

                } catch (err) {
                    console.error('[Monitoramento XML] Erro:', err);
                    if (window.showErrorAlert) {
                        window.showErrorAlert('Erro ao importar XML: ' + err.message);
                    }
                    importarBtn.disabled = false;
                    const totalXmls = selectedFiles.reduce((sum, f) => sum + (f.totalXmls > 0 ? f.totalXmls : 0), 0);
                    const hasUnknown = selectedFiles.some(f => f.totalXmls === -1);
                    if (btnText) {
                        if (hasUnknown) {
                            btnText.textContent = totalXmls > 0 ? 'Importar ' + totalXmls + '+ docs' : 'Importar';
                        } else {
                            btnText.textContent = totalXmls > 0 ? 'Importar ' + totalXmls + ' docs' : 'Importar';
                        }
                    }
                }
            });
        }

        // Botao tentar novamente
        const btnTentarNovamente = document.getElementById('btn-tentar-novamente');
        if (btnTentarNovamente) {
            btnTentarNovamente.addEventListener('click', function() {
                importacaoEmAndamento = false;
                currentImportacaoId = null;
                if (reconnectTimer !== null) {
                    clearTimeout(reconnectTimer);
                    reconnectTimer = null;
                }
                reconnectAttempts = 0;
                if (eventSource) {
                    eventSource.close();
                    eventSource = null;
                }
                // CRÍTICO: Regenerar tabId para evitar receber dados de erro do cache anterior
                regenerarTabId();
                voltarAoUpload();
                resetarProgresso();
                updateImportButtonState();
            });
        }

        // Botao nova importacao
        const btnNovaImportacao = document.getElementById('btn-nova-importacao');
        if (btnNovaImportacao) {
            btnNovaImportacao.addEventListener('click', function() {
                if (viewEmBreve) {
                    openEmBreveModal();
                    return;
                }
                importacaoEmAndamento = false;
                currentImportacaoId = null;
                salvarMovimentacoesAtivo = false;
                if (reconnectTimer !== null) {
                    clearTimeout(reconnectTimer);
                    reconnectTimer = null;
                }
                reconnectAttempts = 0;
                if (eventSource) {
                    eventSource.close();
                    eventSource = null;
                }
                clearFiles();
                voltarAoUpload();
                resetarProgresso();
            });
        }

        if (xmlEmBreveModalClose) {
            xmlEmBreveModalClose.addEventListener('click', closeEmBreveModal);
        }

        if (xmlEmBreveModalOverlay) {
            xmlEmBreveModalOverlay.addEventListener('click', closeEmBreveModal);
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEmBreveModal();
            }
        });

        if (viewEmBreve) {
            bloquearViewEmBreve();
            openEmBreveModal();
        }

        // Cleanup
        window._cleanupFunctions = window._cleanupFunctions || {};
        window._cleanupFunctions.initImportacaoXml = function() {
            if (reconnectTimer !== null) {
                clearTimeout(reconnectTimer);
                reconnectTimer = null;
            }
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
        };

        console.log('[Monitoramento XML] Inicializado com tab_id:', tabId);
    }

    // Auto-inicializar
    function _initAll() {
        try { initMonitoramentoXml(); } catch(e) { console.error('[XML] Erro init:', e); }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', _initAll);
    } else {
        _initAll();
    }

    // Expor para SPA (chama ambas as funcoes)
    window.initMonitoramentoXml = function() {
        try { initMonitoramentoXml(); } catch(e) { console.error('[XML] Erro init:', e); }
    };
})();
</script>
