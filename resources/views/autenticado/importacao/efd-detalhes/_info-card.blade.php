{{-- Info Card --}}
@php
    $isSped = false;
    $spedPeriodo = '';
    $spedHash = '';
    if ($importacao->filename && str_contains($importacao->filename, '|')) {
        $parts = explode('|', $importacao->filename);
        if (count($parts) >= 3) {
            $isSped = true;
            $spedPeriodo = trim($parts[1]);
            $rawHash = trim($parts[2]);
            $spedHash = str_replace('.txt', '', $rawHash);
        }
    }

    $tipoLabel = (str_contains(strtolower($importacao->tipo_efd ?? ''), 'pis') || $importacao->tipo_efd === 'efd-contrib')
        ? 'EFD PIS/COFINS'
        : 'EFD ICMS/IPI';
    $arquivoLabel = $isSped
        ? trim($parts[0]).' | '.$spedPeriodo.' | '.$spedHash.'.txt'
        : ($importacao->filename ?: 'Arquivo não informado');
@endphp

<div class="bg-white rounded border border-gray-300 overflow-hidden mb-4" id="info-section">
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
 <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
 </div>
 <div class="grid grid-cols-2 lg:grid-cols-6 divide-x divide-y lg:divide-y-0 divide-gray-200">
 <div class="px-4 py-3 min-h-[96px] flex flex-col">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Importação</p>
 <div class="flex-1 flex items-center">
 <p class="text-lg font-bold text-gray-900">#{{ $importacao->id }}</p>
 </div>
 <p class="text-[11px] text-gray-500 mt-1">{{ $importacao->created_at->format('d/m/Y H:i') }}</p>
 </div>
 <div class="px-4 py-3 min-h-[96px] flex flex-col">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Tipo EFD</p>
 <div class="flex-1 flex items-center">
 <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="{{ $tipoStyle }}">{{ $tipoLabel }}</span>
 </div>
 <p class="text-[11px] text-gray-500 mt-1">{{ $spedPeriodo ?: 'Arquivo analisado' }}</p>
 </div>
 <div class="px-4 py-3 min-h-[96px] flex flex-col">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Cliente</p>
 <div class="flex-1 flex items-center">
 <p class="text-sm font-bold text-gray-900">{{ $importacao->cliente?->razao_social ?? 'Não associado' }}</p>
 </div>
 <p class="text-[11px] text-gray-500 mt-1">{{ $importacao->cliente?->documento_formatado ?? 'Sem vínculo a cliente' }}</p>
 </div>
 <div class="px-4 py-3 min-h-[96px] flex flex-col">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Créditos</p>
 <div class="flex-1 flex items-center">
 <p class="text-lg font-bold text-gray-900">{{ number_format((int) ($importacao->creditos_cobrados ?? 0), 0, ',', '.') }}</p>
 </div>
 <p class="text-[11px] text-gray-500 mt-1">cobrados no envio</p>
 </div>
 <div class="px-4 py-3 min-h-[96px] flex flex-col">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Status</p>
 <div class="flex-1 flex items-center">
 <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="{{ $badgeStyle }}">{{ $badgeLabel }}</span>
 </div>
 <p class="text-[11px] text-gray-500 mt-1">{{ $importacao->tempo_processamento ?: 'Em andamento' }}</p>
 </div>
 <div class="px-4 py-3 min-h-[96px] flex flex-col">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Processado em</p>
 <div class="flex-1 flex items-center">
 <p class="text-sm font-bold text-gray-900">{{ $importacao->concluido_em?->format('d/m/Y H:i') ?? 'Em andamento' }}</p>
 </div>
 <p class="text-[11px] text-gray-500 mt-1">última atualização disponível</p>
 </div>
 </div>

 @if($importacao->filename)
 <div class="px-4 py-3 border-t border-gray-200">
 <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Arquivo</p>
 <p class="text-xs font-mono text-gray-700 break-all">{{ $arquivoLabel }}</p>
 </div>
 @endif
</div>
