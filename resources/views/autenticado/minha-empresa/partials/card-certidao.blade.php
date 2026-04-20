{{-- Card de Certidão — DANFE Modernizado --}}
@php
    $consultado = $dados['consultado'] ?? false;
    $status = strtoupper($dados['status'] ?? '');
    $validade = $dados['validade'] ?? null;

    $corHex = '#9ca3af';
    $statusLabel = 'Não consultado';

    if ($consultado && !empty($status)) {
        if (in_array($status, ['NEGATIVA', 'REGULAR', 'REGULARIDADE'])) {
            $corHex = '#047857';
            $statusLabel = 'Negativa';
        } elseif (str_contains($status, 'POSITIVA COM EFEITO') || str_contains($status, 'EFEITO DE NEGATIVA')) {
            $corHex = '#d97706';
            $statusLabel = 'Positiva c/ Efeito';
        } elseif (in_array($status, ['POSITIVA', 'IRREGULAR', 'IRREGULARIDADE'])) {
            $corHex = '#b91c1c';
            $statusLabel = 'Positiva';
        } else {
            $corHex = '#4338ca';
            $statusLabel = $status;
        }
    }

    $diasRestantes = null;
    if ($validade) {
        try {
            $dataValidade = \Carbon\Carbon::parse($validade);
            $diasRestantes = now()->diffInDays($dataValidade, false);
        } catch (\Exception $e) {
            $diasRestantes = null;
        }
    }
@endphp

<div class="bg-white rounded border border-gray-300 border-t-2 p-4" style="border-top-color: {{ $corHex }}">
    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">{{ $nome }}</span>
    <div class="mt-2">
        @if($consultado)
            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                  style="background-color: {{ $corHex }}">{{ $statusLabel }}</span>
            @if($validade && $diasRestantes !== null)
                <p class="text-[11px] text-gray-500 mt-2">
                    @if($diasRestantes <= 0)
                        <span class="font-semibold" style="color: #b91c1c">Vencida</span>
                    @elseif($diasRestantes <= 7)
                        <span class="font-semibold" style="color: #d97706">Vence em {{ $diasRestantes }} dias</span>
                    @else
                        Val: {{ \Carbon\Carbon::parse($validade)->format('d/m/Y') }}
                    @endif
                </p>
            @endif
        @else
            <p class="text-sm font-semibold text-gray-300">—</p>
            <p class="text-[11px] text-gray-500 mt-1">Não consultado</p>
        @endif
    </div>
</div>
