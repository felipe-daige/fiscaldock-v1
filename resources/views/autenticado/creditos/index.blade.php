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

        {{-- Faixa comercial — resumo numa linha (detalhe vive em /app/faixa-comercial) --}}
        <a href="/app/faixa-comercial" data-link class="flex flex-wrap items-center gap-x-2 gap-y-1 bg-white rounded border border-gray-300 px-4 py-3 text-[13px] text-gray-600 hover:bg-gray-50 transition-colors">
            <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Faixa</span>
            <span class="font-semibold text-gray-900">{{ $pricing['current_tier']['nome'] ?? 'Base' }}</span>
            <span class="text-gray-300">·</span>
            <span>pago {{ number_format($pricing['paid_credits'] ?? 0, 0, ',', '.') }} cr</span>
            <span class="text-gray-300">·</span>
            @if($pricing['next_tier'])
                <span>próxima <span class="font-medium text-gray-700">{{ $pricing['next_tier']['nome'] }}</span> (faltam {{ number_format($pricing['credits_remaining'], 0, ',', '.') }} cr)</span>
            @else
                <span>melhor condição comercial</span>
            @endif
            <span class="ml-auto text-gray-400">→</span>
        </a>

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(320px,0.9fr)_minmax(0,1.1fr)] gap-6 items-start">
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Valor livre</span>
                </div>
                <div class="p-4 sm:p-6 space-y-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Deposite o valor que fizer sentido agora</p>
                        <p class="text-sm text-gray-600 mt-1">Mínimo de R$ {{ number_format($pricing['minimum_deposit'] ?? 100, 0, ',', '.') }}. Os créditos entram como saldo pré-pago e o histórico comprado continua contando para a sua faixa.</p>
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
                                    min="{{ (int) ($pricing['minimum_deposit'] ?? 100) }}"
                                    step="1"
                                    value="{{ (int) ($pricing['minimum_deposit'] ?? 100) }}"
                                    class="w-full border-0 px-0 py-3 text-sm text-gray-900 focus:ring-0 focus:outline-none"
                                >
                            </div>
                            @if($errors->has('amount'))
                                <p class="mt-2 text-xs text-red-600">{{ $errors->first('amount') }}</p>
                            @else
                                <p class="mt-2 text-[11px] text-gray-500">Exemplo: R$ 200 libera {{ number_format((int) round(200 / ($pricing['credit_unit_price'] ?? 0.20)), 0, ',', '.') }} créditos.</p>
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
                    <a href="/app/faixa-comercial" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">Ver faixa comercial</a>
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

        {{-- Recarga automática por tempo (Fase 2) --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Recarga automática</span>
            </div>
            <div class="p-4 sm:p-6">
                @if(config('services.mercadopago.auto_topup.habilitado') && $recargaAtual && in_array($recargaAtual->status, ['ativa', 'inadimplente']) && $recargaAtual->gatilho === 'saldo')
                    {{-- Estado ativo: auto top-up por saldo baixo --}}
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm text-gray-700">
                            Recompra de <span class="font-semibold">{{ number_format($recargaAtual->creditos, 0, ',', '.') }} créditos</span>
                            (R$ {{ number_format($recargaAtual->valor, 2, ',', '.') }}) quando o saldo fica abaixo de
                            <span class="font-semibold">{{ number_format($recargaAtual->limite_creditos, 0, ',', '.') }} créditos</span>.
                            @if($recargaAtual->status === 'inadimplente')
                                <span class="ml-2 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #b91c1c">Pausada</span>
                            @else
                                <span class="ml-2 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Ativa</span>
                            @endif
                        </div>
                        <button type="button" id="recarga-cancelar" class="px-3 py-2 text-[11px] font-semibold uppercase tracking-wide rounded border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar recarga</button>
                    </div>
                    @if($recargaAtual->status === 'inadimplente')
                        <div class="mt-3 rounded px-3 py-2 text-[13px]" style="background-color:#fef2f2; color:#991b1b;">
                            Sua recarga pausada — o cartão foi recusado ou o limite diário foi atingido. Reative atualizando o cartão abaixo.
                        </div>
                        <div class="mt-4">@include('autenticado.creditos.partials.recarga-saldo-form')</div>
                    @endif
                @elseif($recargaAtual && in_array($recargaAtual->status, ['ativa', 'inadimplente']))
                    {{-- Estado ativo: recarga por tempo (mensal) --}}
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm text-gray-700">
                            Recompra de <span class="font-semibold">{{ number_format($recargaAtual->creditos, 0, ',', '.') }} créditos</span>
                            (R$ {{ number_format($recargaAtual->valor, 2, ',', '.') }}) todo mês.
                            @if($recargaAtual->status === 'inadimplente')
                                <span class="ml-2 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #b91c1c">Pagamento pendente</span>
                            @else
                                <span class="ml-2 px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Ativa</span>
                            @endif
                        </div>
                        <button type="button" id="recarga-cancelar" class="px-3 py-2 text-[11px] font-semibold uppercase tracking-wide rounded border border-gray-300 text-gray-700 hover:bg-gray-50">Cancelar recarga</button>
                    </div>
                @else
                    {{-- Sem recarga: oferece os DOIS gatilhos (exclusivos — ativar um cancela o outro) --}}
                    <p class="text-sm text-gray-700 mb-3">Cobre um pacote de créditos automaticamente todo mês, sem precisar lembrar. Cancele quando quiser.</p>
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label for="recarga-pacote" class="block text-[11px] text-gray-500 mb-1">Pacote mensal</label>
                            <select id="recarga-pacote" class="text-[13px] py-2.5 px-3 border border-gray-300 rounded bg-white">
                                @foreach(($pricing['featured_offers'] ?? []) as $pac)
                                    <option value="{{ $pac['slug'] }}" data-valor="{{ $pac['preco'] }}">{{ $pac['nome'] }} — {{ number_format($pac['creditos'], 0, ',', '.') }} cr / R$ {{ number_format($pac['preco'], 0, ',', '.') }}/mês</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" id="recarga-ativar" class="px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-white rounded" style="background-color: #1f2937">Ativar recarga automática</button>
                    </div>
                    @if(config('services.mercadopago.auto_topup.habilitado'))
                        <div class="mt-6 border-t border-gray-100 pt-6">@include('autenticado.creditos.partials.recarga-saldo-form')</div>
                    @endif
                @endif
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Histórico de créditos</span>
                <a href="/app/faixa-comercial" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">Ver consumo detalhado</a>
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

        <p class="text-[11px] text-gray-500 text-center pt-1">
            Créditos pagos não expiram; o bônus do trial expira em {{ config('trial.validade_dias') }} dias. Prefere mensalidade? <a href="/app/planos" data-link class="underline hover:text-gray-700">Ver planos</a>.
        </p>

    </div>

    {{-- Modal do cartão da recarga automática (Card Payment Brick) --}}
    <div id="recarga-modal" class="hidden fixed inset-0 z-50 items-center justify-center" style="background-color: rgba(17,24,39,.6)">
        <div class="bg-white rounded-xl w-full max-w-md mx-4 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800">Cartão da recarga automática</h3>
                <button type="button" id="recarga-fechar" class="text-gray-400 text-xl leading-none">&times;</button>
            </div>
            <div id="recarga-brick"></div>
            <p id="recarga-erro" class="hidden mt-3 text-xs" style="color: #dc2626"></p>
        </div>
    </div>
</div>

<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
    window.__MP_PUBLIC_KEY = @json($mpPublicKey);
    window.__RECARGA_URL = @json(route('app.recarga.criar'));
    window.__RECARGA_SALDO_URL = @json(route('app.recarga.criar-saldo'), JSON_UNESCAPED_SLASHES);
    window.__RECARGA_CANCELAR_URL = @json(route('app.recarga.cancelar'));
    window.__CSRF = @json(csrf_token());
</script>
<script src="{{ asset('js/recarga.js') }}"></script>
