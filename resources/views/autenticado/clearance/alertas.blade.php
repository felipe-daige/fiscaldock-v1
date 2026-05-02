@php
    $notas = $notas ?? collect();
    $contadores = $contadores ?? ['bloqueante' => 0, 'atencao' => 0, 'info' => 0];
    $filtroNivel = $filtroNivel ?? '';
    $filtroCategoria = $filtroCategoria ?? '';
    $categorias = $categorias ?? [];
    $divergenciaResumo = $divergenciaResumo ?? ['critica' => 0, 'revisar' => 0, 'valor_exposto' => 0];
    $notasCriticasDivergencia = $notasCriticasDivergencia ?? [];
    $catalogoAlertasResumo = $catalogoAlertasResumo ?? [
        'sem_catalogo' => 0,
        'ncm_divergente' => 0,
        'unidade_divergente' => 0,
        'aliquota_incompativel' => 0,
    ];
    $catalogoAlertasTop = $catalogoAlertasTop ?? collect();

    $cards = [
        'bloqueante' => ['label' => 'Bloqueantes', 'hex' => '#dc2626', 'valor' => $contadores['bloqueante'] ?? 0],
        'atencao' => ['label' => 'Atenção', 'hex' => '#d97706', 'valor' => $contadores['atencao'] ?? 0],
        'info' => ['label' => 'Informativos', 'hex' => '#374151', 'valor' => $contadores['info'] ?? 0],
    ];

    $catalogoCards = [
        'sem_catalogo' => ['label' => 'Sem catálogo', 'hex' => '#dc2626', 'descricao' => 'Itens declarados em notas sem registro no 0200'],
        'ncm_divergente' => ['label' => 'NCM divergente', 'hex' => '#d97706', 'descricao' => 'NCM cadastrado ≠ NCM declarado'],
        'unidade_divergente' => ['label' => 'Unidade divergente', 'hex' => '#d97706', 'descricao' => 'Unidade catalogada ≠ unidade da nota'],
        'aliquota_incompativel' => ['label' => 'Alíquota incompatível', 'hex' => '#b45309', 'descricao' => 'Cadastrado vs ponderado das notas (tol. 0,5pp)'],
    ];

    $catalogoTotalAlertas = array_sum($catalogoAlertasResumo);

    $tipoAlertaLabel = [
        'sem_catalogo' => ['label' => 'Sem catálogo', 'hex' => '#dc2626'],
        'ncm_divergente' => ['label' => 'NCM', 'hex' => '#d97706'],
        'unidade_divergente' => ['label' => 'Unidade', 'hex' => '#d97706'],
        'aliquota_incompativel' => ['label' => 'Alíquota', 'hex' => '#b45309'],
    ];

    $classificacaoHex = [
        'conforme' => '#047857',
        'atencao' => '#d97706',
        'irregular' => '#b45309',
        'critico' => '#dc2626',
    ];
@endphp

<div class="min-h-screen bg-gray-100" id="validacao-alertas-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-8">
            <a href="/app/clearance/dashboard" data-link class="inline-flex items-center gap-2 text-xs text-gray-600 hover:text-gray-900 hover:underline mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Voltar para validação
            </a>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Alertas de Validação</h1>
            <p class="text-xs text-gray-500 mt-1">Notas fiscais validadas com ocorrências classificadas por severidade e categoria.</p>
        </div>

        @if(($divergenciaResumo['critica'] ?? 0) > 0 || ($divergenciaResumo['revisar'] ?? 0) > 0)
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Divergências declarado vs SEFAZ</span>
                    <span class="text-[10px] font-semibold text-gray-400">Snapshot persistido</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-200">
                    <a href="/app/clearance/notas?divergencia=CRITICA" data-link class="block p-4 hover:bg-gray-50/50 transition-colors">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Críticas</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #b91c1c">{{ number_format($divergenciaResumo['critica'], 0, ',', '.') }}</span>
                        </div>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($divergenciaResumo['critica'], 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">NCM/CFOP, cancelamento ou Δ valor &gt; 10%</p>
                    </a>
                    <a href="/app/clearance/notas?divergencia=REVISAR" data-link class="block p-4 hover:bg-gray-50/50 transition-colors">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Revisar</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">{{ number_format($divergenciaResumo['revisar'], 0, ',', '.') }}</span>
                        </div>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($divergenciaResumo['revisar'], 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Diferenças menores ou cabeçalho</p>
                    </a>
                    <div class="block p-4">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Valor exposto</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">Críticas</span>
                        </div>
                        <p class="text-lg font-bold text-gray-900 font-mono">R$ {{ number_format($divergenciaResumo['valor_exposto'], 2, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Soma do valor total das notas críticas</p>
                    </div>
                </div>

                @if(count($notasCriticasDivergencia) > 0)
                    <div class="border-t border-gray-200">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Notas críticas mais recentes</span>
                            <span class="text-[10px] font-semibold text-gray-400">Top {{ count($notasCriticasDivergencia) }}</span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach($notasCriticasDivergencia as $nc)
                                <div class="px-4 py-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between hover:bg-gray-50/50">
                                    <div class="min-w-0">
                                        <p class="text-sm text-gray-900 truncate">NF {{ $nc['numero'] ?? '—' }} — {{ $nc['emit_razao_social'] ?: 'Emitente desconhecido' }}</p>
                                        <p class="text-[11px] text-gray-500 mt-0.5">
                                            @if(! empty($nc['divergencia_count']))
                                                {{ $nc['divergencia_count'] }} campo(s) divergente(s)
                                            @endif
                                            @if(! empty($nc['situacao_sefaz']))
                                                · SEFAZ: {{ $nc['situacao_sefaz'] }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2 self-start">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #b91c1c">Crítica</span>
                                        <span class="text-xs text-gray-700 font-mono">R$ {{ number_format($nc['valor_total'], 2, ',', '.') }}</span>
                                        @if(! empty($nc['chave']) && strlen($nc['chave']) === 44)
                                            <a href="{{ route('app.clearance.nota.comparar', ['chave' => $nc['chave']]) }}" data-link class="text-[11px] text-blue-700 hover:underline">Comparar ↗</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if($catalogoTotalAlertas > 0)
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Catálogo × Notas</span>
                    <span class="text-[10px] font-semibold text-gray-400">XML + EFD com dedup por chave</span>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-gray-200">
                    @foreach($catalogoCards as $tipo => $card)
                        <div class="block p-4">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">{{ $card['label'] }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $card['hex'] }}">{{ number_format($catalogoAlertasResumo[$tipo] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <p class="text-lg font-bold text-gray-900">{{ number_format($catalogoAlertasResumo[$tipo] ?? 0, 0, ',', '.') }}</p>
                            <p class="text-[11px] text-gray-500 mt-1">{{ $card['descricao'] }}</p>
                        </div>
                    @endforeach
                </div>

                @if(count($catalogoAlertasTop) > 0)
                    <div class="border-t border-gray-200">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Top itens em risco (por valor movimentado)</span>
                            <span class="text-[10px] font-semibold text-gray-400">Top {{ count($catalogoAlertasTop) }}</span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach($catalogoAlertasTop as $alerta)
                                @php
                                    $tag = $tipoAlertaLabel[$alerta['tipo']] ?? ['label' => $alerta['tipo'], 'hex' => '#374151'];
                                @endphp
                                <div class="px-4 py-3 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between hover:bg-gray-50/50">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm text-gray-900 truncate">
                                            <span class="font-mono">{{ $alerta['codigo_item'] }}</span>
                                            @if(! empty($alerta['descricao']))
                                                — <span class="text-gray-700">{{ $alerta['descricao'] }}</span>
                                            @endif
                                        </p>
                                        <p class="text-[11px] text-gray-500 mt-0.5">{{ $alerta['detalhe'] }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 self-start whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tag['hex'] }}">{{ $tag['label'] }}</span>
                                        <span class="text-xs text-gray-700 font-mono">{{ $alerta['total_notas'] }} nota(s)</span>
                                        <span class="text-xs text-gray-700 font-mono">R$ {{ number_format($alerta['valor_movimentado'], 2, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            @foreach($cards as $nivel => $card)
                <a
                    href="/app/clearance/alertas{{ $nivel ? '?nivel=' . $nivel : '' }}"
                    data-link
                    class="bg-white rounded border {{ $filtroNivel === $nivel ? 'border-gray-500' : 'border-gray-300' }} overflow-hidden hover:border-gray-400 transition-colors"
                >
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">{{ $card['label'] }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $card['hex'] }}">{{ number_format($card['valor'], 0, ',', '.') }}</span>
                    </div>
                    <div class="p-4">
                        <p class="text-lg font-bold text-gray-900">{{ number_format($card['valor'], 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Ocorrências registradas neste nível.</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Nível</label>
                        <select id="filtro-nivel" class="w-full border border-gray-300 rounded text-sm px-3 py-2 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                            <option value="">Todos</option>
                            <option value="bloqueante" {{ $filtroNivel === 'bloqueante' ? 'selected' : '' }}>Bloqueante</option>
                            <option value="atencao" {{ $filtroNivel === 'atencao' ? 'selected' : '' }}>Atenção</option>
                            <option value="info" {{ $filtroNivel === 'info' ? 'selected' : '' }}>Informativo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Categoria</label>
                        <select id="filtro-categoria" class="w-full border border-gray-300 rounded text-sm px-3 py-2 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                            <option value="">Todas</option>
                            @foreach($categorias as $key => $categoria)
                                <option value="{{ $key }}" {{ $filtroCategoria === $key ? 'selected' : '' }}>{{ $categoria['nome'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <a href="/app/clearance/alertas" data-link class="px-4 py-2 bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium text-center {{ ($filtroNivel || $filtroCategoria) ? '' : 'invisible' }}">
                            Limpar filtros
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            @if($notas->count() > 0)
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Nota</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Emitente</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Score</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Classificação</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Alertas</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($notas as $nota)
                                @php
                                    $alertasBloqueantes = collect($nota->validacao_alertas)->where('nivel', 'bloqueante')->count();
                                    $alertasAtencao = collect($nota->validacao_alertas)->where('nivel', 'atencao')->count();
                                    $alertasInfo = collect($nota->validacao_alertas)->where('nivel', 'info')->count();
                                    $badgeHex = $classificacaoHex[strtolower((string) ($nota->validacao_classificacao ?? ''))] ?? '#374151';
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-3 py-3">
                                        <div class="text-sm text-gray-900">NF {{ $nota->numero_nota }}</div>
                                        <div class="text-[11px] text-gray-500">{{ $nota->data_emissao?->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="text-sm text-gray-700">{{ $nota->emitente->razao_social ?? $nota->emit_razao_social ?? '-' }}</div>
                                        <div class="text-[11px] font-mono text-gray-400">{{ $nota->emit_cnpj_formatado }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span class="text-sm font-semibold text-gray-900 font-mono">{{ $nota->validacao_score ?? '-' }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $badgeHex }}">
                                            {{ strtoupper($nota->validacao_classificacao_label ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            @if($alertasBloqueantes > 0)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #dc2626">{{ $alertasBloqueantes }}</span>
                                            @endif
                                            @if($alertasAtencao > 0)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">{{ $alertasAtencao }}</span>
                                            @endif
                                            @if($alertasInfo > 0)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ $alertasInfo }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="/app/clearance/nota/{{ $nota->id }}" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">
                                            Ver detalhes
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="md:hidden divide-y divide-gray-100">
                    @foreach($notas as $nota)
                        @php
                            $alertasBloqueantes = collect($nota->validacao_alertas)->where('nivel', 'bloqueante')->count();
                            $alertasAtencao = collect($nota->validacao_alertas)->where('nivel', 'atencao')->count();
                            $alertasInfo = collect($nota->validacao_alertas)->where('nivel', 'info')->count();
                            $badgeHex = $classificacaoHex[strtolower((string) ($nota->validacao_classificacao ?? ''))] ?? '#374151';
                        @endphp
                        <div class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm text-gray-900">NF {{ $nota->numero_nota }}</p>
                                    <p class="text-[10px] text-gray-400 uppercase mt-1">Emitente</p>
                                    <p class="text-sm text-gray-700">{{ $nota->emitente->razao_social ?? $nota->emit_razao_social ?? '-' }}</p>
                                    <p class="text-[11px] font-mono text-gray-400 mt-1">{{ $nota->emit_cnpj_formatado }}</p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white shrink-0" style="background-color: {{ $badgeHex }}">
                                    {{ strtoupper($nota->validacao_classificacao_label ?? 'N/A') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between gap-3 mt-3">
                                <span class="text-sm text-gray-700">Score: <span class="font-mono text-gray-900">{{ $nota->validacao_score ?? '-' }}</span></span>
                                <div class="flex gap-2">
                                    @if($alertasBloqueantes > 0)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #dc2626">{{ $alertasBloqueantes }}</span>
                                    @endif
                                    @if($alertasAtencao > 0)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">{{ $alertasAtencao }}</span>
                                    @endif
                                    @if($alertasInfo > 0)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">{{ $alertasInfo }}</span>
                                    @endif
                                </div>
                            </div>
                            <a href="/app/clearance/nota/{{ $nota->id }}" data-link class="mt-3 inline-flex text-xs text-gray-600 hover:text-gray-900 hover:underline">
                                Ver detalhes
                            </a>
                        </div>
                    @endforeach
                </div>

                @if($notas->hasPages())
                    <div class="border-t border-gray-300 px-4 py-3">
                        {{ $notas->withQueryString()->links() }}
                    </div>
                @endif
            @else
                <div class="px-4 py-10 text-center">
                    <p class="text-sm text-gray-700">Nenhum alerta encontrado.</p>
                    <p class="text-[11px] text-gray-500 mt-1">
                        @if($filtroNivel || $filtroCategoria)
                            Nenhuma nota corresponde aos filtros aplicados.
                        @else
                            Todas as notas validadas estão sem ocorrências visíveis.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

<script src="{{ asset('js/clearance.js') }}"></script>
