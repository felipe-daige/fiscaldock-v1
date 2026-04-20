<header class="bg-white border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex justify-between items-center py-4">
            <a href="/inicio" class="flex items-center gap-3">
                <img src="{{ asset('binary_files/logo/logo-fiscaldock_whitebg-removebg.png') }}" alt="FiscalDock" class="h-8 md:h-10 object-contain">
                <span class="text-xl font-bold text-gray-900">FiscalDock</span>
            </a>

            <ul class="hidden md:flex items-center gap-8">
                <li class="flex items-center"><a href="/solucoes" class="text-gray-600 hover:text-gray-900 transition-colors font-medium inline-flex items-center" style="min-height: 40px; line-height: 1.2;">Soluções</a></li>
                <li class="flex items-center"><a href="/precos" class="text-gray-600 hover:text-gray-900 transition-colors font-medium inline-flex items-center" style="min-height: 40px; line-height: 1.2;">Preços</a></li>
                <li class="flex items-center"><a href="/duvidas" class="text-gray-600 hover:text-gray-900 transition-colors font-medium inline-flex items-center" style="min-height: 40px; line-height: 1.2;">Dúvidas</a></li>
                <li class="flex items-center"><a href="/blog" class="text-gray-600 hover:text-gray-900 transition-colors font-medium inline-flex items-center" style="min-height: 40px; line-height: 1.2;">Blog</a></li>
                <li class="flex items-center" aria-hidden="true"><span class="text-gray-300 select-none">|</span></li>
                <li class="flex items-center"><a href="/login" class="text-gray-600 hover:text-gray-900 transition-colors font-medium inline-flex items-center" style="min-height: 40px; line-height: 1.2;">Login</a></li>
                <li class="flex items-center">
                    <a href="/criar-conta" class="btn-cta btn-cta--nav">
                        Criar conta grátis
                    </a>
                </li>
                <li class="flex items-center">
                    <a href="/agendar" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50" style="min-height: 40px;">
                        Falar com especialista
                    </a>
                </li>
            </ul>

            <button id="mobile-menu-btn" class="md:hidden p-2 text-gray-600 hover:text-gray-900 transition-colors">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </nav>

        <div id="mobile-menu" class="hidden md:hidden flex-col gap-4 py-4 border-t border-gray-200">
            <a href="/solucoes" data-link class="text-gray-600 hover:text-gray-900 transition-colors font-medium py-2">Soluções</a>
            <a href="/precos" data-link class="text-gray-600 hover:text-gray-900 transition-colors font-medium py-2">Preços</a>
            <a href="/duvidas" data-link class="text-gray-600 hover:text-gray-900 transition-colors font-medium py-2">Dúvidas</a>
            <a href="/blog" data-link class="text-gray-600 hover:text-gray-900 transition-colors font-medium py-2">Blog</a>
            <div class="border-t border-gray-200 pt-4 flex flex-col gap-4">
                <a href="/login" data-link class="text-gray-600 hover:text-gray-900 transition-colors font-medium py-2">Login</a>
                <a href="/criar-conta" data-link class="btn-cta btn-cta--block">
                    Criar conta grátis
                </a>
                <a href="/agendar" data-link class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">
                    Falar com especialista
                </a>
            </div>
        </div>
    </div>
</header>
