@if($assinaturaAtiva ?? null)
<div class="bg-white rounded border border-gray-300 overflow-hidden">
    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Assinatura Ativa</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $assinaturaAtiva->status === 'ativo' ? '#047857' : '#d97706' }}">
                {{ $assinaturaAtiva->status === 'ativo' ? 'ATIVA' : 'PAUSADA' }}
            </span>
        </div>
    </div>
    <div class="p-4 sm:p-6 space-y-4">
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Plano</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $assinaturaAtiva->plano->nome ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Frequência</p>
            <p class="mt-1 text-sm text-gray-900">
                @php
                    $frequencias = ['diario' => 'Diaria', 'semanal' => 'Semanal', 'quinzenal' => 'Quinzenal', 'mensal' => 'Mensal'];
                @endphp
                {{ $frequencias[$assinaturaAtiva->frequencia] ?? ucfirst($assinaturaAtiva->frequencia) }}
            </p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Próxima Execução</p>
            <p class="mt-1 text-sm text-gray-900">{{ $assinaturaAtiva->proxima_execucao_em ? $assinaturaAtiva->proxima_execucao_em->format('d/m/Y H:i') : '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Última Execução</p>
            <p class="mt-1 text-sm text-gray-900">{{ $assinaturaAtiva->ultima_execucao_em ? $assinaturaAtiva->ultima_execucao_em->format('d/m/Y H:i') : 'Nunca' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Créditos/Execução</p>
            <p class="mt-1 text-sm text-gray-900">{{ $assinaturaAtiva->plano->custo_creditos ?? 0 }} creditos</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Custo Mensal Estimado</p>
            <p class="mt-1 text-sm text-gray-900">{{ $custoMensalEstimado ?? 0 }} creditos</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Já Consumido</p>
            <p class="mt-1 text-sm text-gray-900">{{ $totalConsumido ?? 0 }} creditos</p>
        </div>
        <div class="pt-4 border-t border-gray-200 flex gap-2">
            @if($assinaturaAtiva->status === 'ativo')
                <button type="button" class="btn-pausar-assinatura flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 rounded border border-gray-300 bg-white text-gray-600 text-sm font-semibold transition hover:bg-gray-50" data-assinatura-id="{{ $assinaturaAtiva->id }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Pausar
                </button>
            @else
                <button type="button" class="btn-reativar-assinatura flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 rounded border border-gray-300 bg-white text-gray-600 text-sm font-semibold transition hover:bg-gray-50" data-assinatura-id="{{ $assinaturaAtiva->id }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Reativar
                </button>
            @endif
            <button type="button" class="btn-cancelar-assinatura inline-flex items-center justify-center gap-2 px-3 py-2 rounded border border-gray-300 bg-white text-gray-600 text-sm font-semibold transition hover:bg-gray-50" data-assinatura-id="{{ $assinaturaAtiva->id }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Cancelar
            </button>
        </div>
    </div>
</div>
@endif
