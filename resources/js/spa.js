import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('app');

    // Sistema de gerenciamento de recursos globais
    window._spaResources = {
        intervals: [],
        swipers: [],
        listeners: []
    };

    // Funcao para atualizar CSRF token apos navegacao SPA
    async function atualizarCsrfToken() {
        try {
            const response = await fetch('/api/csrf-token', {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (response.ok) {
                const data = await response.json();
                if (data.csrf_token) {
                    const meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) {
                        meta.setAttribute('content', data.csrf_token);
                        console.log('[SPA] CSRF token atualizado');
                    }
                }
            }
        } catch (e) {
            console.error('[SPA] Erro ao atualizar CSRF token:', e);
        }
    }

    // Configuração de mapeamento para páginas com nomes de arquivo diferentes
    const _spaScriptOverrides = {
        monitoramento: null, // Código inline na view
        importacaoEfd: null, // Código inline na view
        importacao: null, // Código inline na view (importação XML)
        monitoramentoAvulso: null, // Código inline na view
        consultasAvulso: null, // Código inline na view (mesma view do monitoramentoAvulso)
        monitoramentoHistorico: null, // Código inline na view
        monitoramentoParticipante: null, // Código inline na view
        consultas: '/js/consulta-lote.js',
        consultaLote: '/js/consulta-lote.js',
        bi: null, // Script carregado como tag externa na view — nao tentar recarregar no SPA
        notasFiscais: null, // Código inline na view
        alertas: null, // Código inline na view
        clearance: null, // Código inline/script externo por view de clearance
    };

    // Converte slug (com hífen/underscore) para camelCase
    function slugToCamel(slug) {
        return slug
            .split(/[-_]+/)
            .filter(Boolean)
            .map((parte, index) => index === 0 
                ? parte.toLowerCase() 
                : parte.charAt(0).toUpperCase() + parte.slice(1).toLowerCase()
            )
            .join('');
    }

    // Resolve nome da página, função init e caminho do script a partir da URL
    function obterInfoPagina(caminho) {
        const segmentos = caminho.split('/').filter(Boolean);

        let baseSlug = 'inicio';
        if (segmentos.length > 0) {
            if (segmentos[0] === 'app') {
                // Rotas autenticadas
                if (segmentos[1] === 'participante') {
                    // Rota de participante específico: /app/participante/{id}
                    baseSlug = 'monitoramentoParticipante';
                } else {
                    baseSlug = segmentos[1] || 'dashboard';
                }
            } else {
                baseSlug = segmentos[0];
            }
        }

        const paginaCamel = slugToCamel(baseSlug);
        const nomePagina = paginaCamel || 'inicio';
        const nomeFuncao = `init${nomePagina.charAt(0).toUpperCase() + nomePagina.slice(1)}`;
        const scriptPath = Object.prototype.hasOwnProperty.call(_spaScriptOverrides, nomePagina)
            ? _spaScriptOverrides[nomePagina]
            : `/js/${nomePagina}.js`;

        return { nomePagina, scriptPath, nomeFuncao };
    }

    // Mapeamento específico para views
    const funcoesEspecificas = {
        '/': 'initInicio',
        '/inicio': 'initInicio',
        '/login': 'initLogin',
        '/criar-conta': 'initCriarConta',
        '/agendar': 'initAgendar',
        '/solucoes': 'initSolucoes',
        '/app/importacao/efd': 'initImportacaoEfd',
        '/app/monitoramento/avulso': 'initMonitoramentoAvulso',
        '/app/consultas/avulso': 'initMonitoramentoAvulso',
        '/app/consulta/avulso': 'initMonitoramentoAvulso',
        '/app/novo-participante': 'initNovoParticipante',
        '/app/novo-cliente': 'initNovoCliente',
        '/app/monitoramento/historico': 'initMonitoramentoHistorico',
        // Nota: /app/participante/{id} é tratada dinamicamente em obterInfoPagina()
        '/app/importacao/xml': 'initMonitoramentoXml',
        '/app/consultas/nova': 'initConsultaLote',
        '/app/consulta/nova': 'initConsultaLote',
        '/app/perfil': 'initPerfil',
        '/app/bi/dashboard': 'initBi',
        '/app/dashboard': 'initDashboard',
        '/app/notas-fiscais': null, // IIFE inline na view, sem init function
        '/app/notas-fiscais/dashboard': null, // IIFE inline na view
        '/app/alertas': null, // IIFE inline na view
        '/app/clearance/dashboard': null, // Clearance dashboard — IIFE inline
        '/app/clearance/notas': 'initClearanceNotas', // Clearance notas — reinicializa via initClearanceNotas no SPA
        '/app/clearance/buscar': 'initClearanceBuscar', // Clearance busca avulsa — reinicializa via initClearanceBuscar no SPA
    };
    
    // 0. LIMPAR RECURSOS ANTES DE NAVEGAR
    function limparRecursos() {
        // Resetar flags de inicialização ANTES de limpar recursos
        // Isso garante que as páginas possam ser reinicializadas após navegação
        
        // Resetar layout
        if (window.resetLayout && typeof window.resetLayout === 'function') {
            try {
                window.resetLayout();
            } catch (error) {
                console.error('Erro ao resetar layout:', error);
            }
        }
        
        // Resetar flags globais de inicialização se existirem
        if (typeof window._layoutInitialized !== 'undefined') {
            window._layoutInitialized = false;
        }
        if (typeof window._inicioInitialized !== 'undefined') {
            window._inicioInitialized = false;
        }
        if (typeof window._precosInitialized !== 'undefined') {
            window._precosInitialized = false;
        }
        if (typeof window._solucoesInitialized !== 'undefined') {
            window._solucoesInitialized = false;
        }
        if (typeof window._duvidasInitialized !== 'undefined') {
            window._duvidasInitialized = false;
        }
        if (typeof window._impactosInitialized !== 'undefined') {
            window._impactosInitialized = false;
        }
        if (typeof window._consultaLoteLastInit !== 'undefined') {
            window._consultaLoteLastInit = 0;
        }
        if (typeof window._consultaLoteModuleLoaded !== 'undefined') {
            window._consultaLoteModuleLoaded = false;
        }

        // Limpar todos os intervalos
        window._spaResources.intervals.forEach(intervalId => {
            clearInterval(intervalId);
        });
        window._spaResources.intervals = [];
        
        // Destruir todas as instâncias Swiper
        window._spaResources.swipers.forEach(swiper => {
            try {
                if (swiper && typeof swiper.destroy === 'function') {
                    swiper.destroy(true, true);
                }
            } catch (error) {
                // Ignorar erros ao destruir Swiper
            }
        });
        window._spaResources.swipers = [];
        
        // Remover listeners específicos (se necessário)
        window._spaResources.listeners.forEach(({ element, event, handler }) => {
            try {
                if (element && handler) {
                    element.removeEventListener(event, handler);
                }
            } catch (error) {
                // Ignorar erros ao remover listeners
            }
        });
        window._spaResources.listeners = [];
        
        // Limpar recursos de funções init específicas se existirem
        if (window._cleanupFunctions) {
            Object.values(window._cleanupFunctions).forEach(cleanup => {
                try {
                    if (typeof cleanup === 'function') {
                        cleanup();
                    }
                } catch (error) {
                    // Ignorar erros de cleanup
                }
            });
            window._cleanupFunctions = {};
        }
        
        // Destruir instâncias ApexCharts e resetar estado do módulo BI
        try {
            if (typeof window.cleanupBi === 'function') {
                window.cleanupBi();
            }
        } catch (error) {
            // Ignorar erro se cleanupBi não estiver definida (BI não foi carregado)
        }

        // Limpar alerta de erro inline ao navegar entre páginas
        try {
            if (typeof window.hideErrorAlert === 'function') {
                window.hideErrorAlert();
            } else {
                const errBox = document.getElementById('error-alert-container');
                if (errBox) errBox.innerHTML = '';
            }
        } catch (error) {
            // Ignorar
        }
    }
    
    // 1. INTERCEPTAR CLIQUES EM LINKS
    document.body.addEventListener('click', async (e) => {
        const link = e.target.closest('[data-link]');
        if (link) {
            // Ignorar navegação SPA para rotas que NÃO começam com /app/
            const linkPath = new URL(link.href, window.location.origin).pathname;
            if (!linkPath.startsWith('/app/')) {
                return; // Deixar o browser fazer navegação normal (full page reload)
            }

            e.preventDefault(); // Não recarregar página
            e.stopPropagation(); // Evitar propagação
            console.log('[SPA] Link clicado:', link.href, 'Target:', e.target);
            try {
                await navegar(link.href); // Navegar via JavaScript
            } catch (error) {
                console.error('[SPA] Erro ao navegar:', error);
                // Fallback: recarregar página completa
                window.location.href = link.href;
            }
        }
    });
    
    // 1.1. INTERCEPTAR FORMULÁRIO DE LOGOUT
    document.body.addEventListener('submit', async (e) => {
        const form = e.target;
        if (form && (form.id === 'logout-form' || form.id === 'logout-form-header' || form.id === 'logout-form-mobile')) {
            e.preventDefault(); // Não recarregar página
            
            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': formData.get('_token')
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.redirect) {
                    // Limpar recursos antes de navegar
                    limparRecursos();
                    // Recarregar página completa para trocar o header (layout muda entre autenticado/não autenticado)
                    window.location.href = data.redirect;
                } else {
                    // Fallback: recarregar página se não for JSON
                    window.location.href = data.redirect || '/inicio';
                }
            } catch (error) {
                console.error('Erro ao fazer logout:', error);
                // Fallback: recarregar página em caso de erro
                window.location.href = '/inicio';
            }
        }
    });
    
    // 2. FUNÇÃO PRINCIPAL DE NAVEGAÇÃO
    async function navegar(url, options = {}) {
        const targetUrl = new URL(url, window.location.origin);
        const browserUrl = `${targetUrl.pathname}${targetUrl.search}${targetUrl.hash}`;

        try {
            const { updateHistory = true } = options;

            // Fechar sidebar drawer no mobile antes de navegar
            if (window.closeSidebarDrawer) {
                window.closeSidebarDrawer();
            }

            // Verificar se há mudança de contexto (autenticado <-> não autenticado)
            // URLs autenticadas: /dashboard, /app/*
            // URLs não autenticadas: /inicio, /login, etc.
            const urlPath = targetUrl.pathname;
            const currentPath = window.location.pathname;
            
            // Detectar se estamos navegando para/da área autenticada
            const isDashboardArea = (path) => path.startsWith('/app/');
            
            const currentIsDashboard = isDashboardArea(currentPath);
            const targetIsDashboard = isDashboardArea(urlPath);
            
            // Se há mudança entre área autenticada e não autenticada, recarregar página completa
            // para garantir que o header seja trocado corretamente
            if (currentIsDashboard !== targetIsDashboard) {
                window.location.href = targetUrl.toString();
                return;
            }
            
            // Mostrar loading
            mostrarLoading();
            
            // Limpar recursos antes de navegar
            limparRecursos();
            
            // Buscar conteúdo da nova página
            const resposta = await fetch(targetUrl.toString(), {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                },
                credentials: 'same-origin'
            });
            
            console.log('[SPA] Resposta recebida:', {
                url,
                status: resposta.status,
                ok: resposta.ok,
                contentType: resposta.headers.get('content-type'),
                redirected: resposta.redirected
            });
            
            // Verificar se é erro de autenticação (sessão expirada)
            if (resposta.status === 401 || resposta.status === 419) {
                console.warn('[SPA] Erro de autenticação, redirecionando para login');
                window.location.href = '/login';
                return;
            }
            
            if (!resposta.ok) {
                console.error('[SPA] Resposta não OK:', resposta.status, resposta.statusText);
                const isServerError = resposta.status >= 500;
                let botaoSuporte = '';
                if (isServerError) {
                    const supportCfg = window.systemSupportConfig || {};
                    const baseSupportUrl = supportCfg.whatsappUrl || 'https://wa.me/5567999844366';
                    const supportLabel = supportCfg.contactLabel || 'Falar com o suporte no WhatsApp';
                    const supportMsg = [
                        'Olá, preciso de suporte com uma falha de processamento na FiscalDock.',
                        `Contexto: Erro ${resposta.status} ao acessar a página`,
                        `Página: ${targetUrl.href}`,
                    ].join('\n');
                    const supportUrl = baseSupportUrl + (baseSupportUrl.includes('?') ? '&' : '?') + 'text=' + encodeURIComponent(supportMsg);
                    const whatsappSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" style="margin-right:6px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.272-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.247-.694.247-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>`;
                    botaoSuporte = `<a href="${supportUrl}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center px-4 py-2 rounded-lg text-white text-sm font-medium transition" style="background-color:#25D366;">${whatsappSvg}${supportLabel}</a>`;
                }
                app.innerHTML = `
                    <div class="flex flex-col items-center justify-center min-h-[60vh] text-gray-500">
                        <div class="text-6xl font-bold text-gray-300 mb-4">${resposta.status}</div>
                        <p class="text-lg mb-6">${resposta.status === 404 ? 'Página não encontrada' : 'Erro ao carregar a página'}</p>
                        ${isServerError ? `<p class="text-sm text-gray-500 mb-6 max-w-md text-center">Já registramos o erro — se preferir agilizar, fale com o suporte e nós te ajudamos.</p>` : ''}
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            ${botaoSuporte}
                            <button onclick="history.back()" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                                ← Voltar
                            </button>
                        </div>
                    </div>`;
                esconderLoading();
                return;
            }
            
            // Verificar se é JSON (erro de autenticação, etc.)
            const contentType = resposta.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const data = await resposta.json();
                
                // Só processar redirects se:
                // 1. A URL solicitada não for uma rota de API (/api/*)
                // 2. A resposta contém redirect E indica explicitamente que é um redirect de navegação
                const isApiRoute = urlPath.startsWith('/api/');
                
                if (data.redirect && !isApiRoute) {
                    // Se a mensagem indica mudança de contexto (login/logout), recarregar página completa
                    // para garantir que o header seja trocado corretamente
                    if (data.message && (data.message.includes('logado') || data.message.includes('logout') || data.message.includes('não está logado'))) {
                        window.location.href = data.redirect;
                        return;
                    }
                    // Navegar via SPA em vez de recarregar página
                    await navegar(data.redirect);
                    return;
                }
                
                // Se for JSON mas não tem redirect ou é uma rota de API, tratar como erro de navegação
                // (requisições de API não devem ser processadas pela função navegar)
                if (isApiRoute) {
                    throw new Error('Resposta JSON de API recebida em requisição de navegação');
                }
                
                throw new Error('Resposta JSON inesperada');
            }
            
            // Pegar HTML da resposta
            const html = await resposta.text();
            
            // Trocar conteúdo
            app.innerHTML = html;

            // Atualizar URL do browser
            if (updateHistory) {
                history.pushState(null, '', browserUrl);
            }

            // Atualizar CSRF token após navegação SPA
            atualizarCsrfToken();
            
            // Destacar link ativo
            destacarLinkAtivo(targetUrl.toString());
            
            // Executar scripts da nova página
            executarScripts();
            
            // Inicializar layout (menu mobile, etc.)
            if (window.initLayout && typeof window.initLayout === 'function') {
                try {
                    window.initLayout();
                } catch (error) {
                    console.error('Erro ao inicializar layout:', error);
                }
            }
            
            // Voltar ao topo
            window.scrollTo(0, 0);
            
        } catch (erro) {
            // Log do erro para debug
            console.error('[SPA] Erro ao navegar:', {
                url,
                error: erro.message,
                stack: erro.stack
            });
            
            // Só mostrar alert se não for erro de rede
            if (erro.message && !erro.message.includes('Failed to fetch')) {
                // Erro de navegação - tentar recarregar a página completa como fallback
                console.warn('[SPA] Erro na navegação SPA, recarregando página completa:', url);
                window.location.href = targetUrl.toString();
                return;
            }
        } finally {
            esconderLoading();
        }
    }

    window.navigateTo = function(url, options = {}) {
        return navegar(url, options);
    };
    
    // 3. DESTACAR LINK ATIVO
    function destacarLinkAtivo(url) {
        // Usar a função do layout.js se disponível
        if (window.setActiveLink) {
            const caminhoAtual = new URL(url).pathname;
            window.setActiveLink(caminhoAtual);
            return;
        }
        
        // Fallback: remover destaque de todos
        document.querySelectorAll('[data-link]').forEach(link => {
            const isButton = link.dataset.button !== undefined;
            
            if (isButton) {
                // Para botões, remover indicadores visuais de ativo
                link.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
            } else {
                // Para links normais, remover classes de texto ativo
                link.classList.remove('text-blue-500', 'font-semibold');
                link.classList.add('text-gray-600');
            }
        });
        
        // Destacar link atual
        const caminhoAtual = new URL(url).pathname;
        const linkAtivo = document.querySelector(`[data-link][href="${caminhoAtual}"]`);
        if (linkAtivo) {
            const isButton = linkAtivo.dataset.button !== undefined;
            
            if (isButton) {
                // Para botões, usar ring como indicador visual sem alterar o peso da fonte
                linkAtivo.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
            } else {
                // Para links normais, usar classes de texto ativo
                linkAtivo.classList.remove('text-gray-600');
                linkAtivo.classList.add('text-blue-500', 'font-semibold');
            }
        }
    }
    
    // 4. EXECUTAR SCRIPTS
    function executarScripts() {
        const scripts = app.querySelectorAll('script');
        const loadPromises = [];

        scripts.forEach((script, index) => {
            try {
                // Script externo com src - carregar dinamicamente
                if (script.src) {
                    const scriptSrc = script.getAttribute('src');
                    const existente = document.head.querySelector('script[src="' + scriptSrc + '"]');
                    if (!existente) {
                        const novoScript = document.createElement('script');
                        novoScript.src = scriptSrc;
                        // Collect a promise for each NEW external script
                        const p = new Promise((resolve) => {
                            novoScript.onload = resolve;
                            novoScript.onerror = resolve; // resolve even on error so we don't hang
                        });
                        loadPromises.push(p);
                        document.head.appendChild(novoScript);
                    }
                    script.parentNode.removeChild(script);
                    return;
                }

                const novoScript = document.createElement('script');
                novoScript.textContent = script.textContent;

                // Validar se o script tem conteúdo válido antes de executar
                if (script.textContent && script.textContent.trim() !== '') {
                    // Adicionar handler de erro para capturar erros de sintaxe
                    novoScript.onerror = function(error) {
                        console.error('Erro ao executar script:', error);
                    };

                    // Adicionar ao head para executar
                    document.head.appendChild(novoScript);

                    // Remover script original do app
                    script.parentNode.removeChild(script);
                } else {
                    // Remover script vazio
                    script.parentNode.removeChild(script);
                }
            } catch (error) {
                console.error('Erro ao processar script:', error);
                // Continuar com outros scripts mesmo se um falhar
            }
        });

        function chamarFuncoesEspecificas() {
            try {
                executarFuncoesEspecificas();
            } catch (error) {
                console.error('Erro ao executar funções específicas:', error);
            }
        }

        if (loadPromises.length > 0) {
            // Wait for all newly added external scripts to load before calling init functions
            Promise.all(loadPromises).then(chamarFuncoesEspecificas);
        } else {
            // No new external scripts — keep short fallback for inline scripts
            setTimeout(chamarFuncoesEspecificas, 50);
        }
    }
    
    // 4.1. EXECUTAR FUNÇÕES ESPECÍFICAS
    function executarFuncoesEspecificas() {
        const caminho = window.location.pathname;

        // Carregar JavaScript específico da página se necessário
        carregarJavaScriptEspecifico(caminho);

        const infoPagina = obterInfoPagina(caminho);
        const funcaoAlvo = Object.prototype.hasOwnProperty.call(funcoesEspecificas, caminho)
            ? funcoesEspecificas[caminho]
            : infoPagina.nomeFuncao;

        if (!funcaoAlvo) {
            return;
        }

        if (window[funcaoAlvo] && typeof window[funcaoAlvo] === 'function') {
            window[funcaoAlvo]();
        }
    }
    
    // 4.2. CARREGAR JAVASCRIPT ESPECÍFICO (SISTEMA DINÂMICO)
    function carregarJavaScriptEspecifico(caminho) {
        const { scriptPath, nomePagina } = obterInfoPagina(caminho);

        if (nomePagina && nomePagina !== '') {
            // Verificar se a função já está disponível (código inline já foi executado)
            const infoPagina = obterInfoPagina(caminho);
            const nomeFuncao = Object.prototype.hasOwnProperty.call(funcoesEspecificas, caminho)
                ? funcoesEspecificas[caminho]
                : infoPagina.nomeFuncao;
            if (nomeFuncao && window[nomeFuncao] && typeof window[nomeFuncao] === 'function') {
                // Função já está disponível (provavelmente de código inline), não precisa carregar arquivo
                return;
            }

            // Se scriptPath for null ou vazio, não tentar carregar arquivo externo (código está inline)
            if (!scriptPath || scriptPath === 'null' || scriptPath === '') {
                // Código está inline, apenas tentar executar a função quando disponível
                setTimeout(() => {
                    executarFuncaoEspecifica(caminho);
                }, 100);
                return;
            }

            const scriptExistente = document.querySelector(`script[src="${scriptPath}"]`);

            if (!scriptExistente) {
                const script = document.createElement('script');
                script.src = scriptPath;
                script.onload = function() {
                    // Aguardar um pouco para garantir que a função foi definida
                    setTimeout(() => {
                        executarFuncaoEspecifica(caminho);
                    }, 100);
                };
                script.onerror = function() {
                    // Arquivo não encontrado (normal se não tiver JavaScript específico ou se o código estiver inline)
                    // Tentar executar a função mesmo assim, caso esteja disponível via código inline
                    setTimeout(() => {
                        executarFuncaoEspecifica(caminho);
                    }, 100);
                };
                document.head.appendChild(script);
            } else {
                // Script já carregado, executar função
                executarFuncaoEspecifica(caminho);
            }
        }
    }
    
    // 4.3. EXECUTAR FUNÇÃO ESPECÍFICA (SISTEMA DINÂMICO)
    function executarFuncaoEspecifica(caminho) {
        // Sistema dinâmico: gera nome da função automaticamente
        // /contato → initContato
        // /sobre → initSobre
        // /dashboard → initDashboard
        const infoPagina = obterInfoPagina(caminho);
        const nomeFuncao = Object.prototype.hasOwnProperty.call(funcoesEspecificas, caminho)
            ? funcoesEspecificas[caminho]
            : infoPagina.nomeFuncao;

        if (nomeFuncao && infoPagina.nomePagina !== '') {
            // Tentar executar a função com retry
            tentarExecutarFuncao(nomeFuncao, 0);
        }
    }
    
    // 4.4. TENTAR EXECUTAR FUNÇÃO COM RETRY
    function tentarExecutarFuncao(nomeFuncao, tentativas) {
        if (window[nomeFuncao] && typeof window[nomeFuncao] === 'function') {
            try {
                window[nomeFuncao]();
            } catch (error) {
                console.error(`Erro ao executar função ${nomeFuncao}:`, error);
            }
        } else if (tentativas < 15) {
            // Função ainda não está disponível, tentar novamente
            setTimeout(() => {
                tentarExecutarFuncao(nomeFuncao, tentativas + 1);
            }, 200);
        } else {
            console.warn(`Função ${nomeFuncao} não encontrada após ${tentativas} tentativas`);
        }
    }
    
    // 5. LOADING (desabilitado)
    function mostrarLoading() {
        // Loading desabilitado - sem barra no topo
    }
    
    function esconderLoading() {
        // Loading desabilitado - sem barra no topo
    }
    
    // 6. BOTÕES VOLTAR/AVANÇAR
    window.addEventListener('popstate', () => {
        // Só navegar se não for a página inicial
        if (location.pathname !== '/') {
            navegar(window.location.href, { updateHistory: false });
        }
    });
    
    // 7. INICIALIZAR
    destacarLinkAtivo(window.location.href);
    
    // 8. PROCESSAR SCRIPTS INLINE NA PRIMEIRA CARGA
    // Esta função processa scripts inline que já estão no DOM na primeira carga
    // Garante que funções definidas em scripts inline estejam disponíveis antes de executar funções específicas
    function processarScriptsInline() {
        const scripts = app.querySelectorAll('script');
        scripts.forEach((script, index) => {
            try {
                // Script externo com src - carregar dinamicamente
                if (script.src) {
                    const scriptSrc = script.getAttribute('src');
                    const existente = document.head.querySelector('script[src="' + scriptSrc + '"]');
                    if (!existente) {
                        // Mover tag para <head> como referência para navegações SPA futuras.
                        // Script já foi executado pelo parser — mover NÃO re-executa (HTML spec: "already started" flag).
                        document.head.appendChild(script);
                    } else {
                        script.parentNode.removeChild(script);
                    }
                    return;
                }

                const novoScript = document.createElement('script');
                novoScript.textContent = script.textContent;

                // Validar se o script tem conteúdo válido antes de executar
                if (script.textContent && script.textContent.trim() !== '') {
                    // Adicionar handler de erro para capturar erros de sintaxe
                    novoScript.onerror = function(error) {
                        console.error('Erro ao executar script inline:', error);
                    };
                    
                    // Adicionar ao head para executar
                    document.head.appendChild(novoScript);

                    // Remover script original do app
                    script.parentNode.removeChild(script);
                } else {
                    // Remover script vazio
                    script.parentNode.removeChild(script);
                }
            } catch (error) {
                console.error('Erro ao processar script inline:', error);
                // Continuar com outros scripts mesmo se um falhar
            }
        });
    }
    
    // 9. CARREGAR JAVASCRIPT NA PRIMEIRA CARGA
    function carregarJavaScriptInicial() {
        const caminho = window.location.pathname;
        
        // Primeiro, processar scripts inline que já estão no DOM
        processarScriptsInline();

        // Aguardar um delay para garantir que todos os scripts foram executados
        setTimeout(() => {
            carregarJavaScriptEspecifico(caminho);

            setTimeout(() => {
                try {
                    executarFuncoesEspecificas();
                } catch (error) {
                    console.error('Erro ao executar funções específicas na primeira carga:', error);
                }
            }, 100);
        }, 150);
    }
    
    // 10. CARREGAR JAVASCRIPT NA PRIMEIRA CARGA
    carregarJavaScriptInicial();

});
