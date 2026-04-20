<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seo['title'] ?? 'FiscalDock | Radar de Riscos Fiscais' }}</title>
    <meta name="description" content="{{ $seo['description'] ?? 'Monitore CNPJs, emita CND, CNDT e FGTS numa só consulta e detecte inconsistências no SPED antes da malha fiscal. Créditos prepagos, sem mensalidade.' }}">
    <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">
    <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow,max-image-preview:large' }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('binary_files/logo/Logo FiscalDock.png') }}">

    <!-- Open Graph (WhatsApp, Facebook) -->
    <meta property="og:type" content="{{ $seo['og_type'] ?? 'website' }}">
    <meta property="og:site_name" content="FiscalDock">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:url" content="{{ $seo['canonical'] ?? url()->current() }}">
    <meta property="og:title" content="{{ $seo['og_title'] ?? $seo['title'] ?? 'FiscalDock | Radar de Riscos Fiscais' }}">
    <meta property="og:description" content="{{ $seo['og_description'] ?? $seo['description'] ?? 'Monitore CNPJs, emita CND, CNDT e FGTS numa só consulta e detecte inconsistências no SPED antes da malha fiscal. Créditos prepagos, sem mensalidade.' }}">
    <meta property="og:image" content="{{ $seo['og_image'] ?? asset('binary_files/logo/Logo FiscalDock.png') }}">

    <!-- Twitter Card (X) -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['og_title'] ?? $seo['title'] ?? 'FiscalDock | Radar de Riscos Fiscais' }}">
    <meta name="twitter:description" content="{{ $seo['og_description'] ?? $seo['description'] ?? 'Monitore CNPJs, emita CND, CNDT e FGTS numa só consulta e detecte inconsistências no SPED antes da malha fiscal. Créditos prepagos, sem mensalidade.' }}">
    <meta name="twitter:image" content="{{ $seo['og_image'] ?? asset('binary_files/logo/Logo FiscalDock.png') }}">

    <!-- Fallback crítico do CTA (caso o CSS do Vite não carregue) -->
    <style>
        .btn-cta{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:8px;padding:.875rem 1.5rem;min-height:48px;font-size:1rem;border:2px solid #facc15;background:#facc15;color:#0b1f3a;font-weight:700;letter-spacing:normal;line-height:1.1;text-decoration:none;transform:translateY(0);box-shadow:0 20px 50px -20px rgba(250,204,21,.55);transition:transform .16s ease,box-shadow .16s ease,background-color .16s ease,border-color .16s ease;-webkit-tap-highlight-color:transparent}
        .btn-cta:hover{background:#eab308;border-color:#eab308;transform:translateY(-1px);box-shadow:0 26px 60px -22px rgba(250,204,21,.65)}
        .btn-cta:active{background:#ca8a04;border-color:#ca8a04;transform:translateY(0);box-shadow:0 14px 35px -22px rgba(250,204,21,.55)}
        .btn-cta:focus-visible{outline:none;box-shadow:0 0 0 4px rgba(250,204,21,.35),0 20px 50px -20px rgba(250,204,21,.55)}
        .btn-cta--nav{border-radius:6px;min-height:40px;padding:.625rem 1.2rem;font-size:.875rem;line-height:1.2;font-weight:700}
        .btn-cta--block{width:100%;justify-content:center}
        @media (prefers-reduced-motion:reduce){.btn-cta{transition:none}}
    </style>

    @vite(['resources/css/app.css', 'resources/js/spa.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    <!-- JavaScript Files -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
    <script src="{{ asset('js/layout.js') }}?v={{ filemtime(public_path('js/layout.js')) }}"></script>
    <script src="{{ asset('js/inicio.js') }}?v={{ filemtime(public_path('js/inicio.js')) }}"></script>
    <script src="{{ asset('js/duvidas.js') }}?v={{ filemtime(public_path('js/duvidas.js')) }}"></script>
    <script src="{{ asset('js/solucoes.js') }}?v={{ filemtime(public_path('js/solucoes.js')) }}"></script>
    <script src="{{ asset('js/precos.js') }}?v={{ filemtime(public_path('js/precos.js')) }}"></script>
    <script src="{{ asset('js/login.js') }}?v={{ filemtime(public_path('js/login.js')) }}"></script>
    <script src="{{ asset('js/criar-conta.js') }}?v={{ filemtime(public_path('js/criar-conta.js')) }}"></script>
    <script src="{{ asset('js/agendar.js') }}?v={{ filemtime(public_path('js/agendar.js')) }}"></script>
    <script src="{{ asset('js/toast.js') }}?v={{ filemtime(public_path('js/toast.js')) }}"></script>
</head>
<body class="{{ $themeClass ?? 'bg-surface text-slate-900 font-sans antialiased' }}">
    @include('landing_page.partials.header')

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Main Content Area -->
    <main id="app">
        @if(isset($initialView))
            @include("landing_page.$initialView")
        @else
            @include('landing_page.paginas.inicio')
        @endif
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-10">
                <!-- Company Info -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <img src="{{ asset('binary_files/logo/logo-fiscaldock_whitebg-removebg.png') }}" alt="FiscalDock" class="h-6 md:h-8 object-contain">
                        <span class="text-lg font-bold text-gray-900">FiscalDock</span>
                    </div>
                    <p class="text-sm text-gray-500 leading-relaxed max-w-xs">
                        O centro operacional que transforma SPED e documentos em ações, relatórios e previsibilidade para escritórios contábeis e empresas.
                    </p>
                </div>

                <!-- Quick Links -->
                <div class="md:flex md:justify-center">
                    <div>
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 mb-5">Links Rápidos</h3>
                        <div class="grid grid-cols-2 gap-x-8 gap-y-3">
                            <a href="/inicio" class="text-sm" style="color: #1e4fa0">Home</a>
                            <a href="/duvidas" class="text-sm" style="color: #1e4fa0">Dúvidas</a>
                            <a href="/solucoes" class="text-sm" style="color: #1e4fa0">Soluções</a>
                            <a href="/blog" class="text-sm" style="color: #1e4fa0">Blog</a>
                            <a href="/precos" class="text-sm" style="color: #1e4fa0">Preços</a>
                            <a href="/agendar" class="text-sm" style="color: #1e4fa0">Contato</a>
                            <a href="/criar-conta" class="text-sm" style="color: #1e4fa0">Criar conta</a>
                            <a href="/login" class="text-sm" style="color: #1e4fa0">Login</a>
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 mb-5">Contato</h3>
                    <div class="flex items-center gap-3 mb-3 text-gray-600">
                        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span class="text-sm">contato@fiscaldock.com.br</span>
                    </div>
                    <div class="flex items-center gap-3 mb-3 text-gray-600">
                        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M19.05 4.91A9.82 9.82 0 0012.03 2C6.55 2 2.1 6.46 2.1 11.93c0 1.75.46 3.47 1.33 4.98L2 22l5.24-1.37a9.9 9.9 0 004.77 1.22h.01c5.47 0 9.92-4.45 9.92-9.92 0-2.65-1.03-5.13-2.89-7.02zM12.03 20.17h-.01a8.2 8.2 0 01-4.18-1.15l-.3-.18-3.11.82.83-3.03-.2-.31a8.21 8.21 0 01-1.27-4.39c0-4.52 3.68-8.2 8.21-8.2 2.19 0 4.24.85 5.79 2.4a8.14 8.14 0 012.4 5.8c0 4.52-3.68 8.2-8.16 8.2zm4.5-6.16c-.25-.13-1.47-.72-1.7-.8-.23-.09-.39-.13-.56.12-.16.25-.64.8-.78.96-.14.17-.28.19-.53.07-.25-.13-1.05-.39-2-1.24a7.46 7.46 0 01-1.39-1.73c-.15-.25-.02-.38.11-.51.11-.11.25-.28.37-.42.12-.14.16-.25.25-.41.08-.17.04-.32-.02-.45-.06-.13-.56-1.34-.76-1.84-.2-.47-.4-.41-.56-.42h-.48c-.16 0-.42.06-.64.31s-.84.82-.84 2 .86 2.32.98 2.48c.13.17 1.69 2.58 4.09 3.62.57.25 1.02.4 1.37.5.58.18 1.1.16 1.51.1.46-.07 1.47-.6 1.68-1.19.21-.58.21-1.08.15-1.19-.06-.1-.23-.16-.48-.29z"/>
                        </svg>
                        <span class="text-sm">(67) 99984-4366</span>
                    </div>
                    <a href="https://instagram.com/fiscaldock" target="_blank" rel="noopener" class="flex items-center gap-3 text-gray-600 hover:text-gray-900 transition-colors">
                        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                        <span class="text-sm">@fiscaldock</span>
                    </a>
                    <a href="{{ route('agendar') }}" class="mt-3 inline-flex items-center justify-center rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Falar com especialista
                    </a>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 pt-8 border-t border-gray-200">
                <p class="text-[11px] text-gray-400 uppercase tracking-wide">&copy; <span id="current-year"></span> FiscalDock. Todos os direitos reservados.</p>
                <div class="flex items-center gap-4">
                    <a href="{{ route('termos') }}" class="text-[11px] text-gray-400 uppercase tracking-wide hover:text-gray-600 transition-colors">Termos de Uso</a>
                    <span class="text-gray-300">·</span>
                    <a href="{{ route('privacidade') }}" class="text-[11px] text-gray-400 uppercase tracking-wide hover:text-gray-600 transition-colors">Privacidade</a>
                </div>
            </div>
        </div>
    </footer>

    @stack('structured-data')
</body>
</html>
