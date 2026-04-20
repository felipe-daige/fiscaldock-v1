@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Soluções', 'item' => url('/solucoes')],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'Produtos FiscalDock',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Importação SPED/EFD', 'description' => 'Importe arquivos SPED Fiscal e Contribuições e extraia notas, participantes e apurações automaticamente.', 'url' => url('/solucoes#produto-sped')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Monitoramento de Participantes', 'description' => 'Acompanhe situação cadastral, regime tributário e regularidade dos seus fornecedores.', 'url' => url('/solucoes#produto-monitoramento')],
        ['@type' => 'ListItem', 'position' => 3, 'name' => 'Consultas CNPJ', 'description' => 'Consultas tributárias em lote via integração InfoSimples com débito de créditos.', 'url' => url('/solucoes#produto-consultas')],
        ['@type' => 'ListItem', 'position' => 4, 'name' => 'BI Fiscal', 'description' => 'Dashboards de notas, CFOP, participantes e apuração tributária.', 'url' => url('/solucoes#produto-bi')],
        ['@type' => 'ListItem', 'position' => 5, 'name' => 'Clearance de Notas', 'description' => 'Validação de NF-e/CT-e/NFS-e contra SEFAZ (em construção).', 'url' => url('/solucoes#produto-clearance')],
        ['@type' => 'ListItem', 'position' => 6, 'name' => 'Central de Alertas', 'description' => 'Alertas fiscais consolidados e priorizados por risco.', 'url' => url('/solucoes#produto-alertas')],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

<style>
    .sol-hero-gradient {
        background: linear-gradient(135deg, #0b1f3a 0%, #1e4fa0 50%, #133a73 100%);
    }

    .sol-fade-in-up {
        animation: solFadeInUp 0.6s ease-out forwards;
        opacity: 0;
    }

    @keyframes solFadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .sol-section-fade-in {
        opacity: 0;
        transform: translateY(30px);
        transition: all 0.6s ease;
    }

    .sol-section-fade-in.visible {
        opacity: 1;
        transform: translateY(0);
    }

    .sol-anchor-nav {
        position: sticky;
        top: 0;
        z-index: 30;
        backdrop-filter: saturate(180%) blur(8px);
    }

    .sol-anchor-nav::-webkit-scrollbar { display: none; }
    .sol-anchor-nav { scrollbar-width: none; }

    .sol-pill {
        white-space: nowrap;
        transition: all 0.2s ease;
        background-color: #f3f4f6;
        color: #0b1f3a;
        border: 1px solid #e5e7eb;
    }

    .sol-pill:hover {
        background-color: #0b1f3a;
        color: #ffffff;
        border-color: #0b1f3a;
    }

    .sol-cta-primary {
        background-color: #facc15;
        color: #0b1f3a;
        transition: all 0.2s ease;
        box-shadow: 0 10px 20px -10px rgba(234, 179, 8, 0.6);
    }

    .sol-cta-primary:hover {
        background-color: #eab308;
        transform: translateY(-1px);
    }

    .sol-cta-secondary {
        border: 1px solid rgba(255,255,255,0.4);
        color: #ffffff;
        transition: all 0.2s ease;
    }

    .sol-cta-secondary:hover {
        background-color: rgba(255,255,255,0.08);
        border-color: rgba(255,255,255,0.7);
    }

    .sol-link-cta {
        color: #1e4fa0;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: gap 0.2s ease;
    }

    .sol-link-cta:hover { gap: 10px; }

    .sol-mockup {
        border-radius: 1rem;
        border: 1px solid #e5e7eb;
        background-color: #ffffff;
        box-shadow: 0 25px 50px -20px rgba(11, 31, 58, 0.25), 0 10px 20px -10px rgba(11, 31, 58, 0.15);
        overflow: hidden;
    }

    .sol-mockup-header {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 10px 14px;
        background-color: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
    }

    .sol-mockup-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
    }

    .sol-bar-track {
        width: 100%;
        height: 6px;
        border-radius: 999px;
        background-color: #e5e7eb;
        overflow: hidden;
    }

    .sol-bar-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #1e4fa0 0%, #3b82f6 100%);
    }

    .sol-score {
        width: 44px;
        height: 44px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        color: #ffffff;
    }

    .sol-kpi {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px 14px;
        background-color: #ffffff;
    }

    .sol-bar-chart {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        height: 100px;
        padding-top: 8px;
    }

    .sol-bar-chart > div {
        flex: 1;
        border-radius: 6px 6px 0 0;
        background: linear-gradient(180deg, #3b82f6 0%, #1e4fa0 100%);
        min-height: 12px;
    }

    .sol-timeline-item {
        position: relative;
        padding-left: 40px;
        padding-bottom: 18px;
    }

    .sol-timeline-item:last-child { padding-bottom: 0; }

    .sol-timeline-item::before {
        content: "";
        position: absolute;
        left: 15px;
        top: 28px;
        bottom: 0;
        width: 2px;
        background-color: #e5e7eb;
    }

    .sol-timeline-item:last-child::before { display: none; }

    .sol-timeline-icon {
        position: absolute;
        left: 0;
        top: 0;
        width: 32px;
        height: 32px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    html { scroll-behavior: smooth; }

    @media (max-width: 640px) {
        .sol-hero-gradient h1 { font-size: 2.15rem; }
        .sol-hero-gradient p  { font-size: 1rem; }
    }
</style>

<!-- ============================================================
     HERO
============================================================ -->
<section class="sol-hero-gradient py-16 md:py-24 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center sol-fade-in-up">
            <span class="inline-block text-[11px] font-semibold uppercase tracking-[0.22em] text-blue-200 mb-4">
                Plataforma Fiscal Completa
            </span>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight mb-5">
                Seis produtos. Um só radar fiscal.
            </h1>
            <p class="text-lg md:text-xl text-blue-100 max-w-3xl mx-auto mb-8">
                Do upload do SPED ao alerta de risco, o FiscalDock conecta importação, monitoramento e inteligência em uma operação só — pensada para contadores que não podem errar.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="#produto-sped" class="sol-cta-primary inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg font-semibold text-base">
                    Ver todos os produtos
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </a>
                <a href="{{ route('agendar') }}" class="sol-cta-secondary inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg font-semibold text-base">
                    Agendar demonstração
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     NAV ÂNCORA STICKY
============================================================ -->
<nav class="sol-anchor-nav bg-white/90 border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-center gap-2 md:gap-3 overflow-x-auto py-4 text-sm">
            <a href="#produto-sped"           class="sol-pill px-4 py-2 rounded-full font-semibold">1. Importação SPED</a>
            <a href="#produto-monitoramento"  class="sol-pill px-4 py-2 rounded-full font-semibold">2. Monitoramento</a>
            <a href="#produto-consultas"      class="sol-pill px-4 py-2 rounded-full font-semibold">3. Consultas CNPJ</a>
            <a href="#produto-bi"             class="sol-pill px-4 py-2 rounded-full font-semibold">4. BI Fiscal</a>
            <a href="#produto-clearance"      class="sol-pill px-4 py-2 rounded-full font-semibold">5. Clearance de Notas</a>
            <a href="#produto-alertas"        class="sol-pill px-4 py-2 rounded-full font-semibold">6. Central de Alertas</a>
        </div>
    </div>
</nav>

<!-- ============================================================
     PRODUTO 1 — IMPORTAÇÃO SPED/EFD + XML
============================================================ -->
<section id="produto-sped" class="py-20 lg:py-28 bg-white sol-section-fade-in">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <!-- Texto -->
            <div class="lg:order-1">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5" style="background-color: #eef2f7; border: 1px solid #dce3ed;">
                    <svg class="w-7 h-7" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                </div>
                <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Documentos Fiscais</span>
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-gray-900 mt-2 mb-4">
                    Importe SPED e XMLs sem digitar nada
                </h2>
                <p class="text-base text-gray-600 leading-relaxed mb-6">
                    Upload de EFD ICMS/IPI e EFD PIS/COFINS com extração automática de participantes, notas e blocos (A, C, D, E, F, M). XMLs de NF-e, CT-e e NFS-e também em massa, via zip.
                </p>
                <ul class="space-y-3 mb-7">
                    @foreach ([
                        'Extração automática dos blocos SPED em minutos',
                        'Progresso em tempo real via Server-Sent Events',
                        'Upload em massa de XMLs NF-e, CT-e e NFS-e',
                        'Histórico completo de importações auditável',
                    ] as $bullet)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm text-gray-700">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('agendar') }}" class="sol-link-cta">
                    Agendar demo da Importação SPED
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
                <p class="sol-card-interlinks mt-4 text-xs text-gray-500">
                    <a href="{{ route('precos') }}#precos-consumo" class="hover:underline" style="color: #1e4fa0">Ver créditos para este módulo</a>
                    <span class="mx-2 text-gray-300">·</span>
                    <a href="{{ route('duvidas') }}" class="hover:underline" style="color: #1e4fa0">Tirar dúvida</a>
                    <span class="mx-2 text-gray-300">·</span>
                    <a href="{{ route('blog.tema', 'efd') }}" class="hover:underline" style="color: #1e4fa0">Guia de EFD</a>
                </p>
            </div>

            <!-- Mockup -->
            <div class="lg:order-2">
                <div class="sol-mockup">
                    <div class="sol-mockup-header">
                        <span class="sol-mockup-dot" style="background-color: #ef4444;"></span>
                        <span class="sol-mockup-dot" style="background-color: #f59e0b;"></span>
                        <span class="sol-mockup-dot" style="background-color: #10b981;"></span>
                        <span class="ml-3 text-xs text-gray-500 font-mono">importacao.efd</span>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: #eef2f7;">
                                    <svg class="w-5 h-5" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-7 4h8a2 2 0 002-2V8l-5-5H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">efd_pis_cofins_032026.txt</div>
                                    <div class="text-xs text-gray-500">18,4 MB · 7.312 registros</div>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold" style="background-color: #eefbf5; color: #047857; border: 1px solid #c6eed8;">
                                Extraindo
                            </span>
                        </div>
                        <div class="sol-bar-track mt-4 mb-1">
                            <div class="sol-bar-fill" style="width: 85%;"></div>
                        </div>
                        <div class="text-xs text-gray-500 text-right mb-5">85%</div>

                        <div class="space-y-2">
                            @foreach ([
                                ['participantes',       '312'],
                                ['notas_mercadorias',   '1.847'],
                                ['apuracao_pis_cofins', '13'],
                                ['retencoes_fonte',     '22'],
                            ] as [$label, $count])
                                <div class="flex items-center justify-between px-3 py-2 rounded-lg border border-gray-200">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="#047857" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-xs font-mono text-gray-700">{{ $label }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-gray-900">{{ $count }}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background-color: #eefbf5; color: #047857;">concluído</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     PRODUTO 2 — MONITORAMENTO DE PARTICIPANTES
============================================================ -->
<section id="produto-monitoramento" class="py-20 lg:py-28 bg-gray-50 sol-section-fade-in">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <!-- Mockup (esquerda) -->
            <div class="lg:order-1">
                <div class="sol-mockup">
                    <div class="sol-mockup-header">
                        <span class="sol-mockup-dot" style="background-color: #ef4444;"></span>
                        <span class="sol-mockup-dot" style="background-color: #f59e0b;"></span>
                        <span class="sol-mockup-dot" style="background-color: #10b981;"></span>
                        <span class="ml-3 text-xs text-gray-500 font-mono">monitoramento/participantes</span>
                    </div>
                    <div class="p-5">
                        <div class="space-y-2">
                            @foreach ([
                                ['Distribuidora Norte Ltda',       '12.345.678/0001-90', 'ATIVA',        '#eefbf5', '#047857', '#c6eed8'],
                                ['Metalúrgica Horizonte SA',       '98.765.432/0001-11', 'IE SUSPENSA',  '#fef2f2', '#b91c1c', '#fecaca'],
                                ['Transportes Via Sul ME',         '55.443.322/0001-44', 'PENDENTE',     '#fef8ee', '#b45309', '#f5e6c8'],
                                ['Comercial Atlas Importação',     '77.112.998/0001-22', 'ATIVA',        '#eefbf5', '#047857', '#c6eed8'],
                            ] as [$nome, $cnpj, $status, $bg, $fg, $bd])
                                <div class="flex items-center justify-between px-3 py-3 rounded-lg border border-gray-200 bg-white">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background-color: #eef2f7;">
                                            <svg class="w-4 h-4" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.93 23.93 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs font-semibold text-gray-900 truncate">{{ $nome }}</div>
                                            <div class="text-[10px] font-mono text-gray-500">{{ $cnpj }}</div>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold flex-shrink-0 ml-2" style="background-color: {{ $bg }}; color: {{ $fg }}; border: 1px solid {{ $bd }};">
                                        {{ $status }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 flex items-center justify-between text-xs text-gray-500 px-1">
                            <span>Última sincronização: há 2 min</span>
                            <span class="font-semibold" style="color: #1e4fa0;">Ver todos (312)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Texto -->
            <div class="lg:order-2">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5" style="background-color: #eef2f7; border: 1px solid #dce3ed;">
                    <svg class="w-7 h-7" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
                <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Radar Fiscal</span>
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-gray-900 mt-2 mb-4">
                    Saiba antes do fisco quando um fornecedor vira risco
                </h2>
                <p class="text-base text-gray-600 leading-relaxed mb-6">
                    Cada CNPJ extraído do SPED recebe consulta de situação cadastral via Receita Federal e, nas consultas premium pagas com créditos, enriquecimento via sistemas públicos oficiais (CND Federal). Integração com SINTEGRA e CEIS está no roadmap.
                </p>
                <ul class="space-y-3 mb-7">
                    @foreach ([
                        'Detecção de CNPJ baixado ou suspenso',
                        'Alerta de Inscrição Estadual suspensa ou inidônea',
                        'Alteração de regime tributário (Simples, Lucro Real)',
                        'Enriquecimento com dados cadastrais em tempo real',
                    ] as $bullet)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm text-gray-700">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('agendar') }}" class="sol-link-cta">
                    Agendar demo do Monitoramento
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
                <p class="sol-card-interlinks mt-4 text-xs text-gray-500">
                    <a href="{{ route('precos') }}#precos-consumo" class="hover:underline" style="color: #1e4fa0">Ver créditos para este módulo</a>
                    <span class="mx-2 text-gray-300">·</span>
                    <a href="{{ route('duvidas') }}" class="hover:underline" style="color: #1e4fa0">Tirar dúvida</a>
                    <span class="mx-2 text-gray-300">·</span>
                    <a href="{{ route('blog.tema', 'compliance') }}" class="hover:underline" style="color: #1e4fa0">Guia de compliance</a>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     PRODUTO 3 — CONSULTAS CNPJ + SCORE DE RISCO
============================================================ -->
<section id="produto-consultas" class="py-20 lg:py-28 bg-white sol-section-fade-in">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <!-- Texto -->
            <div class="lg:order-1">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5" style="background-color: #eef2f7; border: 1px solid #dce3ed;">
                    <svg class="w-7 h-7" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Due Diligence</span>
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-gray-900 mt-2 mb-4">
                    Consulte centenas de CNPJs em uma operação
                </h2>
                <p class="text-base text-gray-600 leading-relaxed mb-6">
                    Envie uma planilha ou lista de CNPJs e receba situação cadastral, regime tributário, IE, sócios e um score de risco consolidado — tudo em minutos.
                </p>
                <ul class="space-y-3 mb-7">
                    @foreach ([
                        'Consulta em lote via CSV ou digitação livre',
                        'Score de risco em beta, baseado em situação cadastral e infrações públicas',
                        'Relatório detalhado exportável em Excel e CSV',
                        'Integração oficial com bases públicas e Receita Federal',
                    ] as $bullet)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm text-gray-700">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('agendar') }}" class="sol-link-cta">
                    Agendar demo das Consultas CNPJ
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
                <p class="sol-card-interlinks mt-4 text-xs text-gray-500">
                    <a href="{{ route('precos') }}#precos-consumo" class="hover:underline" style="color: #1e4fa0">Ver créditos para este módulo</a>
                    <span class="mx-2 text-gray-300">·</span>
                    <a href="{{ route('duvidas') }}" class="hover:underline" style="color: #1e4fa0">Tirar dúvida</a>
                    <span class="mx-2 text-gray-300">·</span>
                    <a href="{{ route('blog.tema', 'consultas') }}" class="hover:underline" style="color: #1e4fa0">Guia de consultas CNPJ</a>
                </p>
            </div>

            <!-- Mockup -->
            <div class="lg:order-2">
                <div class="sol-mockup">
                    <div class="sol-mockup-header">
                        <span class="sol-mockup-dot" style="background-color: #ef4444;"></span>
                        <span class="sol-mockup-dot" style="background-color: #f59e0b;"></span>
                        <span class="sol-mockup-dot" style="background-color: #10b981;"></span>
                        <span class="ml-3 text-xs text-gray-500 font-mono">consulta/lote_abril.csv</span>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Processamento em lote</div>
                                <div class="text-xs text-gray-500">47 de 120 CNPJs processados</div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold" style="background-color: #eff6ff; color: #1e4fa0; border: 1px solid #bfdbfe;">
                                em andamento
                            </span>
                        </div>
                        <div class="sol-bar-track mb-5">
                            <div class="sol-bar-fill" style="width: 39%;"></div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between px-3 py-3 rounded-lg border border-gray-200">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="sol-score" style="background: linear-gradient(135deg, #047857 0%, #10b981 100%);">23</div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold text-gray-900 truncate">Indústria Horizonte SA</div>
                                        <div class="text-[10px] text-gray-500">Simples Nacional · Ativa</div>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background-color: #eefbf5; color: #047857;">baixo risco</span>
                            </div>
                            <div class="flex items-center justify-between px-3 py-3 rounded-lg border border-gray-200">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="sol-score" style="background: linear-gradient(135deg, #b45309 0%, #f59e0b 100%);">78</div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold text-gray-900 truncate">Transportes Vitória Ltda</div>
                                        <div class="text-[10px] text-gray-500">Lucro Presumido · IE suspensa</div>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background-color: #fef8ee; color: #b45309;">alto risco</span>
                            </div>
                            <div class="flex items-center justify-between px-3 py-3 rounded-lg border border-gray-200">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="sol-score" style="background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%);">94</div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold text-gray-900 truncate">Metal Forja do Sul ME</div>
                                        <div class="text-[10px] text-gray-500">CNPJ baixado · CEIS</div>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background-color: #fef2f2; color: #b91c1c;">crítico</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     PRODUTO 4 — BI FISCAL & DASHBOARDS
============================================================ -->
<section id="produto-bi" class="py-20 lg:py-28 bg-gray-50 sol-section-fade-in">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <!-- Mockup -->
            <div class="lg:order-1">
                <div class="sol-mockup">
                    <div class="sol-mockup-header">
                        <span class="sol-mockup-dot" style="background-color: #ef4444;"></span>
                        <span class="sol-mockup-dot" style="background-color: #f59e0b;"></span>
                        <span class="sol-mockup-dot" style="background-color: #10b981;"></span>
                        <span class="ml-3 text-xs text-gray-500 font-mono">bi/dashboard-fiscal</span>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            <div class="sol-kpi">
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Faturamento</div>
                                <div class="text-lg font-bold text-gray-900 mt-1">R$ 2,41M</div>
                                <div class="text-[10px] font-semibold" style="color: #047857;">+12,4% vs. mês ant.</div>
                            </div>
                            <div class="sol-kpi">
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Compras</div>
                                <div class="text-lg font-bold text-gray-900 mt-1">R$ 1,18M</div>
                                <div class="text-[10px] font-semibold" style="color: #b45309;">+3,8% vs. mês ant.</div>
                            </div>
                            <div class="sol-kpi">
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">ICMS a pagar</div>
                                <div class="text-lg font-bold text-gray-900 mt-1">R$ 184k</div>
                                <div class="text-[10px] text-gray-500">Apuração mar/26</div>
                            </div>
                            <div class="sol-kpi">
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">PIS/COFINS</div>
                                <div class="text-lg font-bold text-gray-900 mt-1">R$ 62k</div>
                                <div class="text-[10px] text-gray-500">Não cumulativo</div>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-semibold text-gray-900">Faturamento por CFOP</span>
                                <span class="text-[10px] text-gray-500">últimos 30 dias</span>
                            </div>
                            <div class="sol-bar-chart">
                                <div style="height: 85%;" title="5102"></div>
                                <div style="height: 62%;" title="5405"></div>
                                <div style="height: 94%;" title="5910"></div>
                                <div style="height: 38%;" title="6102"></div>
                                <div style="height: 71%;" title="5101"></div>
                            </div>
                            <div class="flex justify-between text-[10px] font-mono text-gray-500 mt-2">
                                <span>5102</span><span>5405</span><span>5910</span><span>6102</span><span>5101</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Texto -->
            <div class="lg:order-2">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5" style="background-color: #eef2f7; border: 1px solid #dce3ed;">
                    <svg class="w-7 h-7" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>
                </div>
                <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Inteligência</span>
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-gray-900 mt-2 mb-4">
                    Faturamento e compras em dashboards interativos
                </h2>
                <p class="text-base text-gray-600 leading-relaxed mb-6">
                    Todos os dados importados alimentam dashboards por CFOP, participante e período. Visualize compras, vendas, tributos e alertas em um único lugar — com filtros globais e comparação entre clientes.
                </p>
                <ul class="space-y-3 mb-7">
                    @foreach ([
                        '6 abas: Visão Geral, CFOP, Participantes, Tributário, Alertas, Compliance',
                        'Filtros globais por período, cliente, participante e tipo de EFD',
                        'Exportação de tabelas e gráficos em CSV e Excel',
                        'Comparação rápida entre clientes do escritório',
                    ] as $bullet)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm text-gray-700">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('agendar') }}" class="sol-link-cta">
                    Agendar demo do BI Fiscal
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
                <p class="sol-card-interlinks mt-4 text-xs text-gray-500">
                    <a href="{{ route('precos') }}#precos-consumo" class="hover:underline" style="color: #1e4fa0">Ver créditos para este módulo</a>
                    <span class="mx-2 text-gray-300">·</span>
                    <a href="{{ route('duvidas') }}" class="hover:underline" style="color: #1e4fa0">Tirar dúvida</a>
                    <span class="mx-2 text-gray-300">·</span>
                    <a href="{{ route('blog.tema', 'efd') }}" class="hover:underline" style="color: #1e4fa0">Análise de EFD</a>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     PRODUTO 5 — CLEARANCE NF-e
============================================================ -->
<section id="produto-clearance" class="py-20 lg:py-28 bg-white sol-section-fade-in">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <!-- Texto -->
            <div class="lg:order-1">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center" style="background-color: #eef2f7; border: 1px solid #dce3ed;">
                        <svg class="w-7 h-7" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-semibold" style="background-color: #fef8ee; color: #b45309; border: 1px solid #f5e6c8;">
                        Em construção
                    </span>
                </div>
                <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Validação Documental</span>
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-gray-900 mt-2 mb-4">
                    Valide notas fiscais, de serviço e transporte em um só fluxo
                </h2>
                <p class="text-base text-gray-600 leading-relaxed mb-6">
                    Verificação em lote de <strong>NF-e</strong>, <strong>CT-e</strong> e <strong>NFS-e</strong> contra SEFAZ e prefeituras. <strong>Módulo em construção</strong> — release previsto para 2026-Q2. Entre na lista de espera para acompanhar o lançamento.
                </p>
                <ul class="space-y-3 mb-7">
                    @foreach ([
                        'Validação contra SEFAZ (NF-e/CT-e) e prefeituras (NFS-e) — em construção',
                        'Detecção de documentos cancelados, denegados ou frios — planejado',
                        'Confronto automático de valores XML × EFD — planejado',
                        'Cobertura dos três tipos em uma única operação — planejado',
                    ] as $bullet)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm text-gray-700">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('agendar') }}" class="sol-link-cta">
                    Entrar na lista de espera
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
                <p class="sol-card-interlinks mt-4 text-xs text-gray-500">
                    <a href="{{ route('duvidas') }}" class="hover:underline" style="color: #1e4fa0">Quando o Clearance fica disponível?</a>
                    <span class="mx-2 text-gray-300">·</span>
                    <a href="{{ route('blog.tema', 'clearance') }}" class="hover:underline" style="color: #1e4fa0">Guia de Clearance NF-e</a>
                </p>
            </div>

            <!-- Mockup -->
            <div class="lg:order-2">
                <div class="sol-mockup">
                    <div class="sol-mockup-header">
                        <span class="sol-mockup-dot" style="background-color: #ef4444;"></span>
                        <span class="sol-mockup-dot" style="background-color: #f59e0b;"></span>
                        <span class="sol-mockup-dot" style="background-color: #10b981;"></span>
                        <span class="ml-3 text-xs text-gray-500 font-mono">clearance/nfe</span>
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- NF-e (mercadorias) -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold" style="background-color: #eff6ff; color: #1e4fa0; border: 1px solid #bfdbfe;">NF-e</span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">000.184.923</span>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background-color: #eefbf5; color: #047857; border: 1px solid #c6eed8;">
                                    ✓ validada
                                </span>
                            </div>
                            <div class="text-[11px] font-mono text-gray-600 truncate mb-3">35240612345678000190550010001849231234567890</div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">Distribuidora Norte Ltda</span>
                                <span class="font-semibold text-gray-900">R$ 18.420,00</span>
                            </div>
                        </div>

                        <!-- CT-e (transporte) -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold" style="background-color: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe;">CT-e</span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">000.047.182</span>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background-color: #fef8ee; color: #b45309; border: 1px solid #f5e6c8;">
                                    ⚠ cancelado
                                </span>
                            </div>
                            <div class="text-[11px] font-mono text-gray-600 truncate mb-3">35240655443322000144570030000471821122334455</div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">Transportes Via Sul ME</span>
                                <span class="font-semibold text-gray-900">R$ 3.140,00</span>
                            </div>
                        </div>

                        <!-- NFS-e (serviços) -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold" style="background-color: #fff7ed; color: #c2410c; border: 1px solid #fed7aa;">NFS-e</span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">2026/00389</span>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">
                                    ✗ divergência
                                </span>
                            </div>
                            <div class="text-[11px] text-gray-600 truncate mb-3">Prefeitura de São Paulo · Consultoria Horizonte SS</div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500">XML R$ 9.200 · EFD R$ 8.750</span>
                                <span class="font-semibold" style="color: #b91c1c;">−R$ 450,00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     PRODUTO 6 — CENTRAL DE ALERTAS
============================================================ -->
<section id="produto-alertas" class="py-20 lg:py-28 bg-gray-50 sol-section-fade-in">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            <!-- Mockup -->
            <div class="lg:order-1">
                <div class="sol-mockup">
                    <div class="sol-mockup-header">
                        <span class="sol-mockup-dot" style="background-color: #ef4444;"></span>
                        <span class="sol-mockup-dot" style="background-color: #f59e0b;"></span>
                        <span class="sol-mockup-dot" style="background-color: #10b981;"></span>
                        <span class="ml-3 text-xs text-gray-500 font-mono">alertas/central</span>
                    </div>
                    <div class="p-6">
                        <div class="sol-timeline-item">
                            <div class="sol-timeline-icon" style="background-color: #fef2f2; border: 1px solid #fecaca;">
                                <svg class="w-4 h-4" fill="none" stroke="#b91c1c" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background-color: #fef2f2; color: #b91c1c;">crítico</span>
                                <span class="text-[10px] text-gray-500">há 12 min</span>
                            </div>
                            <div class="text-sm font-semibold text-gray-900">IE suspensa — Metalúrgica Horizonte SA</div>
                            <div class="text-xs text-gray-500">Inscrição estadual 110.445.221 saiu do SINTEGRA</div>
                        </div>

                        <div class="sol-timeline-item">
                            <div class="sol-timeline-icon" style="background-color: #fef8ee; border: 1px solid #f5e6c8;">
                                <svg class="w-4 h-4" fill="none" stroke="#b45309" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background-color: #fef8ee; color: #b45309;">atenção</span>
                                <span class="text-[10px] text-gray-500">há 2 h</span>
                            </div>
                            <div class="text-sm font-semibold text-gray-900">Mudança de regime — Comercial Atlas</div>
                            <div class="text-xs text-gray-500">Migração Simples → Lucro Presumido detectada</div>
                        </div>

                        <div class="sol-timeline-item">
                            <div class="sol-timeline-icon" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <svg class="w-4 h-4" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold" style="background-color: #eff6ff; color: #1e4fa0;">informativo</span>
                                <span class="text-[10px] text-gray-500">há 4 h</span>
                            </div>
                            <div class="text-sm font-semibold text-gray-900">312 participantes sincronizados</div>
                            <div class="text-xs text-gray-500">Última sincronização da importação mar/26 concluída</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Texto -->
            <div class="lg:order-2">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5" style="background-color: #eef2f7; border: 1px solid #dce3ed;">
                    <svg class="w-7 h-7" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Notificações</span>
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-gray-900 mt-2 mb-4">
                    Toda irregularidade vira um alerta acionável
                </h2>
                <p class="text-base text-gray-600 leading-relaxed mb-6">
                    Irregularidades cadastrais, divergências de dados e vencimentos fiscais chegam em uma central única, priorizados por severidade e acompanhados do contexto necessário para agir.
                </p>
                <ul class="space-y-3 mb-7">
                    @foreach ([
                        'Classificação automática por severidade',
                        'Entrega por e-mail e notificação in-app',
                        'Histórico auditável com triagem e responsáveis',
                        'Integração nativa com o monitoramento de participantes',
                    ] as $bullet)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="#1e4fa0" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="text-sm text-gray-700">{{ $bullet }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('agendar') }}" class="sol-link-cta">
                    Agendar demo da Central de Alertas
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
                <p class="sol-card-interlinks mt-4 text-xs text-gray-500">
                    <a href="{{ route('precos') }}#precos-consumo" class="hover:underline" style="color: #1e4fa0">Ver créditos para este módulo</a>
                    <span class="mx-2 text-gray-300">·</span>
                    <a href="{{ route('duvidas') }}" class="hover:underline" style="color: #1e4fa0">Tirar dúvida</a>
                    <span class="mx-2 text-gray-300">·</span>
                    <a href="{{ route('blog.tema', 'compliance') }}" class="hover:underline" style="color: #1e4fa0">Riscos fiscais no blog</a>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     CTA FINAL
============================================================ -->
<section class="sol-hero-gradient py-16 md:py-24 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center sol-fade-in-up">
            <h2 class="text-3xl md:text-5xl font-extrabold tracking-tight mb-5">
                Chega de risco fiscal no escuro
            </h2>
            <p class="text-lg md:text-xl text-blue-100 max-w-3xl mx-auto mb-8">
                Comece gratuitamente e monitore fornecedores, importe SPED e antecipe irregularidades antes que virem autuações.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('agendar') }}" class="sol-cta-primary inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg font-semibold text-base">
                    Agendar demonstração
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="{{ route('precos') }}" class="sol-cta-secondary inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg font-semibold text-base">
                    Ver créditos e faixas
                </a>
            </div>
        </div>
    </div>
</section>

<script>
    (function () {
        function setupScrollAnimations() {
            const sections = document.querySelectorAll('.sol-section-fade-in');
            if (!('IntersectionObserver' in window)) {
                sections.forEach(s => s.classList.add('visible'));
                return;
            }
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

            sections.forEach(section => observer.observe(section));
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupScrollAnimations);
        } else {
            setupScrollAnimations();
        }
    })();
</script>
