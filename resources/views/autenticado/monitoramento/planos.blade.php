{{-- Monitoramento - Planos de Consulta --}}
@php
    $planoMeta = [
        'gratuito' => [
            'cor' => 'green',
            'icone' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'consultas_display' => ['Situação cadastral', 'Dados cadastrais completos', 'CNAE principal e secundários', 'Quadro societário (QSA)'],
            'casos_uso' => ['Checagem cadastral inicial', 'Validação de CNPJ ativo', 'Conferência de sócios'],
        ],
        'validacao' => [
            'cor' => 'blue',
            'icone' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'consultas_display' => ['Tudo do Gratuito', 'Simples Nacional', 'MEI', 'SINTEGRA básico'],
            'casos_uso' => ['Qualificação fiscal', 'Regime tributário', 'Homologação inicial'],
        ],
        'licitacao' => [
            'cor' => 'blue',
            'icone' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            'consultas_display' => ['Tudo do Validação', 'CND Federal (PGFN/RFB)', 'CNDT', 'FGTS'],
            'casos_uso' => ['Editais e licitação', 'Contratos públicos', 'Credenciamento'],
        ],
        'compliance' => [
            'cor' => 'purple',
            'icone' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
            'consultas_display' => ['Tudo do Licitação', 'CND Estadual', 'CND Municipal'],
            'casos_uso' => ['Regularidade completa', 'Auditoria de fornecedor', 'Contratos recorrentes'],
        ],
        'due_diligence' => [
            'cor' => 'amber',
            'icone' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7',
            'consultas_display' => ['Tudo do Compliance', 'Sanções', 'CNJ', 'Protestos e processos'],
            'casos_uso' => ['Risco ampliado', 'Due diligence comercial', 'Fornecedores críticos'],
        ],
        'enterprise' => [
            'cor' => 'slate',
            'icone' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
            'consultas_display' => [],
            'casos_uso' => [],
            'coming_soon' => true,
        ],
    ];

    $hasMadeFirstPurchase = $hasMadeFirstPurchase ?? false;
    $firstPurchaseLockedProducts = $firstPurchaseLockedProducts ?? ['compliance', 'due_diligence'];
    $planosDetalhados = [];
    foreach ($planos->where('is_active', true) as $p) {
        $meta = $planoMeta[$p->codigo] ?? ['cor' => 'gray', 'icone' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'consultas_display' => [], 'casos_uso' => []];
        if ($p->codigo === 'enterprise' || ($meta['coming_soon'] ?? false)) {
            continue;
        }

        $requiresFirstPurchase = in_array($p->codigo, $firstPurchaseLockedProducts, true);
        $isLocked = $requiresFirstPurchase && ! $hasMadeFirstPurchase;
        $planosDetalhados[] = [
            'codigo' => $p->codigo,
            'nome' => $p->nome,
            'creditos' => $p->custo_creditos,
            'descricao' => $p->descricao,
            'cor' => $meta['cor'],
            'icone' => $meta['icone'],
            'consultas' => $meta['consultas_display'],
            'casos_uso' => $meta['casos_uso'],
            'popular' => $p->codigo === 'licitacao',
            'coming_soon' => false,
            'gratuito' => $p->is_gratuito,
            'promo' => $meta['promo'] ?? false,
            'preco_original' => $meta['preco_original'] ?? null,
            'requires_first_purchase' => $requiresFirstPurchase,
            'locked' => $isLocked,
        ];
    }

    $planosAtivos = collect($planosDetalhados)->where('coming_soon', false)->values();
@endphp

<div class="bg-gray-100 min-h-screen" id="monitoramento-planos-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="flex items-start justify-between gap-4 mb-4 sm:mb-6">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Planos de Consulta</h1>
                <p class="text-xs text-gray-500 mt-1">Comparativo operacional dos níveis de consulta para fornecedores, clientes e parceiros.</p>
            </div>
            <a
                href="/app/consulta/nova"
                class="inline-flex items-center gap-2 px-4 py-2 rounded border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
                data-link
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar para Consulta
            </a>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Leitura Operacional</span>
            </div>
            <div class="bg-white rounded border-l-4 border-l-blue-500 p-4">
                <p class="text-sm text-gray-700">
                    Cada plano define o conjunto de consultas executadas por CNPJ. O custo é calculado por participante consultado e pela frequência de uso. O objetivo desta tela é facilitar a escolha do nível de cobertura mais adequado ao seu fluxo operacional.
                </p>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Comparativo de Planos</span>
                    <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">Custo por CNPJ</span>
                </div>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50 whitespace-nowrap">Plano</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50 whitespace-nowrap">Créditos / CNPJ</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Cobertura</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Quando usar</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50 whitespace-nowrap">Status</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50 whitespace-nowrap">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($planosDetalhados as $plano)
                            @php
                                $statusHex = ($plano['locked'] ?? false)
                                    ? '#9ca3af'
                                    : ($plano['coming_soon']
                                    ? '#9ca3af'
                                    : ($plano['promo'] ? '#d97706' : ($plano['gratuito'] ? '#047857' : '#374151')));
                                $statusLabel = ($plano['locked'] ?? false)
                                    ? 'Após recarga'
                                    : ($plano['coming_soon']
                                    ? 'Em breve'
                                    : ($plano['promo'] ? 'Promoção' : ($plano['gratuito'] ? 'Grátis' : 'Ativo')));
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900">{{ $plano['nome'] }}</span>
                                        @if($plano['popular'] && ! $plano['coming_soon'])
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">Popular</span>
                                        @endif
                                    </div>
                                    @if($plano['descricao'])
                                        <p class="text-[11px] text-gray-500 mt-1">{{ $plano['descricao'] }}</p>
                                    @endif
                                    @if($plano['locked'] ?? false)
                                        <p class="text-[11px] text-gray-500 mt-1">Disponível após a primeira recarga de créditos.</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700">
                                    @if($plano['coming_soon'])
                                        <span class="text-gray-400 whitespace-nowrap">—</span>
                                    @elseif($plano['gratuito'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #047857">0 créditos</span>
                                    @elseif($plano['promo'])
                                        <div class="text-gray-400 line-through text-[11px] whitespace-nowrap">{{ $plano['preco_original'] }} créditos</div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap mt-1" style="background-color: #d97706">{{ $plano['creditos'] }} créditos</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #374151">{{ $plano['creditos'] }} créditos</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-top">
                                    @if(count($plano['consultas']))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($plano['consultas'] as $consulta)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium" style="background-color: #f3f4f6; color: #374151">{{ $consulta }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">Cobertura em definição</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-top">
                                    @if(count($plano['casos_uso']))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($plano['casos_uso'] as $caso)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium" style="background-color: #eef2ff; color: #4338ca">{{ $caso }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">Uso futuro</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $statusHex }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-3 py-3 text-right">
                                    @if($plano['locked'] ?? false)
                                        <a href="/app/creditos" data-link class="text-xs text-gray-900 hover:text-gray-600 hover:underline">Comprar créditos</a>
                                    @elseif($plano['coming_soon'])
                                        <span class="text-xs text-gray-400">Indisponível</span>
                                    @else
                                        <a href="/app/consulta/nova" data-link class="text-xs text-gray-900 hover:text-gray-600 hover:underline">Usar plano</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-gray-100 md:hidden">
                @foreach($planosDetalhados as $plano)
                    @php
                        $statusHex = ($plano['locked'] ?? false)
                            ? '#9ca3af'
                            : ($plano['coming_soon']
                            ? '#9ca3af'
                            : ($plano['promo'] ? '#d97706' : ($plano['gratuito'] ? '#047857' : '#374151')));
                        $statusLabel = ($plano['locked'] ?? false)
                            ? 'Após recarga'
                            : ($plano['coming_soon']
                            ? 'Em breve'
                            : ($plano['promo'] ? 'Promoção' : ($plano['gratuito'] ? 'Grátis' : 'Ativo')));
                    @endphp
                    <div class="px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900">{{ $plano['nome'] }}</p>
                                @if($plano['descricao'])
                                    <p class="text-xs text-gray-500 mt-1">{{ $plano['descricao'] }}</p>
                                @endif
                                @if($plano['locked'] ?? false)
                                    <p class="text-[11px] text-gray-500 mt-1">Disponível após a primeira recarga de créditos.</p>
                                @endif
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $statusHex }}">{{ $statusLabel }}</span>
                        </div>
                        <div class="grid grid-cols-1 gap-2 mt-3 text-sm text-gray-700">
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase">Créditos / CNPJ</p>
                                <p>
                                    @if($plano['coming_soon'])
                                        —
                                    @elseif($plano['gratuito'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #047857">0 créditos</span>
                                    @elseif($plano['promo'])
                                        <span class="text-gray-400 line-through text-[11px] whitespace-nowrap">{{ $plano['preco_original'] }} créditos</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap mt-1" style="background-color: #d97706">{{ $plano['creditos'] }} créditos</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white whitespace-nowrap" style="background-color: #374151">{{ $plano['creditos'] }} créditos</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase mb-1">Cobertura</p>
                                @if(count($plano['consultas']))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($plano['consultas'] as $consulta)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium" style="background-color: #f3f4f6; color: #374151">{{ $consulta }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">Cobertura em definição</span>
                                @endif
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 uppercase mb-1">Quando usar</p>
                                @if(count($plano['casos_uso']))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($plano['casos_uso'] as $caso)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium" style="background-color: #eef2ff; color: #4338ca">{{ $caso }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">Uso futuro</span>
                                @endif
                            </div>
                            <div>
                                @if($plano['locked'] ?? false)
                                    <a href="/app/creditos" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">Comprar créditos</a>
                                @elseif($plano['coming_soon'])
                                    <span class="text-xs text-gray-400">Indisponível</span>
                                @else
                                    <a href="/app/consulta/nova" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">Usar plano</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Simulador de Custo</span>
            </div>

            <div class="px-4 py-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="calc-cnpjs" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Quantidade de CNPJs</label>
                        <input
                            type="number"
                            id="calc-cnpjs"
                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                            placeholder="Ex: 50"
                            min="1"
                            max="10000"
                            value="10"
                        >
                    </div>

                    <div>
                        <label for="calc-frequencia" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Frequência</label>
                        <select
                            id="calc-frequencia"
                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                        >
                            <option value="1">Mensal (1x por mês)</option>
                            <option value="2">Quinzenal (2x por mês)</option>
                            <option value="4">Semanal (4x por mês)</option>
                        </select>
                    </div>

                    <div>
                        <label for="calc-plano" class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Plano</label>
                        <select
                            id="calc-plano"
                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm text-gray-700 focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                        >
                            @foreach($planosAtivos as $plano)
                                <option value="{{ $plano['creditos'] }}" {{ $plano['codigo'] === 'licitacao' ? 'selected' : '' }}>
                                    {{ $plano['nome'] }} ({{ $plano['creditos'] }} cred.)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="border border-gray-300 rounded px-4 py-3">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Custo mensal</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-lg font-bold text-gray-900" id="calc-creditos">100</span>
                            <span class="text-[11px] text-gray-500">créditos</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-200 text-sm text-gray-600">
                    O custo depende do plano selecionado, da quantidade de CNPJs e da frequência de consulta.
                    <a href="/app/creditos" data-link class="text-gray-900 hover:text-gray-600 hover:underline">Adquirir créditos</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    function initMonitoramentoPlanos() {
        const container = document.getElementById('monitoramento-planos-container');
        if (!container) return;

        if (container.dataset.initialized === '1') return;
        container.dataset.initialized = '1';

        const calcCnpjs = document.getElementById('calc-cnpjs');
        const calcFrequencia = document.getElementById('calc-frequencia');
        const calcPlano = document.getElementById('calc-plano');
        const calcCreditos = document.getElementById('calc-creditos');

        function calcular() {
            const cnpjs = parseInt(calcCnpjs.value, 10) || 0;
            const frequencia = parseInt(calcFrequencia.value, 10) || 1;
            const plano = parseInt(calcPlano.value, 10) || 0;
            const total = cnpjs * frequencia * plano;

            calcCreditos.textContent = total.toLocaleString('pt-BR');
        }

        if (calcCnpjs) calcCnpjs.addEventListener('input', calcular);
        if (calcFrequencia) calcFrequencia.addEventListener('change', calcular);
        if (calcPlano) calcPlano.addEventListener('change', calcular);

        calcular();
    }

    window.initMonitoramentoPlanos = initMonitoramentoPlanos;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMonitoramentoPlanos, { once: true });
    } else {
        initMonitoramentoPlanos();
    }
})();
</script>
