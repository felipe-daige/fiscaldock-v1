@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post['title'],
    'description' => $post['meta_description'],
    'datePublished' => $post['data'],
    'dateModified' => $post['data'],
    'url' => 'https://fiscaldock.com/blog/' . $post['slug'],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => 'https://fiscaldock.com/blog/' . $post['slug'],
    ],
    'image' => asset('binary_files/logo/Logo FiscalDock.png'),
    'articleSection' => $post['categoria'],
    'author' => ['@type' => 'Organization', 'name' => 'FiscalDock'],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'FiscalDock',
        'logo' => [
            '@type' => 'ImageObject',
            'url' => asset('binary_files/logo/Logo FiscalDock.png'),
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => 'https://fiscaldock.com/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => 'https://fiscaldock.com/blog'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => 'https://fiscaldock.com/blog/' . $post['slug']],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            {{-- Artigo --}}
            <article class="lg:col-span-8">
                <div class="mb-8">
                    <a href="/blog" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-medium text-sm mb-6">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Voltar ao Blog
                    </a>
                    <div class="blog-meta-panel">
                        <div class="blog-meta-panel__top">
                            <span class="blog-badge blog-badge--primary">{{ $post['categoria'] }}</span>
                            @if(!empty($post['serie']))
                            <span class="blog-badge blog-badge--series">Série</span>
                            <span class="blog-badge blog-badge--muted">{{ $post['serie'] }}</span>
                            @endif
                            @if(($post['tema'] ?? null) === 'efd')
                            <a href="/blog/efd" class="blog-meta-link">Hub de EFD</a>
                            @endif
                        </div>
                        <div class="blog-meta-panel__facts">
                            <span class="blog-meta-chip">
                                <span class="blog-meta-chip__dot"></span>
                                {{ \Carbon\Carbon::parse($post['data'])->format('d/m/Y') }}
                            </span>
                            <span class="blog-meta-chip">
                                <span class="blog-meta-chip__dot"></span>
                                {{ $post['tempo_leitura'] }} de leitura
                            </span>
                            @if(!empty($seriePos) && !empty($serieTotal))
                            <span class="blog-meta-chip">
                                <span class="blog-meta-chip__dot"></span>
                                Parte {{ $seriePos }} de {{ $serieTotal }}
                            </span>
                            @endif
                        </div>
                        @if(!empty($post['tags']))
                        <div class="blog-meta-panel__top" style="margin-top:0.75rem;">
                            @foreach($post['tags'] as $tag)
                            <span class="blog-badge blog-badge--muted">#{{ $tag }}</span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight">{{ $post['title'] }}</h1>
                </div>

                <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
                    @include($post['view'])
                </div>

                @if(!empty($seriesPosts))
                <div class="mt-10 rounded-2xl border border-gray-200 bg-gray-50 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Continue na série</h3>
                    <div class="space-y-3">
                        @foreach($seriesPosts as $seriesPost)
                        <a href="/blog/{{ $seriesPost['slug'] }}" class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white px-4 py-3 hover:border-blue-300 hover:shadow-sm transition-all">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">{{ $seriesPost['title'] }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ $seriesPost['tempo_leitura'] }} de leitura</div>
                            </div>
                            <span class="text-blue-600 font-medium text-sm">Ler</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                @if(!empty($seriePrev) || !empty($serieNext))
                <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if(!empty($seriePrev))
                    <a href="/blog/{{ $seriePrev['slug'] }}" class="rounded-xl border border-gray-200 bg-white p-4 hover:border-blue-300 hover:shadow-sm transition-all">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 mb-1">← Anterior na série</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $seriePrev['title'] }}</div>
                    </a>
                    @else
                    <div></div>
                    @endif
                    @if(!empty($serieNext))
                    <a href="/blog/{{ $serieNext['slug'] }}" class="rounded-xl border border-gray-200 bg-white p-4 hover:border-blue-300 hover:shadow-sm transition-all sm:text-right">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 mb-1">Próximo na série →</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $serieNext['title'] }}</div>
                    </a>
                    @endif
                </div>
                @endif

                <div class="mt-12 rounded-xl p-8 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e5a9a 50%, #0f172a 100%);">
                    <h3 class="text-2xl font-bold mb-3">Proteja seus clientes contra riscos fiscais</h3>
                    <p class="text-white/80 mb-6">O FiscalDock automatiza o monitoramento de participantes, a importação de SPED e a detecção de riscos. Teste gratuitamente.</p>
                    <a href="/criar-conta" class="btn-cta inline-flex items-center">
                        Criar conta grátis
                        <svg class="h-5 w-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>
            </article>

            {{-- Sidebar --}}
            <aside class="lg:col-span-4">
                <div class="sticky top-24">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">Artigos relacionados</h3>
                    <div class="space-y-6">
                        @foreach($otherPosts as $otherPost)
                        <a href="/blog/{{ $otherPost['slug'] }}" class="group block">
                            <div class="bg-white rounded-lg border border-gray-200 p-4 hover:border-blue-300 hover:shadow-sm transition-all">
                                <span class="inline-block px-2 py-0.5 bg-blue-50 text-blue-600 text-xs font-semibold rounded mb-2">{{ $otherPost['categoria'] }}</span>
                                <h4 class="text-sm font-bold text-gray-900 group-hover:text-blue-600 transition-colors leading-snug">{{ $otherPost['title'] }}</h4>
                                <p class="text-xs text-gray-500 mt-1">{{ $otherPost['tempo_leitura'] }} de leitura</p>
                            </div>
                        </a>
                        @endforeach
                    </div>

                    <div class="mt-8 bg-gray-50 rounded-xl border border-gray-200 p-6">
                        <h4 class="text-base font-bold text-gray-900 mb-2">Quer ver na prática?</h4>
                        <p class="text-sm text-gray-600 mb-4">Fale com a FiscalDock para tirar dúvidas comerciais e entender o melhor caminho para sua operação.</p>
                        <a href="/agendar" class="inline-flex items-center justify-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Falar com um especialista
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>
