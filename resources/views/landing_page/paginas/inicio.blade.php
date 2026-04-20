@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'FiscalDock',
    'url' => 'https://fiscaldock.com',
    'logo' => asset('binary_files/logo/Logo FiscalDock.png'),
    'description' => 'Radar de riscos fiscais para contadores, escritórios contábeis e empresas. Monitora CNPJs, consolida consultas de compliance e ajuda a detectar inconsistências no SPED antes da malha fiscal.',
    'sameAs' => ['https://instagram.com/fiscaldock'],
    'areaServed' => ['@type' => 'Country', 'name' => 'Brasil'],
    'knowsAbout' => [
        'EFD ICMS/IPI', 'EFD Contribuições', 'SPED Fiscal', 'PIS/COFINS',
        'ICMS', 'IPI', 'NF-e', 'CT-e', 'NFS-e',
        'Compliance fiscal', 'Auditoria fiscal', 'Clearance de notas fiscais',
        'Monitoramento de CNPJ', 'Regime tributário', 'Simples Nacional',
    ],
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => '+55-67-99984-4366',
        'email' => 'contato@fiscaldock.com.br',
        'contactType' => 'customer support',
        'areaServed' => 'BR',
        'availableLanguage' => 'Portuguese',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'FiscalDock',
    'url' => 'https://fiscaldock.com',
    'inLanguage' => 'pt-BR',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'serviceType' => 'Radar de riscos fiscais',
    'provider' => ['@type' => 'Organization', 'name' => 'FiscalDock', 'url' => 'https://fiscaldock.com'],
    'areaServed' => ['@type' => 'Country', 'name' => 'Brasil'],
    'audience' => ['@type' => 'Audience', 'audienceType' => 'Contadores, escritórios contábeis e empresas'],
    'hasOfferCatalog' => [
        '@type' => 'OfferCatalog',
        'name' => 'Soluções FiscalDock',
        'itemListElement' => [
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Monitoramento de CNPJs', 'description' => 'Acompanhamento de situação cadastral, regime tributário e sinais de risco de participantes.']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Consultas de compliance', 'description' => 'Consultas de CNPJ, CND, CNDT, FGTS e fontes fiscais em fluxo consolidado.']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Alertas e inconsistências no SPED', 'description' => 'Cruzamentos entre EFD, XML, apurações, participantes e classificações fiscais antes da malha fiscal.']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Importação EFD ICMS/IPI', 'description' => 'Leitura e extração de blocos C, D, E e H do SPED Fiscal com apuração de ICMS e inventário.']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Importação EFD Contribuições', 'description' => 'Extração dos blocos A, M e F do SPED Contribuições com apuração de PIS/COFINS e retenções na fonte.']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Clearance de NF-e', 'description' => 'Consulta e validação de documentos fiscais contra fontes oficiais, com produto ainda em evolução.']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'BI Fiscal', 'description' => 'Dashboards de cruzamento entre apuração, notas fiscais, participantes e CFOP.']],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

<style>
/* Estilos para cards de depoimentos */

/* Subgrid: alinha seções dos cards horizontalmente no desktop */
#testimonials-grid {
    grid-template-rows: auto;
}
@media (min-width: 1024px) {
    #testimonials-grid {
        grid-template-rows: subgrid;
    }
}
.testimonial-card {
    transform: translateY(0);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    animation: fadeInUp 0.6s ease-out;
    gap: 0.5rem;
}

.testimonial-card:hover {
    transform: translateY(-8px) scale(1.02);
    border-color: rgb(59, 130, 246);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}


.avatar-gradient {
    transition: all 0.3s ease;
    position: relative;
}

.avatar-gradient::before {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: 50%;
    padding: 2px;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.testimonial-card:hover .avatar-gradient::before {
    opacity: 1;
    animation: rotate 2s linear infinite;
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.testimonial-metric span {
    animation: scaleIn 0.5s ease-out 0.2s both;
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.stars {
    position: relative;
    display: inline-block;
}

.stars::after {
    content: '★★★★★';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    color: rgb(250, 204, 21);
    animation: fillStars 1s ease-out 0.5s both;
}

@keyframes fillStars {
    from {
        width: 0;
    }
    to {
        width: 100%;
    }
}

.verified-badge {
    animation: slideIn 0.4s ease-out 0.3s both;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Responsividade melhorada */
@media (max-width: 640px) {
    .testimonial-card {
        padding: 1.5rem;
    }
    
    .avatar-gradient {
        width: 3rem;
        height: 3rem;
        font-size: 1.125rem;
    }
}

/* Hero fix: força gradiente mesmo se classes tailwind falharem */
:root {
    --landing-header-height: 88px;
}

.hero-first-fold {
    min-height: calc(100svh - var(--landing-header-height) - 8.25rem);
    display: flex;
    flex-direction: column;
    background-color: #f3f4f6;
}

#hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e5a9a 50%, #0f172a 100%) !important;
    color: #fff;
    display: flex;
    align-items: center;
    flex: 1 0 auto;
}

.hero-shell {
    width: 100%;
    min-height: calc(100svh - var(--landing-header-height) - 14.75rem);
    display: flex;
    align-items: center;
}

.hero-grid {
    width: 100%;
    position: relative;
}

.hero-copy {
    position: relative;
    z-index: 2;
}

.hero-copy > * {
    max-width: 40rem;
}

.hero-visual {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100%;
}

.hero-visual-glow {
    position: absolute;
    inset: 10% 6% 12% 14%;
    border-radius: 9999px;
    background:
        radial-gradient(circle at center,
            rgba(255, 255, 255, 0.2) 0%,
            rgba(96, 165, 250, 0.12) 30%,
            rgba(15, 23, 42, 0) 72%);
    filter: blur(16px);
    opacity: 0.9;
    pointer-events: none;
}

.hero-mockup {
    position: relative;
    z-index: 1;
    width: min(100%, 56rem);
    height: auto;
}

.official-sources-section {
    position: relative;
    z-index: 10;
    margin-top: -3.875rem;
}

/* ── Como Funciona ── */
.cf-step {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity 0.55s cubic-bezier(0.22, 1, 0.36, 1),
                transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
}
.cf-step.cf-visible { opacity: 1; transform: translateY(0); }
.cf-step:nth-child(1) { transition-delay: 0s; }
.cf-step:nth-child(2) { transition-delay: 0.12s; }
.cf-step:nth-child(3) { transition-delay: 0.24s; }
.cf-step:nth-child(4) { transition-delay: 0.36s; }

.cf-step .cf-icon-box {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.cf-step:hover .cf-icon-box {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px -8px rgba(11, 31, 58, 0.15);
}

.official-sources-banner {
    opacity: 0;
    transform: translateY(18px);
    animation: fadeInUp 0.7s ease-out 0.15s forwards;
}

.official-sources-marquee {
    --official-sources-gap: 2.5rem;
    position: relative;
    overflow: hidden;
    mask-image: linear-gradient(to right, transparent, black 8%, black 92%, transparent);
    -webkit-mask-image: linear-gradient(to right, transparent, black 8%, black 92%, transparent);
    opacity: 0.68;
}

.official-sources-track {
    display: flex;
    width: max-content;
    align-items: center;
    gap: 0;
    animation: officialSourcesScroll var(--official-sources-duration, 28s) linear infinite;
    will-change: transform;
}

.official-sources-group {
    display: flex;
    align-items: center;
    gap: var(--official-sources-gap);
    /* Keep the seam spacing inside the measured cycle width. */
    padding-right: var(--official-sources-gap);
    flex-shrink: 0;
}

.official-sources-logo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    min-height: 3rem;
    color: #4b5563;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    white-space: nowrap;
}

.official-sources-logo svg {
    width: 1.5rem;
    height: 1.5rem;
    flex-shrink: 0;
}

@keyframes officialSourcesScroll {
    from {
        transform: translate3d(0, 0, 0);
    }
    to {
        transform: translate3d(calc(-1 * var(--official-sources-cycle-width, 50%)), 0, 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .official-sources-banner {
        opacity: 1;
        transform: none;
        animation: none;
    }
    
    .official-sources-track {
        animation: none;
        transform: none;
    }
}

@media (min-width: 1280px) {
    .hero-first-fold {
        min-height: calc(100svh - var(--landing-header-height) - 8.75rem);
    }

    .hero-shell {
        max-width: min(94vw, 104rem);
        min-height: calc(100svh - var(--landing-header-height) - 15.75rem);
    }

    .hero-grid {
        gap: 3rem;
    }

    .hero-copy > * {
        max-width: 36rem;
    }

    .hero-visual {
        justify-content: flex-end;
    }
}

@media (min-width: 1536px) {
    #hero {
        overflow: clip;
    }

    .hero-first-fold {
        min-height: calc(100svh - var(--landing-header-height) - 9.5rem);
    }

    .hero-shell {
        max-width: min(95vw, 118rem);
        min-height: calc(100svh - var(--landing-header-height) - 16.5rem);
        padding-top: 2.625rem;
        padding-bottom: 3.5rem;
    }

    .hero-grid {
        grid-template-columns: minmax(0, 34rem) minmax(0, 1fr);
        gap: 4rem;
        align-items: center;
    }

    .hero-copy > * {
        max-width: 34rem;
    }

    .hero-copy h1 {
        font-size: 3.75rem;
        line-height: 1.02;
    }

    .hero-copy p {
        max-width: 32rem;
    }

    .hero-visual {
        min-height: 38rem;
        justify-content: flex-end;
        padding-right: 1.5rem;
    }

    .hero-visual-glow {
        inset: 6% 2% 8% 18%;
        transform: scale(1.08);
    }

    .hero-mockup {
        width: min(72rem, 100%);
        transform: translateX(4%) scale(1.08);
        transform-origin: center right;
    }
}

@media (min-width: 1920px) {
    .hero-first-fold {
        min-height: calc(100svh - var(--landing-header-height) - 9.75rem);
    }

    .hero-shell {
        max-width: min(95vw, 132rem);
        min-height: calc(100svh - var(--landing-header-height) - 16.75rem);
    }

    .hero-grid {
        grid-template-columns: minmax(0, 35rem) minmax(0, 1fr);
        gap: 5rem;
    }

    .hero-copy > * {
        max-width: 35rem;
    }

    .hero-copy h1 {
        font-size: 4.3rem;
    }

    .hero-copy p {
        max-width: 33rem;
    }

    .hero-visual {
        min-height: 42rem;
        padding-right: 2.5rem;
    }

    .hero-visual-glow {
        inset: 4% 0 6% 22%;
        transform: scale(1.18);
    }

    .hero-mockup {
        width: min(82rem, 100%);
        transform: translateX(8%) scale(1.14);
    }
}

@media (max-width: 1023px) {
    :root {
        --landing-header-height: 80px;
    }

    .hero-first-fold,
    .hero-shell {
        min-height: auto;
    }

    .official-sources-section {
        margin-top: -2.25rem;
    }
}

@media (max-width: 639px) {
    .official-sources-marquee {
        --official-sources-gap: 1.75rem;
        mask-image: none;
        -webkit-mask-image: none;
    }

    .official-sources-logo {
        min-height: 2.75rem;
        font-size: 0.95rem;
    }
}
</style>

<div class="hero-first-fold">
<!-- Hero Section (refeito) -->
<section id="hero" class="relative overflow-hidden bg-gradient-to-br from-primary-700 to-primary-500 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e5a9a 50%, #0f172a 100%);">
    <div class="hero-shell mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-12 sm:pt-8 sm:pb-12 lg:pt-8 lg:pb-12">
        <div class="hero-grid grid grid-cols-1 lg:grid-cols-12 gap-8 items-center justify-center">
            <!-- Coluna Esquerda: Texto -->
            <div class="hero-copy lg:col-span-5 xl:col-span-6">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold mb-4">
                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                    Inteligência fiscal para contadores
                </div>

                <!-- Título -->
                <h1 class="font-extrabold leading-tight tracking-tight text-3xl sm:text-4xl xl:text-5xl">
                    Importe seu SPED e descubra
                    <span class="block text-white [text-shadow:0_1px_4px_rgba(0,0,0,0.45),0_0_1px_rgba(0,0,0,0.35),0_0_8px_rgba(0,0,0,0.25)]">riscos fiscais em minutos</span>
                </h1>

                <!-- Subtítulo -->
                <p class="mt-4 text-base sm:text-lg text-white/80 max-w-2xl">
                    Cruze dados do SPED com a Receita Federal, SINTEGRA e CEIS para identificar fornecedores irregulares, notas com problemas e certidões vencidas — antes que o fisco encontre.
                </p>

                <!-- CTAs -->
                <div class="mt-5">
                    <a href="/criar-conta" data-link data-button="cta" class="btn-cta">
                        <span class="whitespace-nowrap">Criar conta grátis</span>
                        <svg class="h-5 w-5 shrink-0 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>

                <!-- Frase de apoio -->
                <p class="mt-3 text-sm text-white/70 max-w-2xl">
                    Feito para contadores e escritórios contábeis que querem proteger seus clientes contra riscos fiscais.
                </p>

                <!-- Social Proof: Avatares + Avaliação -->
                <div class="mt-6 mb-6 lg:mb-8 flex items-center gap-4 flex-wrap">
                    <!-- Grupo de Avatares -->
                    <div class="flex items-center -space-x-2">
                        <img src="{{ asset('binary_files/people-pictures/random_person-1.jpg') }}" alt="Avaliador" class="w-12 h-12 rounded-full border-2 border-white object-cover">
                        <img src="{{ asset('binary_files/people-pictures/random_person-2.jpg') }}" alt="Avaliador" class="w-12 h-12 rounded-full border-2 border-white object-cover">
                        <img src="{{ asset('binary_files/people-pictures/random_person-3.jpg') }}" alt="Avaliador" class="w-12 h-12 rounded-full border-2 border-white object-cover">
                        <img src="{{ asset('binary_files/people-pictures/random_person-4.jpg') }}" alt="Avaliador" class="w-12 h-12 rounded-full border-2 border-white object-cover">
                        <img src="{{ asset('binary_files/people-pictures/random_person-5.jpg') }}" alt="Avaliador" class="w-12 h-12 rounded-full border-2 border-white object-cover">
                    </div>

                    <!-- Avaliação -->
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <!-- Estrelas -->
                            <div class="flex items-center gap-0.5">
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            </div>
                            <!-- Nota -->
                            <span class="text-white font-bold text-lg">5.0</span>
                        </div>
                        <!-- Texto de contagem -->
                        <p class="text-white/80 text-sm mt-1">Contadores que confiam no FiscalDock</p>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita: Mockup -->
            <div class="hero-visual lg:col-span-7 xl:col-span-6">
                <div class="hero-visual-glow" aria-hidden="true"></div>
                <img
                    src="{{ asset('binary_files/mockups/macbook-mockup.png') }}"
                    alt="Dashboard do FiscalDock em um notebook"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                    class="hero-mockup w-full h-auto drop-shadow-2xl"
                >
            </div>
        </div>
    </div>
</section>

<!-- Integrações Oficiais Banner -->
<section class="official-sources-section bg-gray-100 border-y border-gray-200 py-5 sm:py-6">
    <div class="official-sources-banner max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-4">
            Dados extraídos e cruzados de fontes oficiais
        </p>
        <div class="official-sources-marquee" aria-label="Fontes oficiais integradas">
            <div class="official-sources-track">
                <div class="official-sources-group" data-official-sources-group aria-hidden="false">
                    <div class="official-sources-logo">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px; flex-shrink: 0; display: block;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3.75h6.586a1 1 0 01.707.293l2.664 2.664a1 1 0 01.293.707V19.25A1.75 1.75 0 0115.5 21h-8A1.75 1.75 0 015.75 19.25V5.5A1.75 1.75 0 017.5 3.75z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3.75V7a1 1 0 001 1h3.25"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.75 10.25h6.5"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.75 13.25h6.5"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.75 16.25h4"></path>
                        </svg>
                        Receita Federal
                    </div>
                    <div class="official-sources-logo">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px; flex-shrink: 0; display: block;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        SEFAZ
                    </div>
                    <div class="official-sources-logo">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px; flex-shrink: 0; display: block;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                        Portal da Transparência
                    </div>
                    <div class="official-sources-logo">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px; flex-shrink: 0; display: block;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        SINTEGRA
                    </div>
                    <div class="official-sources-logo">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px; flex-shrink: 0; display: block;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                        </svg>
                        PGFN
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</div>

<!-- Como Funciona Section -->
<section id="como-funciona" class="bg-gray-50 pt-8 pb-16 sm:pt-10 sm:pb-20 lg:pt-12 lg:pb-24">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 sm:mb-14 lg:mb-16">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 mb-3">Como funciona</p>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight mb-4">Da importação ao monitoramento contínuo</h2>
            <p class="text-sm sm:text-base text-gray-500 max-w-2xl mx-auto">
                Quatro passos entre o upload do arquivo e o primeiro alerta no seu painel — sem configuração manual.
            </p>
        </div>

        <div class="bg-white rounded-[2rem] border border-gray-200 shadow-[0_22px_50px_-30px_rgba(15,23,42,0.18)] overflow-hidden">
            <div class="px-5 py-5 sm:px-7 sm:py-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 via-white to-gray-50">
                <div class="relative">
                    <div class="hidden lg:block absolute left-[12.5%] right-[12.5%] top-6 border-t-[3px] border-dashed border-slate-400" aria-hidden="true"></div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4 lg:gap-6">
                        <div class="relative flex items-center gap-4 lg:flex-col lg:text-center lg:items-center">
                            <div class="w-12 h-12 rounded-full border-2 border-slate-500 bg-white flex items-center justify-center text-sm font-bold text-slate-700 shrink-0 relative z-10">1</div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-400">Upload</p>
                                <p class="text-sm font-bold text-gray-900 mt-1">Importe</p>
                            </div>
                        </div>
                        <div class="relative flex items-center gap-4 lg:flex-col lg:text-center lg:items-center">
                            <div class="w-12 h-12 rounded-full border-2 border-slate-500 bg-white flex items-center justify-center text-sm font-bold text-slate-700 shrink-0 relative z-10">2</div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-400">Validação</p>
                                <p class="text-sm font-bold text-gray-900 mt-1">Cruze</p>
                            </div>
                        </div>
                        <div class="relative flex items-center gap-4 lg:flex-col lg:text-center lg:items-center">
                            <div class="w-12 h-12 rounded-full border-2 border-slate-500 bg-white flex items-center justify-center text-sm font-bold text-slate-700 shrink-0 relative z-10">3</div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-400">Análise</p>
                                <p class="text-sm font-bold text-gray-900 mt-1">Identifique</p>
                            </div>
                        </div>
                        <div class="relative flex items-center gap-4 lg:flex-col lg:text-center lg:items-center">
                            <div class="w-12 h-12 rounded-full border-2 border-slate-500 bg-white flex items-center justify-center text-sm font-bold text-slate-700 shrink-0 relative z-10">4</div>
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-400">Contínuo</p>
                                <p class="text-sm font-bold text-gray-900 mt-1">Monitore</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-5 sm:p-7">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4 xl:gap-7">
                <article class="relative bg-white rounded-[1.75rem] border border-gray-200 px-6 py-8 sm:px-7 sm:py-9 shadow-[0_18px_40px_-24px_rgba(15,23,42,0.18)] flex flex-col">
                    <div class="w-20 h-20 rounded-[1.5rem] flex items-center justify-center mb-6 relative z-10" style="background-color: #eef2f7; border: 1px solid #dce3ed;">
                        <svg class="w-10 h-10" style="color: #1f2937;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                    </div>
                    <span class="px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.14em] text-white" style="background-color: #374151; align-self: flex-start">Etapa 1</span>
                    <h3 class="text-xl font-bold text-gray-900 mt-5">Importe</h3>
                    <p class="text-sm text-gray-600 leading-7 mt-3">Envie arquivos SPED, importe XMLs ou consulte documentos fiscais — em segundos a plataforma organiza notas, participantes e apurações para você.</p>
                    <div style="margin-top: auto; padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
                        <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">SPED · XML · Automático</span>
                    </div>
                </article>

                <article class="relative bg-white rounded-[1.75rem] border border-gray-200 px-6 py-8 sm:px-7 sm:py-9 shadow-[0_18px_40px_-24px_rgba(15,23,42,0.18)] flex flex-col">
                    <div class="w-20 h-20 rounded-[1.5rem] flex items-center justify-center mb-6 relative z-10" style="background-color: #eef2f7; border: 1px solid #dce3ed;">
                        <svg class="w-10 h-10" style="color: #1f2937;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <span class="px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.14em] text-white" style="background-color: #374151; align-self: flex-start">Etapa 2</span>
                    <h3 class="text-xl font-bold text-gray-900 mt-5">Cruze</h3>
                    <p class="text-sm text-gray-600 leading-7 mt-3">A plataforma consulta automaticamente Receita Federal, SEFAZ e PGFN — você descobre quem está inapto, o que foi cancelado e onde os valores não batem.</p>
                    <div style="margin-top: auto; padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
                        <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Receita · SEFAZ · CNDs</span>
                    </div>
                </article>

                <article class="relative bg-white rounded-[1.75rem] border border-gray-200 px-6 py-8 sm:px-7 sm:py-9 shadow-[0_18px_40px_-24px_rgba(15,23,42,0.18)] flex flex-col">
                    <div class="w-20 h-20 rounded-[1.5rem] flex items-center justify-center mb-6 relative z-10" style="background-color: #fef8ee; border: 1px solid #f5e6c8;">
                        <svg class="w-10 h-10" style="color: #b45309;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <span class="px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.14em] text-white" style="background-color: #374151; align-self: flex-start">Etapa 3</span>
                    <h3 class="text-xl font-bold text-gray-900 mt-5">Identifique</h3>
                    <p class="text-sm text-gray-600 leading-7 mt-3">Receba alertas automáticos de fornecedores inaptos, notas canceladas e divergências fiscais — antes que virem autuação.</p>
                    <div style="margin-top: auto; padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
                        <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Alertas · Score de Risco</span>
                    </div>
                </article>

                <article class="relative bg-white rounded-[1.75rem] border border-gray-200 px-6 py-8 sm:px-7 sm:py-9 shadow-[0_18px_40px_-24px_rgba(15,23,42,0.18)] flex flex-col">
                    <div class="w-20 h-20 rounded-[1.5rem] flex items-center justify-center mb-6 relative z-10" style="background-color: #eefbf5; border: 1px solid #c6eed8;">
                        <svg class="w-10 h-10" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                    <span class="px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.14em] text-white" style="background-color: #374151; align-self: flex-start">Etapa 4</span>
                    <h3 class="text-xl font-bold text-gray-900 mt-5">Monitore</h3>
                    <p class="text-sm text-gray-600 leading-7 mt-3">Acompanhe mudanças cadastrais de cada participante de forma contínua. Se algo mudar, você é o primeiro a saber.</p>
                    <div style="margin-top: auto; padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
                        <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Diário · Semanal · Mensal</span>
                    </div>
                </article>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Soluções Section -->
<section id="funcionalidades" class="bg-white pt-8 pb-20 sm:pt-10 sm:pb-24 lg:pt-12 lg:pb-28">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="text-center mb-16 sm:mb-20">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-400 mb-3">Funcionalidades</p>
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 tracking-tight mb-4">
                Tudo que o seu escritório precisa
            </h2>
            <p class="text-base text-gray-500 max-w-2xl mx-auto">
                Um ecossistema completo para compliance fiscal, monitoramento contínuo e decisões mais seguras.
            </p>
        </div>

        <!-- Bento Grid — layout assimétrico -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6">

            <!-- 1. SPED / EFD — Hero Card (2 colunas) -->
            <div class="md:col-span-2 group rounded-2xl border border-gray-200 p-6 lg:p-8 overflow-hidden hover:-translate-y-1 hover:shadow-xl transition-all duration-300 flex flex-col">
                <div class="flex flex-col lg:flex-row lg:gap-8 flex-1">
                    <div class="flex-1 flex flex-col">
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5" style="background-color: #eef2f7; border: 1px solid #dce3ed;">
                            <svg class="w-7 h-7" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-gray-900 mb-2">Auditoria e Compliance</h3>
                        <p class="text-sm text-gray-500 leading-relaxed mb-4">
                            Importe o arquivo EFD e receba a radiografia completa: apurações de ICMS e PIS/COFINS, alertas de inconsistência interna e o status fiscal de cada participante — tudo extraído automaticamente.
                        </p>
                        <ul class="space-y-2">
                            <li class="flex items-start text-xs text-gray-600">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                7 alertas automáticos: duplicatas, CFOP invertido, notas zeradas e mais
                            </li>
                            <li class="flex items-start text-xs text-gray-600">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Apuração ICMS, PIS/COFINS e retenções extraídas do arquivo
                            </li>
                        </ul>
                        <div class="flex flex-wrap gap-2" style="margin-top: auto; padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
                            <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">SPED / EFD</span>
                            <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">ICMS</span>
                            <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">PIS/COFINS</span>
                        </div>
                    </div>
                    <!-- Mockup decorativo -->
                    <div class="hidden lg:flex w-56 shrink-0 items-center justify-center">
                        <div class="relative w-full h-48 rounded-xl overflow-hidden" style="background: linear-gradient(135deg, #eef2f7 0%, #dce3ed 100%);">
                            <!-- Mini-card flutuante -->
                            <div class="absolute top-4 left-4 bg-white rounded-lg shadow-md px-3 py-2">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wide">Compliance</p>
                                <p class="text-sm font-bold text-gray-900">98.7%</p>
                            </div>
                            <!-- Mini badge -->
                            <div class="absolute bottom-4 right-4 px-2.5 py-1 rounded-full text-[10px] font-bold text-white" style="background-color: #1e4fa0;">SPED</div>
                            <!-- Mini barras de gráfico -->
                            <div class="absolute bottom-4 left-4 flex items-end gap-1">
                                <div class="w-2 h-5 rounded-sm" style="background-color: #1e4fa0; opacity: 0.3;"></div>
                                <div class="w-2 h-8 rounded-sm" style="background-color: #1e4fa0; opacity: 0.5;"></div>
                                <div class="w-2 h-6 rounded-sm" style="background-color: #1e4fa0; opacity: 0.4;"></div>
                                <div class="w-2 h-10 rounded-sm" style="background-color: #1e4fa0; opacity: 0.7;"></div>
                                <div class="w-2 h-7 rounded-sm" style="background-color: #1e4fa0; opacity: 0.5;"></div>
                            </div>
                            <!-- Mini alerta flutuante -->
                            <div class="absolute top-4 right-4 bg-white rounded-lg shadow-md px-2.5 py-1.5 flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full" style="background-color: #047857;"></div>
                                <span class="text-[10px] font-semibold text-gray-600">OK</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Clearance NF-e — compacto -->
            <div class="group rounded-2xl border border-gray-200 p-6 lg:p-8 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 self-end flex flex-col">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5" style="background-color: #eef2f7; border: 1px solid #dce3ed;">
                    <svg class="w-7 h-7" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-2">Clearance NF-e</h3>
                <p class="text-sm text-gray-500 leading-relaxed mb-4">
                    Verifique suas notas fiscais diretamente na SEFAZ. A partir das notas importadas do SPED, descubra quais foram canceladas, quais têm divergência de valor e quais não existem na base oficial.
                </p>
                <ul class="space-y-2">
                    <li class="flex items-start text-xs text-gray-600">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Detecção automática de notas canceladas, frias ou com valores divergentes
                    </li>
                    <li class="flex items-start text-xs text-gray-600">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Seleção em lote com verificação por chave de acesso de 44 dígitos
                    </li>
                </ul>
                <div class="flex flex-wrap gap-2" style="margin-top: auto; padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">SEFAZ</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">NF-e</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">CT-e</span>
                </div>
            </div>

            <!-- 3. Monitoramento — pill AO VIVO + bullets + métrica -->
            <div class="group rounded-2xl border border-gray-200 p-6 lg:p-8 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 flex flex-col">
                <div class="flex items-center justify-between mb-5">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center" style="background-color: #fef8ee; border: 1px solid #f5e6c8;">
                        <svg class="w-7 h-7" style="color: #b45309;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider text-white" style="background-color: #dc2626;">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background-color: #fca5a5;"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                        </span>
                        Ao vivo
                    </span>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-2">Monitoramento 24/7</h3>
                <p class="text-sm text-gray-500 leading-relaxed mb-3">
                    Saiba na hora quando um fornecedor ou cliente muda de status. Vigile seus participantes de forma contínua e receba alertas automáticos sem precisar consultar manualmente.
                </p>
                <ul class="space-y-2 mb-3">
                    <li class="flex items-start text-xs text-gray-600">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #b45309;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Assinaturas diárias, semanais ou mensais por CNPJ
                    </li>
                    <li class="flex items-start text-xs text-gray-600">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #b45309;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Alertas automáticos quando algo muda — sem intervenção manual
                    </li>
                </ul>
                <div class="flex items-baseline gap-1.5 mt-auto pt-3">
                    <span class="text-2xl font-extrabold text-gray-900">1.200+</span>
                    <span class="text-xs text-gray-500">CNPJs monitorados</span>
                </div>
                <div class="flex flex-wrap gap-2" style="padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Participantes</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">CNPJ</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Alertas</span>
                </div>
            </div>

            <!-- 4. BI Fiscal — compacto -->
            <div class="group rounded-2xl border border-gray-200 p-6 lg:p-8 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 flex flex-col">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5" style="background-color: #eefbf5; border: 1px solid #c6eed8;">
                    <svg class="w-7 h-7" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-2">BI Fiscal</h3>
                <p class="text-sm text-gray-500 leading-relaxed mb-3">
                    Dê ao seu escritório visão rápida da operação fiscal de cada cliente, com faturamento, compras e tributos organizados em um único painel.
                </p>
                <ul class="space-y-2 mb-3">
                    <li class="flex items-start text-xs text-gray-600">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Análises por CFOP, participante e período para cada cliente
                    </li>
                    <li class="flex items-start text-xs text-gray-600">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Dashboards executivos para acompanhar tributos e tomar decisão mais cedo
                    </li>
                    <li class="flex items-start text-xs text-gray-600">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Gestão de múltiplos clientes com leitura rápida para priorizar onde agir primeiro
                    </li>
                </ul>
                <div class="flex items-baseline gap-1.5 mt-auto pt-3">
                    <span class="text-2xl font-extrabold text-gray-900">R$ 47M+</span>
                    <span class="text-xs text-gray-500">em notas analisadas</span>
                </div>
                <div class="flex flex-wrap gap-2" style="padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">BI Fiscal</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">CFOP</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Tributário</span>
                </div>
            </div>

            <!-- 5. Regularidade Fiscal — compacto -->
            <div class="group rounded-2xl border border-gray-200 p-6 lg:p-8 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 flex flex-col">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5" style="background-color: #eef2f7; border: 1px solid #dce3ed;">
                    <svg class="w-7 h-7" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-2">Regularidade Fiscal</h3>
                <p class="text-sm text-gray-500 leading-relaxed mb-4">
                    Verifique a situação fiscal de fornecedores e clientes nas três esferas. Certidões negativas emitidas automaticamente, com situação cadastral, regime tributário e alerta de pendências — em lote ou individualmente.
                </p>
                <ul class="space-y-2">
                    <li class="flex items-start text-xs text-gray-600">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        CND Federal (PGFN), Estadual (SEFAZ) e Municipal (Prefeituras) em uma única consulta
                    </li>
                    <li class="flex items-start text-xs text-gray-600">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Histórico de consultas com vencimento e renovação automática
                    </li>
                </ul>
                <div class="flex flex-wrap gap-2" style="margin-top: auto; padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Receita Federal</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">SEFAZ</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">PGFN</span>
                </div>
            </div>

            <!-- 6. Raio-X do Fornecedor — full width, dark, premium -->
            <div class="md:col-span-2 lg:col-span-3 group rounded-2xl p-6 lg:p-8 lg:px-10 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 overflow-hidden relative" style="background-color: #0b1f3a;">
                <div class="flex flex-col lg:flex-row lg:items-center gap-8">
                    <div class="flex-1">
                        <span class="inline-block mb-4 px-3 py-1.5 rounded-full text-[11px] font-bold uppercase tracking-[0.12em]" style="background-color: #facc15; color: #0b1f3a;">Diferencial</span>
                        <h3 class="text-xl sm:text-2xl font-bold text-white mb-3">Raio-X do Fornecedor</h3>
                        <p class="text-sm leading-relaxed mb-5" style="color: rgba(255,255,255,0.6);">
                            Com um único SPED, o FiscalDock monta o dossiê completo de cada fornecedor: situação cadastral, certidões negativas, notas fiscais verificadas na SEFAZ, score de risco e alertas — tudo cruzado automaticamente.
                        </p>

                        <!-- Fluxo visual: 5 etapas inline -->
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-5">
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg" style="background-color: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
                                <svg class="w-4 h-4 shrink-0" style="color: #facc15;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                <span class="text-[11px] font-semibold text-white/70 whitespace-nowrap">SPED</span>
                            </div>
                            <svg class="w-4 h-4 shrink-0 hidden sm:block" style="color: rgba(255,255,255,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg" style="background-color: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
                                <svg class="w-4 h-4 shrink-0" style="color: #facc15;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                <span class="text-[11px] font-semibold text-white/70 whitespace-nowrap">Receita</span>
                            </div>
                            <svg class="w-4 h-4 shrink-0 hidden sm:block" style="color: rgba(255,255,255,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg" style="background-color: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
                                <svg class="w-4 h-4 shrink-0" style="color: #facc15;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                <span class="text-[11px] font-semibold text-white/70 whitespace-nowrap">CNDs</span>
                            </div>
                            <svg class="w-4 h-4 shrink-0 hidden sm:block" style="color: rgba(255,255,255,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg" style="background-color: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
                                <svg class="w-4 h-4 shrink-0" style="color: #facc15;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span class="text-[11px] font-semibold text-white/70 whitespace-nowrap">SEFAZ</span>
                            </div>
                            <svg class="w-4 h-4 shrink-0 hidden sm:block" style="color: rgba(255,255,255,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg" style="background-color: rgba(250,204,21,0.12); border: 1px solid rgba(250,204,21,0.25);">
                                <svg class="w-4 h-4 shrink-0" style="color: #facc15;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                <span class="text-[11px] font-bold text-white whitespace-nowrap">Dossiê</span>
                            </div>
                        </div>

                        <ul class="space-y-2 mb-4">
                            <li class="flex items-start text-xs" style="color: rgba(255,255,255,0.5);">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #facc15;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Fornecedor inapto + notas dele no SPED = alerta imediato
                            </li>
                            <li class="flex items-start text-xs" style="color: rgba(255,255,255,0.5);">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #facc15;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                CND vencida + volume de compras no período = risco de solidariedade tributária
                            </li>
                            <li class="flex items-start text-xs" style="color: rgba(255,255,255,0.5);">
                                <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #facc15;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Nota cancelada na SEFAZ + valor declarado no SPED = divergência fiscal
                            </li>
                        </ul>
                        <div style="margin-top: auto; padding-top: 1.25rem; border-top: 1px solid rgba(255,255,255,0.1);">
                            <span class="text-[10px] font-medium uppercase tracking-wide whitespace-nowrap" style="background-color: rgba(250,204,21,0.15); color: #facc15; padding: 4px 10px; border-radius: 4px; display: inline-block; border: 1px solid rgba(250,204,21,0.25);">Due Diligence Fiscal</span>
                        </div>
                    </div>
                    <div class="shrink-0 flex flex-col items-center gap-3">
                        <a href="/criar-conta" class="btn-cta">
                            Criar conta grátis
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </a>
                        <p class="text-[11px]" style="color: rgba(255,255,255,0.35);">Sem cartão de crédito</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- CTA da seção -->
        <div class="text-center mt-12 sm:mt-16">
            <a href="/criar-conta" class="btn-cta">
                Criar conta grátis
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
            <p class="mt-3 text-xs text-gray-400">Sem mensalidade — pague apenas pelos créditos que usar</p>
        </div>
    </div>
</section>



<!-- Métricas Banner -->
<section id="metricas" class="relative py-8 sm:py-10" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%); box-shadow: inset 0 1px 0 rgba(255,255,255,0.05), inset 0 -1px 0 rgba(255,255,255,0.05);">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-3xl sm:text-4xl font-extrabold" style="color: #ffffff;">R$ 47M+</div>
                <p class="mt-2 text-xs sm:text-sm font-medium" style="color: rgba(255,255,255,0.55);">em notas fiscais importadas</p>
            </div>
            <div>
                <div class="text-3xl sm:text-4xl font-extrabold" style="color: #ffffff;">1.200+</div>
                <p class="mt-2 text-xs sm:text-sm font-medium" style="color: rgba(255,255,255,0.55);">CNPJs monitorados</p>
            </div>
            <div>
                <div class="text-3xl sm:text-4xl font-extrabold" style="color: #ffffff;">18.000+</div>
                <p class="mt-2 text-xs sm:text-sm font-medium" style="color: rgba(255,255,255,0.55);">documentos fiscais analisados</p>
            </div>
            <div>
                <div class="text-3xl sm:text-4xl font-extrabold" style="color: #ffffff;">R$ 2,3M+</div>
                <p class="mt-2 text-xs sm:text-sm font-medium" style="color: rgba(255,255,255,0.55);">em riscos fiscais detectados</p>
            </div>
        </div>
    </div>
</section>

<!-- Para Quem E -->
<section id="para-quem-e" class="bg-white pt-8 pb-20 sm:pt-10 sm:pb-24 lg:pt-12 lg:pb-28">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 sm:mb-14 lg:mb-16">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 mb-3">Para quem é</p>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight mb-4">Feito para quem vive compliance fiscal</h2>
            <p class="text-sm sm:text-base text-gray-500 max-w-2xl mx-auto">
                Escritórios contábeis, empresas e contadores autônomos que querem proteger seus clientes contra riscos fiscais
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 lg:gap-6">
            <!-- Escritórios Contábeis -->
            <div class="rounded-2xl border border-gray-200 p-6 lg:p-8 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 flex flex-col">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5" style="background-color: #eef2f7; border: 1px solid #dce3ed;">
                    <svg class="w-7 h-7" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-3">Escritórios Contábeis</h3>
                <ul class="space-y-2.5 mb-4">
                    <li class="flex items-start text-sm text-gray-500 leading-relaxed">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Importação de SPED com extração automática de participantes e notas
                    </li>
                    <li class="flex items-start text-sm text-gray-500 leading-relaxed">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Monitoramento contínuo de fornecedores e clientes na Receita Federal
                    </li>
                    <li class="flex items-start text-sm text-gray-500 leading-relaxed">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #1e4fa0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Alertas automáticos de riscos fiscais e situação cadastral
                    </li>
                </ul>
                <div class="flex flex-wrap gap-2" style="margin-top: auto; padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">SPED</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Multi-cliente</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Alertas</span>
                </div>
            </div>

            <!-- Empresas -->
            <div class="rounded-2xl border border-gray-200 p-6 lg:p-8 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 flex flex-col">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5" style="background-color: #fef8ee; border: 1px solid #f5e6c8;">
                    <svg class="w-7 h-7" style="color: #b45309;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-3">Empresas</h3>
                <ul class="space-y-2.5 mb-4">
                    <li class="flex items-start text-sm text-gray-500 leading-relaxed">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #b45309;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Visibilidade completa dos fornecedores e sua situação cadastral
                    </li>
                    <li class="flex items-start text-sm text-gray-500 leading-relaxed">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #b45309;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Dashboards com faturamento, compras e análise tributária
                    </li>
                    <li class="flex items-start text-sm text-gray-500 leading-relaxed">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #b45309;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Consultas em lote de CNPJ com resultados em tempo real
                    </li>
                </ul>
                <div class="flex flex-wrap gap-2" style="margin-top: auto; padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Fornecedores</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">BI Fiscal</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Lote</span>
                </div>
            </div>

            <!-- Contadores Autônomos -->
            <div class="rounded-2xl border border-gray-200 p-6 lg:p-8 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 flex flex-col">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-5" style="background-color: #eefbf5; border: 1px solid #c6eed8;">
                    <svg class="w-7 h-7" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 mb-3">Contadores Autônomos</h3>
                <ul class="space-y-2.5 mb-4">
                    <li class="flex items-start text-sm text-gray-500 leading-relaxed">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Créditos acessíveis com faixas de economia para consultas tributárias
                    </li>
                    <li class="flex items-start text-sm text-gray-500 leading-relaxed">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Dashboard simplificado para acompanhar seus clientes
                    </li>
                    <li class="flex items-start text-sm text-gray-500 leading-relaxed">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0 mt-0.5" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Importacao de SPED e XML em uma interface intuitiva
                    </li>
                </ul>
                <div class="flex flex-wrap gap-2" style="margin-top: auto; padding-top: 1.25rem; border-top: 1px solid #e5e7eb;">
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Creditos</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Simples</span>
                    <span class="text-[10px] font-medium uppercase tracking-wide text-gray-400 whitespace-nowrap" style="background-color: #f3f4f6; padding: 4px 10px; border-radius: 4px; display: inline-block;">Intuitivo</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Diferenciais — Sem vs Com -->
<section id="diferenciais" class="bg-gray-50 pt-8 pb-20 sm:pt-10 sm:pb-24 lg:pt-12 lg:pb-28">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 sm:mb-14 lg:mb-16">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 mb-3">Diferenciais</p>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight mb-4">O que muda com o FiscalDock</h2>
            <p class="text-sm sm:text-base text-gray-500 max-w-2xl mx-auto">
                Compare o dia a dia do seu escritório sem e com a plataforma
            </p>
        </div>

        <div class="rounded-2xl border border-gray-200 overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <!-- Sem FiscalDock -->
                <div class="bg-gray-100">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span class="text-sm font-bold text-gray-900">Sem FiscalDock</span>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            <span class="text-sm text-gray-600">Consulta CNPJ um por um no site da Receita</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            <span class="text-sm text-gray-600">Descobre fornecedor inapto so na auditoria</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            <span class="text-sm text-gray-600">Planilha manual de controle de CNDs</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            <span class="text-sm text-gray-600">Revisa notas fiscais por amostragem manual</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" style="color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            <span class="text-sm text-gray-600">Sem visao consolidada da operacao fiscal</span>
                        </div>
                    </div>
                </div>
                <!-- Com FiscalDock -->
                <div class="bg-white border-t md:border-t-0 md:border-l border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-2">
                        <svg class="w-5 h-5 shrink-0" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm font-bold text-gray-900">Com FiscalDock</span>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-sm text-gray-900 font-medium">Importa o SPED e consulta todos de uma vez</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-sm text-gray-900 font-medium">Alerta automático assim que o status muda</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-sm text-gray-900 font-medium">Dashboard com vencimentos e renovação automática</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-sm text-gray-900 font-medium">Verificacao em lote na SEFAZ por chave de acesso</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" style="color: #047857;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-sm text-gray-900 font-medium">BI Fiscal com faturamento, compras e tributos por cliente</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Seguranca e LGPD Banner -->
<section id="seguranca-lgpd" class="relative py-8 sm:py-10" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%); box-shadow: inset 0 1px 0 rgba(255,255,255,0.05), inset 0 -1px 0 rgba(255,255,255,0.05);">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8 text-center">
            <div>
                <svg class="w-6 h-6 mx-auto mb-2" style="color: rgba(255,255,255,0.55);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <div class="text-sm font-semibold text-white">Controle de Acesso</div>
                <p class="mt-1 text-xs" style="color: rgba(255,255,255,0.45);">Por perfil e empresa</p>
            </div>
            <div>
                <svg class="w-6 h-6 mx-auto mb-2" style="color: rgba(255,255,255,0.55);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <div class="text-sm font-semibold text-white">Auditoria Completa</div>
                <p class="mt-1 text-xs" style="color: rgba(255,255,255,0.45);">Registro de todas as acoes</p>
            </div>
            <div>
                <svg class="w-6 h-6 mx-auto mb-2" style="color: rgba(255,255,255,0.55);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <div class="text-sm font-semibold text-white">Dados Criptografados</div>
                <p class="mt-1 text-xs" style="color: rgba(255,255,255,0.45);">Segregacao por cliente</p>
            </div>
            <div>
                <svg class="w-6 h-6 mx-auto mb-2" style="color: rgba(255,255,255,0.55);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <div class="text-sm font-semibold text-white">Conformidade LGPD</div>
                <p class="mt-1 text-xs" style="color: rgba(255,255,255,0.45);">Boas praticas de tratamento</p>
            </div>
        </div>
    </div>
</section>
<!-- Depoimentos Section -->
<section id="depoimentos" class="bg-gray-50 pt-8 pb-20 sm:pt-10 sm:pb-24 lg:pt-12 lg:pb-28">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8 sm:mb-10 lg:mb-12">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 mb-3">Depoimentos</p>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight mb-4">O que nossos clientes dizem</h2>
            <p class="text-sm sm:text-base text-gray-500 max-w-2xl mx-auto">
                Resultados reais de escritórios contábeis que transformaram seu compliance fiscal com o FiscalDock
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="testimonials-grid">
            <!-- Card 1 — foto: homem barbudo -->
            <div class="testimonial-card grid bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 p-8 border border-gray-200 hover:border-gray-300" style="grid-template-rows: subgrid; grid-row: span 4;">
                <div class="testimonial-header flex items-center gap-4">
                    <img src="{{ asset('binary_files/people-pictures/random_person-1.jpg') }}" alt="Carlos Mendes" class="avatar-gradient w-14 h-14 rounded-full object-cover shadow-lg">
                    <div class="flex-1">
                        <div class="font-bold text-gray-900 text-lg">Carlos Mendes</div>
                        <div class="text-sm text-gray-600">Sócio, Mendes Contabilidade</div>
                        <div class="verified-badge inline-flex items-center gap-1 mt-1 px-2 py-0.5 bg-green-50 text-green-600 rounded-full text-xs font-semibold">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            Verificado
                        </div>
                    </div>
                </div>
                <div class="testimonial-metric self-start pt-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold text-white shadow-md" style="background-color: #047857;">80% menos tempo</span>
                </div>
                <p class="testimonial-text text-gray-700 leading-relaxed text-base self-start">
                    "Antes do FiscalDock, levávamos dias para revisar a situação cadastral dos fornecedores de um único cliente. Agora importamos o SPED e temos o diagnóstico completo em minutos."
                </p>
                <div class="testimonial-footer flex items-center justify-between pt-4 border-t border-gray-100 self-end">
                    <div class="stars text-yellow-400 text-xl">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                    <div class="date text-xs text-gray-400">Há 2 meses</div>
                </div>
            </div>

            <!-- Card 2 — foto: mulher de óculos -->
            <div class="testimonial-card grid bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 p-8 border border-gray-200 hover:border-gray-300" style="grid-template-rows: subgrid; grid-row: span 4;">
                <div class="testimonial-header flex items-center gap-4">
                    <img src="{{ asset('binary_files/people-pictures/random_person-2.jpg') }}" alt="Patrícia Oliveira" class="avatar-gradient w-14 h-14 rounded-full object-cover shadow-lg">
                    <div class="flex-1">
                        <div class="font-bold text-gray-900 text-lg">Patrícia Oliveira</div>
                        <div class="text-sm text-gray-600">Contadora, Oliveira & Associados</div>
                        <div class="verified-badge inline-flex items-center gap-1 mt-1 px-2 py-0.5 bg-green-50 text-green-600 rounded-full text-xs font-semibold">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            Verificado
                        </div>
                    </div>
                </div>
                <div class="testimonial-metric self-start pt-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold text-white shadow-md" style="background-color: #047857;">70% redução</span>
                </div>
                <p class="testimonial-text text-gray-700 leading-relaxed text-base self-start">
                    "As consultas em lote e os alertas automáticos reduziram em 70% o tempo que gastávamos verificando situação cadastral. Já identificamos participantes inaptos antes de fechar operações."
                </p>
                <div class="testimonial-footer flex items-center justify-between pt-4 border-t border-gray-100 self-end">
                    <div class="stars text-yellow-400 text-xl">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                    <div class="date text-xs text-gray-400">Há 1 mês</div>
                </div>
            </div>

            <!-- Card 3 — foto: homem careca -->
            <div class="testimonial-card grid bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 p-8 border border-gray-200 hover:border-gray-300" style="grid-template-rows: subgrid; grid-row: span 4;">
                <div class="testimonial-header flex items-center gap-4">
                    <img src="{{ asset('binary_files/people-pictures/random_person-3.jpg') }}" alt="Ricardo Lima" class="avatar-gradient w-14 h-14 rounded-full object-cover shadow-lg">
                    <div class="flex-1">
                        <div class="font-bold text-gray-900 text-lg">Ricardo Lima</div>
                        <div class="text-sm text-gray-600">Contador, Lima Assessoria Contábil</div>
                        <div class="verified-badge inline-flex items-center gap-1 mt-1 px-2 py-0.5 bg-green-50 text-green-600 rounded-full text-xs font-semibold">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            Verificado
                        </div>
                    </div>
                </div>
                <div class="testimonial-metric self-start pt-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold text-white shadow-md" style="background-color: #047857;">100% compliance</span>
                </div>
                <p class="testimonial-text text-gray-700 leading-relaxed text-base self-start">
                    "Com o monitoramento contínuo e os dashboards, nosso compliance fiscal está 100% em dia. Identificamos um fornecedor inscrito no CEIS antes mesmo da auditoria externa."
                </p>
                <div class="testimonial-footer flex items-center justify-between pt-4 border-t border-gray-100 self-end">
                    <div class="stars text-yellow-400 text-xl">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                    <div class="date text-xs text-gray-400">Há 3 meses</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Dúvidas Section -->
<section id="duvidas" class="bg-white pt-8 pb-20 sm:pt-10 sm:pb-24 lg:pt-12 lg:pb-28">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12 sm:mb-14 lg:mb-16">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 mb-3">Perguntas frequentes</p>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight mb-4">Tire suas dúvidas antes de começar</h2>
            <p class="text-sm sm:text-base text-gray-500 max-w-2xl mx-auto">
                Respostas diretas para as perguntas mais comuns de contadores e escritórios contábeis
            </p>
        </div>

        <div class="max-w-3xl mx-auto">
            <div class="duvidas-item border border-gray-200 rounded-xl mb-3 transition-colors overflow-hidden hover:bg-gray-50/50">
                <button class="duvidas-question w-full text-left px-5 py-4 text-sm font-bold text-gray-900 flex justify-between items-center">
                    <span>Preciso cancelar meu sistema contábil para usar o FiscalDock?</span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-5 py-4 text-sm text-gray-600 border-t border-gray-100">
                        Não. O FiscalDock complementa Domínio, Alterdata, Contmatic e qualquer outro sistema. Você continua usando normalmente — basta exportar o SPED do seu sistema e importar no FiscalDock. Sem integração técnica, sem configuração.
                    </div>
                </div>
            </div>

            <div class="duvidas-item border border-gray-200 rounded-xl mb-3 transition-colors overflow-hidden hover:bg-gray-50/50">
                <button class="duvidas-question w-full text-left px-5 py-4 text-sm font-bold text-gray-900 flex justify-between items-center">
                    <span>Como funciona o sistema de créditos?</span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-5 py-4 text-sm text-gray-600 border-t border-gray-100">
                        Você compra créditos e usa conforme a necessidade. Cada tipo de consulta (CNPJ, CND, verificação de nota) consome uma quantidade específica de créditos. Sem mensalidade fixa e sem surpresas — pague só pelo que usar.
                    </div>
                </div>
            </div>

            <div class="duvidas-item border border-gray-200 rounded-xl mb-3 transition-colors overflow-hidden hover:bg-gray-50/50">
                <button class="duvidas-question w-full text-left px-5 py-4 text-sm font-bold text-gray-900 flex justify-between items-center">
                    <span>Quais fontes de dados o FiscalDock consulta?</span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-5 py-4 text-sm text-gray-600 border-t border-gray-100">
                        Receita Federal, SEFAZ (todos os estados), PGFN, SINTEGRA e CEIS. Todos os dados vêm de fontes oficiais do governo, consultados em tempo real. Nenhuma informação é estimada ou inferida.
                    </div>
                </div>
            </div>

            <div class="duvidas-item border border-gray-200 rounded-xl mb-3 transition-colors overflow-hidden hover:bg-gray-50/50">
                <button class="duvidas-question w-full text-left px-5 py-4 text-sm font-bold text-gray-900 flex justify-between items-center">
                    <span>Meus dados e os de meus clientes ficam seguros?</span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-5 py-4 text-sm text-gray-600 border-t border-gray-100">
                        Sim. Controle de acesso por perfil e empresa, segregação completa entre clientes, criptografia em trânsito e repouso, e conformidade com LGPD. Cada usuário vê apenas os dados que precisa.
                    </div>
                </div>
            </div>

            <div class="duvidas-item border border-gray-200 rounded-xl mb-3 transition-colors overflow-hidden hover:bg-gray-50/50">
                <button class="duvidas-question w-full text-left px-5 py-4 text-sm font-bold text-gray-900 flex justify-between items-center">
                    <span>Posso testar antes de comprar créditos?</span>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-5 py-4 text-sm text-gray-600 border-t border-gray-100">
                        Sim. Oferecemos acesso gratuito para você conhecer a plataforma. Importe um SPED, veja os participantes extraídos, explore os dashboards — tudo sem custo. Quando decidir, compre créditos para ativar as consultas.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Final -->
<section id="contato" class="relative py-16 sm:py-20 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e5a9a 50%, #0f172a 100%);">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h2 class="text-2xl sm:text-3xl font-bold text-white tracking-tight mb-4">
            Proteja seus clientes contra riscos fiscais
        </h2>
        <p class="text-base text-white/90 max-w-2xl mx-auto mb-8">
            Importe seu primeiro SPED gratuitamente e veja os resultados em minutos
        </p>

        <form action="{{ route('landing.lead.banner') }}" method="POST"
              class="mx-auto max-w-xl flex flex-col sm:flex-row gap-3">
            @csrf
            <label for="lead-email" class="sr-only">E-mail corporativo</label>
            <input id="lead-email" type="email" name="email" required
                   value="{{ old('email') }}"
                   placeholder="seu@empresa.com.br"
                   class="flex-1 px-4 py-3 rounded-lg text-sm text-gray-900 bg-white border border-white/20 focus:outline-none focus:ring-2 focus:ring-yellow-400 placeholder-gray-400" />
            <button type="submit" data-button="cta" class="btn-cta">
                <span class="whitespace-nowrap">Começar grátis</span>
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </button>
        </form>

        @error('email')
            <p class="mt-3 text-xs text-red-200">{{ $message }}</p>
        @enderror

        <p class="mt-5 flex flex-col items-center justify-center gap-2 text-xs sm:flex-row" style="color: rgba(255,255,255,0.88);">
            <span>Prefere falar com alguém?</span>
            <a href="/agendar"
               data-link
               class="inline-flex items-center justify-center rounded-full border border-white/35 bg-white/12 px-3 py-1.5 text-sm font-semibold text-white shadow-sm backdrop-blur-sm transition hover:bg-white/20 hover:border-white/55 focus:outline-none focus:ring-2 focus:ring-white/70 focus:ring-offset-2 focus:ring-offset-slate-900">
                Falar com um especialista
            </a>
        </p>

        <p class="mt-3 text-xs" style="color: rgba(255,255,255,0.7);">Sem cartão de crédito · Sem mensalidade</p>
    </div>
</section>



<!-- Scripts carregados no layout -->
