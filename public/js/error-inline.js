(function () {
    'use strict';

    function resolveContainer(container) {
        if (container instanceof Element) {
            return container;
        }

        if (typeof container === 'string' && container.trim() !== '') {
            return document.querySelector(container);
        }

        return document.getElementById('global-error-region');
    }

    function buildSupportUrl(message, context) {
        var params = new URLSearchParams();
        var pageUrl = window.location.pathname + window.location.search;

        if (context && context.action) {
            params.set('contexto', String(context.action));
        }

        params.set('url', context && context.url ? String(context.url) : pageUrl);
        params.set('mensagem', String(message || 'Erro inesperado.'));

        if (context && context.code) {
            params.set('codigo', String(context.code));
        }

        return '/app/suporte?' + params.toString();
    }

    function ensureRegion(region) {
        if (!region) {
            return null;
        }

        if (!region.classList.contains('space-y-3')) {
            region.classList.add('space-y-3');
        }

        return region;
    }

    function clearInlineError(container) {
        var region = resolveContainer(container);
        if (!region) {
            return;
        }

        region.innerHTML = '';
    }

    function showInlineError(container, options) {
        var region = ensureRegion(resolveContainer(container));
        var opts = options || {};
        var message = (opts.message || '').trim();

        if (!region || !message) {
            return null;
        }

        region.innerHTML = '';

        var card = document.createElement('div');
        card.className = 'bg-white rounded border border-gray-300 p-4 border-l-4 border-l-red-500';
        card.setAttribute('role', 'alert');

        var messageNode = document.createElement('p');
        messageNode.className = 'text-sm text-gray-700 mb-3';
        messageNode.textContent = message;
        card.appendChild(messageNode);

        var helperNode = document.createElement('p');
        helperNode.className = 'text-sm text-gray-600 mb-4';
        helperNode.textContent = 'Se o problema persistir, contate o suporte.';
        card.appendChild(helperNode);

        var actions = document.createElement('div');
        actions.className = 'flex flex-wrap gap-2';

        if (typeof opts.retryFn === 'function') {
            var retryButton = document.createElement('button');
            retryButton.type = 'button';
            retryButton.dataset.retry = 'true';
            retryButton.className = 'px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50';
            retryButton.textContent = 'Tentar novamente';
            retryButton.addEventListener('click', opts.retryFn);
            actions.appendChild(retryButton);
        }

        var supportLink = document.createElement('a');
        supportLink.href = buildSupportUrl(message, opts.context || {});
        supportLink.className = 'px-4 py-2 rounded text-white text-sm font-medium';
        supportLink.style.backgroundColor = '#1f2937';
        supportLink.textContent = 'Ir para Suporte';
        supportLink.setAttribute('data-link', 'true');
        actions.appendChild(supportLink);

        card.appendChild(actions);
        region.appendChild(card);

        return card;
    }

    window.showInlineError = showInlineError;
    window.clearInlineError = clearInlineError;
})();
