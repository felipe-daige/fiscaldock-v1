{{-- Importação XML - Detalhes --}}
@php
    [$badgeStyle, $badgeLabel] = match($importacao->status) {
        'concluido'   => ['background-color: #047857', 'Concluído'],
        'processando' => ['background-color: #d97706', 'Processando'],
        'erro'        => ['background-color: #dc2626', 'Erro'],
        default       => ['background-color: #9ca3af', 'Pendente'],
    };

    [$tipoDocLabel, $tipoStyle] = match(strtolower($importacao->tipo_documento ?? '')) {
        'nfe'  => ['NF-e',  'background-color: #047857'],
        'nfse' => ['NFS-e', 'background-color: #4338ca'],
        'cte'  => ['CT-e',  'background-color: #c2410c'],
        default => ['XML',  'background-color: #6b7280'],
    };

    $emProcessamento = in_array($importacao->status, ['processando', 'pendente'], true);
    $arquivoLabel = $importacao->filename ?: 'Importação #'.$importacao->id;
    $tabIdQuery = request()->query('tab_id', '');
@endphp

<div class="bg-gray-100 min-h-screen" id="xml-detalhes-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        {{-- Header --}}
        <div class="mb-4 sm:mb-6 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <a href="/app/importacao/historico" data-link class="inline-flex items-center gap-2 text-xs text-gray-600 hover:text-gray-900 hover:underline mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Voltar para importações
                </a>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">
                    Detalhe da Importação XML
                </h1>
                <p class="text-xs text-gray-500 mt-1">Consulte o resultado consolidado da extração dos documentos fiscais importados via XML.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="/app/importacao/xml" data-link class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-medium">Nova importação</a>
                @if(! $emProcessamento)
                    <button type="button"
                        data-excluir-xml="{{ $importacao->id }}"
                        data-filename="{{ $importacao->filename ?: ('Importação #'.$importacao->id) }}"
                        data-redirect="/app/importacao/historico"
                        class="px-3 py-1.5 rounded text-xs font-medium text-white" style="background-color: #dc2626">
                        Excluir
                    </button>
                @endif
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="{{ $tipoStyle }}">{{ $tipoDocLabel }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="{{ $badgeStyle }}">{{ $badgeLabel }}</span>
            </div>
        </div>

        {{-- Banner de erro --}}
        @if($importacao->status === 'erro')
            @include('autenticado.partials.system-critical-error', [
                'errorUi' => $importacao->publicErrorUi([
                    'url' => request()->getPathInfo(),
                ]),
            ])
        @endif

        {{-- Info Card --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4" id="info-section">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-6 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="px-4 py-3 min-h-[96px] flex flex-col">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Importação</p>
                    <div class="flex-1 flex items-center">
                        <p class="text-lg font-bold text-gray-900">#{{ $importacao->id }}</p>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $importacao->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="px-4 py-3 min-h-[96px] flex flex-col">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Tipo Documento</p>
                    <div class="flex-1 flex items-center">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="{{ $tipoStyle }}">{{ $tipoDocLabel }}</span>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">Documentos importados</p>
                </div>
                <div class="px-4 py-3 min-h-[96px] flex flex-col">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Cliente</p>
                    <div class="flex-1 flex items-center">
                        <p class="text-sm font-bold text-gray-900">{{ $importacao->cliente?->razao_social ?? 'Não associado' }}</p>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $importacao->cliente?->documento_formatado ?? 'Sem vínculo a cliente' }}</p>
                </div>
                <div class="px-4 py-3 min-h-[96px] flex flex-col">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Tamanho</p>
                    <div class="flex-1 flex items-center">
                        <p class="text-sm font-bold text-gray-900">{{ $importacao->tamanho_formatado }}</p>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">arquivo enviado</p>
                </div>
                <div class="px-4 py-3 min-h-[96px] flex flex-col">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Status</p>
                    <div class="flex-1 flex items-center">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="{{ $badgeStyle }}">{{ $badgeLabel }}</span>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $importacao->tempo_processamento ?: 'Em andamento' }}</p>
                </div>
                <div class="px-4 py-3 min-h-[96px] flex flex-col">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Processado em</p>
                    <div class="flex-1 flex items-center">
                        <p class="text-sm font-bold text-gray-900">{{ $importacao->concluido_em?->format('d/m/Y H:i') ?? 'Em andamento' }}</p>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-1">última atualização disponível</p>
                </div>
            </div>

            @if($importacao->filename)
            <div class="px-4 py-3 border-t border-gray-200">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Arquivo</p>
                <p class="text-xs font-mono text-gray-700 break-all">{{ $arquivoLabel }}</p>
            </div>
            @endif
        </div>

        {{-- Andamento da importação (throttle no padrão de toda automação).
             Enquanto processando, o resultado consolidado fica oculto; ao concluir, o
             xml-importacao-progresso.js recarrega a página e o servidor renderiza tudo. --}}
        @if($emProcessamento)
            <div id="xml-progresso-root"
                 class="mb-4"
                 data-tab-id="{{ $tabIdQuery }}"
                 data-importacao-id="{{ $importacao->id }}"
                 data-iniciado-em="{{ optional($importacao->created_at)->timestamp }}">
                <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Andamento da Importação</span>
                        <span id="xml-progresso-percent" class="text-[10px] text-gray-500 font-mono">0%</span>
                    </div>
                    <div class="p-4">
                        <div class="w-full h-1.5 rounded-full overflow-hidden" style="background-color: #e5e7eb">
                            <div id="xml-progresso-bar" class="h-full" style="background-color: #1f2937; width: 0%; transition: width 350ms ease-out"></div>
                        </div>
                        <p id="xml-progresso-etapa" class="text-xs text-gray-600 mt-3">Preparando importação...</p>
                        @include('autenticado.partials.progresso-tempo', [
                            'prefixo' => 'xml-progresso',
                            'dica' => 'lendo e gravando as notas — pode levar alguns instantes em lotes grandes.',
                        ])
                    </div>
                </div>
            </div>

            <div class="bg-white rounded border border-gray-300 p-6 text-center">
                <p class="text-sm text-gray-500">Os dados da importação aparecerão aqui assim que o processamento terminar.</p>
                <p class="text-xs text-gray-400 mt-1">Você pode fechar esta aba e voltar depois pelo histórico.</p>
            </div>
        @endif

        @if(! $emProcessamento)
        @php $notasColl = $notas ?? collect(); @endphp
        @include('autenticado.importacao.xml-detalhes._sticky-nav')

        {{-- Decidir depois: escolher qual lado é o cliente, vendo os dados de cada parte --}}
        @if($definirClienteCandidatos ?? null)
        @php
            $fmtDoc = fn ($d) => $d && strlen($d) === 14
                ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $d)
                : ($d ?: '—');
        @endphp
        <div class="bg-white rounded border border-gray-300 border-l-4 mb-4 overflow-hidden" id="definir-cliente-card" data-importacao-id="{{ $importacao->id }}" style="border-left-color: #1d4ed8">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Defina o cliente deste lote</span>
            </div>
            <div class="p-4">
                <p class="text-sm text-gray-700 mb-3">Esta importação foi feita sem definir o cliente. Confirme qual lado é o cliente — o outro lado vira participante:</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach(['emit' => 'Emitente · saídas', 'dest' => 'Destinatário · entradas'] as $lado => $titulo)
                        @php $cand = $definirClienteCandidatos[$lado] ?? []; @endphp
                        <button type="button" data-definir-cliente-lado="{{ $lado }}"
                            class="text-left p-3 rounded border border-gray-300 hover:border-gray-700 hover:bg-gray-50 transition disabled:opacity-50"
                            @disabled(empty($cand['documento']))>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">{{ $titulo }}</p>
                            <p class="text-sm font-semibold text-gray-900 mt-1 truncate" title="{{ $cand['razao'] ?? '' }}">{{ $cand['razao'] ?: '—' }}</p>
                            <p class="text-xs font-mono text-gray-600">{{ $fmtDoc($cand['documento'] ?? null) }}</p>
                            @if(($cand['distintos'] ?? 0) > 1)
                                <p class="text-[11px] text-gray-400 mt-1">+{{ $cand['distintos'] - 1 }} {{ $cand['distintos'] - 1 === 1 ? 'outro' : 'outros' }} neste lado</p>
                            @endif
                        </button>
                    @endforeach
                </div>
                <p id="definir-cliente-erro" class="hidden text-xs mt-2" style="color:#dc2626"></p>
            </div>
        </div>
        <script>
        (function () {
            var card = document.getElementById('definir-cliente-card');
            if (!card || card.dataset.bound === '1') return;
            card.dataset.bound = '1';
            var impId = card.dataset.importacaoId;
            var erro = document.getElementById('definir-cliente-erro');
            function csrf() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.getAttribute('content') : ''; }
            card.querySelectorAll('[data-definir-cliente-lado]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var lado = btn.getAttribute('data-definir-cliente-lado');
                    card.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
                    if (erro) erro.classList.add('hidden');
                    fetch('/app/importacao/xml/' + impId + '/definir-cliente', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json' },
                        body: JSON.stringify({ lado: lado })
                    })
                    .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                    .then(function (res) {
                        if (!res.ok || !res.j.success) {
                            if (erro) { erro.textContent = res.j.error || 'Falha ao definir o cliente.'; erro.classList.remove('hidden'); }
                            card.querySelectorAll('button').forEach(function (b) { b.disabled = false; });
                            return;
                        }
                        window.location.reload();
                    })
                    .catch(function () {
                        if (erro) { erro.textContent = 'Erro de rede.'; erro.classList.remove('hidden'); }
                        card.querySelectorAll('button').forEach(function (b) { b.disabled = false; });
                    });
                });
            });
        })();
        </script>
        @endif

        {{-- Indicadores --}}
        @php
            $valorTotal = $importacao->valor_total ?? 0;
            $xmlsComErro = $importacao->xmls_com_erro ?? 0;
        @endphp
        <div class="bg-white rounded border border-gray-300 mb-4 overflow-hidden" id="indicadores-section">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resultado Consolidado</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-6 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Total XMLs</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($importacao->total_xmls ?? 0) }}</p>
                    <p class="text-[11px] text-gray-500">documentos no envio</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Novos</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($importacao->xmls_novos ?? 0) }}</p>
                    <p class="text-[11px] text-gray-500">importados</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Duplicados</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($importacao->xmls_duplicados_processados ?? 0) }}</p>
                    <p class="text-[11px] text-gray-500">já no acervo</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Com erro</p>
                    <p class="text-lg font-bold {{ $xmlsComErro > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($xmlsComErro) }}</p>
                    <p class="text-[11px] text-gray-500">não processados</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participantes</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($importacao->participantes_novos ?: count($importacao->participante_ids ?? [])) }}</p>
                    <p class="text-[11px] text-gray-500">extraídos das notas</p>
                </div>
                <div class="px-4 py-3">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor Total</p>
                    <p class="text-lg font-bold text-gray-900">@if($valorTotal)R$ {{ number_format($valorTotal, 2, ',', '.') }}@else<span class="text-gray-400">—</span>@endif</p>
                    <p class="text-[11px] text-gray-500">somatório dos documentos</p>
                </div>
            </div>
        </div>

        @php
            $notasColl = $notas ?? collect();
            $loteTodoDuplicado = $importacao->status === 'concluido'
                && ($importacao->xmls_novos ?? 0) === 0
                && ($importacao->xmls_duplicados_processados ?? 0) > 0;
        @endphp

        {{-- Lote 100% duplicado: explica por que o resultado vem vazio --}}
        @if($loteTodoDuplicado)
        <div class="bg-white rounded border border-gray-300 border-l-4 mb-4 px-4 py-3" style="border-left-color: #d97706">
            <p class="text-sm font-semibold text-gray-900">Nenhuma nota nova neste lote</p>
            <p class="text-xs text-gray-600 mt-1">
                {{ number_format($importacao->xmls_duplicados_processados) }} {{ $importacao->xmls_duplicados_processados == 1 ? 'nota já existia' : 'notas já existiam' }} no seu acervo e {{ $importacao->xmls_duplicados_processados == 1 ? 'foi ignorada' : 'foram ignoradas' }}. As notas continuam disponíveis na importação original.
            </p>
            <a href="/app/notas" data-link class="inline-flex items-center gap-1.5 mt-2 text-xs font-semibold text-gray-700 hover:text-gray-900 hover:underline">
                Ver notas no acervo
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        @endif

        @include('autenticado.importacao.xml-detalhes._resumo-tributario')

        {{-- Notas Fiscais importadas neste lote --}}
        <div class="bg-white rounded border border-gray-300 mb-4 overflow-hidden" id="notas-section">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Notas Fiscais Importadas</span>
                    @if($notasColl->count() > 0)
                        <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $notasColl->count() }}</span>
                    @endif
                </div>
            </div>
            @if($notasColl->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Nº / Série</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Emissão</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Emitente</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Destinatário</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($notasColl as $nota)
                        @php
                            $ehSaida = $nota->tipo_nota === \App\Models\XmlNota::TIPO_SAIDA;
                            $tipoHex = $ehSaida ? '#d97706' : '#047857';
                            $donoEmit = $nota->lado_dono === 'emit';
                            $donoDest = $nota->lado_dono === 'dest';
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-3 py-3 text-sm font-mono text-gray-900 whitespace-nowrap">{{ $nota->numero_documento ?: '—' }}<span class="text-gray-400">/{{ $nota->serie ?: '0' }}</span></td>
                            <td class="px-3 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $nota->data_emissao?->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-3 py-3 max-w-[220px]">
                                <p class="text-sm text-gray-900 truncate" title="{{ $nota->emit_razao_social }}">{{ $nota->emit_razao_social ?: $nota->emit_documento_formatado ?: '—' }}</p>
                                <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $donoEmit ? '#1d4ed8' : '#6b7280' }}">{{ $donoEmit ? 'Cliente' : 'Participante' }}</span>
                            </td>
                            <td class="px-3 py-3 max-w-[220px]">
                                <p class="text-sm text-gray-700 truncate" title="{{ $nota->dest_razao_social }}">{{ $nota->dest_razao_social ?: $nota->dest_documento_formatado ?: '—' }}</p>
                                <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $donoDest ? '#1d4ed8' : '#6b7280' }}">{{ $donoDest ? 'Cliente' : 'Participante' }}</span>
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-900 font-mono text-right whitespace-nowrap">{{ $nota->valor_formatado }}</td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tipoHex }}">{{ $nota->tipo_nota_descricao }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(($importacao->xmls_novos ?? 0) > $notasColl->count())
            <div class="px-4 py-2 border-t border-gray-200">
                <p class="text-[11px] text-gray-500">Exibindo as primeiras {{ $notasColl->count() }} notas de {{ number_format($importacao->xmls_novos) }}.</p>
            </div>
            @endif
            @else
            <div class="px-6 py-10 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm font-medium text-gray-700">{{ $loteTodoDuplicado ? 'Notas já existiam no acervo' : 'Nenhuma nota importada' }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $loteTodoDuplicado ? 'Este lote não gerou notas novas — todas eram duplicadas.' : 'Esta importação não gravou notas fiscais.' }}</p>
            </div>
            @endif
        </div>

        @include('autenticado.importacao.xml-detalhes._catalogo')

        {{-- Card Cliente --}}
        <div class="bg-white rounded border border-gray-300 mb-4">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Cliente Associado</span>
            </div>
            <div class="p-4">
                @if($importacao->cliente)
                    <div class="flex items-center gap-4 flex-wrap">
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Razão Social</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $importacao->cliente->razao_social }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">{{ $importacao->cliente->tipo_pessoa === 'PJ' ? 'CNPJ' : 'CPF' }}</p>
                            <p class="text-sm font-mono text-gray-900">{{ $importacao->cliente->documento_formatado ?? $importacao->cliente->documento ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Tipo</p>
                            <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded text-white" style="background-color: #374151">
                                {{ $importacao->cliente->tipo_pessoa === 'PF' ? 'Pessoa Física' : 'Pessoa Jurídica' }}
                            </span>
                        </div>
                        <div class="ml-auto">
                            <a
                                href="/app/cliente/{{ $importacao->cliente->id }}"
                                data-link
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 transition"
                            >
                                Ver no cadastro
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400 italic">Nenhum cliente associado a esta importação.</p>
                @endif
            </div>
        </div>

        {{-- Participantes --}}
        <div class="bg-white rounded border border-gray-300" id="participantes-section">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Participantes</span>
                    @if($participantes->total() > 0)
                        <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $participantes->total() }}</span>
                    @endif
                </div>
                @if($participantes->total() > 0)
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label for="per-page-participantes-xml" class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Por pág.</label>
                        <select id="per-page-participantes-xml" class="border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400 px-2 py-1.5 bg-white" onchange="let u = new URL(window.location.href); u.searchParams.set('per_page_participantes', this.value); u.searchParams.delete('page'); const a = document.createElement('a'); a.href = u.toString(); a.setAttribute('data-link', ''); document.body.appendChild(a); a.click(); a.remove();">
                            <option value="10" {{ request('per_page_participantes', 10) == 10 ? 'selected' : '' }}>10 por pág.</option>
                            <option value="25" {{ request('per_page_participantes') == 25 ? 'selected' : '' }}>25 por pág.</option>
                            <option value="50" {{ request('per_page_participantes') == 50 ? 'selected' : '' }}>50 por pág.</option>
                            <option value="100" {{ request('per_page_participantes') == 100 ? 'selected' : '' }}>100 por pág.</option>
                        </select>
                    </div>
                    <div class="relative">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            type="text"
                            id="busca-participantes-xml"
                            placeholder="Buscar participante..."
                            class="pl-9 pr-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400 w-64"
                        >
                    </div>
                </div>
                @endif
            </div>

            @if($participantes->total() > 0)
            {{-- Desktop: Table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full" id="tabela-participantes-xml">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNPJ/CPF</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Razão Social</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">UF</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Situação</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CRT</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Cadastro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="tbody-participantes-xml">
                        @foreach($participantes as $part)
                        <tr
                            class="hover:bg-gray-50/50 cursor-pointer transition-colors"
                            data-href="/app/participante/{{ $part->id }}"
                            data-razao="{{ strtolower($part->razao_social ?: '') }}"
                            data-doc="{{ $part->cnpj_formatado ?: $part->cpf ?: '' }}"
                        >
                            <td class="px-3 py-3 text-sm font-mono text-gray-900 whitespace-nowrap">{{ $part->cnpj_formatado ?: $part->cpf ?: '—' }}</td>
                            <td class="px-3 py-3 text-sm text-gray-900 max-w-[280px] truncate" title="{{ $part->razao_social ?: 'Razão social não informada' }}">{{ $part->razao_social ?: '—' }}</td>
                            <td class="px-3 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $part->uf ?: '—' }}</td>
                            <td class="px-3 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $part->situacao_cadastral ?: '—' }}</td>
                            <td class="px-3 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $part->crt ?: '—' }}</td>
                            <td class="px-3 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $part->tipo_pessoa ?: ($part->documento && strlen(preg_replace('/[^0-9]/', '', $part->documento)) === 11 ? 'PF' : 'PJ') }}</td>
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                <a href="/app/participante/{{ $part->id }}" data-link onclick="event.stopPropagation()" class="inline-flex items-center gap-1 text-xs font-semibold text-gray-700 hover:text-gray-900 hover:underline">
                                    Ver no cadastro
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: Cards --}}
            <div class="md:hidden divide-y divide-gray-100" id="mobile-participantes-xml">
                @foreach($participantes as $part)
                <div
                    class="px-4 py-4 cursor-pointer hover:bg-gray-50 transition-colors"
                    data-href="/app/participante/{{ $part->id }}"
                    data-razao="{{ strtolower($part->razao_social ?: '') }}"
                    data-doc="{{ $part->cnpj_formatado ?: $part->cpf ?: '' }}"
                >
                    <p class="text-sm font-medium text-gray-900">{{ $part->razao_social ?: '—' }}</p>
                    <p class="text-xs font-mono text-gray-500 mt-0.5">{{ $part->cnpj_formatado ?: $part->cpf ?: '—' }}</p>
                    <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                        @if($part->uf) <span>{{ $part->uf }}</span> @endif
                        @if($part->situacao_cadastral) <span>&middot;</span><span>{{ $part->situacao_cadastral }}</span> @endif
                    </div>
                    <a href="/app/participante/{{ $part->id }}" data-link onclick="event.stopPropagation()" class="inline-flex items-center gap-1 mt-2 text-xs font-semibold text-gray-700 hover:text-gray-900 hover:underline">
                        Ver no cadastro
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                @endforeach
            </div>

            {{-- Paginação --}}
            @if($participantes->hasPages())
            <div class="border-t border-gray-300 px-4 py-3 flex items-center justify-between gap-3 flex-wrap">
                <span class="text-[10px] text-gray-500 uppercase tracking-wide">
                    Mostrando {{ $participantes->firstItem() }}–{{ $participantes->lastItem() }} de {{ $participantes->total() }} participantes
                </span>
                <div class="flex items-center gap-2">
                    @if($participantes->onFirstPage())
                        <span class="px-3 py-1.5 text-[10px] text-gray-400 bg-gray-100 border border-gray-200 rounded">Anterior</span>
                    @else
                        <a href="{{ $participantes->previousPageUrl() }}" data-link class="px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Anterior</a>
                    @endif

                    <span class="text-[10px] text-gray-500 uppercase tracking-wide">{{ $participantes->currentPage() }} / {{ $participantes->lastPage() }}</span>

                    @if($participantes->hasMorePages())
                        <a href="{{ $participantes->nextPageUrl() }}" data-link class="px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Próxima</a>
                    @else
                        <span class="px-3 py-1.5 text-[10px] text-gray-400 bg-gray-100 border border-gray-200 rounded">Próxima</span>
                    @endif
                </div>
            </div>
            @endif

            {{-- Zero-state de busca --}}
            <div id="zero-state-busca-xml" class="hidden px-6 py-12 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <p class="text-sm text-gray-500">Nenhum participante encontrado para esta busca.</p>
            </div>

            @else
            {{-- Zero-state --}}
            <div class="px-6 py-12 text-center">
                @if($importacao->status === 'processando' || $importacao->status === 'pendente')
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-700">Importação em andamento</p>
                    <p class="text-xs text-gray-500 mt-1">Os participantes aparecerão aqui quando o processamento for concluído.</p>
                @elseif($importacao->status === 'erro')
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-700">Nenhum participante extraído</p>
                    <p class="text-xs text-gray-500 mt-1">A importação terminou com erro. Nenhum participante foi extraído.</p>
                @else
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-700">Nenhum participante encontrado</p>
                    <p class="text-xs text-gray-500 mt-1">Esta importação não gerou participantes.</p>
                @endif
            </div>
            @endif
        </div>

        @include('autenticado.importacao.xml-detalhes._alertas')
        @endif

    </div>
</div>

@if($emProcessamento)
<script src="/js/progresso-automacao.js?v={{ @filemtime(public_path('js/progresso-automacao.js')) ?: time() }}"></script>
<script src="/js/xml-importacao-progresso.js?v={{ @filemtime(public_path('js/xml-importacao-progresso.js')) ?: time() }}"></script>
@endif

<script>
(function () {
    // Row-click navigation (SPA-aware)
    function navigateToHref(el) {
        var href = el.getAttribute('data-href');
        if (!href) return;
        var link = document.createElement('a');
        link.setAttribute('data-link', '');
        link.href = href;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    document.querySelectorAll('[data-href]').forEach(function (row) {
        row.addEventListener('click', function () { navigateToHref(this); });
    });

    // Sticky-nav scrollspy (paridade com o resultado do EFD): destaca a seção ativa,
    // faz scroll suave ao clicar e revela o "Voltar" flutuante ao rolar.
    (function initScrollSpy() {
        var nav = document.getElementById('xml-sticky-nav');
        if (!nav) return;
        var links = Array.prototype.slice.call(nav.querySelectorAll('.xml-nav-link'));
        var sections = links.map(function (link) {
            var id = link.getAttribute('href');
            var el = id && id.charAt(0) === '#' ? document.getElementById(id.substring(1)) : null;
            if (el) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    var top = el.getBoundingClientRect().top + window.pageYOffset - 90;
                    window.scrollTo({ top: top, behavior: 'smooth' });
                });
            }
            return { link: link, el: el };
        }).filter(function (s) { return s.el; });

        var btnVoltar = document.getElementById('xml-btn-voltar-sticky');

        function onScroll() {
            var y = window.scrollY + 110;
            var active = null;
            for (var i = sections.length - 1; i >= 0; i--) {
                if (sections[i].el.offsetTop <= y) { active = sections[i].link; break; }
            }
            links.forEach(function (l) {
                l.classList.remove('bg-gray-800', 'text-white');
                l.classList.add('text-gray-600');
            });
            if (active) { active.classList.add('bg-gray-800', 'text-white'); active.classList.remove('text-gray-600'); }
            if (btnVoltar) {
                var revelar = window.scrollY > 200;
                btnVoltar.classList.toggle('opacity-0', !revelar);
                btnVoltar.classList.toggle('pointer-events-none', !revelar);
                btnVoltar.classList.toggle('translate-x-4', !revelar);
                btnVoltar.classList.toggle('opacity-100', revelar);
                btnVoltar.classList.toggle('translate-x-0', revelar);
            }
        }

        if (window._cleanupFunctions && window._cleanupFunctions.xmlDetalhesScroll) {
            window._cleanupFunctions.xmlDetalhesScroll();
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
        window._cleanupFunctions = window._cleanupFunctions || {};
        window._cleanupFunctions.xmlDetalhesScroll = function () {
            window.removeEventListener('scroll', onScroll);
        };
    })();

    // Client-side search filter
    var input = document.getElementById('busca-participantes-xml');
    if (!input) return;

    input.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        var rows   = document.querySelectorAll('#tbody-participantes-xml tr');
        var cards  = document.querySelectorAll('#mobile-participantes-xml > div');
        var zeroBusca = document.getElementById('zero-state-busca-xml');
        var visible = 0;

        function filterEl(el) {
            var razao = el.getAttribute('data-razao') || '';
            var doc   = el.getAttribute('data-doc')   || '';
            var match = !q || razao.includes(q) || doc.includes(q);
            el.style.display = match ? '' : 'none';
            if (match) visible++;
        }

        rows.forEach(filterEl);
        cards.forEach(filterEl);

        if (zeroBusca) zeroBusca.classList.toggle('hidden', visible > 0 || !q);
    });
})();
</script>

@if(! $emProcessamento)
    @include('autenticado.importacao._modal-excluir-xml')
@endif
