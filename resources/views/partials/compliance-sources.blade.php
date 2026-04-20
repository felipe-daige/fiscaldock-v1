@php
    $sources = $sources ?? [];
    $variant = $variant ?? 'autenticado'; // 'autenticado' | 'publico'
    $statusBadges = [
        'ativo' => ['label' => 'Ativo', 'bg' => '#047857'],
        'em_implementacao' => ['label' => 'Em implementação', 'bg' => '#1d4ed8'],
        'em_breve' => ['label' => 'Em breve', 'bg' => '#6b7280'],
    ];
@endphp

<ul class="space-y-1.5">
    @foreach($sources as $src)
        @php
            $badge = $statusBadges[$src['status']] ?? $statusBadges['em_breve'];
            $muted = $src['status'] === 'em_breve';
        @endphp
        <li class="flex items-start gap-2 text-xs">
            <span class="mt-1 inline-block w-1.5 h-1.5 rounded-full flex-shrink-0" style="background-color: {{ $badge['bg'] }}"></span>
            <div class="flex-1 min-w-0 flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="font-medium {{ $muted ? 'text-gray-500' : 'text-gray-800' }}">{{ $src['nome'] }}</span>
                <span class="text-[9px] uppercase tracking-wide text-gray-400">{{ $src['categoria'] }}</span>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $badge['bg'] }}">
                    {{ $badge['label'] }}
                </span>
            </div>
        </li>
    @endforeach
</ul>
