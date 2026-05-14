@if($assinaturaAtiva ?? null)
<div class="bg-white rounded border border-gray-300 overflow-hidden">
    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Histórico de Execuções do Monitoramento</span>
            <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $consultas->total() }}</span>
        </div>
    </div>
    @if($consultas->isEmpty())
        <div class="px-6 py-10 text-center text-gray-400">
            <p class="text-sm">Nenhuma execução registrada ainda. A primeira consulta recorrente será disparada na próxima data agendada.</p>
        </div>
    @else
        @php
            $statusCores = ['sucesso' => '#047857', 'erro' => '#dc2626', 'processando' => '#6b7280', 'pendente' => '#6b7280'];
            $statusLabels = ['sucesso' => 'Sucesso', 'erro' => 'Erro', 'processando' => 'Processando', 'pendente' => 'Pendente'];
            $situacaoCores = ['regular' => '#047857', 'atencao' => '#d97706', 'irregular' => '#dc2626'];
            $situacaoLabels = ['regular' => 'Regular', 'atencao' => 'Atenção', 'irregular' => 'Irregular'];
        @endphp
        <div class="divide-y divide-gray-200">
            @foreach($consultas as $consulta)
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">
                                Execução #{{ $consulta->id }}
                                @if($consulta->parent_consulta_id)
                                    <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #6b7280">Retentativa</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">{{ ($consulta->executado_em ?? $consulta->created_at)?->format('d/m/Y H:i') ?? '-' }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $consulta->creditos_cobrados }} creditos</p>
                            @if($consulta->status === 'erro' && $consulta->error_message)
                                <p class="text-xs text-gray-500 mt-0.5 max-w-xl">{{ $consulta->error_message }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if($consulta->situacao_geral)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $situacaoCores[$consulta->situacao_geral] ?? '#6b7280' }}">
                                    {{ $situacaoLabels[$consulta->situacao_geral] ?? ucfirst($consulta->situacao_geral) }}
                                </span>
                            @endif
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $statusCores[$consulta->status] ?? '#6b7280' }}">
                                {{ $statusLabels[$consulta->status] ?? ucfirst($consulta->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if($consultas->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $consultas->links() }}
            </div>
        @endif
    @endif
</div>
@endif
