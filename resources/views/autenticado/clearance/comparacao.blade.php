@php
    $sevHex = match ($comparacao->resumo->severidade) {
        'critica' => '#dc2626',
        'revisar' => '#d97706',
        'ruido' => '#9ca3af',
        default => '#047857',
    };
    $sevLabel = match ($comparacao->resumo->severidade) {
        'critica' => 'CRÍTICA',
        'revisar' => 'REVISAR',
        'ruido' => 'RUÍDO',
        default => 'OK',
    };
    $statusIcon = function (bool $div) {
        return $div
            ? '<span style="color: #d97706;">⚠</span>'
            : '<span style="color: #047857;">✓</span>';
    };
    $fmtMoney = fn ($v) => $v !== null ? 'R$ '.number_format((float) $v, 2, ',', '.') : '—';
    $fmtNum = fn ($v, $dec = 2) => $v !== null ? number_format((float) $v, $dec, ',', '.') : '—';
    $parteLabels = [
        'emit' => 'Emitente',
        'dest' => 'Destinatário',
        'tomador' => 'Tomador',
        'remetente' => 'Remetente',
        'expedidor' => 'Expedidor',
        'recebedor' => 'Recebedor',
    ];
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-4">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            @if ($lote_id)
                <a href="{{ route('app.clearance.notas.resultado', ['consultaLoteId' => $lote_id]) }}"
                    class="text-sm text-blue-700 hover:underline">← Voltar pro lote #{{ $lote_id }}</a>
            @endif
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Comparar declarado vs SEFAZ</h1>
            <p class="text-xs text-gray-500 font-mono mt-1">{{ $comparacao->chave }}</p>
            <p class="text-sm text-gray-700 mt-1">
                {{ $comparacao->tipoDocumento === 'CTE' ? 'CT-e' : 'NF-e' }}
                @if ($comparacao->declarado?->header['modelo'] ?? null)
                    · Modelo {{ $comparacao->declarado->header['modelo'] }}
                @endif
                @if ($comparacao->declarado?->header['serie'] ?? null)
                    · Série {{ $comparacao->declarado->header['serie'] }}
                @endif
                @if ($comparacao->declarado?->header['numero'] ?? null)
                    · Número {{ $comparacao->declarado->header['numero'] }}
                @endif
            </p>
        </div>
        <div>
            <span style="background-color: {{ $sevHex }}; color: white;"
                class="inline-block px-3 py-1.5 rounded text-xs font-bold uppercase tracking-wide">
                {{ $sevLabel }}
            </span>
        </div>
    </div>

    {{-- Origens --}}
    <div class="bg-white border border-gray-200 rounded-lg p-4 space-y-2">
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Origens</h2>
        <div class="text-sm text-gray-700">
            <div><strong>Declarado:</strong> {{ $origem_declarado ?? '—' }}</div>
            @if ($tem_efd_alternativo)
                <div class="text-xs text-gray-500 mt-1">ⓘ EFD também existente para esta chave</div>
            @endif
            <div class="mt-1"><strong>SEFAZ:</strong> {{ $origem_sefaz ?? '—' }}</div>
        </div>
    </div>

    {{-- Cabeçalho --}}
    @if ($comparacao->headerDiff)
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Cabeçalho</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Campo</th>
                        <th class="px-4 py-2 text-left">Declarado</th>
                        <th class="px-4 py-2 text-left">SEFAZ</th>
                        <th class="px-4 py-2 text-center w-16">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparacao->headerDiff as $campo)
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-2 font-medium text-gray-700">{{ $campo->label }}</td>
                            <td class="px-4 py-2 text-gray-900">{{ $campo->declarado ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-900">{{ $campo->sefaz ?? '—' }}</td>
                            <td class="px-4 py-2 text-center">{!! $statusIcon($campo->divergente) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Status SEFAZ (informativo) --}}
    @if ($comparacao->sefaz && ! empty($comparacao->sefaz->metaSefaz['situacao']))
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-1.5 text-sm">
            <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-2">Status SEFAZ (informativo)</h2>
            <div><strong>Situação:</strong> {{ $comparacao->sefaz->metaSefaz['situacao'] ?? '—' }}</div>
            <div><strong>Protocolo:</strong> {{ $comparacao->sefaz->metaSefaz['protocolo'] ?? '—' }}</div>
            <div><strong>Autorizada em:</strong> {{ $comparacao->sefaz->metaSefaz['data_autorizacao'] ?? '—' }}</div>
        </div>
    @endif

    {{-- Partes --}}
    @if ($comparacao->partesDiff)
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Partes</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Campo</th>
                        <th class="px-4 py-2 text-left">Declarado</th>
                        <th class="px-4 py-2 text-left">SEFAZ</th>
                        <th class="px-4 py-2 text-center w-16">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparacao->partesDiff as $parteKey => $campos)
                        <tr class="bg-gray-50">
                            <td colspan="4" class="px-4 py-2 text-xs font-bold uppercase text-gray-600 tracking-wide">
                                {{ $parteLabels[$parteKey] ?? ucfirst($parteKey) }}
                            </td>
                        </tr>
                        @foreach ($campos as $campo)
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-2 pl-8 font-medium text-gray-700">{{ $campo->label }}</td>
                                <td class="px-4 py-2 text-gray-900">{{ $campo->declarado ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-900">{{ $campo->sefaz ?? '—' }}</td>
                                <td class="px-4 py-2 text-center">{!! $statusIcon($campo->divergente) !!}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Totais --}}
    @if ($comparacao->totaisDiff)
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Totais</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Campo</th>
                        <th class="px-4 py-2 text-right">Declarado</th>
                        <th class="px-4 py-2 text-right">SEFAZ</th>
                        <th class="px-4 py-2 text-right">Δ</th>
                        <th class="px-4 py-2 text-center w-16">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparacao->totaisDiff as $campo)
                        @php
                            $delta = $campo->declarado !== null && $campo->sefaz !== null
                                ? ((float) $campo->sefaz - (float) $campo->declarado)
                                : null;
                            $deltaPct = $delta !== null && (float) $campo->declarado != 0
                                ? ($delta / (float) $campo->declarado) * 100
                                : null;
                        @endphp
                        <tr class="border-t border-gray-100 {{ $campo->divergente ? 'bg-amber-50' : '' }}"
                            @if ($campo->divergente) style="border-left: 3px solid #d97706;" @endif>
                            <td class="px-4 py-2 font-medium text-gray-700">{{ $campo->label }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $fmtMoney($campo->declarado) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $fmtMoney($campo->sefaz) }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">
                                @if ($delta !== null && $campo->divergente)
                                    {{ $fmtMoney($delta) }}
                                    @if ($deltaPct !== null)
                                        <span
                                            class="text-xs text-gray-500">({{ number_format($deltaPct, 1, ',', '.') }}%)</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">{!! $statusIcon($campo->divergente) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Itens NF-e --}}
    @if ($comparacao->itensPareados && $comparacao->tipoDocumento === 'NFE')
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Itens</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-3 py-2 text-left">#</th>
                            <th class="px-3 py-2 text-left">cProd</th>
                            <th class="px-3 py-2 text-left">Descrição</th>
                            <th class="px-3 py-2 text-right">Qtd</th>
                            <th class="px-3 py-2 text-right">Vlr unit</th>
                            <th class="px-3 py-2 text-right">Vlr total</th>
                            <th class="px-3 py-2 text-left">NCM</th>
                            <th class="px-3 py-2 text-left">CFOP</th>
                            <th class="px-3 py-2 text-center w-16">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparacao->itensPareados as $idx => $par)
                            @php
                                $declarado = $par->declarado;
                                $sefaz = $par->sefaz;
                                $rowStyle = $par->temDivergencia ? 'border-left: 3px solid #d97706;' : '';
                            @endphp

                            <tr class="border-t border-gray-200 bg-gray-50">
                                <td colspan="9" class="px-3 py-1.5 text-xs text-gray-600">
                                    Item {{ $idx + 1 }} —
                                    @if ($par->matchType === 'cprod')
                                        <span class="text-blue-700">match por cProd</span>
                                    @elseif ($par->matchType === 'sequencia')
                                        <span class="text-blue-700">match por sequência</span>
                                    @elseif ($par->matchType === 'fantasma_declarado')
                                        <span class="text-amber-700">presente apenas no declarado</span>
                                    @else
                                        <span class="text-amber-700">presente apenas no SEFAZ</span>
                                    @endif
                                </td>
                            </tr>

                            @if ($declarado)
                                <tr class="border-t border-gray-100" style="{{ $rowStyle }}">
                                    <td class="px-3 py-2 text-xs text-gray-500 font-medium">DEC</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $declarado->cProd ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $declarado->xProd ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $fmtNum($declarado->qCom, 4) }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $fmtNum($declarado->vUnCom) }}
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $fmtNum($declarado->vProd) }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $declarado->ncm ?? '—' }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $declarado->cfop ?? '—' }}</td>
                                    <td class="px-3 py-2 text-center">{!! $statusIcon($par->temDivergencia) !!}</td>
                                </tr>
                            @endif

                            @if ($sefaz)
                                <tr class="border-t border-gray-100" style="{{ $rowStyle }}">
                                    <td class="px-3 py-2 text-xs text-gray-500 font-medium">SEF</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $sefaz->cProd ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ $sefaz->xProd ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $fmtNum($sefaz->qCom, 4) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $fmtNum($sefaz->vUnCom) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $fmtNum($sefaz->vProd) }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $sefaz->ncm ?? '—' }}</td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $sefaz->cfop ?? '—' }}</td>
                                    <td class="px-3 py-2"></td>
                                </tr>
                            @endif

                            @if ($par->temDivergencia && $par->diffs)
                                <tr class="border-t border-amber-100 bg-amber-50">
                                    <td colspan="9" class="px-3 py-1.5 text-xs text-amber-800">
                                        @foreach (collect($par->diffs)->filter(fn($c) => $c->divergente) as $diff)
                                            <span class="mr-3">{{ $diff->label }} divergente</span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Componentes CT-e --}}
    @if ($comparacao->itensPareados && $comparacao->tipoDocumento === 'CTE')
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="px-4 py-2.5 border-b border-gray-200 bg-gray-50">
                <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Componentes do frete</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-2 text-left">Componente</th>
                        <th class="px-3 py-2 text-right">Declarado</th>
                        <th class="px-3 py-2 text-right">SEFAZ</th>
                        <th class="px-3 py-2 text-center w-16">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparacao->itensPareados as $par)
                        @php
                            $declarado = $par->declarado;
                            $sefaz = $par->sefaz;
                            $nome = $declarado?->nome ?? $sefaz?->nome ?? '—';
                        @endphp
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-2 text-gray-700">{{ $nome }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $fmtMoney($declarado?->valor) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $fmtMoney($sefaz?->valor) }}</td>
                            <td class="px-3 py-2 text-center">{!! $statusIcon($par->temDivergencia) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Resumo --}}
    <div class="bg-white border border-gray-200 rounded-lg p-4 space-y-2 text-sm">
        <h2 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Resumo</h2>
        <div>Header: <strong>{{ $comparacao->resumo->headerDivergencias }}</strong> divergência(s)</div>
        <div>Totais: <strong>{{ $comparacao->resumo->totaisDivergencias }}</strong> divergência(s)</div>
        <div>Itens: <strong>{{ $comparacao->resumo->itensDivergentes }}</strong> com diferença
            @if ($comparacao->resumo->itensFantasmaDeclarado > 0)
                · <strong>{{ $comparacao->resumo->itensFantasmaDeclarado }}</strong> só no declarado
            @endif
            @if ($comparacao->resumo->itensFantasmaSefaz > 0)
                · <strong>{{ $comparacao->resumo->itensFantasmaSefaz }}</strong> só no SEFAZ
            @endif
        </div>
        <div class="pt-2">
            Severidade global:
            <span style="background-color: {{ $sevHex }}; color: white;"
                class="inline-block px-2 py-0.5 rounded text-xs font-bold uppercase">{{ $sevLabel }}</span>
        </div>
    </div>

    {{-- Edge: declarado ausente --}}
    @if ($comparacao->resumo->declaradoAusente)
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <h2 class="text-sm font-bold text-amber-900 uppercase tracking-wide mb-2">Sem nota declarada</h2>
            <p class="text-sm text-amber-900">
                Esta chave foi consultada via busca avulsa mas não está no seu acervo declarado (XML ou EFD).
                Pra fechar o ciclo de auditoria:
            </p>
            <div class="mt-3 flex gap-2 flex-wrap">
                <a href="{{ route('app.notas-fiscais.index') }}?upload=xml"
                    class="inline-block px-3 py-1.5 rounded text-sm font-medium"
                    style="background-color: #d97706; color: white;">Importar XML desta chave ↗</a>
                <a href="{{ route('app.importacao.efd') }}"
                    class="inline-block px-3 py-1.5 rounded text-sm font-medium border border-amber-700 text-amber-900">Importar
                    EFD do período ↗</a>
            </div>
        </div>
    @endif

    {{-- Edge: SEFAZ ausente --}}
    @if ($comparacao->resumo->sefazAusente)
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <h2 class="text-sm font-bold text-amber-900 uppercase tracking-wide mb-2">Sem snapshot SEFAZ</h2>
            <p class="text-sm text-amber-900">
                Esta chave ainda não tem consulta SEFAZ. Pra comparar:
            </p>
            <div class="mt-3 flex gap-2 flex-wrap">
                <a href="{{ route('app.clearance.buscar') }}?chave={{ $comparacao->chave }}"
                    class="inline-block px-3 py-1.5 rounded text-sm font-medium"
                    style="background-color: #d97706; color: white;">Fazer busca avulsa (1 crédito) ↗</a>
                <a href="{{ route('app.clearance.notas') }}?selecionar={{ $comparacao->chave }}"
                    class="inline-block px-3 py-1.5 rounded text-sm font-medium border border-amber-700 text-amber-900">Incluir
                    em lote de clearance ↗</a>
            </div>
        </div>
    @endif

</div>
