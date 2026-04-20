@php
    $kpis = $kpis ?? [
        'total' => 0, 'verificadas' => 0, 'nao_verificadas' => 0,
        'autorizadas' => 0, 'canceladas' => 0, 'denegadas' => 0,
        'inutilizadas' => 0, 'nao_encontradas' => 0, 'indeterminadas' => 0,
    ];
    $notasBloqueantes = $notasBloqueantes ?? [];
    $ultimasVerificacoes = $ultimasVerificacoes ?? [];
    $saldoCreditos = $saldoCreditos ?? 0;
    $custoConsultaUnitaria = $custoConsultaUnitaria ?? 14;

    $statusReceita = [
        ['key' => 'autorizadas',     'label' => 'Autorizadas',     'hex' => '#047857', 'situacao' => 'AUTORIZADA',     'descricao' => 'Reconhecidas pela Receita Federal'],
        ['key' => 'canceladas',      'label' => 'Canceladas',      'hex' => '#dc2626', 'situacao' => 'CANCELADA',      'descricao' => 'Cancelamento confirmado'],
        ['key' => 'denegadas',       'label' => 'Denegadas',       'hex' => '#991b1b', 'situacao' => 'DENEGADA',       'descricao' => 'Uso negado pelo SEFAZ'],
        ['key' => 'inutilizadas',    'label' => 'Inutilizadas',    'hex' => '#374151', 'situacao' => 'INUTILIZADA',    'descricao' => 'Numeração inutilizada'],
        ['key' => 'nao_encontradas', 'label' => 'Não encontradas', 'hex' => '#d97706', 'situacao' => 'NAO_ENCONTRADA', 'descricao' => 'Receita não retornou registro (612)'],
        ['key' => 'indeterminadas',  'label' => 'Indeterminadas',  'hex' => '#1d4ed8', 'situacao' => 'INDETERMINADO',  'descricao' => 'Fonte oficial sem dados (611)'],
    ];

    $situacaoBadge = [
        'AUTORIZADA' => '#047857',
        'CANCELADA' => '#dc2626',
        'DENEGADA' => '#991b1b',
        'INUTILIZADA' => '#374151',
        'NAO_ENCONTRADA' => '#d97706',
        'INDETERMINADO' => '#1d4ed8',
    ];

    $situacaoLabel = [
        'AUTORIZADA' => 'Autorizada',
        'CANCELADA' => 'Cancelada',
        'DENEGADA' => 'Denegada',
        'INUTILIZADA' => 'Inutilizada',
        'NAO_ENCONTRADA' => 'Não encontrada',
        'INDETERMINADO' => 'Indeterminada',
    ];

    $formatarConsultadoEm = function ($iso) {
        if (! $iso) {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($iso)->format('d/m/Y H:i');
        } catch (\Exception $e) {
            return $iso;
        }
    };
@endphp

<div class="min-h-screen bg-gray-100" id="validacao-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-8 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Clearance DF-e — Painel</h1>
                <p class="text-xs text-gray-500 mt-1">Posição de NF-e/CT-e/NFS-e na Receita Federal, unificando notas importadas via XML e extraídas do SPED/EFD.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                <span class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 rounded text-xs text-gray-700">
                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Saldo</span>
                    <span class="font-bold text-gray-900">{{ number_format($saldoCreditos, 0, ',', '.') }}</span>
                    <span class="text-gray-500">créditos</span>
                </span>
                <a href="/app/clearance/buscar" data-link class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium self-start">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M16 10a6 6 0 11-12 0 6 6 0 0112 0z"></path>
                    </svg>
                    Verificar nota
                </a>
            </div>
        </div>

        <div id="validacao-error-region" class="mb-6"></div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Universo de Documentos</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-3 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Total DF-e</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($kpis['total'], 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Notas únicas (deduplicadas por chave)</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Verificadas na Receita</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($kpis['verificadas'], 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $kpis['total'] > 0 ? round(($kpis['verificadas'] / $kpis['total']) * 100, 1) : 0 }}% do universo</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Não verificadas</p>
                    <p class="text-lg font-bold text-gray-900">{{ number_format($kpis['nao_verificadas'], 0, ',', '.') }}</p>
                    @if($kpis['nao_verificadas'] > 0)
                        <a href="/app/clearance/notas?status_validacao=sem_situacao_receita" data-link class="text-[11px] text-gray-600 hover:text-gray-900 hover:underline mt-1 inline-flex">
                            Ver notas pendentes →
                        </a>
                    @else
                        <p class="text-[11px] text-gray-500 mt-1">Toda a base já foi consultada</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Status na Receita Federal</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-6 divide-x divide-y lg:divide-y-0 divide-gray-200">
                @foreach($statusReceita as $s)
                    <a href="/app/clearance/notas?situacao_receita={{ $s['situacao'] }}" data-link class="block p-4 sm:p-6 hover:bg-gray-50/50 transition-colors">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ $s['label'] }}</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($kpis[$s['key']], 0, ',', '.') }}</p>
                        <p class="text-[11px] mt-1">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $s['hex'] }}">{{ $s['label'] }}</span>
                        </p>
                        <p class="text-[11px] text-gray-500 mt-2">{{ $s['descricao'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>

        @if(count($notasBloqueantes) > 0)
            <div class="bg-white rounded border border-gray-300 p-4 mb-6 border-l-4 border-l-red-500">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Atenção Bloqueante</p>
                <p class="mt-2 text-sm text-gray-700">As notas abaixo estão Canceladas, Denegadas ou Inutilizadas — exigem revisão imediata.</p>
                <div class="mt-4 divide-y divide-gray-100">
                    @foreach($notasBloqueantes as $nota)
                        @php
                            $val = is_string($nota['validacao'] ?? null) ? json_decode($nota['validacao'], true) : ($nota['validacao'] ?? []);
                            $situacao = $val['situacao'] ?? null;
                            $consultadoEm = $val['consultado_em'] ?? null;
                        @endphp
                        <div class="py-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <a href="/app/clearance/nota/{{ $nota['id'] }}?origem={{ $nota['origem'] }}" data-link class="text-sm text-gray-900 hover:text-gray-600 hover:underline">
                                    NF {{ $nota['numero'] }} — {{ $nota['emit_razao_social'] ?: 'Emitente desconhecido' }}
                                </a>
                                <p class="text-[11px] text-gray-500 mt-1">
                                    Consultado em {{ $formatarConsultadoEm($consultadoEm) }}
                                    · Origem {{ strtoupper($nota['origem']) }}
                                </p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white self-start" style="background-color: {{ $situacaoBadge[$situacao] ?? '#374151' }}">
                                {{ $situacaoLabel[$situacao] ?? $situacao }}
                            </span>
                        </div>
                    @endforeach
                </div>
                <a href="/app/clearance/notas?situacao_receita=CANCELADA" data-link class="mt-3 inline-flex text-xs text-gray-600 hover:text-gray-900 hover:underline">
                    Ver todas as notas com situação bloqueante →
                </a>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Últimas Verificações</span>
                    <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ count($ultimasVerificacoes) }}</span>
                </div>

                @if(count($ultimasVerificacoes) > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($ultimasVerificacoes as $nota)
                            @php
                                $val = is_string($nota['validacao'] ?? null) ? json_decode($nota['validacao'], true) : ($nota['validacao'] ?? []);
                                $situacao = $val['situacao'] ?? null;
                                $consultadoEm = $val['consultado_em'] ?? null;
                            @endphp
                            <div class="px-4 py-3 hover:bg-gray-50/50 transition-colors">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <a href="/app/clearance/nota/{{ $nota['id'] }}?origem={{ $nota['origem'] }}" data-link class="text-sm text-gray-700 hover:text-gray-900 hover:underline">
                                            NF {{ $nota['numero'] }} — {{ $nota['emit_razao_social'] ?: 'Emitente desconhecido' }}
                                        </a>
                                        <p class="text-[11px] text-gray-500 mt-1">{{ $formatarConsultadoEm($consultadoEm) }} · Origem {{ strtoupper($nota['origem']) }}</p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white self-start" style="background-color: {{ $situacaoBadge[$situacao] ?? '#374151' }}">
                                        {{ $situacaoLabel[$situacao] ?? $situacao }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-4 py-8 text-center">
                        <p class="text-sm text-gray-700">Nenhuma verificação ainda.</p>
                        <a href="/app/clearance/buscar" data-link class="mt-2 inline-flex text-xs text-gray-600 hover:text-gray-900 hover:underline">
                            Verificar a primeira nota →
                        </a>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Como Funciona</span>
                </div>
                <div class="p-4 space-y-4 text-sm text-gray-700">
                    <div>
                        <p class="font-semibold text-gray-900">1. Listagem unificada</p>
                        <p class="text-[11px] text-gray-500 mt-1">Notas importadas via XML e extraídas do SPED/EFD aparecem juntas, deduplicadas pela chave de acesso.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">2. Verificação por chave</p>
                        <p class="text-[11px] text-gray-500 mt-1">Consulta direta à Receita Federal via InfoSimples. Retorna situação oficial: autorizada, cancelada, denegada, inutilizada ou indeterminada.</p>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">3. Cobrança por consulta</p>
                        <p class="text-[11px] text-gray-500 mt-1">Custo aproximado: <span class="font-semibold text-gray-900">{{ $custoConsultaUnitaria }} créditos por nota</span>. Consultas que falham antes de chegar na fonte oficial não são cobradas.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
