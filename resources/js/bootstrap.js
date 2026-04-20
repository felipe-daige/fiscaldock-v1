import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Redirecionamento unificado para sessão expirada ou falha de rede
const LOGIN_PATH = '/login';

function shouldRedirectToLogin() {
    return typeof window !== 'undefined' && window.location && window.location.pathname !== LOGIN_PATH;
}

function redirectToLogin() {
    if (shouldRedirectToLogin()) {
        window.location.href = LOGIN_PATH;
    }
}

function isAuthExpiredStatus(status) {
    return status === 401 || status === 419;
}

// Interceptor global do axios (SPA + Blade)
axios.interceptors.response.use(
    (response) => {
        // Se o backend redirecionou para /login, forçar navegação completa
        if (response?.request?.responseURL && response.request.responseURL.includes(LOGIN_PATH)) {
            redirectToLogin();
        }
        return response;
    },
    (error) => {
        const status = error?.response?.status;
        const isNetworkError = !error?.response;
        
        // Se for erro de autenticação, verificar se é JSON antes de redirecionar
        if (isAuthExpiredStatus(status) && !isNetworkError) {
            const contentType = error?.response?.headers?.['content-type'] || '';
            const isJsonResponse = contentType.includes('application/json');
            
            // Só redirecionar se NÃO for JSON
            if (!isJsonResponse) {
                redirectToLogin();
            }
        } else if (isNetworkError) {
            redirectToLogin();
        }

        return Promise.reject(error);
    }
);

// Wrapper global para fetch, garantindo tratamento uniforme em chamadas AJAX
if (typeof window !== 'undefined' && !window.__fetchAuthWrapped) {
    const originalFetch = window.fetch.bind(window);

    window.fetch = async (...args) => {
        try {
            // Verificar se a requisição é para uma rota de API
            const requestUrl = typeof args[0] === 'string' ? args[0] : args[0]?.url || '';
            const isApiRoute = requestUrl.startsWith('/api/') || requestUrl.includes('/api/');
            
            // Para rotas de API, usar redirect: 'manual' para evitar seguir redirects automaticamente
            // Isso previne que redirects do Laravel (como 401 -> /login -> /dashboard) sejam seguidos
            let fetchOptions = {};
            if (isApiRoute) {
                fetchOptions = typeof args[1] === 'object' ? { ...args[1] } : {};
                // Se já tem redirect configurado, manter; senão, usar 'manual'
                if (!fetchOptions.redirect) {
                    fetchOptions.redirect = 'manual';
                }
            } else {
                fetchOptions = args[1];
            }
            
            const response = await originalFetch(args[0], fetchOptions);

            // Se foi usado redirect: 'manual' e a resposta é um redirect (status 0 e tipo 'opaqueredirect')
            // Para rotas de API, isso significa que houve um redirect (provavelmente 401 -> /login)
            // Não seguir o redirect - retornar uma resposta 401 JSON para que o código específico trate
            if (isApiRoute && fetchOptions.redirect === 'manual' && (response.status === 0 || response.type === 'opaqueredirect')) {
                // Criar uma resposta 401 JSON simulada
                // Usar Response constructor que é suportado em navegadores modernos
                try {
                    return new Response(JSON.stringify({
                        success: false,
                        message: 'Usuário não autenticado.'
                    }), {
                        status: 401,
                        statusText: 'Unauthorized',
                        headers: new Headers({
                            'Content-Type': 'application/json'
                        })
                    });
                } catch (e) {
                    // Fallback: retornar resposta original se não conseguir criar nova Response
                    console.warn('[Bootstrap] Erro ao criar Response simulada:', e);
                    return response;
                }
            }
            
            // Verificar se a resposta foi redirecionada para login/dashboard
            // Isso não deve acontecer com redirect: 'manual', mas verificamos por segurança
            const responseUrl = response.url || '';
            const wasRedirectedToLogin = response.redirected && responseUrl.includes(LOGIN_PATH);
            const wasRedirectedToDashboard = response.redirected && responseUrl.includes('/dashboard');
            
            // Verificar também se a URL da resposta contém login/dashboard (mesmo sem response.redirected)
            // Isso pode acontecer quando redirect: 'manual' bloqueia o redirect mas a URL ainda aponta para o destino
            const urlContainsLogin = responseUrl.includes(LOGIN_PATH);
            const urlContainsDashboard = responseUrl.includes('/dashboard');
            
            // Se foi redirecionado e é uma rota de API, não processar o redirect
            // Retornar uma resposta 401 JSON para que o código específico trate
            if (isApiRoute && (wasRedirectedToLogin || wasRedirectedToDashboard || urlContainsLogin || urlContainsDashboard)) {
                console.warn('[Bootstrap] Redirect detectado para rota de API, convertendo para 401 JSON', {
                    responseUrl,
                    redirected: response.redirected,
                    status: response.status
                });
                try {
                    return new Response(JSON.stringify({
                        success: false,
                        message: 'Usuário não autenticado.'
                    }), {
                        status: 401,
                        statusText: 'Unauthorized',
                        headers: new Headers({
                            'Content-Type': 'application/json'
                        })
                    });
                } catch (e) {
                    // Fallback: retornar resposta original se não conseguir criar nova Response
                    console.warn('[Bootstrap] Erro ao criar Response simulada:', e);
                    return response;
                }
            }
            
            // Verificar se é erro de autenticação
            if (isAuthExpiredStatus(response.status)) {
                // Se a resposta é JSON, não redirecionar - deixar o código específico tratar
                const contentType = response.headers.get('content-type') || '';
                const isJsonResponse = contentType.includes('application/json');
                
                // Para rotas de API, nunca redirecionar automaticamente - deixar o código específico tratar
                if (isApiRoute) {
                    return response;
                }
                
                // Só redirecionar se NÃO for JSON (requisições web normais) e NÃO for rota de API
                if (!isJsonResponse) {
                    redirectToLogin();
                }
            } else if (wasRedirectedToLogin && !isApiRoute) {
                // Só processar redirects para login se não for rota de API
                redirectToLogin();
            }

            return response;
        } catch (err) {
            // Verificar se é uma requisição de API antes de redirecionar
            const requestUrl = typeof args[0] === 'string' ? args[0] : args[0]?.url || '';
            const isApiRoute = requestUrl.startsWith('/api/') || requestUrl.includes('/api/');
            
            // Erro de rede ou offline - só redirecionar se não for rota de API
            if (!isApiRoute) {
                redirectToLogin();
            }
            throw err;
        }
    };

    // Flag para evitar múltiplos wrappers (hot reload / reexecução)
    window.__fetchAuthWrapped = true;
}
