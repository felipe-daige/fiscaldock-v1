<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 space-y-6">

        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Faixa Comercial</h1>
            <p class="text-xs text-gray-500 mt-1">Resumo operacional do consumo de créditos e da sua economia atual por volume.</p>
        </div>

        @if(($trialResumo['is_active'] ?? false) || ($trialResumo['is_expired'] ?? false))
            <div class="bg-white rounded border border-gray-300 p-4 border-l-4 {{ ($trialResumo['is_active'] ?? false) ? 'border-l-blue-500' : 'border-l-amber-500' }}">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Créditos promocionais</p>
                @if($trialResumo['is_active'] ?? false)
                    <p class="mt-2 text-sm text-gray-700">
                        Você recebeu {{ number_format($trialResumo['granted'] ?? 0, 0, ',', '.') }} créditos grátis.
                        Restam {{ number_format($trialResumo['remaining'] ?? 0, 0, ',', '.') }} até {{ optional($trialResumo['expires_at'])->format('d/m/Y H:i') }}.
                    </p>
                @else
                    <p class="mt-2 text-sm text-gray-700">
                        O trial expirou em {{ optional($trialResumo['expires_at'])->format('d/m/Y H:i') }}.
                        Créditos comprados continuam válidos normalmente.
                    </p>
                @endif
            </div>
        @endif

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Saldo atual</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($saldoAtual, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">créditos disponíveis</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Faixa atual</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ $pricing['current_tier']['nome'] ?? 'Base' }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">economia ativa nas próximas consultas</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Histórico pago</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($pricing['paid_credits'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">créditos comprados acumulados</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Usados no mês</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($creditosUsadosMes, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">créditos consumidos</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Progresso para a próxima faixa</span>
                    </div>
                    <div class="p-6 space-y-4">
                        @if($pricing['next_tier'])
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $pricing['current_tier']['nome'] }} → {{ $pricing['next_tier']['nome'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Faltam {{ number_format($pricing['credits_remaining'], 0, ',', '.') }} créditos pagos acumulados para destravar a próxima economia.
                                    </p>
                                </div>
                                <a href="/app/creditos" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">Comprar créditos</a>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                                <div class="h-3 rounded-full" style="width: {{ $pricing['progress_percent'] }}%; background-color: #1f2937"></div>
                            </div>
                        @else
                            <p class="text-sm text-gray-700">Você já está na maior faixa comercial disponível. As melhores condições já estão ativas.</p>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Custo atual por produto</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4">
                        @foreach($pricing['products'] as $product)
                            <div class="border border-gray-200 rounded p-4">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">{{ $product['nome'] }}</p>
                                <p class="mt-2 text-lg font-bold text-gray-900">{{ number_format($product['credits'], 0, ',', '.') }} créditos</p>
                                <p class="text-[11px] text-gray-500 mt-1">R$ {{ number_format($product['price'], 2, ',', '.') }} por consulta na faixa {{ $pricing['current_tier']['nome'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Histórico de consumo</span>
                        <a href="/app/creditos" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">Ver recargas</a>
                    </div>
                    @if($ultimasTransacoes->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b border-gray-300">
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Data</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Execução</th>
                                        <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Produto</th>
                                        <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participantes</th>
                                        <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Créditos</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($ultimasTransacoes as $tx)
                                        @php
                                            $statusHex = match($tx->status) {
                                                'concluido' => '#047857',
                                                'processando' => '#d97706',
                                                'erro' => '#dc2626',
                                                default => '#9ca3af',
                                            };
                                            $statusLabel = match($tx->status) {
                                                'concluido' => 'Concluído',
                                                'processando' => 'Processando',
                                                'pendente' => 'Pendente',
                                                'erro' => 'Erro',
                                                default => ucfirst($tx->status ?: '—'),
                                            };
                                        @endphp
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-3 py-2.5 text-sm text-gray-700 whitespace-nowrap">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="px-3 py-2.5 text-sm text-gray-700">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $statusHex }}">{{ $statusLabel }}</span>
                                                <span class="ml-2">Lote #{{ $tx->id }}</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-sm text-gray-600">{{ $tx->plano->nome ?? '-' }}</td>
                                            <td class="px-3 py-2.5 text-sm text-gray-700 text-center">{{ $tx->total_participantes ?? '-' }}</td>
                                            <td class="px-3 py-2.5 text-sm font-semibold text-right {{ $tx->creditos_cobrados > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                                @if($tx->creditos_cobrados > 0)
                                                    -{{ $tx->creditos_cobrados }}
                                                @else
                                                    0
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-6 text-center text-sm text-gray-500 space-y-2">
                            <p>Nenhuma consulta realizada ainda.</p>
                            <a href="/app/consulta/nova" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">Iniciar primeira consulta</a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Valor livre</span>
                    </div>
                    <div class="p-4 space-y-4">
                        <p class="text-sm text-gray-700">Faça uma recarga personalizada a partir de R$ {{ number_format($pricing['minimum_deposit'] ?? 50, 0, ',', '.') }} e continue acumulando histórico pago para subir de faixa.</p>
                        <form method="GET" action="/app/checkout/custom" class="space-y-3">
                            <div>
                                <label for="plan-custom-amount" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Quanto deseja pagar</label>
                                <div class="flex items-center rounded border border-gray-300 bg-white">
                                    <span class="px-3 text-sm text-gray-500">R$</span>
                                    <input
                                        id="plan-custom-amount"
                                        name="amount"
                                        type="number"
                                        min="{{ (int) ($pricing['minimum_deposit'] ?? 50) }}"
                                        step="1"
                                        value="{{ (int) ($pricing['minimum_deposit'] ?? 50) }}"
                                        class="w-full border-0 px-0 py-3 text-sm text-gray-900 focus:ring-0 focus:outline-none"
                                    >
                                </div>
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-3 text-sm font-semibold text-white rounded" style="background-color: #1f2937">
                                Continuar para pagamento
                            </button>
                        </form>
                    </div>
                </div>

                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Como funciona</span>
                    </div>
                    <div class="p-6 space-y-4 text-sm text-gray-700">
                        <div class="flex items-start gap-3">
                            <span class="text-[10px] font-bold uppercase tracking-wide text-white rounded px-2 py-0.5" style="background-color: #374151">1</span>
                            <p>Você escolhe quanto quer depositar acima do mínimo, sem mensalidade e sem assinatura recorrente.</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-[10px] font-bold uppercase tracking-wide text-white rounded px-2 py-0.5" style="background-color: #374151">2</span>
                            <p>O custo das consultas cai conforme a sua faixa comercial sobe pelo histórico acumulado de créditos pagos.</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-[10px] font-bold uppercase tracking-wide text-white rounded px-2 py-0.5" style="background-color: #374151">3</span>
                            <p>Créditos comprados não expiram; créditos promocionais do trial expiram ao fim do período informado.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Ofertas promocionais</span>
                    </div>
                    <div class="p-4 space-y-3">
                        @foreach($pricing['featured_offers'] as $pacote)
                            <a href="/app/checkout/{{ $pacote['slug'] }}" data-link class="block border border-gray-200 rounded px-4 py-3 transition-colors hover:border-gray-400">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $pacote['nome'] }}</p>
                                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $pacote['usage_hint'] ?? '' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-gray-900">R$ {{ number_format($pacote['preco'], 0, ',', '.') }}</p>
                                        <p class="text-[11px] text-gray-500">{{ number_format($pacote['creditos'], 0, ',', '.') }} créditos</p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Consumo mensal</span>
                    </div>
                    <div class="p-6 space-y-3">
                        @foreach($consumoMensal as $mes)
                            @php
                                $width = $maxConsumo > 0 ? min(100, max(0, ($mes['valor'] / $maxConsumo) * 100)) : 0;
                            @endphp
                            <div class="flex items-center gap-3">
                                <span class="text-[11px] text-gray-500 w-16 text-right font-mono">{{ $mes['label'] }}</span>
                                <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                                    <div class="h-6 rounded-full" style="width: {{ $width }}%; background-color: #374151"></div>
                                </div>
                                <span class="text-[11px] text-gray-500 font-mono">{{ number_format($mes['valor'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                        @if($maxConsumo === 0)
                            <p class="text-sm text-gray-400 text-center">Nenhum consumo registrado nos últimos 6 meses.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
