/**
 * Auto-recuperação de CSRF (419 TokenMismatch).
 *
 * Quando o token CSRF rotaciona/expira, o backend responde 419 (JSON) com um
 * `csrf_token` novo. Este interceptor envolve `window.fetch` e, ao ver um 419:
 *   1. lê o token novo (do corpo do 419 ou de /api/csrf-token);
 *   2. atualiza a <meta name="csrf-token">;
 *   3. refaz a requisição UMA vez com o token novo.
 *
 * Se a segunda tentativa ainda for 419, a sessão realmente expirou → vai pro login.
 * Rotas de autenticação (/login, /logout) são excluídas para não criar loop.
 *
 * Carregado direto de public/js (sem build) e antes dos scripts de página.
 */
(function () {
    'use strict';

    if (window.__csrfRecoveryInstalled || typeof window.fetch !== 'function') {
        return;
    }
    window.__csrfRecoveryInstalled = true;

    var originalFetch = window.fetch.bind(window);

    function urlOf(input) {
        if (typeof input === 'string') return input;
        if (input instanceof Request) return input.url;
        return String(input || '');
    }

    function isAuthRoute(url) {
        // Não auto-recuperar login/logout (evita loop e mascaramento de falha real).
        return /\/(login|logout)(\?|#|$)/.test(url);
    }

    async function getFreshToken(response) {
        // 1) Token novo enviado no corpo do 419 pelo backend.
        try {
            var data = await response.clone().json();
            if (data && data.csrf_token) return data.csrf_token;
        } catch (e) { /* corpo não-JSON; segue pro fallback */ }

        // 2) Fallback: endpoint dedicado.
        try {
            var r = await originalFetch('/api/csrf-token', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (r.ok) {
                var d = await r.json();
                if (d && d.csrf_token) return d.csrf_token;
            }
        } catch (e) { /* sem rede; desiste */ }

        return null;
    }

    function withToken(init, token) {
        var newInit = Object.assign({}, init || {});
        var headers = new Headers((init && init.headers) || {});
        headers.set('X-CSRF-TOKEN', token);
        newInit.headers = headers;

        // Se o corpo carregar `_token`, o Laravel prioriza ele sobre o header —
        // então precisa ser atualizado também.
        var body = init && init.body;
        if (body instanceof FormData && body.has('_token')) {
            body.set('_token', token);
            newInit.body = body;
        } else if (body instanceof URLSearchParams && body.has('_token')) {
            body.set('_token', token);
            newInit.body = body;
        }

        return newInit;
    }

    window.fetch = async function (input, init) {
        init = init || {};

        // Clona Request com corpo antes de consumir, para permitir o retry.
        var retrySource = (input instanceof Request) ? input.clone() : null;

        var response = await originalFetch(input, init);
        if (response.status !== 419) {
            return response;
        }

        var url = urlOf(input);
        if (isAuthRoute(url)) {
            return response;
        }

        var token = await getFreshToken(response);
        if (!token) {
            window.location.href = '/login';
            return response;
        }

        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);

        // Refaz UMA vez, direto pelo fetch original (sem reentrar no interceptor).
        var retryResponse;
        if (retrySource) {
            var headers = new Headers(retrySource.headers);
            headers.set('X-CSRF-TOKEN', token);
            retryResponse = await originalFetch(new Request(retrySource, { headers: headers }));
        } else {
            retryResponse = await originalFetch(input, withToken(init, token));
        }

        // Ainda 419: a sessão realmente acabou → manda pro login.
        if (retryResponse.status === 419) {
            window.location.href = '/login';
        }

        return retryResponse;
    };
})();
