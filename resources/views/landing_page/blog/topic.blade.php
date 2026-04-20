@php($canonical = 'https://fiscaldock.com' . ($topic['slug'] === 'efd' ? '/blog/efd' : '/blog/tema/' . $topic['slug']))
@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $topic['title'],
    'description' => $topic['description'],
    'url' => $canonical,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => 'https://fiscaldock.com/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => 'https://fiscaldock.com/blog'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $topic['title'], 'item' => $canonical],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@include('landing_page.blog.partials.topic-faq-schema')
@endpush

<section class="py-12 sm:py-14 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl border border-gray-200 overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 mb-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-0">
                <div class="lg:col-span-7 p-6 sm:p-8 text-white">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-[0.2em] bg-white/10 mb-4">
                        {{ $topic['hero_eyebrow'] ?? 'Hub temática' }}
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold leading-tight mb-3">{{ $topic['title'] }}</h1>
                    <p class="text-white/75 text-sm sm:text-base max-w-2xl mb-5">
                        {{ $topic['hero_description'] ?? $topic['description'] }}
                    </p>
                    @if(!empty($featuredPost))
                    <a href="/blog/{{ $featuredPost['slug'] }}" class="inline-flex items-center gap-2 rounded-full bg-white text-slate-900 px-4 py-2.5 text-sm font-semibold hover:bg-slate-100 transition-colors">
                        Ler artigo pilar
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                    @endif
                </div>
                <div class="lg:col-span-5 bg-white/5 backdrop-blur-sm p-6 sm:p-8 border-t lg:border-t-0 lg:border-l border-white/10">
                    <div class="text-white/70 text-xs font-semibold uppercase tracking-[0.2em] mb-3">O que você encontra aqui</div>
                    <ul class="space-y-2 text-sm text-white/80 leading-relaxed">
                        @foreach(($topic['hero_checklist'] ?? []) as $item)
                        <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            <div class="lg:col-span-8">
                <div class="mb-10">
                    <h2 class="text-2xl font-bold text-gray-900 mb-3">Artigos do cluster</h2>
                    <p class="text-gray-600 max-w-3xl">
                        {{ $topic['description'] }}
                    </p>
                </div>

                @if(count($posts) === 0)
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6 text-sm text-gray-600">
                    Novos artigos neste cluster em breve.
                </div>
                @else
                <div class="space-y-5">
                    @foreach($posts as $index => $post)
                    <a href="/blog/{{ $post['slug'] }}" class="block rounded-2xl border border-gray-200 bg-white p-6 hover:border-blue-300 hover:shadow-sm transition-all">
                        <div class="flex flex-wrap items-center gap-3 mb-3">
                            <span class="inline-flex items-center justify-center rounded-full bg-gray-900 text-white text-xs font-bold w-7 h-7">{{ $index + 1 }}</span>
                            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">{{ $post['categoria'] }}</span>
                            <span class="text-xs text-gray-500">{{ $post['tempo_leitura'] }} de leitura</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $post['title'] }}</h3>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $post['excerpt'] }}</p>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>

            <aside class="lg:col-span-4">
                <div class="sticky top-24 space-y-6">
                    @if(!empty($topic['faqs']))
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Perguntas frequentes</h3>
                        <div class="space-y-4 text-sm text-gray-600">
                            @foreach($topic['faqs'] as $faq)
                            <div>
                                <div class="font-semibold text-gray-900 mb-1">{{ $faq['q'] }}</div>
                                <p>{{ $faq['a'] }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="rounded-2xl border border-gray-200 bg-white p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-3">Próximo passo</h3>
                        <p class="text-sm text-gray-600 mb-4">Veja o FiscalDock aplicar isso em escala na sua carteira de clientes.</p>
                        <a href="/agendar" class="inline-flex items-center justify-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Falar com um especialista
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>
