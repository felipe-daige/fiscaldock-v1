@php
    // Fonte única para accordion + FAQPage JSON-LD.
    // Ao editar uma pergunta/resposta, atualize também o bloco HTML abaixo (o conteúdo é espelhado
    // para preservar links e formatação no accordion).
    $faqs = [
        ['q' => 'O que é o FiscalDock e como ele ajuda meu escritório contábil?', 'a' => 'O FiscalDock é uma plataforma de inteligência fiscal para contadores e escritórios contábeis. Ele importa seus arquivos EFD SPED (ICMS/IPI e PIS/COFINS) e XMLs de notas fiscais, cruza os dados automaticamente com fontes oficiais como a Receita Federal e o CEIS, e gera alertas quando detecta riscos — como fornecedores com CNPJ irregular, inscrição estadual suspensa ou divergências em notas fiscais. Em vez de verificar manualmente cada participante, você tem dashboards interativos com a situação fiscal de todos os seus clientes.'],
        ['q' => 'O FiscalDock substitui meu sistema contábil (Domínio, Alterdata, Contmatic)?', 'a' => 'Não. O FiscalDock complementa o que você já usa. Ele funciona como uma camada de inteligência fiscal sobre seus dados: você exporta o SPED do seu sistema contábil, importa no FiscalDock, e ele faz toda a análise de riscos, cruzamentos e monitoramento que seu sistema não faz. Funciona ao lado de Domínio, Alterdata, Contmatic e qualquer outro ERP contábil.'],
        ['q' => 'Como começo a usar? Preciso de treinamento técnico?', 'a' => 'O processo é simples: crie sua conta, cadastre sua empresa e seus clientes, e faça o upload dos arquivos SPED (.txt) ou XMLs de notas fiscais. O FiscalDock processa tudo automaticamente — com progresso em tempo real na tela. A interface foi projetada para contadores, sem exigir conhecimento técnico. Oferecemos também um período de teste gratuito para você conhecer a plataforma antes de comprar créditos.'],
        ['q' => 'Quais tipos de arquivo posso importar e o que é extraído?', 'a' => 'Você pode importar arquivos EFD SPED (tanto ICMS/IPI quanto PIS/COFINS) e XMLs de documentos fiscais (NF-e, CT-e, NFS-e). Do SPED, o FiscalDock extrai automaticamente: participantes (fornecedores e clientes), notas fiscais organizadas por bloco, catálogo de produtos, apurações de impostos (ICMS, PIS, COFINS) e retenções na fonte. Esses dados alimentam dashboards com visão geral, análise por CFOP, perfil de participantes, resumo tributário, alertas e compliance.'],
        ['q' => 'Como funciona a validação de notas fiscais (Clearance)?', 'a' => 'O módulo de Clearance está em construção. A validação em lote de NF-e, CT-e e NFS-e contra SEFAZ e prefeituras tem release previsto para 2026-Q2 e incluirá verificação de existência, detecção de notas canceladas após a escrituração e confronto de valores XML × EFD. Hoje as notas importadas aparecem com status "pendente" até a integração ser concluída — entre na lista de espera se quiser acompanhar o lançamento.'],
        ['q' => 'O que são as Consultas de CNPJ e como funciona o sistema de créditos?', 'a' => 'O módulo de Consultas verifica a situação cadastral de CNPJs em lote — tanto participantes extraídos dos SPEDs quanto qualquer CNPJ avulso. Usamos a Receita Federal (consulta gratuita, não consome créditos) e consultas premium em sistemas públicos oficiais. O sistema funciona com créditos pré-pagos: você compra pacotes avulsos e o custo de cada consulta cai conforme sua faixa comercial sobe pelo histórico acumulado de créditos pagos.'],
        ['q' => 'Meus dados e os dados dos meus clientes estão seguros?', 'a' => 'Sim. O acesso é isolado por conta — cada escritório vê apenas seus próprios clientes e dados. Os arquivos SPED e XMLs importados são processados e os dados extraídos ficam associados exclusivamente à sua conta. Não compartilhamos informações entre escritórios e você pode excluir seus dados a qualquer momento.'],
        ['q' => 'O que acontece com meus dados se eu parar de usar a plataforma?', 'a' => 'Seus dados permanecem disponíveis por um período para que você possa exportar relatórios e resumos. Após esse período, os dados são removidos permanentemente. Caso decida voltar depois, basta reimportar seus arquivos SPED e o sistema reprocessa tudo.'],
        ['q' => 'Posso testar o FiscalDock antes de comprar créditos?', 'a' => 'Sim. O período de teste gratuito inclui importação de SPED, dashboards, alertas e consultas de CNPJ. Você pode importar arquivos reais e avaliar os resultados antes de comprar créditos. Observações: consultas premium em bases públicas consomem créditos mesmo durante o teste, porque toda chamada tem custo real; e o módulo de Clearance ainda está em construção, portanto não está disponível no trial.'],
        ['q' => 'Qual pacote de créditos é ideal para meu escritório?', 'a' => 'Depende do volume de consultas e da rotina do seu time. Escritórios menores podem começar com pacotes de entrada e evoluir conforme o uso real. Operações com maior volume destravam automaticamente as faixas X, Y e Z, reduzindo o custo por consulta de Compliance e Clearance. Confira a página de preços para comparar pacotes, faixas e custos por consulta.'],
    ];
@endphp

@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => url('/')],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Dúvidas', 'item' => url('/duvidas')],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@include('landing_page.paginas.partials.duvidas-faq-schema', ['faqs' => $faqs])
@endpush

<section id="duvidas" class="bg-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                Perguntas <span class="bg-linear-to-r from-blue-500 to-blue-600 bg-clip-text text-transparent">Frequentes</span>
            </h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                Encontre respostas sobre importação de SPED, validação fiscal, segurança dos dados, créditos e faixas
            </p>
        </div>

        {{-- Tabs de categoria --}}
        <div class="flex justify-center mb-10">
            <div class="inline-flex flex-wrap justify-center gap-2 bg-gray-50 rounded-lg p-1.5 border border-gray-200">
                <button class="duvidas-cat-btn px-4 py-2 rounded-md font-semibold text-sm transition-all duration-200 text-white"
                        style="background-color: #1e4fa0"
                        data-category="todos">Todos</button>
                <button class="duvidas-cat-btn px-4 py-2 rounded-md font-semibold text-sm transition-all duration-200 text-gray-600 hover:text-gray-900 hover:bg-white"
                        data-category="primeiros-passos">Primeiros Passos</button>
                <button class="duvidas-cat-btn px-4 py-2 rounded-md font-semibold text-sm transition-all duration-200 text-gray-600 hover:text-gray-900 hover:bg-white"
                        data-category="funcionalidades">Funcionalidades</button>
                <button class="duvidas-cat-btn px-4 py-2 rounded-md font-semibold text-sm transition-all duration-200 text-gray-600 hover:text-gray-900 hover:bg-white"
                        data-category="seguranca">Segurança e Dados</button>
                <button class="duvidas-cat-btn px-4 py-2 rounded-md font-semibold text-sm transition-all duration-200 text-gray-600 hover:text-gray-900 hover:bg-white"
                        data-category="planos">Créditos e Faixas</button>
            </div>
        </div>

        <div class="max-w-4xl mx-auto">

            {{-- ── PRIMEIROS PASSOS ── --}}
            <h3 id="categoria-primeiros-passos" class="duvidas-category-header text-xs font-bold uppercase tracking-wider mb-3 mt-2" style="color: #1e4fa0" data-category-header="primeiros-passos">
                Primeiros Passos
            </h3>

            <div class="duvidas-item border border-gray-200 rounded-lg mb-4 hover:border-blue-500 transition-colors overflow-hidden" data-category="primeiros-passos">
                <button class="duvidas-question w-full text-left px-6 py-4 font-semibold text-gray-900 hover:text-blue-500 flex justify-between items-center">
                    <span>O que é o FiscalDock e como ele ajuda meu escritório contábil?</span>
                    <svg class="w-5 h-5 shrink-0 ml-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-6 py-4 text-gray-600 bg-white border-t border-gray-100">
                        O FiscalDock é uma plataforma de inteligência fiscal para contadores e escritórios contábeis. Ele importa seus arquivos EFD SPED (ICMS/IPI e PIS/COFINS) e XMLs de notas fiscais, cruza os dados automaticamente com fontes oficiais como a Receita Federal e o CEIS, e gera alertas quando detecta riscos — como fornecedores com CNPJ irregular, inscrição estadual suspensa ou divergências em notas fiscais. Em vez de verificar manualmente cada participante, você tem dashboards interativos com a situação fiscal de todos os seus clientes.
                    </div>
                </div>
            </div>

            <div class="duvidas-item border border-gray-200 rounded-lg mb-4 hover:border-blue-500 transition-colors overflow-hidden" data-category="primeiros-passos">
                <button class="duvidas-question w-full text-left px-6 py-4 font-semibold text-gray-900 hover:text-blue-500 flex justify-between items-center">
                    <span>O FiscalDock substitui meu sistema contábil (Domínio, Alterdata, Contmatic)?</span>
                    <svg class="w-5 h-5 shrink-0 ml-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-6 py-4 text-gray-600 bg-white border-t border-gray-100">
                        Não. O FiscalDock complementa o que você já usa. Ele funciona como uma camada de inteligência fiscal sobre seus dados: você exporta o SPED do seu sistema contábil, importa no FiscalDock, e ele faz toda a análise de riscos, cruzamentos e monitoramento que seu sistema não faz. Funciona ao lado de Domínio, Alterdata, Contmatic e qualquer outro ERP contábil.
                    </div>
                </div>
            </div>

            <div class="duvidas-item border border-gray-200 rounded-lg mb-4 hover:border-blue-500 transition-colors overflow-hidden" data-category="primeiros-passos">
                <button class="duvidas-question w-full text-left px-6 py-4 font-semibold text-gray-900 hover:text-blue-500 flex justify-between items-center">
                    <span>Como começo a usar? Preciso de treinamento técnico?</span>
                    <svg class="w-5 h-5 shrink-0 ml-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-6 py-4 text-gray-600 bg-white border-t border-gray-100">
                        O processo é simples: crie sua conta, cadastre sua empresa e seus clientes, e faça o upload dos arquivos SPED (.txt) ou XMLs de notas fiscais. O FiscalDock processa tudo automaticamente — com progresso em tempo real na tela. A interface foi projetada para contadores, sem exigir conhecimento técnico. Oferecemos também um período de teste gratuito para você conhecer a plataforma antes de comprar créditos.
                    </div>
                </div>
            </div>

            <p class="mt-2 mb-6 text-sm text-gray-500">
                Quer ver os módulos em detalhes?
                <a href="{{ route('solucoes') }}#produto-sped" data-link class="hover:underline" style="color: #1e4fa0">Conheça a Importação SPED →</a>
            </p>

            {{-- ── FUNCIONALIDADES ── --}}
            <h3 id="categoria-funcionalidades" class="duvidas-category-header text-xs font-bold uppercase tracking-wider mb-3 mt-8" style="color: #1e4fa0" data-category-header="funcionalidades">
                Funcionalidades
            </h3>

            <div class="duvidas-item border border-gray-200 rounded-lg mb-4 hover:border-blue-500 transition-colors overflow-hidden" data-category="funcionalidades">
                <button class="duvidas-question w-full text-left px-6 py-4 font-semibold text-gray-900 hover:text-blue-500 flex justify-between items-center">
                    <span>Quais tipos de arquivo posso importar e o que é extraído?</span>
                    <svg class="w-5 h-5 shrink-0 ml-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-6 py-4 text-gray-600 bg-white border-t border-gray-100">
                        Você pode importar arquivos EFD SPED (tanto ICMS/IPI quanto PIS/COFINS) e XMLs de documentos fiscais (NF-e, CT-e, NFS-e). Do SPED, o FiscalDock extrai automaticamente: participantes (fornecedores e clientes), notas fiscais organizadas por bloco, catálogo de produtos, apurações de impostos (ICMS, PIS, COFINS) e retenções na fonte. Esses dados alimentam dashboards com visão geral, análise por CFOP, perfil de participantes, resumo tributário, alertas e compliance.
                    </div>
                </div>
            </div>

            <div class="duvidas-item border border-gray-200 rounded-lg mb-4 hover:border-blue-500 transition-colors overflow-hidden" data-category="funcionalidades">
                <button class="duvidas-question w-full text-left px-6 py-4 font-semibold text-gray-900 hover:text-blue-500 flex justify-between items-center">
                    <span>Como funciona a validação de notas fiscais (Clearance)?</span>
                    <svg class="w-5 h-5 shrink-0 ml-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-6 py-4 text-gray-600 bg-white border-t border-gray-100">
                        O módulo de Clearance está em construção. A validação em lote de NF-e, CT-e e NFS-e contra SEFAZ e prefeituras tem release previsto para 2026-Q2 e incluirá verificação de existência, detecção de notas canceladas após a escrituração e confronto de valores XML × EFD. Hoje as notas importadas aparecem com status "pendente" até a integração ser concluída — entre na lista de espera se quiser acompanhar o lançamento.
                    </div>
                </div>
            </div>

            <div class="duvidas-item border border-gray-200 rounded-lg mb-4 hover:border-blue-500 transition-colors overflow-hidden" data-category="funcionalidades">
                <button class="duvidas-question w-full text-left px-6 py-4 font-semibold text-gray-900 hover:text-blue-500 flex justify-between items-center">
                    <span>O que são as Consultas de CNPJ e como funciona o sistema de créditos?</span>
                    <svg class="w-5 h-5 shrink-0 ml-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-6 py-4 text-gray-600 bg-white border-t border-gray-100">
                        O módulo de Consultas verifica a situação cadastral de CNPJs em lote — tanto participantes extraídos dos SPEDs quanto qualquer CNPJ avulso. Usamos a Receita Federal (consulta gratuita, não consome créditos) e consultas premium em sistemas públicos oficiais. O sistema funciona com créditos pré-pagos: você compra pacotes avulsos e o custo de cada consulta cai conforme sua faixa comercial sobe pelo histórico acumulado de créditos pagos.
                    </div>
                </div>
            </div>

            <p class="mt-2 mb-6 text-sm text-gray-500">
                Veja todos os 6 produtos no detalhe:
                <a href="{{ route('solucoes') }}" data-link class="hover:underline" style="color: #1e4fa0">Página de Soluções →</a>
            </p>

            {{-- ── SEGURANÇA E DADOS ── --}}
            <h3 id="categoria-seguranca" class="duvidas-category-header text-xs font-bold uppercase tracking-wider mb-3 mt-8" style="color: #1e4fa0" data-category-header="seguranca">
                Segurança e Dados
            </h3>

            <div class="duvidas-item border border-gray-200 rounded-lg mb-4 hover:border-blue-500 transition-colors overflow-hidden" data-category="seguranca">
                <button class="duvidas-question w-full text-left px-6 py-4 font-semibold text-gray-900 hover:text-blue-500 flex justify-between items-center">
                    <span>Meus dados e os dados dos meus clientes estão seguros?</span>
                    <svg class="w-5 h-5 shrink-0 ml-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-6 py-4 text-gray-600 bg-white border-t border-gray-100">
                        Sim. O acesso é isolado por conta — cada escritório vê apenas seus próprios clientes e dados. Os arquivos SPED e XMLs importados são processados e os dados extraídos ficam associados exclusivamente à sua conta. Não compartilhamos informações entre escritórios e você pode excluir seus dados a qualquer momento.
                    </div>
                </div>
            </div>

            <div class="duvidas-item border border-gray-200 rounded-lg mb-4 hover:border-blue-500 transition-colors overflow-hidden" data-category="seguranca">
                <button class="duvidas-question w-full text-left px-6 py-4 font-semibold text-gray-900 hover:text-blue-500 flex justify-between items-center">
                    <span>O que acontece com meus dados se eu parar de usar a plataforma?</span>
                    <svg class="w-5 h-5 shrink-0 ml-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-6 py-4 text-gray-600 bg-white border-t border-gray-100">
                        Seus dados permanecem disponíveis por um período para que você possa exportar relatórios e resumos. Após esse período, os dados são removidos permanentemente. Caso decida voltar depois, basta reimportar seus arquivos SPED e o sistema reprocessa tudo.
                    </div>
                </div>
            </div>

            <p class="mt-2 mb-6 text-sm text-gray-500">
                Quer entender como funciona a plataforma por dentro?
                <a href="{{ route('blog') }}" data-link class="hover:underline" style="color: #1e4fa0">Leia o blog da FiscalDock →</a>
            </p>

            {{-- ── PLANOS E PREÇOS ── --}}
            <h3 id="categoria-planos" class="duvidas-category-header text-xs font-bold uppercase tracking-wider mb-3 mt-8" style="color: #1e4fa0" data-category-header="planos">
                Créditos e Faixas
            </h3>

            <div class="duvidas-item border border-gray-200 rounded-lg mb-4 hover:border-blue-500 transition-colors overflow-hidden" data-category="planos">
                <button class="duvidas-question w-full text-left px-6 py-4 font-semibold text-gray-900 hover:text-blue-500 flex justify-between items-center">
                    <span>Posso testar o FiscalDock antes de comprar créditos?</span>
                    <svg class="w-5 h-5 shrink-0 ml-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-6 py-4 text-gray-600 bg-white border-t border-gray-100">
                        Sim. O período de teste gratuito inclui importação de SPED, dashboards, alertas e consultas de CNPJ. Você pode importar arquivos reais e avaliar os resultados antes de comprar créditos. Observações: consultas premium em bases públicas consomem créditos mesmo durante o teste, porque toda chamada tem custo real; e o módulo de Clearance ainda está em construção, portanto não está disponível no trial.
                    </div>
                </div>
            </div>

            <div class="duvidas-item border border-gray-200 rounded-lg mb-4 hover:border-blue-500 transition-colors overflow-hidden" data-category="planos">
                <button class="duvidas-question w-full text-left px-6 py-4 font-semibold text-gray-900 hover:text-blue-500 flex justify-between items-center">
                    <span>Qual pacote de créditos é ideal para meu escritório?</span>
                    <svg class="w-5 h-5 shrink-0 ml-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="duvidas-answer hidden">
                    <div class="px-6 py-4 text-gray-600 bg-white border-t border-gray-100">
                        Depende do volume de consultas e da rotina do seu time. Escritórios menores podem começar com pacotes de entrada e evoluir conforme o uso real. Operações com maior volume destravam automaticamente as faixas X, Y e Z, reduzindo o custo por consulta de Compliance e Clearance. <a href="{{ route('precos') }}" data-link class="text-blue-600 hover:underline">Confira nossa página de preços</a> ou fale conosco para uma recomendação personalizada.
                    </div>
                </div>
            </div>

            <p class="mt-2 mb-6 text-sm text-gray-500">
                Pronto para escolher?
                <a href="{{ route('precos') }}" data-link class="hover:underline" style="color: #1e4fa0">Comparar créditos e faixas →</a>
            </p>

        </div>

        {{-- CTA final --}}
        <div class="text-center mt-12 pt-8 border-t border-gray-200 max-w-4xl mx-auto">
            <p class="text-lg text-gray-600 mb-4">Ainda tem dúvidas? Fale com nosso time.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center items-center">
                <a href="{{ route('agendar') }}" data-link class="btn-cta">Falar com especialista</a>
                <a href="{{ route('precos') }}" data-link class="text-sm font-medium text-gray-600 hover:text-blue-600">
                    ou veja os créditos e faixas →
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Scripts carregados no layout -->
