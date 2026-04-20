<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reforma Tributária 2026 | Soluções Inteligentes</title>
    <meta name="description" content="Prepare sua empresa para a Reforma Tributária de 2026 com soluções inteligentes. Otimize créditos, automatize processos e garanta conformidade fiscal.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('binary_files/logo/Logo FiscalDock.png') }}">

    <!-- Open Graph (WhatsApp, Facebook) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="FiscalDock | Soluções Inteligentes">
    <meta property="og:description" content="Prepare sua empresa para a Reforma Tributária de 2026 com soluções inteligentes.">
    <meta property="og:image" content="{{ asset('binary_files/logo/Logo FiscalDock.png') }}">

    <!-- Twitter Card (X) -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="FiscalDock | Soluções Inteligentes">
    <meta name="twitter:description" content="Prepare sua empresa para a Reforma Tributária de 2026 com soluções inteligentes.">
    <meta name="twitter:image" content="{{ asset('binary_files/logo/Logo FiscalDock.png') }}">

    @vite(['resources/css/app.css', 'resources/js/spa.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="{{ asset('js/layout.js') }}?v={{ filemtime(public_path('js/layout.js')) }}"></script>
    <script src="{{ asset('js/toast.js') }}?v={{ filemtime(public_path('js/toast.js')) }}"></script>
</head>
<body class="{{ $themeClass ?? 'bg-surface text-slate-900 font-sans antialiased' }}">
    <div class="min-h-screen md:flex">
        @include('autenticado.partials.sidebar')

        <div id="layout-shell" class="layout-shell layout-with-sidebar layout-sidebar-expanded flex-1 min-w-0 flex flex-col">
            <div class="mobile-auth-topbar">
                <div class="mobile-auth-topbar__inner">
                    <button id="sidebar-open-btn" type="button" class="mobile-auth-topbar__button" aria-label="Abrir menu">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <a href="/app/dashboard" class="mobile-auth-topbar__brand" data-link data-no-active>
                        <img src="{{ asset('binary_files/logo/logo-fiscaldock_whitebg-removebg.png') }}" alt="FiscalDock" class="mobile-auth-topbar__brand-logo">
                        <span class="mobile-auth-topbar__brand-text">FiscalDock</span>
                    </a>
                </div>
            </div>

            <!-- Toast Container -->
            <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

            <!-- Main Content Area -->
            <main id="app" class="flex-1">
                @if(isset($initialView))
                    @include($initialView)
                @endif
            </main>

        </div>
    </div>
</body>
</html>

