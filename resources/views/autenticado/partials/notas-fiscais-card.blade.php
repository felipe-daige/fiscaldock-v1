{{-- Card modular de Notas Fiscais (EFD + XML unificadas) --}}
{{-- Variáveis: $notas (paginator), $totalNotas (int), $ajaxUrl (string), $contexto ('participante'|'cliente'), $entityId (int) --}}
<div class="bg-white rounded border border-gray-300 overflow-hidden" id="notas-fiscais-card">
    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
        <div class="flex items-center justify-between gap-3">
            <div>
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Notas Fiscais</span>
                <p class="text-[11px] text-gray-500 mt-1">Base unificada de EFD e XML vinculada a este cadastro.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ number_format($totalNotas, 0, ',', '.') }}</span>
                @if($totalNotas > 0)
                    <a href="/app/notas-fiscais?{{ $contexto }}_id={{ $entityId }}" data-link
                       class="text-xs text-gray-600 hover:text-gray-900 hover:underline font-medium whitespace-nowrap">
                        Ver todas
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if($totalNotas === 0)
        <div class="px-4 py-10 text-center">
            <svg class="mx-auto w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p class="text-sm text-gray-500">Nenhuma nota fiscal encontrada.</p>
        </div>
    @else
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-300">
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Origem</th>
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Nº / Série</th>
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Modelo</th>
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Emissão</th>
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>
                        @if($contexto !== 'participante')
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participante</th>
                        @endif
                        @if($contexto !== 'cliente')
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Cliente</th>
                        @endif
                        <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th>
                        <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50 w-12">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($notas as $n)
                        @php
                            $origem = strtolower($n['origem'] ?? '');
                            $origemLabel = strtoupper($n['origem'] ?? '—');
                            $origemHex = $origem === 'efd' ? '#4338ca' : '#0f766e';
                            $tipoEntrada = ($n['tipo_operacao'] ?? '') === 'entrada';
                            $tipoHex = $tipoEntrada ? '#047857' : '#d97706';
                            $tipoLabel = $tipoEntrada ? 'Entrada' : 'Saída';
                            $dataFormatada = $n['data_emissao'] ? \Carbon\Carbon::parse($n['data_emissao'])->format('d/m/Y') : '—';
                            $numero = $n['numero'] ?? '—';
                            $serie = $n['serie'] ? ' / ' . $n['serie'] : '';
                            $modeloLabel = $n['modelo_label'] ?? '—';
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors cursor-pointer nf-card-row" data-origem="{{ $n['origem'] }}" data-id="{{ $n['id'] }}">
                            <td class="px-3 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $origemHex }}">{{ $origemLabel }}</span>
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-700 font-mono whitespace-nowrap">{{ $numero }}{{ $serie }}</td>
                            <td class="px-3 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ $modeloLabel }}</span>
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $dataFormatada }}</td>
                            <td class="px-3 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tipoHex }}">{{ $tipoLabel }}</span>
                            </td>
                            @if($contexto !== 'participante')
                                <td class="px-3 py-3 text-sm text-gray-700 max-w-xs">
                                    @if($n['participante_id'])
                                        <a href="/app/participante/{{ $n['participante_id'] }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline block truncate">
                                            {{ $n['participante_nome'] ?? '—' }}
                                        </a>
                                    @else
                                        <div class="truncate">{{ $n['participante_nome'] ?? '—' }}</div>
                                    @endif
                                    @if($n['participante_doc'])
                                        <div class="text-[11px] font-mono text-gray-400 mt-0.5">{{ $n['participante_doc'] }}</div>
                                    @endif
                                </td>
                            @endif
                            @if($contexto !== 'cliente')
                                <td class="px-3 py-3 text-sm text-gray-700 max-w-[10rem]">
                                    @if($n['cliente_id'])
                                        <a href="/app/cliente/{{ $n['cliente_id'] }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline truncate block">
                                            {{ $n['cliente_nome'] ?? '—' }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-3 py-3 text-sm font-semibold text-gray-900 text-right whitespace-nowrap">
                                R$ {{ number_format($n['valor_total'], 2, ',', '.') }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                <button type="button" class="nf-card-expand-btn text-gray-400 hover:text-gray-700 transition-colors p-1" data-origem="{{ $n['origem'] }}" data-id="{{ $n['id'] }}" title="Ver detalhes">
                                    <svg class="w-5 h-5 nf-card-expand-icon transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr class="nf-card-detail-row hidden" data-detail-for="{{ $n['origem'] }}-{{ $n['id'] }}">
                            <td colspan="{{ $contexto === 'participante' || $contexto === 'cliente' ? 8 : 9 }}" class="px-0 py-0">
                                <div class="nf-card-detail-content bg-gray-50 border-t border-gray-200"></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="md:hidden divide-y divide-gray-100">
            @foreach($notas as $n)
                @php
                    $origem = strtolower($n['origem'] ?? '');
                    $origemLabel = strtoupper($n['origem'] ?? '—');
                    $origemHex = $origem === 'efd' ? '#4338ca' : '#0f766e';
                    $tipoEntrada = ($n['tipo_operacao'] ?? '') === 'entrada';
                    $tipoHex = $tipoEntrada ? '#047857' : '#d97706';
                    $tipoLabel = $tipoEntrada ? 'Entrada' : 'Saída';
                    $dataFormatada = $n['data_emissao'] ? \Carbon\Carbon::parse($n['data_emissao'])->format('d/m/Y') : '—';
                    $numero = $n['numero'] ?? '—';
                    $serie = $n['serie'] ? ' / ' . $n['serie'] : '';
                    $modeloLabel = $n['modelo_label'] ?? '—';
                @endphp
                <div class="px-4 py-3 nf-card-mobile cursor-pointer" data-origem="{{ $n['origem'] }}" data-id="{{ $n['id'] }}">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $origemHex }}">{{ $origemLabel }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ $modeloLabel }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tipoHex }}">{{ $tipoLabel }}</span>
                        </div>
                        <button type="button" class="nf-card-expand-btn text-gray-400 hover:text-gray-700 p-2 -mr-2 min-w-[40px] min-h-[40px] flex items-center justify-center" data-origem="{{ $n['origem'] }}" data-id="{{ $n['id'] }}">
                            <svg class="w-5 h-5 nf-card-expand-icon transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="text-sm font-mono font-medium text-gray-900">{{ $numero }}{{ $serie }}</span>
                        <span class="text-sm font-semibold text-gray-900">R$ {{ number_format($n['valor_total'], 2, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between mt-1 gap-2">
                        <span class="text-xs text-gray-500">{{ $dataFormatada }}</span>
                        @if($contexto !== 'participante' && ($n['participante_nome'] ?? null))
                            <span class="text-xs text-gray-500 truncate max-w-[50%]">{{ $n['participante_nome'] }}</span>
                        @elseif($contexto !== 'cliente' && ($n['cliente_nome'] ?? null))
                            <span class="text-xs text-gray-500 truncate max-w-[50%]">{{ $n['cliente_nome'] }}</span>
                        @endif
                    </div>
                    <div class="nf-card-detail-mobile hidden mt-3" data-detail-for="{{ $n['origem'] }}-{{ $n['id'] }}">
                        <div class="nf-card-detail-content bg-gray-50 rounded border border-gray-200 p-2"></div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($notas->hasPages())
            <div class="border-t border-gray-300 px-4 py-3 flex items-center justify-between gap-3">
                <p class="text-[10px] text-gray-500 uppercase tracking-wide">
                    Mostrando {{ $notas->firstItem() }}–{{ $notas->lastItem() }} de {{ $totalNotas }}
                </p>
                <div class="flex items-center gap-1">
                    @if($notas->currentPage() > 1)
                        <button type="button" data-nf-page="{{ $notas->currentPage() - 1 }}"
                            class="px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                            Anterior
                        </button>
                    @endif
                    <span class="px-3 py-1.5 text-[10px] text-gray-600">{{ $notas->currentPage() }} / {{ $notas->lastPage() }}</span>
                    @if($notas->hasMorePages())
                        <button type="button" data-nf-page="{{ $notas->currentPage() + 1 }}"
                            class="px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                            Próxima
                        </button>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>

<script>
(function() {
    if (window._cleanupFunctions && window._cleanupFunctions.notasFiscaisCard) {
        window._cleanupFunctions.notasFiscaisCard();
    }

    var ajaxUrl = @json($ajaxUrl);
    var detailCache = {};

    function toggleDetail(container, origem, id, btnEl) {
        var key = origem + '-' + id;
        var detailRow = container.querySelector('tr.nf-card-detail-row[data-detail-for="' + key + '"]');
        var detailMobile = container.querySelector('.nf-card-detail-mobile[data-detail-for="' + key + '"]');
        var target = detailRow || detailMobile;
        if (!target) return;

        var icon = btnEl ? btnEl.querySelector('.nf-card-expand-icon') : null;
        var isOpen = !target.classList.contains('hidden');

        if (isOpen) {
            target.classList.add('hidden');
            if (icon) icon.style.transform = '';
            return;
        }

        target.classList.remove('hidden');
        if (icon) icon.style.transform = 'rotate(180deg)';

        var contentEl = target.querySelector('.nf-card-detail-content');
        if (!contentEl) return;

        if (detailCache[key]) {
            contentEl.innerHTML = detailCache[key];
            return;
        }

        contentEl.innerHTML = '<div class="p-4 text-center text-sm text-gray-500">Carregando...</div>';

        fetch('/app/notas-fiscais/' + origem + '/' + id, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
        })
        .then(function(r) { return r.text(); })
        .then(function(html) {
            detailCache[key] = html;
            contentEl.innerHTML = html;
        })
        .catch(function() {
            contentEl.innerHTML = '<div class="p-4 text-center text-sm text-red-500">Erro ao carregar detalhes</div>';
        });
    }

    function handleClick(e) {
        var container = document.getElementById('notas-fiscais-card');
        if (!container || !container.contains(e.target)) return;

        var pageBtn = e.target.closest('[data-nf-page]');
        if (pageBtn) {
            e.preventDefault();
            e.stopPropagation();
            var page = pageBtn.dataset.nfPage;
            fetch(ajaxUrl + '?page=' + page, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var tmp = document.createElement('div');
                tmp.innerHTML = html;
                var newCard = tmp.querySelector('#notas-fiscais-card');
                if (newCard) {
                    container.innerHTML = newCard.innerHTML;
                }
            });
            return;
        }

        if (e.target.closest('a, button[type="submit"], input, select, textarea, label')) {
            return;
        }

        var expandBtn = e.target.closest('.nf-card-expand-btn');
        if (expandBtn) {
            e.preventDefault();
            e.stopPropagation();
            toggleDetail(container, expandBtn.dataset.origem, expandBtn.dataset.id, expandBtn);
            return;
        }

        var wrapper = e.target.closest('.nf-card-row, .nf-card-mobile');
        if (!wrapper) return;
        var chevron = wrapper.querySelector('.nf-card-expand-btn');
        if (!chevron) return;
        toggleDetail(container, chevron.dataset.origem, chevron.dataset.id, chevron);
    }

    document.addEventListener('click', handleClick);

    if (!window._cleanupFunctions) window._cleanupFunctions = {};
    window._cleanupFunctions.notasFiscaisCard = function() {
        document.removeEventListener('click', handleClick);
    };
})();
</script>
