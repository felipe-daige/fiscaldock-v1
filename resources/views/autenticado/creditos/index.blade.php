@php
    $tipoBadgeMap = [
        'purchase' => ['label' => 'Compra', 'hex' => '#047857'],
        'refund' => ['label' => 'Reembolso', 'hex' => '#d97706'],
        'manual_add' => ['label' => 'Ajuste', 'hex' => '#4338ca'],
        'trial_bonus' => ['label' => 'Trial', 'hex' => '#1d4ed8'],
        'trial_expiration' => ['label' => 'Expiração', 'hex' => '#b45309'],
    ];
@endphp

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 space-y-6">

        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Comprar créditos</h1>
            <p class="text-xs text-gray-500 mt-1">Escolha quanto quer depositar, acompanhe sua faixa atual e use as ofertas promocionais como atalho.</p>
        </div>

        @if(($trialResumo['is_active'] ?? false) || ($trialResumo['is_expired'] ?? false))
            <div class="bg-white rounded border border-gray-300 p-4 border-l-4 {{ ($trialResumo['is_active'] ?? false) ? 'border-l-blue-500' : 'border-l-amber-500' }}">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Status do trial</p>
                @if($trialResumo['is_active'] ?? false)
                    <p class="mt-2 text-sm text-gray-700">
                        Trial ativo: {{ number_format($trialResumo['remaining'] ?? 0, 0, ',', '.') }} créditos promocionais restantes até {{ optional($trialResumo['expires_at'])->format('d/m/Y H:i') }}.
                    </p>
                @else
                    <p class="mt-2 text-sm text-gray-700">
                        Trial encerrado em {{ optional($trialResumo['expires_at'])->format('d/m/Y H:i') }}. Compras novas não expiram.
                    </p>
                @endif
            </div>
        @endif

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Saldo atual</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($saldoAtual, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">créditos</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Total recebido</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($totalRecebido, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">créditos</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Total consumido</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($totalConsumido, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">créditos</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Última entrada</p>
                    @if($ultimaEntrada)
                        <p class="text-lg sm:text-xl font-bold text-gray-900">+{{ number_format($ultimaEntrada->amount, 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">{{ $ultimaEntrada->created_at->format('d/m/Y') }}</p>
                    @else
                        <p class="text-lg sm:text-xl font-bold text-gray-900">--</p>
                        <p class="text-[11px] text-gray-500 mt-1">nenhuma entrada</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200">
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Faixa atual</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ $pricing['current_tier']['nome'] ?? 'Base' }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">desconto aplicado nas próximas consultas</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Histórico pago</p>
                    <p class="text-lg sm:text-xl font-bold text-gray-900">{{ number_format($pricing['paid_credits'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">créditos comprados acumulados</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Próxima faixa</p>
                    @if($pricing['next_tier'])
                        <p class="text-lg sm:text-xl font-bold text-gray-900">{{ $pricing['next_tier']['nome'] }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">faltam {{ number_format($pricing['credits_remaining'], 0, ',', '.') }} créditos pagos</p>
                    @else
                        <p class="text-lg sm:text-xl font-bold text-gray-900">Máxima</p>
                        <p class="text-[11px] text-gray-500 mt-1">você já está na melhor condição comercial</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(320px,0.9fr)_minmax(0,1.1fr)] gap-6 items-start">
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Valor livre</span>
                </div>
                <div class="p-4 sm:p-6 space-y-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Deposite o valor que fizer sentido agora</p>
                        <p class="text-sm text-gray-600 mt-1">Mínimo de R$ {{ number_format($pricing['minimum_deposit'] ?? 50, 0, ',', '.') }}. Os créditos entram como saldo pré-pago e o histórico comprado continua contando para a sua faixa.</p>
                    </div>
                    <form method="GET" action="/app/checkout/custom" class="space-y-3">
                        <div>
                            <label for="credit-custom-amount" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Quanto deseja pagar</label>
                            <div class="flex items-center rounded border border-gray-300 bg-white">
                                <span class="px-3 text-sm text-gray-500">R$</span>
                                <input
                                    id="credit-custom-amount"
                                    name="amount"
                                    type="number"
                                    min="{{ (int) ($pricing['minimum_deposit'] ?? 50) }}"
                                    step="1"
                                    value="{{ (int) ($pricing['minimum_deposit'] ?? 50) }}"
                                    class="w-full border-0 px-0 py-3 text-sm text-gray-900 focus:ring-0 focus:outline-none"
                                >
                            </div>
                            @if($errors->has('amount'))
                                <p class="mt-2 text-xs text-red-600">{{ $errors->first('amount') }}</p>
                            @else
                                <p class="mt-2 text-[11px] text-gray-500">Exemplo: R$ 80 libera {{ number_format((int) round(80 / ($pricing['credit_unit_price'] ?? 0.20)), 0, ',', '.') }} créditos.</p>
                            @endif
                        </div>
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-3 text-sm font-semibold text-white rounded" style="background-color: #1f2937">
                            Continuar para pagamento
                        </button>
                    </form>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Ofertas promocionais</h2>
                    <a href="/app/plano" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">Ver faixa comercial</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(($pricing['featured_offers'] ?? $pacotes) as $pacote)
                        <div class="bg-white border {{ !empty($pacote['featured']) ? 'border-gray-900' : 'border-gray-200' }} rounded overflow-hidden flex flex-col h-full">
                            <div class="px-4 py-5 space-y-2 flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ $pacote['usage_hint'] ?? 'Oferta' }}</p>
                                        <p class="text-sm font-semibold text-gray-900 mt-1">{{ $pacote['nome'] }}</p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ !empty($pacote['featured']) ? '#0f766e' : '#374151' }}">{{ $pacote['badge'] ?? 'Oferta' }}</span>
                                </div>
                                <p class="text-[11px] text-gray-400">{{ number_format($pacote['creditos'], 0, ',', '.') }} créditos</p>
                                <p class="text-2xl font-bold text-gray-900">R$ {{ number_format($pacote['preco'], 0, ',', '.') }}</p>
                                <p class="text-[11px] text-gray-500">{{ $pacote['descricao'] ?? 'Créditos pré-pagos para uso conforme a necessidade.' }}</p>
                            </div>
                            <div class="px-4 py-4 border-t border-gray-100 mt-auto">
                                <a href="/app/checkout/{{ $pacote['slug'] }}" data-link class="w-full inline-flex items-center justify-center px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-white rounded" style="background-color: #1f2937">Usar oferta</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Histórico de créditos</span>
                <a href="/app/plano" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">Ver consumo detalhado</a>
            </div>
            @if($historicoCreditos->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-300">
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Data</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>
                                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Descrição</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Créditos</th>
                                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Saldo após</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($historicoCreditos as $tx)
                                @php
                                    $badge = $tipoBadgeMap[$tx->type] ?? ['label' => ucfirst($tx->type ?? 'Outro'), 'hex' => '#9ca3af'];
                                    $amountClass = $tx->amount >= 0 ? 'text-emerald-600' : 'text-red-600';
                                    $amountPrefix = $tx->amount >= 0 ? '+' : '';
                                @endphp
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-3 py-2.5 text-sm text-gray-700 whitespace-nowrap">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2.5 text-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $badge['hex'] }}">{{ $badge['label'] }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 text-sm text-gray-600">{{ $tx->description ?? '-' }}</td>
                                    <td class="px-3 py-2.5 text-sm text-right font-semibold {{ $amountClass }}">{{ $amountPrefix }}{{ number_format($tx->amount, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2.5 text-sm text-right text-gray-500">{{ $tx->balance_after !== null ? number_format($tx->balance_after, 0, ',', '.') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-6 text-center text-sm text-gray-500 space-y-2">
                    <p>Nenhuma movimentação de créditos ainda.</p>
                    <p class="text-xs text-gray-400">Crie saldo com o trial, faça uma recarga livre ou use uma das ofertas promocionais.</p>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded border border-gray-300 p-4 text-center space-y-2">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Pre-pago</p>
                <p class="text-sm font-semibold text-gray-900">Compre e use quando precisar.</p>
            </div>
            <div class="bg-white rounded border border-gray-300 p-4 text-center space-y-2">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Valor livre</p>
                <p class="text-sm font-semibold text-gray-900">Você escolhe quanto pagar acima do mínimo.</p>
            </div>
            <div class="bg-white rounded border border-gray-300 p-4 text-center space-y-2">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Validade</p>
                <p class="text-sm font-semibold text-gray-900">Créditos pagos não expiram; o bônus do trial expira em 30 dias.</p>
            </div>
            <div class="bg-white rounded border border-gray-300 p-4 text-center space-y-2">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Ofertas</p>
                <p class="text-sm font-semibold text-gray-900">Business e Enterprise continuam como atalhos promocionais.</p>
            </div>
        </div>

    </div>
</div>
