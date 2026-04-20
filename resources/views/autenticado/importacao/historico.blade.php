@php
    $totalImportacoes = $importacoes->count();
    $totalEfd = $importacoes->where('_tipo', 'efd')->count();
    $totalXml = $importacoes->where('_tipo', 'xml')->count();
@endphp

{{-- Histórico Unificado de Importações --}}
<div class="min-h-screen bg-gray-100" id="historico-importacoes-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-8">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Histórico de Importações</h1>
            <p class="text-xs text-gray-500 mt-1">Consolidado operacional das importações EFD e XML processadas pela conta.</p>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-3 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Total</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($totalImportacoes, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">operações registradas</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">EFD</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($totalEfd, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">arquivos SPED</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">XML</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($totalXml, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">lotes XML</p>
                </div>
            </div>
        </div>

        @if($importacoes->isNotEmpty())
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 hidden sm:block">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtro</span>
                </div>
                <div class="px-4 py-4">
                    <div class="flex items-center gap-2 flex-wrap" id="filtro-tipo-wrapper">
                        <button
                            type="button"
                            data-tipo="todos"
                            class="filtro-tipo px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wide border rounded bg-gray-800 text-white border-gray-800 hover:bg-gray-800 hover:text-white"
                        >Todos</button>
                        <button
                            type="button"
                            data-tipo="efd"
                            class="filtro-tipo px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wide border rounded bg-white text-gray-700 border-gray-300 hover:bg-gray-50"
                        >EFD</button>
                        <button
                            type="button"
                            data-tipo="xml"
                            class="filtro-tipo px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wide border rounded bg-white text-gray-700 border-gray-300 hover:bg-gray-50"
                        >XML</button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="hidden md:block overflow-hidden">
                    <table class="w-full table-fixed">
                        <colgroup>
                            <col style="width: 10%">
                            <col style="width: 12%">
                            <col style="width: 35%">
                            <col style="width: 11%">
                            <col style="width: 7%">
                            <col style="width: 10%">
                            <col style="width: 9%">
                            <col style="width: 8%">
                        </colgroup>
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Origem</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Arquivo / Lote</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Cliente</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Data</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Duração</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Volume</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Status</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($importacoes as $imp)
                                @php
                                    $tipo = $imp['_tipo'];
                                    $id = $imp['id'];
                                    $dataFormatada = isset($imp['created_at']) ? \Carbon\Carbon::parse($imp['created_at'])->format('d/m/Y H:i') : '—';
                                    $filename = $imp['filename'] ?? $imp['arquivo'] ?? ('Importação #' . $id);
                                    $clienteNome = $imp['cliente']['razao_social'] ?? 'Sem cliente';
                                    $clienteId = $imp['cliente']['id'] ?? null;

                                    $tempoProc = '—';
                                    if (!empty($imp['iniciado_em']) && !empty($imp['concluido_em'])) {
                                        $inicio = \Carbon\Carbon::parse($imp['iniciado_em']);
                                        $fim = \Carbon\Carbon::parse($imp['concluido_em']);
                                        $diff = $inicio->diff($fim);
                                        if ($diff->h > 0) {
                                            $tempoProc = $diff->h . 'h ' . $diff->i . 'm';
                                        } elseif ($diff->i > 0) {
                                            $tempoProc = $diff->i . 'm ' . $diff->s . 's';
                                        } elseif ($diff->s > 0) {
                                            $tempoProc = $diff->s . 's';
                                        } else {
                                            $tempoProc = '< 1s';
                                        }
                                    }

                                    $origemDetalhe = null;

                                    if ($tipo === 'efd') {
                                        $href = '/app/importacao/efd/' . $id;
                                        $origemBadge = ($imp['tipo_efd'] ?? '') === 'EFD PIS/COFINS'
                                            ? ['label' => 'EFD', 'hex' => '#0f766e']
                                            : ['label' => 'EFD', 'hex' => '#4338ca'];
                                        $origemDetalhe = ($imp['tipo_efd'] ?? '') === 'EFD PIS/COFINS' ? 'PIS/COFINS' : 'Fiscal';
                                        $volume = (($imp['novos'] ?? 0) + ($imp['duplicados'] ?? 0)) . ' participante(s)';
                                    } else {
                                        $href = '/app/importacao/xml/' . $id;
                                        $origemBadge = match($imp['tipo_documento'] ?? '') {
                                            'nfe' => ['label' => 'NF-e', 'hex' => '#0f766e'],
                                            'nfse' => ['label' => 'NFS-e', 'hex' => '#374151'],
                                            'cte' => ['label' => 'CT-e', 'hex' => '#d97706'],
                                            default => ['label' => 'XML', 'hex' => '#374151'],
                                        };
                                        $total = $imp['total_xmls'] ?? 0;
                                        $volume = $total . ' XML' . ($total !== 1 ? 's' : '');
                                    }

                                    $statusBadge = match($imp['status'] ?? '') {
                                        'concluido' => ['label' => 'Concluído', 'hex' => '#047857'],
                                        'processando' => ['label' => 'Processando', 'hex' => '#d97706'],
                                        'erro' => ['label' => 'Erro', 'hex' => '#dc2626'],
                                        default => ['label' => 'Pendente', 'hex' => '#9ca3af'],
                                    };
                                @endphp
                                <tr class="hist-row hover:bg-gray-50/50 transition-colors" data-tipo="{{ $tipo }}">
                                    <td class="pl-3 pr-4 py-3">
                                        <div class="flex items-center gap-2 whitespace-nowrap">
                                            <span class="inline-block whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $origemBadge['hex'] }}">{{ $origemBadge['label'] }}</span>
                                            @if($origemDetalhe)
                                                <span class="text-[10px] text-gray-500 uppercase tracking-wide whitespace-nowrap">{{ $origemDetalhe }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="pl-4 pr-2 py-3 text-sm text-gray-700 max-w-0">
                                        <a href="{{ $href }}" data-link class="block truncate text-gray-900 hover:text-gray-600 hover:underline" title="{{ $filename }}">{{ $filename }}</a>
                                    </td>
                                    <td class="px-2 py-3 text-sm text-gray-700 max-w-0">
                                        @if($clienteId)
                                            <a href="/app/cliente/{{ $clienteId }}" data-link class="block truncate text-gray-900 hover:text-gray-600 hover:underline" title="{{ $clienteNome }}">{{ $clienteNome }}</a>
                                        @else
                                            <span class="block truncate" title="{{ $clienteNome }}">{{ $clienteNome }}</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $dataFormatada }}</td>
                                    <td class="px-2 py-3 text-sm text-gray-700 whitespace-nowrap">{{ $tempoProc }}</td>
                                    <td class="pl-2 pr-6 py-3 text-sm text-gray-700 whitespace-nowrap" title="{{ $volume }}">{{ $volume }}</td>
                                    <td class="px-1 py-3 whitespace-nowrap">
                                        <span class="inline-block max-w-full truncate px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white align-middle" style="background-color: {{ $statusBadge['hex'] }}">{{ $statusBadge['label'] }}</span>
                                    </td>
                                    <td class="pl-2 pr-3 py-3 text-right whitespace-nowrap">
                                        <a href="{{ $href }}" data-link class="text-xs text-blue-600 hover:text-blue-800 hover:underline">Abrir</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-gray-100 md:hidden" id="cards-grid">
                    @foreach($importacoes as $imp)
                        @php
                            $tipo = $imp['_tipo'];
                            $id = $imp['id'];
                            $dataFormatada = isset($imp['created_at']) ? \Carbon\Carbon::parse($imp['created_at'])->format('d/m/Y H:i') : '—';
                            $filename = $imp['filename'] ?? $imp['arquivo'] ?? ('Importação #' . $id);
                            $clienteNome = $imp['cliente']['razao_social'] ?? 'Sem cliente';
                            $clienteId = $imp['cliente']['id'] ?? null;

                            $tempoProc = '—';
                            if (!empty($imp['iniciado_em']) && !empty($imp['concluido_em'])) {
                                $inicio = \Carbon\Carbon::parse($imp['iniciado_em']);
                                $fim = \Carbon\Carbon::parse($imp['concluido_em']);
                                $diff = $inicio->diff($fim);
                                if ($diff->h > 0) {
                                    $tempoProc = $diff->h . 'h ' . $diff->i . 'm';
                                } elseif ($diff->i > 0) {
                                    $tempoProc = $diff->i . 'm ' . $diff->s . 's';
                                } elseif ($diff->s > 0) {
                                    $tempoProc = $diff->s . 's';
                                } else {
                                    $tempoProc = '< 1s';
                                }
                            }

                            $origemDetalhe = null;

                            if ($tipo === 'efd') {
                                $href = '/app/importacao/efd/' . $id;
                                $origemBadge = ($imp['tipo_efd'] ?? '') === 'EFD PIS/COFINS'
                                    ? ['label' => 'EFD', 'hex' => '#0f766e']
                                    : ['label' => 'EFD', 'hex' => '#4338ca'];
                                $origemDetalhe = ($imp['tipo_efd'] ?? '') === 'EFD PIS/COFINS' ? 'PIS/COFINS' : 'Fiscal';
                                $volume = (($imp['novos'] ?? 0) + ($imp['duplicados'] ?? 0)) . ' participante(s)';
                            } else {
                                $href = '/app/importacao/xml/' . $id;
                                $origemBadge = match($imp['tipo_documento'] ?? '') {
                                    'nfe' => ['label' => 'NF-e', 'hex' => '#0f766e'],
                                    'nfse' => ['label' => 'NFS-e', 'hex' => '#374151'],
                                    'cte' => ['label' => 'CT-e', 'hex' => '#d97706'],
                                    default => ['label' => 'XML', 'hex' => '#374151'],
                                };
                                $total = $imp['total_xmls'] ?? 0;
                                $volume = $total . ' XML' . ($total !== 1 ? 's' : '');
                            }

                            $statusBadge = match($imp['status'] ?? '') {
                                'concluido' => ['label' => 'Concluído', 'hex' => '#047857'],
                                'processando' => ['label' => 'Processando', 'hex' => '#d97706'],
                                'erro' => ['label' => 'Erro', 'hex' => '#dc2626'],
                                default => ['label' => 'Pendente', 'hex' => '#9ca3af'],
                            };
                        @endphp
                        <div class="hist-card px-4 py-3" data-tipo="{{ $tipo }}">
                            <div class="flex items-center gap-2 flex-wrap mb-2">
                                <span class="inline-block whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $origemBadge['hex'] }}">{{ $origemBadge['label'] }}</span>
                                @if($origemDetalhe)
                                    <span class="text-[10px] text-gray-500 uppercase tracking-wide whitespace-nowrap">{{ $origemDetalhe }}</span>
                                @endif
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $statusBadge['hex'] }}">{{ $statusBadge['label'] }}</span>
                            </div>
                            <a href="{{ $href }}" data-link class="block text-sm text-gray-900 hover:text-gray-600 hover:underline">{{ $filename }}</a>
                            <div class="mt-2 grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase">Cliente</p>
                                    @if($clienteId)
                                        <a href="/app/cliente/{{ $clienteId }}" data-link class="text-xs text-gray-900 hover:text-gray-600 hover:underline">{{ $clienteNome }}</a>
                                    @else
                                        <p class="text-xs text-gray-700">{{ $clienteNome }}</p>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase">Data</p>
                                    <p class="text-xs text-gray-700">{{ $dataFormatada }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase">Duração</p>
                                    <p class="text-xs text-gray-700">{{ $tempoProc }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase">Volume</p>
                                    <p class="text-xs text-gray-700">{{ $volume }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div id="zero-state-filtro" class="hidden py-16 text-center">
                <p class="text-sm text-gray-700">Nenhuma importação deste tipo encontrada.</p>
            </div>
        @else
            <div class="bg-white rounded border border-gray-300 p-8 sm:p-12 text-center">
                <p class="text-base font-semibold text-gray-700 mb-1">Nenhuma importação realizada ainda</p>
                <p class="text-sm text-gray-500 mb-6">Importe seus primeiros arquivos para começar a preencher este histórico.</p>
                <div class="flex items-center justify-center gap-3 flex-wrap">
                    <a href="/app/importacao/efd" data-link class="px-4 py-2 bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium">Importar EFD</a>
                    <a href="/app/importacao/xml" data-link class="px-4 py-2 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium">Importar XML</a>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
(function () {
    var btns = document.querySelectorAll('.filtro-tipo');
    var cards = document.querySelectorAll('.hist-card');
    var rows = document.querySelectorAll('.hist-row');
    var zeroFiltro = document.getElementById('zero-state-filtro');

    if (!btns.length) return;

    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tipo = this.getAttribute('data-tipo');
            var visiveis = 0;

            btns.forEach(function (b) {
                b.classList.remove('bg-gray-800', 'text-white', 'border-gray-800', 'hover:bg-gray-800', 'hover:text-white');
                b.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
            });

            this.classList.add('bg-gray-800', 'text-white', 'border-gray-800', 'hover:bg-gray-800', 'hover:text-white');
            this.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');

            cards.forEach(function (card) {
                var cardTipo = card.getAttribute('data-tipo');
                var show = tipo === 'todos' || cardTipo === tipo;
                card.style.display = show ? '' : 'none';
                if (show) visiveis++;
            });

            rows.forEach(function (row) {
                var rowTipo = row.getAttribute('data-tipo');
                var show = tipo === 'todos' || rowTipo === tipo;
                row.style.display = show ? '' : 'none';
            });

            if (zeroFiltro) zeroFiltro.classList.toggle('hidden', visiveis > 0);
        });
    });
})();
</script>
