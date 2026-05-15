{{-- Monitoramento — Painel (DANFE Modernizado) --}}
<div class="min-h-screen bg-gray-100" id="monitoramento-painel-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        {{-- Header --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Compliance Automático</h1>
                <p class="text-xs text-gray-500 mt-1">Checagem recorrente de compliance dos clientes e participantes selecionados.</p>
            </div>
            <button type="button"
                    id="btn-nova-assinatura"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded text-white text-xs font-semibold transition"
                    style="background-color: #047857;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nova assinatura
            </button>
        </div>

        {{-- KPIs --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Ativas</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $kpiAtivas }}</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Pausadas</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $kpiPausadas }}</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Créditos no mês</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $kpiCreditosMes }}</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Próx. ciclo (est.)</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $kpiPrevisaoCiclo }}</p>
                </div>
            </div>
        </div>

        @include('autenticado.monitoramento._sub-tabs-tipo', [
            'tipoAtivo' => $tipoAtivo,
            'contagens' => $contagens,
            'rota' => 'app.monitoramento.painel',
        ])

        {{-- Filtros --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>
            <form method="GET" action="{{ route('app.monitoramento.painel') }}" class="p-4 flex flex-wrap items-end gap-3">
                <input type="hidden" name="tipo" value="{{ $tipoAtivo }}">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Status</label>
                    <select name="status" class="px-3 py-2 text-sm border border-gray-300 rounded">
                        <option value="">Todos</option>
                        <option value="ativo" @selected($filtros['status'] === 'ativo')>Ativo</option>
                        <option value="pausado" @selected($filtros['status'] === 'pausado')>Pausado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Plano</label>
                    <select name="plano_id" class="px-3 py-2 text-sm border border-gray-300 rounded">
                        <option value="">Todos</option>
                        @foreach ($planos as $p)
                            <option value="{{ $p->id }}" @selected($filtros['plano_id'] === $p->id)>{{ $p->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Busca</label>
                    <input type="text" name="busca" value="{{ $filtros['busca'] }}" placeholder="CNPJ ou razão social"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded">
                </div>
                <button type="submit" class="px-3 py-2 rounded bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold">Filtrar</button>
            </form>
        </div>

        {{-- Tabela --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            @if ($assinaturas->isEmpty())
                <div class="p-10 text-center">
                    <p class="text-sm text-gray-600">Nenhuma assinatura encontrada.</p>
                    <p class="text-xs text-gray-500 mt-2">Crie a primeira pelo botão acima ou direto no detalhe de um cliente/participante.</p>
                </div>
            @else
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr class="text-left text-[10px] font-semibold text-gray-500 uppercase tracking-widest">
                            <th class="px-4 py-2">Tipo</th>
                            <th class="px-4 py-2">CNPJ</th>
                            <th class="px-4 py-2">Razão Social</th>
                            <th class="px-4 py-2">Plano</th>
                            <th class="px-4 py-2">Freq.</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Última situação</th>
                            <th class="px-4 py-2">Próx. exec.</th>
                            <th class="px-4 py-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($assinaturas as $a)
                            @php
                                $alvoTipo = $a->alvoTipo();
                                $alvo = $a->alvo();
                                $corTipo = $alvoTipo === 'cliente' ? '#1e40af' : '#7c3aed';
                                $corStatus = match ($a->status) {
                                    'ativo' => '#047857',
                                    'pausado' => '#d97706',
                                    default => '#6b7280',
                                };
                                $ultima = $ultimasConsultas[$a->id] ?? null;
                                $href = $alvoTipo === 'cliente' ? "/app/cliente/{$alvo?->id}" : "/app/participante/{$alvo?->id}";
                            @endphp
                            <tr>
                                <td class="px-4 py-2">
                                    <span class="text-[10px] font-semibold text-white uppercase px-2 py-1 rounded"
                                          style="background-color: {{ $corTipo }};">{{ $alvoTipo }}</span>
                                </td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $alvo?->documento }}</td>
                                <td class="px-4 py-2">
                                    <a href="{{ $href }}" data-link class="text-gray-900 hover:underline">{{ $alvo?->razao_social ?? '—' }}</a>
                                </td>
                                <td class="px-4 py-2">{{ $a->plano?->nome }}</td>
                                <td class="px-4 py-2 text-xs">{{ $a->frequencia }}</td>
                                <td class="px-4 py-2">
                                    <span class="text-[10px] font-semibold text-white uppercase px-2 py-1 rounded"
                                          style="background-color: {{ $corStatus }};">{{ $a->status }}</span>
                                </td>
                                <td class="px-4 py-2 text-xs">
                                    {{ $ultima?->situacao_geral ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-xs">
                                    {{ $a->proxima_execucao_em?->diffForHumans() ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <div class="inline-flex gap-1">
                                        @if ($a->status === 'ativo')
                                            <button type="button" class="btn-pausar text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50" data-assinatura-id="{{ $a->id }}">Pausar</button>
                                        @else
                                            <button type="button" class="btn-reativar text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50" data-assinatura-id="{{ $a->id }}">Reativar</button>
                                        @endif
                                        <button type="button" class="btn-cancelar text-xs px-2 py-1 rounded border border-red-300 text-red-700 hover:bg-red-50" data-assinatura-id="{{ $a->id }}">Cancelar</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    {{ $assinaturas->links() }}
                </div>
            @endif
        </div>
    </div>

    @include('autenticado.monitoramento._modal-nova-assinatura')
</div>

<script src="/js/monitoramento-modal-nova-assinatura.js?v={{ filemtime(public_path('js/monitoramento-modal-nova-assinatura.js')) }}"></script>
