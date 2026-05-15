<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        <div class="mb-6">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Alertas de Compliance Automático</h1>
            <p class="text-xs text-gray-500 mt-1">Eventos gerados pelas checagens recorrentes ativas.</p>
        </div>

        {{-- KPIs --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo</span>
            </div>
            <div class="grid grid-cols-3 divide-x divide-gray-200">
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Não lidos</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $kpiNaoLidos }}</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Críticos</p>
                    <p class="text-lg font-bold font-mono" style="color: #b91c1c;">{{ $kpiCriticos }}</p>
                </div>
                <div class="p-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Últimos 7 dias</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $kpi7dias }}</p>
                </div>
            </div>
        </div>

        @include('autenticado.monitoramento._sub-tabs-tipo', [
            'tipoAtivo' => $tipoAtivo,
            'contagens' => $contagens,
            'rota' => 'app.monitoramento.alertas',
        ])

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            @if ($alertas->isEmpty())
                <div class="p-10 text-center">
                    <p class="text-sm text-gray-600">Nenhum alerta de monitoramento.</p>
                </div>
            @else
                <ul class="divide-y divide-gray-200">
                    @foreach ($alertas as $alerta)
                        @php
                            $alvo = $alerta->cliente ?? $alerta->participante;
                            $tipoAlvo = $alerta->cliente_id ? 'cliente' : ($alerta->participante_id ? 'participante' : null);
                            $href = $tipoAlvo === 'cliente' ? "/app/cliente/{$alvo?->id}" : ($tipoAlvo === 'participante' ? "/app/participante/{$alvo?->id}" : '#');
                            $corSev = match ($alerta->severidade) {
                                'critico' => '#b91c1c',
                                'atencao' => '#d97706',
                                default => '#374151',
                            };
                        @endphp
                        <li class="p-4 flex items-start gap-3">
                            <span class="mt-1 inline-block w-2 h-2 rounded-full" style="background-color: {{ $corSev }};"></span>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900">{{ $alerta->titulo }}</p>
                                <p class="text-xs text-gray-600 mt-1">{{ $alerta->descricao }}</p>
                                @if ($alvo)
                                    <a href="{{ $href }}" data-link class="text-[11px] text-gray-500 hover:text-gray-700 hover:underline mt-1 inline-block">
                                        {{ $alvo->documento }} — {{ $alvo->razao_social }}
                                    </a>
                                @endif
                            </div>
                            <span class="text-[10px] text-gray-400">{{ $alerta->created_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                    {{ $alertas->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
