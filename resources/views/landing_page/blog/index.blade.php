<section class="py-12 sm:py-14 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Blog FiscalDock</h1>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                Artigos sobre compliance fiscal, SPED, riscos tributários e boas práticas para escritórios contábeis.
            </p>
        </div>

        @if(!empty($topics))
        <div class="mb-12">
            <div class="flex flex-wrap justify-center gap-3">
                @foreach($topics as $topic)
                <a href="{{ $topic['url'] }}" class="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:border-blue-300 hover:text-blue-700 transition-colors">
                    <span>{{ $topic['title'] }}</span>
                    <span class="text-xs text-gray-500">{{ $topic['count'] }} artigos</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($featuredPost))
        @php($trilhaPosts = array_slice($seriesPosts, 0, 5))
        @php($totalSeriesPosts = count($seriesPosts))
        <div class="mb-10 rounded-3xl border border-gray-200 overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-0">
                <div class="lg:col-span-7 p-6 sm:p-8 text-white">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-[0.2em] bg-white/10 mb-4">
                        Série em destaque
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-bold leading-tight mb-3">{{ $featuredPost['serie'] }}</h2>
                    <p class="text-white/75 text-sm sm:text-base max-w-2xl mb-5">
                        Conteúdo direto para contadores: revisar EFD com mais padrão, menos retrabalho e mais segurança.
                    </p>
                    <a href="/blog/{{ $featuredPost['slug'] }}" class="inline-flex items-center gap-2 rounded-full bg-white text-slate-900 px-4 py-2.5 text-sm font-semibold hover:bg-slate-100 transition-colors">
                        Começar pela visão geral
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>
                <div class="lg:col-span-5 bg-white/5 backdrop-blur-sm p-6 sm:p-8 border-t lg:border-t-0 lg:border-l border-white/10">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-white/70 text-xs font-semibold uppercase tracking-[0.2em]">Trilha inicial</div>
                        <div class="text-white/80 text-[11px] font-semibold">{{ $totalSeriesPosts }} artigos</div>
                    </div>
                    <ol class="space-y-1.5">
                        @foreach($trilhaPosts as $idx => $seriesPost)
                        <li>
                            <a href="/blog/{{ $seriesPost['slug'] }}" class="flex items-start gap-3 rounded-lg px-2 py-2 hover:bg-white/10 transition-colors group">
                                <span class="shrink-0 mt-0.5 w-5 h-5 rounded-full bg-white/20 text-white text-[10px] font-bold flex items-center justify-center">{{ $idx + 1 }}</span>
                                <span class="text-sm text-white leading-snug">{{ $seriesPost['title'] }}</span>
                            </a>
                        </li>
                        @endforeach
                    </ol>
                    @if($totalSeriesPosts > count($trilhaPosts))
                    <a href="/blog/efd" class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-white/70 hover:text-white transition-colors">
                        Ver todos os {{ $totalSeriesPosts }} artigos
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <div class="mb-14 grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($topics as $topic)
            <a href="{{ $topic['url'] }}" class="rounded-2xl border border-gray-200 bg-gray-50 p-6 hover:border-blue-300 hover:bg-white transition-all">
                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500 mb-3">Cluster</div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">{{ $topic['title'] }}</h2>
                <p class="text-sm text-gray-600 leading-relaxed mb-4">{{ $topic['description'] }}</p>
                <div class="text-sm font-semibold text-blue-600">Explorar tema</div>
            </a>
            @endforeach
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($posts as $post)
            <a href="/blog/{{ $post['slug'] }}" class="group flex flex-col h-full bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg hover:border-blue-300 transition-all duration-300">
                <div class="h-48 flex items-center justify-center p-6" style="background: linear-gradient(135deg, #0f172a 0%, #1e5a9a 50%, #0f172a 100%);">
                    <div class="text-center">
                        <span class="inline-block px-3 py-1 bg-white/20 text-white text-xs font-semibold rounded-full mb-3">{{ $post['categoria'] }}</span>
                        @if(!empty($post['serie']))
                        <div class="text-white/70 text-[11px] uppercase tracking-[0.2em] mb-2">{{ $post['serie'] }}</div>
                        @endif
                        <div class="text-white/80 text-sm">{{ $post['tempo_leitura'] }} de leitura</div>
                    </div>
                </div>
                <div class="p-6 flex flex-col flex-1">
                    <div class="blog-card-meta">
                        <span class="blog-badge blog-badge--primary">{{ $post['categoria'] }}</span>
                        @if(!empty($post['serie']))
                        <span class="blog-badge blog-badge--muted">Série</span>
                        @endif
                        <span class="blog-card-date">{{ \Carbon\Carbon::parse($post['data'])->format('d/m/Y') }}</span>
                        <span class="blog-card-date">{{ $post['tempo_leitura'] }}</span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors">{{ $post['title'] }}</h2>
                    <p class="text-gray-600 text-sm leading-relaxed">{{ $post['excerpt'] }}</p>
                    <div class="mt-auto pt-4 inline-flex items-center gap-1 text-blue-600 font-semibold text-sm group-hover:gap-2 transition-all">
                        Ler artigo
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
