{{-- Header --}}
<div class="mb-6">
    <div class="flex items-center gap-4">
        <a
            href="/app/importacao/efd"
            data-link
            id="btn-voltar-topo"
            class="inline-flex items-center justify-center w-10 h-10 rounded border border-gray-300 bg-white text-gray-700 transition hover:bg-gray-50 hover:text-gray-900 flex-shrink-0"
            title="Voltar"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div class="min-w-0 flex-1">
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
            @endphp

            <div class="flex items-center gap-2.5 flex-wrap mb-1.5">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">
                    {{ $isSped ? 'SPED ' . $tipoLabel : 'Importação ' . $tipoLabel }}
                </h1>
                <span class="px-2 py-0.5 text-[10px] font-semibold text-gray-400 bg-gray-200 rounded">
                    ID: {{ $importacao->id }}
                </span>
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded text-white" style="{{ $badgeStyle }}">{{ $badgeLabel }}</span>

                @if($isSped)
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded text-white flex items-center gap-1.5" style="background-color: #374151">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ $spedPeriodo }}
                </span>
                @endif
            </div>

            @if($importacao->filename)
            <div class="flex items-center mt-2 max-w-full">
                <div class="inline-flex items-center gap-2 px-2.5 py-1.5 bg-white border border-gray-300 rounded w-full sm:w-auto overflow-hidden">
                    <svg class="w-4 h-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="font-mono text-xs text-gray-500 truncate" title="{{ $importacao->filename }}">
                        @if($isSped)
                            <span class="text-gray-400 font-medium whitespace-nowrap">SPED-FISCAL <span class="mx-0.5">|</span> {{ $spedPeriodo }} <span class="mx-0.5">|</span></span><span class="text-gray-700">{{ $spedHash }}</span><span class="text-gray-400">.txt</span>
                        @else
                            {{ $importacao->filename }}
                        @endif
                    </span>
                </div>
            </div>
            @else
            <p class="mt-1 text-sm text-gray-500">Detalhes da importação EFD</p>
            @endif
        </div>
    </div>
</div>