{{-- Resumo Tributário do lote XML (agregado de xml_notas). --}}
@if(($resumoTributario['qtd'] ?? 0) > 0)
@php $rt = $resumoTributario; @endphp
<div class="bg-white rounded border border-gray-300 mb-4 overflow-hidden" id="tributario-section">
    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Tributário</span>
    </div>

    {{-- KPIs financeiros --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 divide-x divide-y lg:divide-y-0 divide-gray-200 border-b border-gray-200">
        <div class="px-4 py-3">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor Total</p>
            <p class="text-lg font-bold text-gray-900">R$ {{ number_format($rt['valor_total'], 2, ',', '.') }}</p>
            <p class="text-[11px] text-gray-500">{{ number_format($rt['qtd']) }} {{ $rt['qtd'] == 1 ? 'nota' : 'notas' }}</p>
        </div>
        <div class="px-4 py-3">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Entradas</p>
            <p class="text-lg font-bold text-gray-900">{{ number_format($rt['entradas']) }}</p>
            <p class="text-[11px] text-gray-500">R$ {{ number_format($rt['valor_entradas'], 2, ',', '.') }}</p>
        </div>
        <div class="px-4 py-3">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Saídas</p>
            <p class="text-lg font-bold text-gray-900">{{ number_format($rt['saidas']) }}</p>
            <p class="text-[11px] text-gray-500">R$ {{ number_format($rt['valor_saidas'], 2, ',', '.') }}</p>
        </div>
        <div class="px-4 py-3">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Devoluções</p>
            <p class="text-lg font-bold text-gray-900" @if($rt['devolucoes'] > 0) style="color: #d97706" @endif>{{ number_format($rt['devolucoes']) }}</p>
            <p class="text-[11px] text-gray-500">finalidade 4</p>
        </div>
        <div class="px-4 py-3">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Ticket Médio</p>
            <p class="text-lg font-bold text-gray-900">R$ {{ number_format($rt['ticket_medio'], 2, ',', '.') }}</p>
            <p class="text-[11px] text-gray-500">por nota</p>
        </div>
        <div class="px-4 py-3">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Descontos</p>
            <p class="text-lg font-bold text-gray-900">R$ {{ number_format($rt['desconto'], 2, ',', '.') }}</p>
            <p class="text-[11px] text-gray-500">no lote</p>
        </div>
    </div>

    {{-- Impostos destacados --}}
    <div class="p-4">
        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-3">Impostos Destacados</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach([
                ['ICMS', $rt['icms']],
                ['ICMS-ST', $rt['icms_st']],
                ['PIS', $rt['pis']],
                ['COFINS', $rt['cofins']],
                ['IPI', $rt['ipi']],
                ['Tributos (aprox.)', $rt['tributos']],
            ] as [$lbl, $val])
            <div class="flex flex-col">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-0.5">{{ $lbl }}</p>
                <p class="text-sm font-bold text-gray-900 font-mono">R$ {{ number_format($val, 2, ',', '.') }}</p>
            </div>
            @endforeach
        </div>
        <p class="text-[11px] text-gray-400 mt-3">Valores destacados nas notas do lote (não é apuração — NF-e não consolida período fiscal).</p>
    </div>

    {{-- Distribuição por UF da contraparte --}}
    @if(($porUf ?? collect())->count() > 0)
    @php $ufMax = max($porUf->max('valor'), 0.01); @endphp
    <div class="px-4 pb-4">
        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Distribuição por UF (contraparte)</p>
        <div class="border border-gray-200 rounded overflow-hidden">
            @foreach($porUf as $row)
            <div class="flex items-center gap-3 px-3 py-2 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                <span class="text-xs font-bold text-gray-900 w-8">{{ $row->uf ?: '—' }}</span>
                <div class="flex-1 h-2 rounded-full overflow-hidden" style="background-color: #f3f4f6">
                    <div class="h-full" style="background-color: #1f2937; width: {{ round(($row->valor / $ufMax) * 100, 1) }}%"></div>
                </div>
                <span class="text-[11px] text-gray-500 w-16 text-right">{{ number_format($row->qtd) }} {{ $row->qtd == 1 ? 'nota' : 'notas' }}</span>
                <span class="text-xs font-mono text-gray-900 w-28 text-right">R$ {{ number_format($row->valor, 2, ',', '.') }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif
