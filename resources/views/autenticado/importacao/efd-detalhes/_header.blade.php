{{-- Header --}}
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

    $isPisCofins = $importacao->tipo_efd === 'efd-contrib' || str_contains(strtolower($importacao->tipo_efd ?? ''), 'pis');
    $tipoLabel = $isPisCofins ? 'EFD PIS/COFINS' : 'EFD ICMS/IPI';
    $mostrarNovaImportacao = ! ($emProcessamento ?? in_array($importacao->status, ['processando', 'pendente'], true));
@endphp

<div class="mb-4 sm:mb-6 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
    <div class="min-w-0">
        <a href="/app/importacao/efd" data-link id="btn-voltar-topo" class="inline-flex items-center gap-2 text-xs text-gray-600 hover:text-gray-900 hover:underline mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Voltar para importações
        </a>
        <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">
            Detalhe da Importação {{ $tipoLabel }}
        </h1>
        <p class="text-xs text-gray-500 mt-1">Acompanhe o processamento do arquivo SPED e consulte o resultado consolidado da extração fiscal nesta página.</p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if($mostrarNovaImportacao)
            <a href="/app/importacao/efd" data-link class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-medium">Nova importação</a>
        @endif
        @if($isSped && $spedPeriodo)
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">
                {{ $spedPeriodo }}
            </span>
        @endif
        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="{{ $tipoStyle }}">
            {{ $tipoLabel }}
        </span>
        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="{{ $badgeStyle }}">
            {{ $badgeLabel }}
        </span>
    </div>
</div>
