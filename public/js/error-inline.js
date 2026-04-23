(function () {
    'use strict';

    function getSupportConfig() {
        var config = window.systemSupportConfig || {};

        return {
            whatsappUrl: String(config.whatsappUrl || 'https://wa.me/5567999844366'),
            contactLabel: String(config.contactLabel || 'Falar com o suporte')
        };
    }

    function normalizeContext(context) {
        return context && typeof context === 'object' ? context : {};
    }

    function buildSupportMessage(context) {
        var normalizedContext = normalizeContext(context);
        var pageUrl = normalizedContext.url || (window.location.pathname + window.location.search);
        var lines = [
            'Olá, preciso de suporte com uma falha de processamento na FiscalDock.'
        ];

        if (normalizedContext.action) {
            lines.push('Contexto: ' + String(normalizedContext.action));
        }

        if (normalizedContext.reference) {
            lines.push('Referência: ' + String(normalizedContext.reference));
        }

        if (pageUrl) {
            lines.push('Página: ' + String(pageUrl));
        }

        return lines.join('\n');
    }

    function buildSupportUrl(context) {
        var config = getSupportConfig();
        var separator = config.whatsappUrl.indexOf('?') === -1 ? '?' : '&';

        return config.whatsappUrl + separator + 'text=' + encodeURIComponent(buildSupportMessage(context));
    }

    function buildCriticalError(options) {
        var config = getSupportConfig();
        var opts = options && typeof options === 'object' ? options : {};
        var context = normalizeContext(opts.context);
        var title = String(opts.title || 'Falha no processamento').trim();
        var message = String(
            opts.message || 'Ocorreu uma instabilidade interna ao processar sua solicitação. Nosso suporte pode ajudar você a concluir essa etapa.'
        ).trim();

        return {
            title: title,
            message: message,
            actionLabel: String(opts.actionLabel || config.contactLabel || 'Falar com o suporte').trim(),
            actionUrl: String(opts.actionUrl || buildSupportUrl(context)).trim(),
            actionTarget: String(opts.actionTarget || '_blank').trim(),
            actionRel: String(opts.actionRel || 'noopener noreferrer').trim(),
            context: context
        };
    }

    function criticalErrorFromPayload(payload, options) {
        var opts = options && typeof options === 'object' ? options : {};
        var data = payload && typeof payload === 'object' ? payload : {};
        var uiError = data.ui_error && typeof data.ui_error === 'object' ? data.ui_error : {};
        var status = String(data.status || opts.status || 'erro');
        var title = uiError.title || opts.title;
        var message = uiError.message || opts.message;

        if (!title) {
            title = status === 'timeout'
                ? 'Processamento indisponível no momento'
                : 'Falha no processamento';
        }

        if (!message) {
            message = status === 'timeout'
                ? 'A solicitação levou mais tempo do que o esperado para ser concluída. Nosso suporte pode acompanhar esse caso com você.'
                : 'Ocorreu uma instabilidade interna ao processar sua solicitação. Nosso suporte pode ajudar você a concluir essa etapa.';
        }

        return buildCriticalError({
            title: title,
            message: message,
            actionLabel: uiError.action_label || opts.actionLabel,
            actionUrl: uiError.action_url || opts.actionUrl,
            actionTarget: uiError.action_target || opts.actionTarget,
            actionRel: uiError.action_rel || opts.actionRel,
            context: Object.assign({}, normalizeContext(opts.context), normalizeContext(uiError.context))
        });
    }

    function applyActionLink(link, criticalError) {
        if (!link || !criticalError) {
            return;
        }

        link.href = criticalError.actionUrl;
        link.target = criticalError.actionTarget;
        link.rel = criticalError.actionRel;
        link.textContent = criticalError.actionLabel;
    }

    function resolveContainer(container) {
        if (container instanceof Element) {
            return container;
        }

        if (typeof container === 'string' && container.trim() !== '') {
            return document.querySelector(container);
        }

        return document.getElementById('global-error-region');
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
        var criticalError = opts.criticalError
            ? buildCriticalError(opts.criticalError)
            : (opts.systemError ? criticalErrorFromPayload(opts.systemError, { context: opts.context }) : null);
        var message = (criticalError ? criticalError.message : (opts.message || '')).trim();

        if (!region || !message) {
            return null;
        }

        region.innerHTML = '';

        var card = document.createElement('div');
        card.className = 'bg-white rounded border border-gray-300 p-4 border-l-4 border-l-red-500';
        card.setAttribute('role', 'alert');

        if (criticalError && criticalError.title) {
            var titleNode = document.createElement('p');
            titleNode.className = 'text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2';
            titleNode.textContent = criticalError.title;
            card.appendChild(titleNode);
        }

        var messageNode = document.createElement('p');
        messageNode.className = 'text-sm text-gray-700 mb-3';
        messageNode.textContent = message;
        card.appendChild(messageNode);

        if (!criticalError) {
            var helperNode = document.createElement('p');
            helperNode.className = 'text-sm text-gray-600 mb-4';
            helperNode.textContent = 'Se o problema persistir, contate o suporte.';
            card.appendChild(helperNode);
        }

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
        supportLink.className = 'px-4 py-2 rounded text-white text-sm font-medium';
        supportLink.style.backgroundColor = '#1f2937';

        if (criticalError) {
            applyActionLink(supportLink, criticalError);
        } else {
            supportLink.href = buildSupportUrl(opts.context || {});
            supportLink.target = '_blank';
            supportLink.rel = 'noopener noreferrer';
            supportLink.textContent = getSupportConfig().contactLabel;
        }

        actions.appendChild(supportLink);
        card.appendChild(actions);
        region.appendChild(card);

        return card;
    }

    window.SystemCriticalError = {
        buildSupportUrl: buildSupportUrl,
        build: buildCriticalError,
        fromPayload: criticalErrorFromPayload,
        applyActionLink: applyActionLink
    };
    window.showInlineError = showInlineError;
    window.clearInlineError = clearInlineError;
})();
