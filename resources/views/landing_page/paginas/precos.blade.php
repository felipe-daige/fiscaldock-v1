@php
    $pricingData = $pricingData ?? [];
    $minimumDeposit = $pricingData['minimum_deposit'] ?? 50;
    $featuredOffers = $pricingData['featured_offers'] ?? ($pricingData['packages'] ?? []);
    $tiers = $pricingData['tiers'] ?? [];
    $products = $pricingData['products'] ?? [];
    $creditUnitPrice = $pricingData['credit_unit_price'] ?? 0.20;
    $complianceProduct = collect($products)->firstWhere('slug', 'compliance') ?? ($products[0] ?? null);
    $clearanceProduct = collect($products)->firstWhere('slug', 'clearance');
    $complianceSources = $pricingData['compliance_sources'] ?? [];
@endphp

@push('structured-data')
@include('landing_page.partials.breadcrumb-schema', [
    'trail' => [
        ['name' => 'Início', 'url' => url('/')],
        ['name' => 'Preços', 'url' => url('/precos')],
    ],
])
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => 'FiscalDock — Créditos para consultas fiscais',
    'description' => 'Compra avulsa de créditos para consultas de compliance e clearance, com economia progressiva por volume acumulado.',
    'brand' => ['@type' => 'Brand', 'name' => 'FiscalDock'],
    'offers' => array_map(fn ($package) => [
        '@type' => 'Offer',
        'name' => $package['nome'],
        'price' => number_format($package['preco'], 2, '.', ''),
        'priceCurrency' => 'BRL',
        'availability' => 'https://schema.org/InStock',
        'url' => route('signup'),
        'description' => $package['descricao'],
    ], $featuredOffers),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

<section id="precos-hero" class="bg-white pt-14 pb-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <div class="text-center">
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide text-white" style="background-color: #1e4fa0">
                        Sem assinatura
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide border border-gray-300 text-gray-700 bg-white">
                        Créditos não expiram
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide border border-gray-300 text-gray-700 bg-white">
                        Receita Federal grátis
                    </span>
                </div>
                <h1 class="mt-6 text-4xl md:text-5xl font-bold text-gray-900">
                    Comece com <span class="text-blue-600">R$ 50 em créditos</span> e pague menos por consulta conforme o volume cresce
                </h1>
                <p class="mt-5 text-lg text-gray-600 max-w-3xl mx-auto">
                    A FiscalDock funciona no modelo pré-pago: você testa sem cartão, compra créditos quando precisar e destrava faixas melhores automaticamente pelo histórico pago acumulado.
                </p>
            </div>

            <div class="mt-10 space-y-6">
                <div class="bg-white rounded-2xl border border-gray-200 p-6 sm:p-8">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Como funciona</p>
                    <div class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-6 min-w-6 items-center justify-center rounded text-[10px] font-bold text-white" style="background-color: #1f2937">1</span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Crie a conta e receba o trial</p>
                                <p class="text-sm text-gray-600 mt-1">Você conhece a operação antes de fazer a primeira recarga.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-6 min-w-6 items-center justify-center rounded text-[10px] font-bold text-white" style="background-color: #1f2937">2</span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Compre créditos quando precisar</p>
                                <p class="text-sm text-gray-600 mt-1">Sem mensalidade, sem assinatura e sem custo escondido.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-6 min-w-6 items-center justify-center rounded text-[10px] font-bold text-white" style="background-color: #1f2937">3</span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Suba de faixa com o histórico pago</p>
                                <p class="text-sm text-gray-600 mt-1">O preço por consulta cai automaticamente nas próximas execuções.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-2xl border border-gray-200 p-6 sm:p-8">
                    <div class="bg-white rounded-2xl border border-gray-200 p-6 sm:p-7">
                        <div class="flex flex-col gap-6">
                            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.8fr)] gap-5 items-end">
                                <div class="max-w-3xl">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Comece sem assinatura</p>
                                    <h2 class="mt-2 text-3xl sm:text-4xl font-bold text-gray-900">R$ {{ number_format($minimumDeposit, 0, ',', '.') }} para ativar a primeira recarga</h2>
                                    <p class="mt-3 text-sm sm:text-base text-gray-600">Esse é o valor mínimo para começar a operar com saldo real, sem mensalidade e sem travar a entrada no produto.</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3 xl:max-w-sm xl:ml-auto">
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Entrada</p>
                                        <p class="mt-2 text-lg font-bold text-gray-900">R$ {{ number_format($minimumDeposit, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Conversão inicial</p>
                                        <p class="mt-2 text-lg font-bold text-gray-900">{{ number_format((int) round($minimumDeposit / $creditUnitPrice), 0, ',', '.') }} créditos</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                                <div class="rounded-xl border border-gray-200 p-5 sm:p-6">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Compliance</p>
                                    <div class="mt-3 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                                        <p class="text-2xl font-bold text-gray-900">{{ $complianceProduct['entry_price_label'] ?? 'A partir de R$ 2,00/consulta' }}</p>
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Uso recorrente</p>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">Regularidade fiscal por CNPJ para rotina operacional, com economia progressiva conforme sua faixa melhora.</p>
                                    @if(!empty($complianceSources))
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-2">Fontes incluídas</p>
                                            @include('partials.compliance-sources', ['sources' => $complianceSources, 'variant' => 'publico'])
                                            <p class="mt-3 text-[11px] text-gray-500">Você contrata um pacote único — novas fontes entram automaticamente conforme liberadas. Créditos por consulta seguem a tabela de faixas abaixo.</p>
                                        </div>
                                    @endif
                                </div>
                                <div class="rounded-xl border border-gray-200 p-5 sm:p-6">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Clearance</p>
                                    <div class="mt-3 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                                        <p class="text-2xl font-bold text-gray-900">{{ $clearanceProduct['entry_price_label'] ?? 'A partir de R$ 2,80/consulta' }}</p>
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Validação premium</p>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">Validação premium de notas para cenários com mais risco, mais profundidade e custo unitário maior.</p>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-5">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <p class="text-sm text-gray-600 max-w-2xl">Teste a operação antes e compre créditos só quando decidir avançar.</p>
                                    <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3">
                                        <a href="{{ route('signup') }}" data-link class="btn-cta whitespace-nowrap">Criar conta grátis</a>
                                        <a href="{{ route('agendar') }}" data-link class="inline-flex items-center justify-center rounded border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                                            Falar com especialista
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="precos-modelo" class="bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Valor do crédito</p>
                <p class="mt-3 text-3xl font-bold text-gray-900">R$ {{ number_format($creditUnitPrice, 2, ',', '.') }}</p>
                <p class="mt-2 text-sm text-gray-600">O crédito tem preço unitário fixo. A economia real vem da sua faixa comercial.</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Faixas comerciais</p>
                <p class="mt-3 text-3xl font-bold text-gray-900">Base → X → Y → Z</p>
                <p class="mt-2 text-sm text-gray-600">Sua faixa sobe pelo histórico acumulado de créditos pagos e melhora o custo futuro por consulta.</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Uso previsível</p>
                <p class="mt-3 text-3xl font-bold text-gray-900">Sem surpresa</p>
                <p class="mt-2 text-sm text-gray-600">Créditos comprados não expiram. Só o bônus promocional do trial expira em 30 dias.</p>
            </div>
        </div>
    </div>
</section>

<section id="precos-pacotes" class="bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Valor livre com atalhos promocionais</h2>
            <p class="mt-3 text-base text-gray-600">Você pode escolher quanto quer pagar acima do mínimo de R$ {{ number_format($minimumDeposit, 0, ',', '.') }}. Business e Enterprise seguem como atalhos promocionais para acelerar a decisão.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-stretch">
            <div class="bg-white rounded-2xl border border-gray-200 p-6 sm:p-8 flex flex-col h-full">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Valor livre</p>
                <h3 class="mt-2 text-2xl font-bold text-gray-900">Você escolhe quanto quer pagar</h3>
                <p class="mt-4 text-sm text-gray-600">Comece com o mínimo de R$ {{ number_format($minimumDeposit, 0, ',', '.') }} ou faça uma recarga maior conforme seu momento operacional. O saldo comprado não expira e continua contando para subir de faixa.</p>
                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Mínimo</p>
                        <p class="mt-2 text-lg font-bold text-gray-900">R$ {{ number_format($minimumDeposit, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Conversão</p>
                        <p class="mt-2 text-lg font-bold text-gray-900">{{ number_format((int) round($minimumDeposit / $creditUnitPrice), 0, ',', '.') }} créditos</p>
                    </div>
                </div>
                <a href="{{ route('signup') }}" data-link class="mt-6 btn-cta">Criar conta grátis</a>
            </div>

            @foreach($featuredOffers as $package)
                <div class="rounded-2xl border p-6 flex flex-col h-full {{ !empty($package['featured']) ? 'bg-gray-900 border-gray-900 text-white shadow-lg' : 'bg-white border-gray-200' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide {{ !empty($package['featured']) ? 'text-gray-300' : 'text-gray-400' }}">{{ $package['usage_hint'] ?? 'Oferta' }}</p>
                            <h3 class="mt-2 text-xl font-bold {{ !empty($package['featured']) ? 'text-white' : 'text-gray-900' }}">{{ $package['nome'] }}</h3>
                            <p class="mt-2 text-sm {{ !empty($package['featured']) ? 'text-gray-300' : 'text-gray-500' }}">{{ number_format($package['creditos'], 0, ',', '.') }} créditos</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ !empty($package['featured']) ? '#0f766e' : '#374151' }}">{{ $package['badge'] ?? 'Pacote' }}</span>
                    </div>
                    <p class="mt-5 text-4xl font-bold {{ !empty($package['featured']) ? 'text-white' : 'text-gray-900' }}">R$ {{ number_format($package['preco'], 0, ',', '.') }}</p>
                    <p class="mt-2 text-sm {{ !empty($package['featured']) ? 'text-gray-300' : 'text-gray-500' }}">
                        R$ {{ number_format($package['price_per_credit'] ?? $creditUnitPrice, 2, ',', '.') }} por crédito
                    </p>
                    <p class="mt-4 text-sm flex-1 {{ !empty($package['featured']) ? 'text-gray-200' : 'text-gray-600' }}">{{ $package['descricao'] }}</p>
                    <a href="{{ route('signup') }}" data-link class="mt-6 {{ !empty($package['featured']) ? 'inline-flex w-full items-center justify-center rounded border border-white/20 bg-white px-4 py-3 text-sm font-semibold text-gray-900 hover:bg-gray-100' : 'btn-cta btn-cta--block text-center' }}">
                        Criar conta grátis
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="precos-faixas" class="bg-white py-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Faixas de economia</h2>
            <p class="mt-3 text-base text-gray-600">A planilha abaixo mostra em um único quadro quando sua faixa muda e como isso reduz o custo real de Compliance e Clearance.</p>
        </div>
        @php
            $productRowsByTier = [];
            foreach ($products as $product) {
                foreach ($product['rows'] as $row) {
                    $productRowsByTier[$row['tier_slug']][$product['slug']] = $row;
                }
            }
        @endphp
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Comparativo por faixa</h3>
                        <p class="mt-2 text-sm text-gray-600">Quanto maior o histórico pago acumulado, menor o consumo de créditos e menor o custo por consulta.</p>
                    </div>
                    <p class="text-xs font-medium text-gray-500">Role horizontalmente no mobile para ver todas as colunas.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[860px] w-full border-separate border-spacing-0">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400 border-b border-gray-200">Faixa</th>
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400 border-b border-gray-200">Histórico pago acumulado</th>
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400 border-b border-gray-200">Compliance</th>
                            <th class="px-4 py-4 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-400 border-b border-gray-200">Clearance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tiers as $tier)
                            @php
                                $tierRowClass = $tier['slug'] === 'z' ? 'bg-emerald-50/70' : (($loop->even ? 'bg-gray-50/50' : 'bg-white'));
                                $complianceRow = $productRowsByTier[$tier['slug']]['compliance'] ?? null;
                                $clearanceRow = $productRowsByTier[$tier['slug']]['clearance'] ?? null;
                            @endphp
                            <tr class="{{ $tierRowClass }}">
                                <td class="px-4 py-4 align-top border-b border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">{{ $tier['nome'] }}</p>
                                            @if($tier['slug'] === 'z')
                                                <p class="mt-1 text-xs font-semibold text-emerald-700">Melhor economia por consulta</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900">
                                        @if($tier['max_paid_credits'] === null)
                                            {{ number_format($tier['min_paid_credits'], 0, ',', '.') }}+ créditos pagos
                                        @elseif($tier['min_paid_credits'] === 0)
                                            Até {{ number_format($tier['max_paid_credits'], 0, ',', '.') }} créditos pagos
                                        @else
                                            {{ number_format($tier['min_paid_credits'], 0, ',', '.') }} a {{ number_format($tier['max_paid_credits'], 0, ',', '.') }} créditos pagos
                                        @endif
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">Faixa aplicada às próximas consultas pagas.</p>
                                </td>
                                <td class="px-4 py-4 align-top border-b border-gray-100">
                                    @if($complianceRow)
                                        <p class="text-sm font-semibold text-gray-900">{{ number_format($complianceRow['credits'], 0, ',', '.') }} créditos/consulta</p>
                                        <p class="mt-1 text-lg font-bold text-gray-900">R$ {{ number_format($complianceRow['price'], 2, ',', '.') }}</p>
                                        <p class="mt-1 text-xs text-gray-500">por consulta</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 align-top border-b border-gray-100">
                                    @if($clearanceRow)
                                        <p class="text-sm font-semibold text-gray-900">{{ number_format($clearanceRow['credits'], 0, ',', '.') }} créditos/consulta</p>
                                        <p class="mt-1 text-lg font-bold text-gray-900">R$ {{ number_format($clearanceRow['price'], 2, ',', '.') }}</p>
                                        <p class="mt-1 text-xs text-gray-500">por consulta</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section id="precos-consumo" class="bg-gray-50 py-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Custo por consulta e por 100 consultas</h2>
            <p class="mt-3 text-base text-gray-600">A tabela abaixo já considera o valor fixo de R$ {{ number_format($creditUnitPrice, 2, ',', '.') }} por crédito e mostra quanto custa operar em cada faixa.</p>
        </div>
        <div class="space-y-6">
            @foreach($products as $product)
                <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">{{ $product['nome'] }}</h3>
                                <p class="mt-2 text-sm text-gray-600">{{ $product['descricao'] }}</p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 lg:min-w-[420px]">
                                <div class="rounded-xl border border-gray-200 p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Preço inicial</p>
                                    <p class="mt-2 text-lg font-bold text-gray-900">{{ $product['entry_price_label'] }}</p>
                                </div>
                                <div class="rounded-xl border border-gray-200 p-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Melhor faixa</p>
                                    <p class="mt-2 text-lg font-bold text-gray-900">{{ $product['best_price_label'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400 bg-gray-50">Faixa</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400 bg-gray-50">Créditos por consulta</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400 bg-gray-50">Preço por consulta</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400 bg-gray-50">Preço por 100 consultas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($product['rows'] as $row)
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-semibold text-gray-900">{{ $row['tier_name'] }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-600">{{ number_format($row['credits'], 0, ',', '.') }} créditos</td>
                                        <td class="px-4 py-4 text-sm text-gray-600">R$ {{ number_format($row['price'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-4 text-sm font-semibold text-gray-900">R$ {{ number_format($row['price_for_100'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="precos-proximos-passos" class="bg-white py-14">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gray-50 rounded-2xl border border-gray-200 p-8 text-center">
            <h2 class="text-3xl font-bold text-gray-900">Resumo rápido para decidir</h2>
            <p class="mt-4 text-base text-gray-600">
                A lógica comercial da FiscalDock é simples: você testa antes, compra só quando precisar e melhora o custo conforme o uso pago acumulado.
            </p>
            <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4 text-left">
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-sm font-semibold text-gray-900">Sem mensalidade</p>
                    <p class="mt-1 text-sm text-gray-600">O modelo é pré-pago, sem assinatura recorrente.</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-sm font-semibold text-gray-900">Créditos não expiram</p>
                    <p class="mt-1 text-sm text-gray-600">O saldo comprado fica disponível para usar no seu ritmo.</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-sm font-semibold text-gray-900">Receita Federal sem consumo</p>
                    <p class="mt-1 text-sm text-gray-600">A consulta gratuita continua fora do custo dos produtos premium.</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-sm font-semibold text-gray-900">Economia automática</p>
                    <p class="mt-1 text-sm text-gray-600">As faixas melhores entram automaticamente nas consultas futuras.</p>
                </div>
            </div>
            <div class="mt-8 space-y-4">
                <div class="flex justify-center">
                    <a href="{{ route('signup') }}" data-link class="btn-cta">Criar conta grátis</a>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('agendar') }}" data-link class="inline-flex items-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:border-gray-400 hover:text-gray-900">
                        Falar com especialista
                    </a>
                    <a href="{{ route('duvidas') }}" data-link class="inline-flex items-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:border-gray-400 hover:text-gray-900">
                        Ainda com dúvidas?
                    </a>
                    <a href="{{ route('solucoes') }}" data-link class="inline-flex items-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:border-gray-400 hover:text-gray-900">
                        Entenda cada módulo
                    </a>
                    <a href="{{ route('blog.tema', 'efd') }}" data-link class="inline-flex items-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:border-gray-400 hover:text-gray-900">
                        Leia o guia de EFD
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
