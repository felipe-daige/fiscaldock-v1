<div class="bg-gray-50 border border-gray-200 rounded">
    <div class="px-4 py-2 border-b border-gray-200 bg-white">
        <div class="flex items-center justify-between gap-3">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">{{ $titulo }}</span>
            <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">
                {{ number_format($participantes->total(), 0, ',', '.') }} registro(s)
            </span>
        </div>
    </div>

    <form method="GET" action="{{ $ajaxBaseUrl }}" class="js-related-filter-form px-4 py-3 border-b border-gray-200 bg-gray-50">
        <div class="flex flex-col sm:flex-row sm:items-end gap-3">
            <div class="w-full sm:w-56">
                <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 block">Tipo de Documento</label>
                <select name="tipo_documento" class="w-full border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                    <option value="">Todos</option>
                    <option value="CNPJ" {{ ($filtros['tipo_documento'] ?? '') === 'CNPJ' ? 'selected' : '' }}>CNPJ</option>
                    <option value="CPF" {{ ($filtros['tipo_documento'] ?? '') === 'CPF' ? 'selected' : '' }}>CPF</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium px-4 py-2">
                    Filtrar
                </button>
                <a href="{{ $ajaxBaseUrl }}" class="js-related-filter-reset bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium px-4 py-2">
                    Limpar
                </a>
            </div>
            @if(($filtros['tipo_documento'] ?? '') !== '')
                <div class="sm:ml-auto">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">
                        {{ $filtros['tipo_documento'] }}
                    </span>
                </div>
            @endif
        </div>
    </form>

@if($participantes->count() > 0)
        @php
            $pageParams = [];
            if (($filtros['tipo_documento'] ?? '') !== '') {
                $pageParams['tipo_documento'] = $filtros['tipo_documento'];
            }
        @endphp
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-300">
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participante</th>
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Documento</th>
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Situação</th>
                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Origem</th>
                        <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($participantes as $participante)
                        @php
                            $origem = match($participante->origem_tipo) {
                                'SPED_EFD_FISCAL' => ['label' => 'EFD', 'hex' => '#4338ca'],
                                'SPED_EFD_CONTRIB' => ['label' => 'EFD', 'hex' => '#4338ca'],
                                'NFE' => ['label' => 'NF-e', 'hex' => '#374151'],
                                'NFSE' => ['label' => 'NFS-e', 'hex' => '#374151'],
                                'MANUAL' => ['label' => 'Manual', 'hex' => '#9ca3af'],
                                default => ['label' => $participante->origem_tipo ?: 'Manual', 'hex' => '#9ca3af'],
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-3 py-3">
                                <div class="text-sm text-gray-700">
                                    <a href="/app/participante/{{ $participante->id }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline">
                                        {{ $participante->razao_social ?? $participante->nome_fantasia ?? '-' }}
                                    </a>
                                </div>
                                <div class="text-[11px] text-gray-500 mt-1">
                                    {{ number_format($participante->efd_notas_count ?? 0, 0, ',', '.') }} nota(s) vinculada(s)
                                </div>
                            </td>
                            <td class="px-3 py-3 text-sm text-gray-700 font-mono">{{ $participante->cnpj_formatado }}</td>
                            <td class="px-3 py-3">
                                @if(($participante->situacao_cadastral ?? '') === 'ATIVA')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Ativa</span>
                                @elseif($participante->situacao_cadastral)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">
                                        {{ $participante->situacao_cadastral }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">Sem Mov.</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $origem['hex'] }}">
                                    {{ $origem['label'] }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <a href="/app/participante/{{ $participante->id }}" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">
                                    Abrir
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($participantes->hasPages())
            <div class="border-t border-gray-300 px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-[10px] text-gray-500 uppercase tracking-wide">
                        Mostrando {{ $participantes->firstItem() }}-{{ $participantes->lastItem() }} de {{ $participantes->total() }}
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="js-related-page px-3 py-1.5 text-[10px] border rounded {{ $participantes->onFirstPage() ? 'text-gray-400 bg-gray-100 border-gray-200 cursor-not-allowed' : 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50' }}"
                            data-scope="{{ $scope }}"
                            data-entity-id="{{ $entityId }}"
                            data-url="{{ $ajaxBaseUrl }}?{{ http_build_query(array_merge($pageParams, ['page' => max(1, $participantes->currentPage() - 1)])) }}"
                            {{ $participantes->onFirstPage() ? 'disabled' : '' }}
                        >
                            Anterior
                        </button>
                        <span class="px-3 py-1.5 text-[10px] font-bold text-white rounded" style="background-color: #1f2937">
                            {{ $participantes->currentPage() }}
                        </span>
                        <button
                            type="button"
                            class="js-related-page px-3 py-1.5 text-[10px] border rounded {{ $participantes->hasMorePages() ? 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50' : 'text-gray-400 bg-gray-100 border-gray-200 cursor-not-allowed' }}"
                            data-scope="{{ $scope }}"
                            data-entity-id="{{ $entityId }}"
                            data-url="{{ $ajaxBaseUrl }}?{{ http_build_query(array_merge($pageParams, ['page' => min($participantes->lastPage(), $participantes->currentPage() + 1)])) }}"
                            {{ $participantes->hasMorePages() ? '' : 'disabled' }}
                        >
                            Próxima
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="px-4 py-6 text-sm text-gray-500">
            @if(($filtros['tipo_documento'] ?? '') !== '')
                Nenhum participante do tipo {{ $filtros['tipo_documento'] }} vinculado a este cliente.
            @else
                {{ $emptyMessage }}
            @endif
        </div>
    @endif
</div>
