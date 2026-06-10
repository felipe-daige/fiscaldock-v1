{{-- Barra de navegação ancorada (sticky) — paridade com o resultado do EFD. --}}
@if($importacao->status === 'concluido')
@php
    $temTributario = ($resumoTributario['qtd'] ?? 0) > 0;
    $temNotas = ($notasColl ?? collect())->count() > 0;
    $temCatalogo = ($catalogoItens ?? collect())->count() > 0;
    $temAlertas = count($alertas ?? []) > 0;
@endphp
<nav class="sticky top-0 z-20 bg-white/95 backdrop-blur border border-gray-300 rounded mb-4 px-4 py-2 flex items-center justify-between gap-4" id="xml-sticky-nav">
    <div class="flex items-center gap-1 overflow-x-auto" style="scrollbar-width: thin;">
        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide mr-2 flex-shrink-0">Ir para:</span>
        <a href="#indicadores-section" class="xml-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">Resumo</a>
        @if($temTributario)
            <a href="#tributario-section" class="xml-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">Tributário</a>
        @endif
        @if($temNotas)
            <a href="#notas-section" class="xml-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">Notas</a>
        @endif
        @if($temCatalogo)
            <a href="#catalogo-section" class="xml-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">Catálogo</a>
        @endif
        <a href="#participantes-section" class="xml-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">Participantes</a>
        @if($temAlertas)
            <a href="#alertas-section" class="xml-nav-link px-3 py-1.5 text-xs font-medium text-gray-600 border border-transparent rounded hover:bg-gray-100 transition whitespace-nowrap">Alertas</a>
        @endif
    </div>

    <a href="/app/importacao/xml" id="xml-btn-voltar-sticky" data-link class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-700 text-xs font-semibold transition-all duration-300 hover:bg-gray-50 flex-shrink-0 opacity-0 pointer-events-none translate-x-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Voltar
    </a>
</nav>
@endif
