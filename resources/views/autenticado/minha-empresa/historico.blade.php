{{-- Minha Empresa - Historico de Consultas (DANFE Modernizado) --}}
<div class="min-h-screen bg-gray-100" id="minha-empresa-historico">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 text-[11px] text-gray-500 mb-2 uppercase tracking-wide">
            <a href="/app/minha-empresa" data-link class="hover:text-gray-900 hover:underline">Minha Empresa</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span>Histórico</span>
        </div>

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Histórico de Consultas</h1>
                <p class="mt-1 text-xs text-gray-500">{{ $empresa->razao_social ?? $empresa->nome }} — <span class="font-mono">{{ $empresa->documento_formatado }}</span></p>
            </div>
            <div>
                <a href="/app/consulta/nova?participante={{ $participante->id ?? '' }}" data-link class="inline-flex items-center gap-2 px-3 py-2 rounded bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Nova Consulta
                </a>
            </div>
        </div>

        {{-- Lista de Consultas --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            @if(($consultas ?? collect())->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Data</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Plano</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Consultas Realizadas</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Status</th>
                                <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Score</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($consultas as $consulta)
                                @php
                                    $scoreData = $consulta->calcularScore();
                                    $scoreHex = match($scoreData['classificacao']) {
                                        'baixo' => '#047857',
                                        'medio' => '#d97706',
                                        'alto' => '#ea580c',
                                        'critico' => '#b91c1c',
                                        default => '#9ca3af'
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <div class="text-sm text-gray-700 font-mono">
                                            {{ $consulta->consultado_em ? $consulta->consultado_em->format('d/m/Y') : 'N/A' }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 font-mono">
                                            {{ $consulta->consultado_em ? $consulta->consultado_em->format('H:i') : '' }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <div class="text-sm text-gray-700">{{ $consulta->lote->plano->nome ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($consulta->getConsultasRealizadas() as $tipo)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                                                      style="background-color: #374151">
                                                    {{ str_replace('_', ' ', ucfirst($tipo)) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-center">
                                        @if($consulta->isSucesso())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                                                  style="background-color: #047857">Sucesso</span>
                                        @elseif($consulta->isErro())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                                                  style="background-color: #b91c1c">Erro</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                                                  style="background-color: #d97706">Pendente</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-center">
                                        @if($consulta->isSucesso())
                                            <span class="text-lg font-bold font-mono" style="color: {{ $scoreHex }}">{{ $scoreData['score_total'] }}</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-right text-xs">
                                        @if($consulta->lote)
                                            <a href="/app/consulta/lote/{{ $consulta->lote->id }}/baixar" class="text-gray-600 hover:text-gray-900 hover:underline">
                                                Baixar CSV
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Paginacao --}}
                @if($consultas->hasPages())
                    <div class="border-t border-gray-300 px-4 py-3">
                        {{ $consultas->links() }}
                    </div>
                @endif
            @else
                {{-- Estado vazio --}}
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-4 text-sm font-semibold text-gray-900 uppercase tracking-wide">Nenhuma consulta realizada</h3>
                    <p class="mt-2 text-xs text-gray-500">Ainda não foram realizadas consultas para esta empresa.</p>
                    <a href="/app/consulta/nova?participante={{ $participante->id ?? '' }}" data-link class="mt-6 inline-flex items-center gap-2 px-3 py-2 rounded bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Realizar Primeira Consulta
                    </a>
                </div>
            @endif
        </div>

        {{-- Voltar --}}
        <div class="mt-6 text-center">
            <a href="/app/minha-empresa" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline transition-colors">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar para Dashboard
            </a>
        </div>
    </div>
</div>
