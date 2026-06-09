@php
    $tipoEfdBadgeMap = [
        'fiscal' => ['label' => 'EFD ICMS/IPI', 'hex' => '#4338ca'],
        'contrib' => ['label' => 'EFD PIS/COFINS', 'hex' => '#0f766e'],
    ];
    $efdManutencaoAtiva = $efdManutencaoAtiva ?? (bool) config('importacao.efd_manutencao.ativa');
    $podeImportarEfd = $podeImportarEfd ?? (
        ! $efdManutencaoAtiva
        || in_array((int) (auth()->id() ?? 0), array_map('intval', (array) config('importacao.efd_manutencao.usuarios_permitidos', [])), true)
    );
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

        @if ($efdManutencaoAtiva)
            <div class="mb-4 sm:mb-6 rounded border" style="background-color: #fef3c7; border-color: #fcd34d;">
                <div class="px-4 py-3 sm:px-5 sm:py-4 flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" style="color: #92400e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    </svg>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold uppercase tracking-wide" style="color: #92400e;">Módulo em manutenção</p>
                        <p class="text-xs sm:text-[13px] mt-1" style="color: #78350f;">
                            O pipeline de extração EFD (participantes, notas, catálogo e apurações) foi auditado e corrigido contra o SPED bruto; estamos em validação final antes de reabrir os uploads para todos.
                            @if ($podeImportarEfd)
                                Você está na lista de usuários autorizados a importar durante a manutenção para validar o novo pipeline.
                            @else
                                Novos uploads estão temporariamente desativados. As importações anteriores permanecem acessíveis para consulta no histórico.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if (! $podeImportarEfd)
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Upload do Arquivo</span>
                </div>
                <div class="p-6 sm:p-8 text-center">
                    <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"></path>
                    </svg>
                    <p class="text-sm font-semibold text-gray-700">Upload temporariamente desativado</p>
                    <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto">
                        O envio de novos arquivos SPED voltará assim que a reestruturação do pipeline for concluída. Acompanhe o histórico de importações já processadas em
                        <a href="/app/importacao/historico" class="text-gray-700 underline" data-link>Importação · Histórico</a>.
                    </p>
                </div>
            </div>
        @endif

        <div id="efd-upload-workspace" @class(['space-y-4 sm:space-y-6', 'hidden' => ! $podeImportarEfd])>
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

    </div>
</div>

{{-- Modal de duplicidade de importação (padrão design system, tom âmbar = atenção/substituir) --}}
<div id="modal-duplicidade-efd" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity" id="modal-duplicidade-overlay"></div>
        <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6 z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900" id="modal-duplicidade-titulo">Importação duplicada</h3>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-700 mb-3" id="modal-duplicidade-mensagem"></p>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-[13px]">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Importação existente</span>
                        <span class="font-medium text-gray-900" id="modal-duplicidade-tipo"></span>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-gray-500">Período</span>
                        <span class="font-medium text-gray-900" id="modal-duplicidade-periodo"></span>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-gray-500">Importada em</span>
                        <span class="font-medium text-gray-900" id="modal-duplicidade-data"></span>
                    </div>
                </div>
                <p class="hidden text-[13px] text-amber-700 font-medium mt-3" id="modal-duplicidade-aviso-retificadora">Este arquivo é uma retificadora.</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" id="modal-duplicidade-cancelar" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-semibold shadow-sm transition hover:bg-gray-50">Cancelar</button>
                <button type="button" id="modal-duplicidade-confirmar" class="px-4 py-2 rounded-lg text-white text-sm font-semibold shadow-sm transition" style="background-color: #d97706;" onmouseover="this.style.backgroundColor='#b45309'" onmouseout="this.style.backgroundColor='#d97706'">Substituir</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const container = document.getElementById('importacao-efd-container');
    if (!container || container.dataset.initialized === '1') return;
    container.dataset.initialized = '1';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const tabId = crypto.randomUUID
        ? crypto.randomUUID()
        : (Date.now().toString(36) + Math.random().toString(36).substr(2));

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

    const MAX_FILE_SIZE = 10 * 1024 * 1024;

    function getSelectedTipoEfd() {
        const selected = Array.from(tipoEfdRadios).find(r => r.checked);
        return selected ? selected.value : '';
    }

    function updateTipoEfdLabels() {
        const selectedValue = getSelectedTipoEfd();
        document.querySelectorAll('.tipo-efd-label').forEach(function (label) {
            const radio = label.querySelector('input[type="radio"]');
            if (radio && radio.value === selectedValue) {
                label.classList.remove('border-gray-300', 'hover:bg-gray-50');
                label.classList.add('border-gray-500', 'bg-gray-50', 'ring-1', 'ring-gray-300');
            } else {
                label.classList.remove('border-gray-500', 'bg-gray-50', 'ring-1', 'ring-gray-300');
                label.classList.add('border-gray-300', 'hover:bg-gray-50');
            }
        });

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

    function updateDropzoneState() {
        const hasTipoEfd = getSelectedTipoEfd() !== '';
        const dropzoneMainText = document.getElementById('txt-dropzone-main-text');
        const dropzoneSubText = document.getElementById('txt-dropzone-sub-text');

        if (!txtDropzone || !txtFileInput) return;

        if (hasTipoEfd) {
            txtDropzone.classList.remove('bg-gray-100', 'opacity-60', 'cursor-not-allowed', 'pointer-events-none');
            txtDropzone.classList.add('bg-gray-50', 'hover:bg-gray-50', 'cursor-pointer');
            txtDropzone.setAttribute('aria-disabled', 'false');
            txtFileInput.disabled = false;

            const svg = txtDropzone.querySelector('svg');
            if (svg) { svg.classList.remove('text-gray-400'); svg.classList.add('text-gray-500'); }

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
            txtDropzone.classList.remove('bg-gray-50', 'hover:bg-gray-50', 'cursor-pointer');
            txtDropzone.classList.add('bg-gray-100', 'opacity-60', 'cursor-not-allowed', 'pointer-events-none');
            txtDropzone.setAttribute('aria-disabled', 'true');
            txtFileInput.disabled = true;

            const svg = txtDropzone.querySelector('svg');
            if (svg) { svg.classList.remove('text-gray-500'); svg.classList.add('text-gray-400'); }

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

    function updateImportButtonState() {
        const hasTipoEfd = getSelectedTipoEfd() !== '';
        const hasFile = txtFileInput && txtFileInput.files && txtFileInput.files.length > 0;
        if (txtImportarBtn) txtImportarBtn.disabled = !(hasTipoEfd && hasFile);
    }

    function validarArquivoTxt(file) {
        if (!file) return false;
        const fileName = file.name.toLowerCase();
        const isTxt = fileName.endsWith('.txt') || file.type === 'text/plain';
        if (!isTxt) {
            mostrarErroTxt('Apenas arquivos .txt são permitidos.');
            return false;
        }
        if (file.size > MAX_FILE_SIZE) {
            mostrarErroTxt('O arquivo excede o limite de 10MB.');
            return false;
        }
        return true;
    }

    function mostrarErroTxt(mensagem) {
        if (txtErrorText) txtErrorText.textContent = mensagem;
        if (txtErrorMessage) txtErrorMessage.classList.remove('hidden');
    }

    function ocultarErroTxt() {
        if (txtErrorMessage) txtErrorMessage.classList.add('hidden');
    }

    function atualizarUITxt(file) {
        if (!file) {
            if (txtFileMeta) txtFileMeta.classList.add('hidden');
            if (txtDropzone) txtDropzone.classList.remove('hidden');
            updateImportButtonState();
            return;
        }
        if (txtFileName) txtFileName.textContent = file.name;
        if (txtFileSize) txtFileSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        if (txtFileMeta) txtFileMeta.classList.remove('hidden');
        if (txtDropzone) txtDropzone.classList.add('hidden');
        updateImportButtonState();
        if (txtImportarBtn) txtImportarBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function limparArquivoTxt() {
        if (txtFileInput) txtFileInput.value = '';
        atualizarUITxt(null);
        ocultarErroTxt();
        updateImportButtonState();
    }

    function processarArquivoTxt(file) {
        ocultarErroTxt();
        if (!validarArquivoTxt(file)) {
            limparArquivoTxt();
            return;
        }
        atualizarUITxt(file);
    }

    if (txtDropzone && txtFileInput) {
        txtDropzone.addEventListener('click', function () {
            if (!txtFileInput.disabled) txtFileInput.click();
        });

        txtDropzone.addEventListener('dragover', function (e) {
            if (txtFileInput.disabled) return;
            e.preventDefault();
            txtDropzone.classList.add('border-gray-500');
        });

        txtDropzone.addEventListener('dragleave', function () {
            txtDropzone.classList.remove('border-gray-500');
        });

        txtDropzone.addEventListener('drop', function (e) {
            if (txtFileInput.disabled) return;
            e.preventDefault();
            txtDropzone.classList.remove('border-gray-500');
            const file = e.dataTransfer?.files?.[0];
            if (file) {
                processarArquivoTxt(file);
                const dt = new DataTransfer();
                dt.items.add(file);
                txtFileInput.files = dt.files;
            }
        });
    }

    if (txtFileInput) {
        txtFileInput.addEventListener('change', function (e) {
            const file = e.target.files?.[0];
            if (file) processarArquivoTxt(file);
            else limparArquivoTxt();
        });
    }

    if (txtChangeFile) {
        txtChangeFile.addEventListener('click', function (e) {
            e.stopPropagation();
            limparArquivoTxt();
            if (txtFileInput) txtFileInput.click();
        });
    }

    tipoEfdRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            updateTipoEfdLabels();
            updateDropzoneState();
            updateImportButtonState();
        });
    });

    // Abre o modal de duplicidade e resolve com true (substituir/reimportar) ou false (cancelar).
    function confirmarDuplicidade(data) {
        const modal = document.getElementById('modal-duplicidade-efd');
        if (!modal) {
            // Fallback defensivo: se o modal não existir, mantém o fluxo via confirm nativo.
            return Promise.resolve(window.confirm('Já existe uma importação para este documento. Substituir a anterior?'));
        }

        const imp = data.importacao || {};
        const identico = data.caso === 'identico';
        const periodo = (imp.periodo_inicio || '—') + ' a ' + (imp.periodo_fim || '—');

        document.getElementById('modal-duplicidade-titulo').textContent =
            identico ? 'Arquivo já importado' : 'Período já importado';
        document.getElementById('modal-duplicidade-mensagem').textContent = identico
            ? 'Este arquivo idêntico já foi importado. Nada mudou no conteúdo. Você pode reimportar mesmo assim — isso substitui a importação anterior.'
            : 'Já existe uma importação deste período. Substituir pela nova versão remove a anterior e reprocessa com este arquivo.';
        document.getElementById('modal-duplicidade-tipo').textContent = imp.tipo_efd || '—';
        document.getElementById('modal-duplicidade-periodo').textContent = periodo;
        document.getElementById('modal-duplicidade-data').textContent = imp.criada_em || '—';

        const avisoRetif = document.getElementById('modal-duplicidade-aviso-retificadora');
        avisoRetif.classList.toggle('hidden', !(!identico && data.retificadora));

        const btnConfirmar = document.getElementById('modal-duplicidade-confirmar');
        btnConfirmar.textContent = identico ? 'Reimportar' : 'Substituir';

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        return new Promise(function (resolve) {
            const btnCancelar = document.getElementById('modal-duplicidade-cancelar');
            const overlay = document.getElementById('modal-duplicidade-overlay');

            function fechar(resultado) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                btnConfirmar.removeEventListener('click', onConfirmar);
                btnCancelar.removeEventListener('click', onCancelar);
                overlay.removeEventListener('click', onCancelar);
                document.removeEventListener('keydown', onKey);
                resolve(resultado);
            }
            function onConfirmar() { fechar(true); }
            function onCancelar() { fechar(false); }
            function onKey(e) { if (e.key === 'Escape') fechar(false); }

            btnConfirmar.addEventListener('click', onConfirmar);
            btnCancelar.addEventListener('click', onCancelar);
            overlay.addEventListener('click', onCancelar);
            document.addEventListener('keydown', onKey);
        });
    }

    if (txtImportarBtn) {
        txtImportarBtn.addEventListener('click', async function () {
            const tipoEfd = getSelectedTipoEfd();
            if (!tipoEfd) {
                mostrarErroTxt('Selecione o tipo de EFD antes de importar.');
                return;
            }
            if (!txtFileInput || !txtFileInput.files || txtFileInput.files.length === 0) {
                mostrarErroTxt('Selecione um arquivo .txt para importar.');
                return;
            }

            const originalHtml = txtImportarBtn.innerHTML;
            txtImportarBtn.disabled = true;
            txtImportarBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg> Enviando...';

            const enviar = async function (substituir) {
                const formData = new FormData();
                formData.append('arquivo', txtFileInput.files[0]);
                formData.append('tipo_efd', tipoEfd === 'efd-fiscal' ? 'EFD ICMS/IPI' : 'EFD PIS/COFINS');
                formData.append('tab_id', tabId);
                if (substituir) formData.append('substituir', '1');

                const response = await fetch('/app/importacao/efd/importar-txt', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    body: formData,
                });

                const data = await response.json();

                // Duplicidade: backend pede confirmação antes de substituir.
                if (response.status === 409 && data.caso) {
                    const confirmou = await confirmarDuplicidade(data);
                    if (confirmou) {
                        return enviar(true);
                    }
                    return null; // usuário cancelou
                }

                if (!response.ok || !data.success || !data.importacao_id) {
                    throw new Error(data.error || data.message || 'Erro ao enviar arquivo');
                }

                window.location.href = '/app/importacao/efd/' + data.importacao_id + '?tab_id=' + encodeURIComponent(tabId);
                return data;
            };

            try {
                const resultado = await enviar(false);
                if (resultado === null) {
                    // cancelado pelo usuário — restaura o botão
                    txtImportarBtn.disabled = false;
                    txtImportarBtn.innerHTML = originalHtml;
                    updateImportButtonState();
                }
            } catch (err) {
                mostrarErroTxt(err.message || 'Erro ao enviar arquivo.');
                txtImportarBtn.disabled = false;
                txtImportarBtn.innerHTML = originalHtml;
                updateImportButtonState();
            }
        });
    }

    updateTipoEfdLabels();
    updateDropzoneState();
    updateImportButtonState();
})();
</script>
