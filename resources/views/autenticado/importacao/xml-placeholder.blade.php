{{-- Importação de XMLs - Placeholder (Em Desenvolvimento) --}}
<div class="min-h-screen bg-gray-50">
    {{-- Header Section --}}
    <div class="bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 tracking-tight">
                        Importar XMLs de Notas Fiscais
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Importe e processe XMLs de NF-e, NFS-e e CT-e</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            {{-- Construction Icon --}}
            <div class="mx-auto w-24 h-24 bg-amber-100 rounded-full flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
            </div>

            <h2 class="text-2xl font-semibold text-gray-900 mb-3">
                Em Desenvolvimento
            </h2>

            <p class="text-gray-600 max-w-md mx-auto mb-8">
                Esta funcionalidade esta sendo desenvolvida para importar XMLs de NF-e, NFS-e e CT-e automaticamente.
            </p>

            {{-- Features Preview --}}
            <div class="bg-gray-50 rounded-lg p-6 max-w-lg mx-auto mb-8">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
                    O que voce podera fazer aqui:
                </h3>
                <ul class="text-left text-sm text-gray-600 space-y-2">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Importar XMLs de NF-e, NFS-e e CT-e
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Extrair participantes automaticamente
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Validar notas fiscais
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Acompanhar progresso em tempo real
                    </li>
                </ul>
            </div>

            {{-- Back Button --}}
            <a href="/app/participantes" data-link class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Ver Participantes
            </a>
        </div>

        {{-- Contact Info --}}
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                Tem alguma sugestao? Entre em contato conosco pelo
                <a href="mailto:suporte@fiscaldock.com.br" class="text-blue-600 hover:underline">suporte@fiscaldock.com.br</a>
            </p>
        </div>
    </div>
</div>