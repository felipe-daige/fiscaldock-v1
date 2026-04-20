{{-- Score Fiscal - Dashboard (DANFE Modernizado) --}}
@php
    $scoreColor = function($s) {
        if ($s >= 80) return '#b91c1c';
        if ($s >= 50) return '#ea580c';
        if ($s >= 20) return '#d97706';
        return '#047857';
    };
    $classBadge = [
        'baixo' => ['label' => 'BAIXO', 'hex' => '#047857'],
        'medio' => ['label' => 'MÉDIO', 'hex' => '#d97706'],
        'alto' => ['label' => 'ALTO', 'hex' => '#ea580c'],
        'critico' => ['label' => 'CRÍTICO', 'hex' => '#b91c1c'],
    ];
@endphp
<div class="min-h-screen bg-gray-100" id="risk-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Score Fiscal</h1>
            <p class="text-xs text-gray-500 mt-1">Avalie o risco fiscal e de compliance dos seus participantes.</p>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>
            <div class="p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Classificação</label>
                    <select id="filtro-classificacao" class="px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="todos" {{ ($filtroClassificacao ?? 'todos') === 'todos' ? 'selected' : '' }}>Todas as Classificações</option>
                        <option value="baixo" {{ ($filtroClassificacao ?? '') === 'baixo' ? 'selected' : '' }}>Baixo Risco</option>
                        <option value="medio" {{ ($filtroClassificacao ?? '') === 'medio' ? 'selected' : '' }}>Médio Risco</option>
                        <option value="alto" {{ ($filtroClassificacao ?? '') === 'alto' ? 'selected' : '' }}>Alto Risco</option>
                        <option value="critico" {{ ($filtroClassificacao ?? '') === 'critico' ? 'selected' : '' }}>Crítico</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[240px]">
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Buscar</label>
                    <div class="relative">
                        <input type="text" id="busca-participante" placeholder="CNPJ ou razão social..." value="{{ $filtroBusca ?? '' }}" class="w-full px-3 py-2 pl-9 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-5 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Avaliados</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $estatisticas['total_avaliados'] ?? 0 }}</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Baixo Risco</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $estatisticas['baixo_risco'] ?? 0 }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">BAIXO</span>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Médio Risco</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $estatisticas['medio_risco'] ?? 0 }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">MÉDIO</span>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Alto Risco</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $estatisticas['alto_risco'] ?? 0 }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #ea580c">ALTO</span>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Crítico</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $estatisticas['critico'] ?? 0 }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #b91c1c">CRÍTICO</span>
                </div>
            </div>
        </div>

        @if(($emRiscoCritico ?? collect())->count() > 0)
        {{-- Alerta de Risco Crítico --}}
        <div class="bg-white rounded border border-gray-300 border-l-4 border-l-red-500 p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-gray-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Participantes em Risco Crítico</h4>
                    <ul class="mt-2 space-y-1">
                        @foreach($emRiscoCritico as $scoreItem)
                            <li class="text-sm text-gray-700">
                                <a href="/app/score-fiscal/participante/{{ $scoreItem->participante_id }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline">
                                    {{ $scoreItem->participante->razao_social ?? 'N/A' }} <span class="font-mono text-[11px] text-gray-500">({{ $scoreItem->participante->cnpj_formatado ?? '' }})</span> — Score: <span class="font-bold" style="color: #b91c1c">{{ $scoreItem->score_total }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        {{-- Lista de Participantes --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Participantes</span>
                <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $participantes->total() ?? 0 }}</span>
            </div>

            @if(($participantes ?? collect())->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participante</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNPJ</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">UF</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Score</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Classificação</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Última Consulta</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($participantes as $participante)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-3 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900 font-medium">{{ $participante->razao_social ?? 'N/A' }}</div>
                                @if($participante->nome_fantasia)
                                    <div class="text-[11px] text-gray-500">{{ $participante->nome_fantasia }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700 font-mono">
                                {{ $participante->cnpj_formatado }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700">
                                {{ $participante->uf ?? '—' }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                @if($participante->score)
                                    <span class="text-lg font-bold font-mono" style="color: {{ $scoreColor($participante->score->score_total) }}">
                                        {{ $participante->score->score_total }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                @if($participante->score && isset($classBadge[$participante->score->classificacao]))
                                    @php $b = $classBadge[$participante->score->classificacao]; @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $b['hex'] }}">
                                        {{ $b['label'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">
                                        Não Avaliado
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center text-sm text-gray-700 font-mono">
                                @if($participante->score && $participante->score->ultima_consulta_em)
                                    {{ $participante->score->ultima_consulta_em->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-right text-xs">
                                <a href="/app/score-fiscal/participante/{{ $participante->id }}" data-link class="text-gray-600 hover:text-gray-900 hover:underline mr-3">Detalhes</a>
                                <button type="button" class="btn-consultar text-gray-600 hover:text-gray-900 hover:underline" data-id="{{ $participante->id }}">Consultar</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginacao --}}
            @if($participantes->hasPages())
            <div class="border-t border-gray-300 px-4 py-3">
                {{ $participantes->withQueryString()->links() }}
            </div>
            @endif

            @else
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-4 text-sm font-semibold text-gray-900 uppercase tracking-wide">Nenhum participante encontrado</h3>
                <p class="mt-2 text-xs text-gray-500">Importe participantes via SPED ou XMLs.</p>
                <a href="/app/importacao/xml" data-link class="mt-4 inline-flex items-center gap-2 px-3 py-2 rounded bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    Importar XMLs
                </a>
            </div>
            @endif
        </div>

    </div>
</div>

<script src="{{ asset('js/risk-score.js') }}"></script>
