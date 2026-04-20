{{-- Risk Score - Detalhes do Participante (DANFE Modernizado) --}}
@php
    $scoreColor = function($s) {
        if ($s >= 80) return '#b91c1c';
        if ($s >= 50) return '#ea580c';
        if ($s >= 20) return '#d97706';
        return '#047857';
    };
    $scoreLabel = function($s) {
        if ($s >= 80) return 'CRÍTICO';
        if ($s >= 50) return 'ALTO';
        if ($s >= 20) return 'MÉDIO';
        return 'BAIXO';
    };
@endphp
<div class="min-h-screen bg-gray-100" id="risk-detail-container">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        {{-- Breadcrumb --}}
        <nav class="mb-4">
            <ol class="flex items-center gap-2 text-[11px] text-gray-500 uppercase tracking-wide">
                <li><a href="/app/score-fiscal" data-link class="hover:text-gray-900 hover:underline">Score Fiscal</a></li>
                <li><span>/</span></li>
                <li class="text-gray-900 font-semibold">{{ $participante->razao_social ?? 'Participante' }}</li>
            </ol>
        </nav>

        {{-- Header --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Identificação</span>
            </div>
            <div class="p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">{{ $participante->razao_social ?? 'N/A' }}</h1>
                    @if($participante->nome_fantasia)
                        <p class="text-sm text-gray-600">{{ $participante->nome_fantasia }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500 font-mono">CNPJ: {{ $participante->cnpj_formatado }}</p>
                </div>
                <div class="flex items-center gap-4">
                    @if($score)
                        @php $hex = $scoreColor($score->score_total); @endphp
                        <div class="text-center">
                            <div class="text-3xl font-bold font-mono" style="color: {{ $hex }}">
                                {{ $score->score_total }}
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white mt-1" style="background-color: {{ $hex }}">
                                {{ $scoreLabel($score->score_total) }}
                            </span>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="text-3xl font-bold text-gray-400 font-mono">—</div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white mt-1" style="background-color: #9ca3af">
                                Não Avaliado
                            </span>
                        </div>
                    @endif
                    <button type="button" id="btn-consultar" data-id="{{ $participante->id }}" class="inline-flex items-center gap-2 px-3 py-2 rounded bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Atualizar Score
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Informacoes do Participante --}}
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Informações Cadastrais</span>
                    </div>
                    <dl class="divide-y divide-gray-100">
                        <div class="px-4 py-3">
                            <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Situação Cadastral</dt>
                            <dd class="text-sm text-gray-700 mt-0.5">{{ $participante->situacao_cadastral ?? 'Não informado' }}</dd>
                        </div>
                        <div class="px-4 py-3">
                            <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Regime Tributário</dt>
                            <dd class="text-sm text-gray-700 mt-0.5">{{ $participante->regime_tributario ?? 'Não informado' }}</dd>
                        </div>
                        <div class="px-4 py-3">
                            <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">UF / Município</dt>
                            <dd class="text-sm text-gray-700 mt-0.5">{{ $participante->uf ?? '-' }} / {{ $participante->municipio ?? '-' }}</dd>
                        </div>
                        <div class="px-4 py-3">
                            <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">CEP</dt>
                            <dd class="text-sm text-gray-700 mt-0.5 font-mono">{{ $participante->cep ?? '-' }}</dd>
                        </div>
                        <div class="px-4 py-3">
                            <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Telefone</dt>
                            <dd class="text-sm text-gray-700 mt-0.5">{{ $participante->telefone ?? '-' }}</dd>
                        </div>
                        <div class="px-4 py-3">
                            <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Origem</dt>
                            <dd class="text-sm text-gray-700 mt-0.5">{{ $participante->origem_tipo ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Volume de Transacoes --}}
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Volume de Transações</span>
                    </div>
                    <dl class="divide-y divide-gray-100">
                        <div class="px-4 py-3">
                            <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Como Fornecedor (Emitente)</dt>
                            <dd class="text-sm font-semibold text-gray-900 font-mono mt-0.5">R$ {{ number_format($volumeEmitente ?? 0, 2, ',', '.') }}</dd>
                        </div>
                        <div class="px-4 py-3">
                            <dt class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Como Cliente (Destinatário)</dt>
                            <dd class="text-sm font-semibold text-gray-900 font-mono mt-0.5">R$ {{ number_format($volumeDestinatario ?? 0, 2, ',', '.') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Detalhes do Score --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Detalhamento do Score</span>
                    </div>
                    <div class="p-5">

                    @if($score)
                        <div class="space-y-4">
                            @foreach($score->scores_detalhados as $key => $item)
                                @php $ihex = $scoreColor($item['score']); @endphp
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-700">{{ $item['label'] }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[11px] text-gray-500 uppercase tracking-wide">Peso: {{ ($pesos[$key] ?? 0) * 100 }}%</span>
                                            <span class="text-sm font-bold font-mono" style="color: {{ $ihex }}">
                                                {{ $item['score'] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded h-2">
                                        <div class="h-2 rounded" style="width: {{ $item['score'] }}%; background-color: {{ $ihex }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Legenda --}}
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-3">Legenda dos Scores</h4>
                            <div class="flex flex-wrap gap-4 text-xs">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded" style="background-color: #047857"></div>
                                    <span class="text-gray-600">0-20: Baixo Risco</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded" style="background-color: #d97706"></div>
                                    <span class="text-gray-600">21-50: Médio Risco</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded" style="background-color: #ea580c"></div>
                                    <span class="text-gray-600">51-80: Alto Risco</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded" style="background-color: #b91c1c"></div>
                                    <span class="text-gray-600">81-100: Crítico</span>
                                </div>
                            </div>
                        </div>

                        @if($score->ultima_consulta_em)
                        <div class="mt-4 text-[11px] text-gray-500">
                            Última atualização: {{ $score->ultima_consulta_em->format('d/m/Y H:i') }}
                            @if($score->isDesatualizado())
                                <span class="ml-2 font-semibold" style="color: #d97706">(Desatualizado — mais de 30 dias)</span>
                            @endif
                        </div>
                        @endif

                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <h4 class="mt-4 text-sm font-semibold text-gray-900 uppercase tracking-wide">Score não calculado</h4>
                            <p class="mt-2 text-xs text-gray-500">Clique em "Atualizar Score" para calcular o risco deste participante.</p>
                        </div>
                    @endif

                    </div>
                </div>

                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Dados da Última Consulta</span>
                    </div>
                    <div class="p-5">
                        @if($score && $score->dados_consultados)
                            <pre class="text-xs bg-gray-50 border border-gray-200 p-4 rounded overflow-x-auto">{{ json_encode($score->dados_consultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @else
                            <p class="text-sm text-gray-500">Nenhuma consulta realizada ainda.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/risk-score.js') }}"></script>
