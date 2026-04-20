@php
    $validacao = $validacao ?? [];
    $categorias = $categorias ?? [];

    $scoreTotal = (int) ($validacao['score_total'] ?? 0);
    $scoreHex = match (true) {
        $scoreTotal >= 50 => '#dc2626',
        $scoreTotal >= 30 => '#d97706',
        $scoreTotal >= 10 => '#b45309',
        default => '#047857',
    };

    $classificacaoHex = match (strtolower((string) ($nota->validacao_classificacao ?? $validacao['classificacao'] ?? ''))) {
        'conforme' => '#047857',
        'atencao' => '#d97706',
        'irregular' => '#b45309',
        'critico' => '#dc2626',
        default => '#374151',
    };
@endphp

<div class="min-h-screen bg-gray-100" id="validacao-nota-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-8">
            <a href="/app/clearance/dashboard" data-link class="inline-flex items-center gap-2 text-xs text-gray-600 hover:text-gray-900 hover:underline mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Voltar para validação
            </a>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Nota Fiscal {{ $nota->numero_nota }}</h1>
                    <p class="text-xs text-gray-500 mt-1">{{ $nota->emit_razao_social ?? $nota->emit_cnpj }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($validacao['preview'] ?? false)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">
                            Preview
                        </span>
                        <button type="button" id="btn-salvar-validacao" class="px-4 py-2 bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium" data-nota-id="{{ $nota->id }}">
                            Salvar validação
                        </button>
                    @else
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $classificacaoHex }}">
                            {{ strtoupper($nota->validacao_classificacao_label ?? 'VALIDADA') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div id="validacao-nota-error-region" class="mb-6"></div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Score de Validação</span>
            </div>
            <div class="p-4 sm:p-6">
                <div class="grid grid-cols-1 lg:grid-cols-[220px,1fr] gap-6 items-start">
                    <div class="border border-gray-200 rounded p-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Score Total</p>
                        <p class="text-4xl font-bold mt-2" style="color: {{ $scoreHex }}">{{ $scoreTotal }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Quanto menor o score, mais conforme está a nota.</p>
                    </div>
                    <div>
                        <div class="h-3 bg-gray-200 rounded overflow-hidden">
                            <div class="h-3" style="width: {{ max(0, min(100, $scoreTotal)) }}%; background-color: {{ $scoreHex }}"></div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mt-4">
                            @foreach($categorias as $key => $categoria)
                                @php
                                    $scoreCategoria = (int) ($validacao['scores'][$key] ?? 0);
                                    $scoreCategoriaHex = match (true) {
                                        $scoreCategoria >= 50 => '#dc2626',
                                        $scoreCategoria >= 30 => '#d97706',
                                        $scoreCategoria >= 10 => '#b45309',
                                        default => '#047857',
                                    };
                                @endphp
                                <div class="border border-gray-200 rounded p-3 bg-gray-50/60">
                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">{{ $categoria['nome'] }}</p>
                                    <p class="text-lg font-bold mt-1" style="color: {{ $scoreCategoriaHex }}">{{ $scoreCategoria }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(count($validacao['alertas'] ?? []) > 0)
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Alertas</span>
                    <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ count($validacao['alertas']) }}</span>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($validacao['alertas'] as $alerta)
                        @php
                            $nivelHex = match ($alerta['nivel']) {
                                'bloqueante' => '#dc2626',
                                'atencao' => '#d97706',
                                'info' => '#374151',
                                default => '#9ca3af',
                            };
                        @endphp
                        <div class="px-4 py-4 border-l-4" style="border-left-color: {{ $nivelHex }}">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $nivelHex }}">
                                            {{ strtoupper($alerta['nivel']) }}
                                        </span>
                                        <span class="text-[11px] text-gray-400 uppercase tracking-wide">{{ $alerta['categoria'] }}</span>
                                        <span class="text-[11px] font-mono text-gray-400">{{ $alerta['codigo'] }}</span>
                                    </div>
                                    <p class="text-sm text-gray-900">{{ $alerta['mensagem'] }}</p>
                                    @if(!empty($alerta['detalhe']))
                                        <p class="text-sm text-gray-600 mt-1">{{ $alerta['detalhe'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-white rounded border border-gray-300 p-4 mb-6 border-l-4 border-l-green-600">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Sem Ocorrências</p>
                <p class="mt-2 text-sm text-gray-700">Esta nota fiscal está conforme com as regras de validação disponíveis.</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Identificação</span>
                </div>
                <dl class="p-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Número</dt>
                        <dd class="font-medium text-gray-900">{{ $nota->numero_nota }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Série</dt>
                        <dd class="font-medium text-gray-900">{{ $nota->serie }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Emissão</dt>
                        <dd class="font-medium text-gray-900">{{ $nota->data_emissao?->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Tipo</dt>
                        <dd class="font-medium text-gray-900">{{ $nota->tipo_nota_descricao }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Finalidade</dt>
                        <dd class="font-medium text-gray-900">{{ $nota->finalidade_descricao }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 mb-1">Chave de acesso</dt>
                        <dd class="font-mono text-xs text-gray-900 break-all">{{ $nota->nfe_id }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Valores</span>
                </div>
                <dl class="p-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">Valor total</dt>
                        <dd class="font-medium text-gray-900">{{ $nota->valor_formatado }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">ICMS</dt>
                        <dd class="font-medium text-gray-900 font-mono">R$ {{ number_format($nota->icms_valor ?? 0, 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">ICMS-ST</dt>
                        <dd class="font-medium text-gray-900 font-mono">R$ {{ number_format($nota->icms_st_valor ?? 0, 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">PIS</dt>
                        <dd class="font-medium text-gray-900 font-mono">R$ {{ number_format($nota->pis_valor ?? 0, 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">COFINS</dt>
                        <dd class="font-medium text-gray-900 font-mono">R$ {{ number_format($nota->cofins_valor ?? 0, 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">IPI</dt>
                        <dd class="font-medium text-gray-900 font-mono">R$ {{ number_format($nota->ipi_valor ?? 0, 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 pt-3 border-t border-gray-200">
                        <dt class="text-gray-700 font-medium">Total tributos</dt>
                        <dd class="font-bold text-gray-900 font-mono">R$ {{ number_format($nota->tributos_total ?? 0, 2, ',', '.') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Emitente</span>
                </div>
                <dl class="p-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">CNPJ</dt>
                        <dd class="font-medium text-gray-900">{{ $nota->emit_cnpj_formatado }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 mb-1">Razão social</dt>
                        <dd class="font-medium text-gray-900">{{ $nota->emit_razao_social ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">UF</dt>
                        <dd class="font-medium text-gray-900">{{ $nota->emit_uf ?? '-' }}</dd>
                    </div>
                    @if($nota->emitente)
                        <a href="/app/score-fiscal/participante/{{ $nota->emit_participante_id }}" data-link class="inline-flex text-xs text-gray-600 hover:text-gray-900 hover:underline">
                            Ver score de risco do emitente
                        </a>
                    @endif
                </dl>
            </div>

            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Destinatário</span>
                </div>
                <dl class="p-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">CNPJ</dt>
                        <dd class="font-medium text-gray-900">{{ $nota->dest_cnpj_formatado }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 mb-1">Razão social</dt>
                        <dd class="font-medium text-gray-900">{{ $nota->dest_razao_social ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">UF</dt>
                        <dd class="font-medium text-gray-900">{{ $nota->dest_uf ?? '-' }}</dd>
                    </div>
                    @if($nota->destinatario)
                        <a href="/app/score-fiscal/participante/{{ $nota->dest_participante_id }}" data-link class="inline-flex text-xs text-gray-600 hover:text-gray-900 hover:underline">
                            Ver score de risco do destinatário
                        </a>
                    @endif
                </dl>
            </div>
        </div>

        @if(!($validacao['preview'] ?? false) && !empty($validacao['validado_em']))
            <div class="mt-6 text-center text-[11px] text-gray-500 uppercase tracking-wide">
                Validado em {{ \Carbon\Carbon::parse($validacao['validado_em'])->format('d/m/Y H:i:s') }}
            </div>
        @endif
    </div>
</div>

<script src="{{ asset('js/clearance.js') }}"></script>
