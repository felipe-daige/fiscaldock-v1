{{-- Score Fiscal - Dashboard (DANFE Modernizado) --}}
@php
    $scoreColor = function($s) {
        if ($s >= 80) return '#b91c1c';
        if ($s >= 50) return '#ea580c';
        if ($s >= 20) return '#d97706';
        return '#047857';
    };
    $classBadge = [
        'baixo' => ['label' => 'BAIXO', 'hex' => '#047857'],
        'medio' => ['label' => 'MÉDIO', 'hex' => '#d97706'],
        'alto' => ['label' => 'ALTO', 'hex' => '#ea580c'],
        'critico' => ['label' => 'CRÍTICO', 'hex' => '#b91c1c'],
    ];
    $fmtCnpj = function($doc) {
        $d = preg_replace('/\D/', '', (string) $doc);
        return strlen($d) === 14 ? preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $d) : $d;
    };
    $tipoBadge = function($tipo) {
        return $tipo === 'cliente'
            ? ['label' => 'Cliente', 'hex' => '#4338ca']
            : ['label' => 'Participante', 'hex' => '#374151'];
    };
    // Papel comercial do participante: entrada = Fornecedor (vende pra nós),
    // saida = Comprador (compra de nós), os dois = Ambos.
    $papeisParticipante = $papeisParticipante ?? [];
    $papelBadge = function($entry) {
        if (empty($entry)) return null;
        $e = !empty($entry['entrada']); $s = !empty($entry['saida']);
        if ($e && $s) return ['label' => 'Ambos', 'hex' => '#475569'];
        if ($e) return ['label' => 'Fornecedor', 'hex' => '#0369a1'];
        if ($s) return ['label' => 'Comprador', 'hex' => '#7c3aed'];
        return null;
    };
    // Crédito IBS/CBS (Reforma): score_credito_reforma 0=gera cheio, 100=não gera, 1-99=parcial, null=sem regime.
    $creditoBadge = function($sc) {
        if ($sc === null) return ['label' => '—', 'hex' => '#9ca3af'];
        if ($sc <= 0) return ['label' => 'Gera', 'hex' => '#047857'];
        if ($sc >= 100) return ['label' => 'Não gera', 'hex' => '#b91c1c'];
        return ['label' => 'Parcial', 'hex' => '#d97706'];
    };
@endphp
<div class="min-h-screen bg-gray-100" id="risk-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        {{-- Header --}}
        <div class="mb-4">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Score Fiscal</h1>
            <p class="text-xs text-gray-500 mt-1">Avalie o risco fiscal e de compliance dos CNPJs consultados.</p>
        </div>

        {{-- Como funciona --}}
        <details class="bg-white rounded border border-gray-300 border-l-4 mb-6 group" style="border-left-color: #2563eb;">
            <summary class="cursor-pointer px-4 py-3 flex items-center justify-between list-none hover:bg-gray-50">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm font-semibold text-gray-900">Como funciona o Score Fiscal</span>
                </div>
                <span class="text-[11px] font-semibold text-gray-500 group-open:hidden">Abrir</span>
                <span class="text-[11px] font-semibold text-gray-500 hidden group-open:inline">Fechar</span>
            </summary>
            <div class="border-t border-gray-200 px-4 py-4 space-y-4">
                <p class="text-xs text-gray-600 leading-relaxed">
                    O Score é uma nota de <strong>0 (ótimo)</strong> a <strong>100 (pior)</strong>, calculada
                    automaticamente ao final de cada <a href="/app/consulta" data-link class="text-blue-600 hover:underline">Consulta de CNPJ</a>.
                    Quanto menor, mais regular o CNPJ. Vale para participantes (contrapartes) e clientes (suas empresas).
                </p>

                <div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Faixas de classificação</p>
                    <div class="flex flex-wrap gap-3 text-xs">
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background-color: #047857"></span><span class="text-gray-600">0–20 Baixo</span></span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background-color: #d97706"></span><span class="text-gray-600">21–50 Médio</span></span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background-color: #ea580c"></span><span class="text-gray-600">51–80 Alto</span></span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background-color: #b91c1c"></span><span class="text-gray-600">81–100 Crítico</span></span>
                    </div>
                </div>

                <div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">O que entra na nota</p>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        Situação cadastral, CND Federal, CND Estadual, FGTS, CNDT (trabalhista) e sanções (CGU/CNJ).
                        A nota é a <strong>média ponderada só das categorias efetivamente consultadas</strong> — o que não
                        foi consultado, ou veio indeterminado, não entra no cálculo.
                    </p>
                </div>

                <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2">
                    <p class="text-[11px] text-amber-800 leading-relaxed">
                        <strong>Importante:</strong> a profundidade do score depende do plano da consulta. Uma consulta
                        apenas cadastral (Gratuito) avalia só a situação cadastral — pode dar <strong>0/Baixo</strong> por
                        confirmar que a empresa está ativa, sem ter checado certidões. Para um score completo, use
                        Licitação, Compliance ou Due Diligence.
                    </p>
                </div>

                <p class="text-[11px] text-gray-400">ESG (trabalho escravo / IBAMA) e protestos em cartório entrarão no score em breve.</p>
            </div>
        </details>

        {{-- Como funciona: Crédito IBS/CBS na Reforma Tributária --}}
        <details class="bg-white rounded border border-gray-300 border-l-4 mb-6 group" style="border-left-color: #047857;">
            <summary class="cursor-pointer px-4 py-3 flex items-center justify-between list-none hover:bg-gray-50">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                    </svg>
                    <span class="text-sm font-semibold text-gray-900">Crédito IBS/CBS na Reforma Tributária — como funciona e base legal</span>
                </div>
                <span class="text-[11px] font-semibold text-gray-500 group-open:hidden">Abrir</span>
                <span class="text-[11px] font-semibold text-gray-500 hidden group-open:inline">Fechar</span>
            </summary>
            <div class="border-t border-gray-200 px-4 py-4 space-y-4">
                <p class="text-xs text-gray-600 leading-relaxed">
                    Além do risco de regularidade, estimamos se cada <strong>fornecedor</strong> vai
                    <strong>gerar crédito de IBS/CBS</strong> para você sob a Reforma Tributária — e quanto crédito você pode
                    <strong>perder</strong> comprando dele. É uma <strong>previsão</strong>, não garantia.
                </p>

                <div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">A regra (por regime do fornecedor)</p>
                    <ul class="text-xs text-gray-600 leading-relaxed list-disc pl-4 space-y-1">
                        <li><strong>Regime Normal</strong> (Lucro Real/Presumido): gera crédito <strong>integral</strong>.</li>
                        <li><strong>Simples Nacional</strong>: crédito <strong>reduzido</strong>, salvo se optar pelo regime regular/híbrido (aí gera integral).</li>
                        <li><strong>MEI</strong>: <strong>não gera</strong> crédito ao adquirente.</li>
                    </ul>
                </div>

                <div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Semáforo do crédito</p>
                    <div class="flex flex-wrap gap-3 text-xs">
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background-color: #047857"></span><span class="text-gray-600">Gera crédito integral</span></span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background-color: #d97706"></span><span class="text-gray-600">Parcial (Simples)</span></span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background-color: #b91c1c"></span><span class="text-gray-600">Não gera (MEI)</span></span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background-color: #9ca3af"></span><span class="text-gray-600">Regime não identificado</span></span>
                    </div>
                </div>

                <div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Como o valor em risco é calculado</p>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        <span class="font-mono">crédito em risco = volume de entradas (EFD) × alíquota de referência × (1 − fator do regime)</span>.
                        O valor por fornecedor aparece na tela de <strong>detalhe</strong> de cada CNPJ.
                    </p>
                </div>

                <div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Base legal</p>
                    <p class="text-xs text-gray-600 leading-relaxed">
                        EC 132/2023; <strong>LC 214/2025</strong> — crédito do adquirente condicionado à <strong>extinção do
                        débito</strong> (arts. 27 e 47), com dispensa fora do split payment/recolhimento pelo adquirente (art. 48);
                        Simples em regime unificado (crédito reduzido) × híbrido (integral); <strong>LC 225/2026</strong> (devedor
                        contumaz → recolhimento incerto). O <em>split payment</em> (2027+) é que confirmará o recolhimento.
                    </p>
                </div>

                <div>
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Alíquota total IBS+CBS por ano (estimativa)</p>
                    @php $fasesReforma = config('reforma.aliquotas_por_fase', []); $plenoReforma = (float) config('reforma.aliquota_referencia'); @endphp
                    <div class="flex flex-wrap gap-2 text-[11px]">
                        @foreach($fasesReforma as $anoFase => $aliqFase)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 text-gray-600"><strong>{{ $anoFase }}</strong> {{ number_format($aliqFase * 100, 1, ',', '.') }}%</span>
                        @endforeach
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-800"><strong>2033+</strong> {{ number_format($plenoReforma * 100, 1, ',', '.') }}% (pleno)</span>
                    </div>
                    <p class="mt-1 text-[11px] text-gray-400">2026 é fase de teste; CBS plena em 2027; IBS rampa 2029–2032; estado pleno em 2033. Alíquotas fixadas anualmente por Resolução do Senado (cálculo do TCU). O valor em risco no detalhe usa o <strong>estado pleno</strong> (impacto total).</p>
                </div>

                <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2">
                    <p class="text-[11px] text-amber-800 leading-relaxed">
                        <strong>Premissas (não são lei):</strong> alíquota de referência do IVA pleno ≈ <strong>28,5%</strong>
                        (faixa oficial 26,5%–28%) e fator do Simples sem opção ≈ <strong>30%</strong> são parâmetros, revisáveis
                        com a regulamentação. É previsão de risco — <strong>não confirma recolhimento</strong> nem substitui a apuração fiscal.
                    </p>
                </div>
            </div>
        </details>

        {{-- Filtros --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>
            <div class="p-4 flex flex-col sm:flex-row sm:flex-wrap sm:items-end gap-3">
                <div class="w-full sm:w-auto">
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Cliente</label>
                    <select id="filtro-cliente" class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        @foreach(($clientes ?? collect()) as $cli)
                            <option value="{{ $cli->id }}" {{ (! ($verTodosCnpjs ?? false) && (int)($clienteSelecionadoId ?? 0) === (int)$cli->id) ? 'selected' : '' }}>
                                {{ $cli->is_empresa_propria ? '★ '.$cli->nome.' (Minha Empresa)' : $cli->nome }}
                            </option>
                        @endforeach
                        <option value="todos" {{ ($verTodosCnpjs ?? false) ? 'selected' : '' }}>Todos os CNPJs</option>
                    </select>
                </div>
                <div class="w-full sm:w-auto">
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Classificação</label>
                    <select id="filtro-classificacao" class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="todos" {{ ($filtroClassificacao ?? 'todos') === 'todos' ? 'selected' : '' }}>Todas as Classificações</option>
                        <option value="baixo" {{ ($filtroClassificacao ?? '') === 'baixo' ? 'selected' : '' }}>Baixo Risco</option>
                        <option value="medio" {{ ($filtroClassificacao ?? '') === 'medio' ? 'selected' : '' }}>Médio Risco</option>
                        <option value="alto" {{ ($filtroClassificacao ?? '') === 'alto' ? 'selected' : '' }}>Alto Risco</option>
                        <option value="critico" {{ ($filtroClassificacao ?? '') === 'critico' ? 'selected' : '' }}>Crítico</option>
                    </select>
                </div>
                <div class="w-full sm:flex-1 sm:min-w-[240px]">
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Buscar</label>
                    <div class="relative">
                        <input type="text" id="busca-participante" placeholder="CNPJ ou razão social..." value="{{ $filtroBusca ?? '' }}" class="w-full px-3 py-2 pl-9 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="w-full sm:w-auto">
                    <button type="button" id="btn-filtrar-score" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded bg-gray-800 hover:bg-gray-700 text-white text-sm font-medium transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L14 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-7.586L3.293 6.707A1 1 0 013 6V4z"></path>
                        </svg>
                        Filtrar
                    </button>
                </div>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-5 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Avaliados</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $estatisticas['total_avaliados'] ?? 0 }}</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Baixo Risco</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $estatisticas['baixo_risco'] ?? 0 }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">BAIXO</span>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Médio Risco</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $estatisticas['medio_risco'] ?? 0 }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">MÉDIO</span>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Alto Risco</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $estatisticas['alto_risco'] ?? 0 }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #ea580c">ALTO</span>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Crítico</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $estatisticas['critico'] ?? 0 }}</p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #b91c1c">CRÍTICO</span>
                </div>
            </div>
        </div>

        @if(($emRiscoCritico ?? collect())->count() > 0)
        {{-- Alerta de Risco Crítico --}}
        <div class="bg-white rounded border border-gray-300 border-l-4 border-l-red-500 p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-gray-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">CNPJs em Risco Crítico</h4>
                    <ul class="mt-2 space-y-1">
                        @foreach($emRiscoCritico as $scoreItem)
                            <li class="text-sm text-gray-700">
                                @if($scoreItem->participante_id)
                                    <a href="/app/score-fiscal/participante/{{ $scoreItem->participante_id }}" data-link class="text-gray-900 hover:text-gray-600 hover:underline">
                                        {{ $scoreItem->alvo_nome }} <span class="font-mono text-[11px] text-gray-500">({{ $scoreItem->alvo_documento }})</span> — Score: <span class="font-bold" style="color: #b91c1c">{{ $scoreItem->score_total }}</span>
                                    </a>
                                @else
                                    {{ $scoreItem->alvo_nome }} <span class="font-mono text-[11px] text-gray-500">({{ $scoreItem->alvo_documento }})</span> — Score: <span class="font-bold" style="color: #b91c1c">{{ $scoreItem->score_total }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        {{-- CONSULTADOS — participantes que já têm score (ordenados por risco) --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Consultados</span>
                <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $consultados->total() ?? 0 }}</span>
            </div>

            @if(($consultados ?? collect())->count() > 0)
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNPJ / Razão Social</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">UF</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Score</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Classificação</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Crédito IBS/CBS</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Última Consulta</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($consultados as $sc)
                        @php $tb = $tipoBadge($sc->alvo_tipo); @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-3 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900 font-medium">{{ $sc->alvo_nome }}</div>
                                <div class="text-[11px] text-gray-500 font-mono">{{ $sc->alvo_documento }}</div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tb['hex'] }}">{{ $tb['label'] }}</span>
                                    @if($sc->participante_id && ($pb = $papelBadge($papeisParticipante[$sc->participante_id] ?? null)))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $pb['hex'] }}">{{ $pb['label'] }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700">{{ $sc->alvo_uf ?? '—' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                @if($sc->score_total !== null)
                                    <span class="text-lg font-bold font-mono" style="color: {{ $scoreColor($sc->score_total) }}">{{ $sc->score_total }}</span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                @if(isset($classBadge[$sc->classificacao]))
                                    @php $b = $classBadge[$sc->classificacao]; @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $b['hex'] }}">
                                        {{ $b['label'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">
                                        Não Avaliado
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                @php $cb = $creditoBadge($sc->score_credito_reforma); @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $cb['hex'] }}">{{ $cb['label'] }}</span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center text-sm text-gray-700 font-mono">
                                @if($sc->ultima_consulta_em)
                                    {{ $sc->ultima_consulta_em->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-right text-xs">
                                <x-acoes-menu>
                                    @if($sc->participante_id)
                                        <x-acoes-item href="/app/score-fiscal/participante/{{ $sc->participante_id }}" data-link>Detalhes</x-acoes-item>
                                    @endif
                                    <x-acoes-item href="/app/consulta" data-link>Reconsultar</x-acoes-item>
                                </x-acoes-menu>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: cards --}}
            <div class="divide-y divide-gray-100 md:hidden">
                @foreach($consultados as $sc)
                @php $tb = $tipoBadge($sc->alvo_tipo); @endphp
                <div class="px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm text-gray-900 font-medium truncate">{{ $sc->alvo_nome }}</p>
                            <p class="text-[11px] text-gray-500 font-mono">{{ $sc->alvo_documento }}</p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            @if($sc->score_total !== null)
                                <span class="text-xl font-bold font-mono" style="color: {{ $scoreColor($sc->score_total) }}">{{ $sc->score_total }}</span>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tb['hex'] }}">{{ $tb['label'] }}</span>
                        @if($sc->participante_id && ($pb = $papelBadge($papeisParticipante[$sc->participante_id] ?? null)))
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $pb['hex'] }}">{{ $pb['label'] }}</span>
                        @endif
                        @if(isset($classBadge[$sc->classificacao]))
                            @php $b = $classBadge[$sc->classificacao]; @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $b['hex'] }}">{{ $b['label'] }}</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">Não Avaliado</span>
                        @endif
                        @php $cb = $creditoBadge($sc->score_credito_reforma); @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $cb['hex'] }}" title="Crédito IBS/CBS na Reforma">Créd: {{ $cb['label'] }}</span>
                        @if($sc->alvo_uf)
                            <span class="text-[11px] text-gray-500">{{ $sc->alvo_uf }}</span>
                        @endif
                        @if($sc->ultima_consulta_em)
                            <span class="text-[11px] text-gray-400">· {{ $sc->ultima_consulta_em->format('d/m/Y') }}</span>
                        @endif
                    </div>
                    <div class="mt-2 text-xs">
                        <x-acoes-menu align="left">
                            @if($sc->participante_id)
                                <x-acoes-item href="/app/score-fiscal/participante/{{ $sc->participante_id }}" data-link>Detalhes</x-acoes-item>
                            @endif
                            <x-acoes-item href="/app/consulta" data-link>Reconsultar</x-acoes-item>
                        </x-acoes-menu>
                    </div>
                </div>
                @endforeach
            </div>

            @if($consultados->hasPages())
            <div class="border-t border-gray-300 px-4 py-3">
                {{ $consultados->links() }}
            </div>
            @endif

            @else
            <div class="px-6 py-10 text-center">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Nenhum CNPJ consultado ainda</h3>
                <p class="mt-2 text-xs text-gray-500">O score é calculado automaticamente ao final de cada Consulta de CNPJ.</p>
                <a href="/app/consulta" data-link class="mt-4 inline-flex items-center gap-2 px-3 py-2 rounded bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold transition">Fazer uma consulta</a>
            </div>
            @endif
        </div>

        {{-- NÃO CONSULTADOS — CNPJs (participantes + clientes) ainda sem score --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Não consultados</span>
                <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $naoConsultados->total() ?? 0 }}</span>
            </div>

            @if(($naoConsultados ?? collect())->count() > 0)
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNPJ / Razão Social</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">UF</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($naoConsultados as $item)
                        @php $tb = $tipoBadge($item->tipo); @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-3 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-900 font-medium">{{ $item->razao_social ?? 'N/A' }}</div>
                                <div class="text-[11px] text-gray-500 font-mono">{{ $fmtCnpj($item->documento) }}</div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <div class="flex flex-wrap items-center gap-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tb['hex'] }}">{{ $tb['label'] }}</span>
                                    @if($item->tipo === 'participante' && ($pb = $papelBadge($papeisParticipante[$item->id] ?? null)))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $pb['hex'] }}">{{ $pb['label'] }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700">{{ $item->uf ?? '—' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-right text-xs">
                                <a href="/app/consulta" data-link class="text-gray-600 hover:text-gray-900 hover:underline">Consultar</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: cards --}}
            <div class="divide-y divide-gray-100 md:hidden">
                @foreach($naoConsultados as $item)
                @php $tb = $tipoBadge($item->tipo); @endphp
                <div class="px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm text-gray-900 font-medium truncate">{{ $item->razao_social ?? 'N/A' }}</p>
                            <p class="text-[11px] text-gray-500 font-mono">{{ $fmtCnpj($item->documento) }}</p>
                        </div>
                        <div class="flex-shrink-0 flex flex-col items-end gap-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $tb['hex'] }}">{{ $tb['label'] }}</span>
                            @if($item->tipo === 'participante' && ($pb = $papelBadge($papeisParticipante[$item->id] ?? null)))
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $pb['hex'] }}">{{ $pb['label'] }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-[11px] text-gray-500">{{ $item->uf ?? '—' }}</span>
                        <a href="/app/consulta" data-link class="text-xs text-gray-600 hover:text-gray-900 hover:underline">Consultar</a>
                    </div>
                </div>
                @endforeach
            </div>

            @if($naoConsultados->hasPages())
            <div class="border-t border-gray-300 px-4 py-3">
                {{ $naoConsultados->links() }}
            </div>
            @endif

            @else
            <div class="px-6 py-10 text-center">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Nenhum CNPJ pendente</h3>
                <p class="mt-2 text-xs text-gray-500">Todos os CNPJs cadastrados já foram consultados.</p>
            </div>
            @endif
        </div>

    </div>
</div>

<script src="{{ asset('js/risk-score.js') }}"></script>
