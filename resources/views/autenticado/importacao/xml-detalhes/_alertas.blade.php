{{-- Alertas NF-e do lote (derivados do acervo). --}}
@if(count($alertas ?? []) > 0)
<div class="bg-white rounded border border-gray-300 mb-4 overflow-hidden" id="alertas-section">
    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Alertas</span>
            <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ count($alertas) }}</span>
        </div>
    </div>
    <div class="divide-y divide-gray-100">
        @foreach($alertas as $alerta)
        @php $hex = ($alerta['sev'] ?? 'info') === 'alerta' ? '#d97706' : '#6b7280'; @endphp
        <div class="px-4 py-3 flex items-start gap-3 border-l-4" style="border-left-color: {{ $hex }}">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white flex-shrink-0 mt-0.5" style="background-color: {{ $hex }}">
                {{ ($alerta['sev'] ?? 'info') === 'alerta' ? 'Atenção' : 'Info' }}
            </span>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">{{ $alerta['titulo'] }}</p>
                <p class="text-xs text-gray-600 mt-0.5">{{ $alerta['detalhe'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
