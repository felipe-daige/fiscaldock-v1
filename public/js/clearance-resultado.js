function initClearanceResultado() {
    const root = document.getElementById('clearance-resultado-root');
    if (!root) return;
    if (root.dataset.clearanceResultadoInitialized === '1') return;
    root.dataset.clearanceResultadoInitialized = '1';

    const statusInicial = String(root.dataset.status || 'pendente');
    const tabId = root.dataset.tabId || '';
    const streamUrl = root.dataset.streamUrl || '';
    const jsonUrl = root.dataset.jsonUrl || '';
    const awaitResult = root.dataset.awaitResult === '1';

    const progressBar = document.getElementById('clearance-resultado-bar');
    const progressPercent = document.getElementById('clearance-resultado-percent');
    const progressEtapa = document.getElementById('clearance-resultado-etapa');
    const stepsContainer = document.getElementById('clearance-resultado-steps');
    const knownStepLabels = {};

    let currentEventSource = null;
    let currentTimeoutHandle = null;
    let currentPollHandle = null;

    window._cleanupFunctions = window._cleanupFunctions || {};
    window._cleanupFunctions.clearanceResultado = function () {
        closeEventSource();
        stopPolling();
    };

    function closeEventSource() {
        if (currentEventSource) {
            try { currentEventSource.close(); } catch (_) {}
            currentEventSource = null;
        }

        if (currentTimeoutHandle) {
            clearTimeout(currentTimeoutHandle);
            currentTimeoutHandle = null;
        }
    }

    function stopPolling() {
        if (currentPollHandle) {
            clearTimeout(currentPollHandle);
            currentPollHandle = null;
        }
    }

    function updateProgress(percent, label) {
        const value = Math.max(0, Math.min(100, Number(percent) || 0));

        if (progressBar) progressBar.style.width = value + '%';
        if (progressPercent) progressPercent.textContent = value + '%';
        if (label && progressEtapa) progressEtapa.textContent = label;
    }

    function getDefaultStepLabel(index, totalSteps) {
        if (totalSteps === 3) {
            if (index === 1) return 'Preparando consulta';
            if (index === 2) return 'Consulta oficial';
            if (index === 3) return 'Resultado';
        }

        return `Etapa ${index}`;
    }

    function rememberStepLabel(step, label) {
        const stepNumber = Number(step);
        if (!stepNumber || !label) return;
        knownStepLabels[String(stepNumber)] = String(label);
    }

    function getStepLabel(index, totalSteps, currentStep, currentLabel) {
        if (index === Number(currentStep) && currentLabel) {
            return currentLabel;
        }

        return knownStepLabels[String(index)] || getDefaultStepLabel(index, totalSteps);
    }

    function renderStepItem(item, status) {
        if (!item) return;

        const svgSpinner = '<svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>';
        const svgCheck = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
        const svgDash = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>';
        const svgX = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>';

        const estados = {
            pending: { pill: 'bg-gray-100 text-gray-400', icon: svgDash, style: '' },
            current: { pill: 'bg-gray-200 text-gray-700', icon: svgSpinner, style: '' },
            done: { pill: 'text-white', icon: svgCheck, style: 'background-color: #047857' },
            error: { pill: 'text-white', icon: svgX, style: 'background-color: #b91c1c' },
        };

        const estado = estados[status] || estados.pending;
        if (item.dataset.renderedStatus === status) return;

        item.className = `etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium ${estado.pill}`;
        item.style.cssText = estado.style;

        const iconEl = item.querySelector('.etapa-icon');
        if (iconEl) iconEl.innerHTML = estado.icon;

        item.dataset.renderedStatus = status;
    }

    function resolveStepState(index, currentStep, progress, status) {
        if (status === 'finalizado') return 'done';
        if (status === 'erro') {
            if (index < currentStep) return 'done';
            if (index === currentStep) return 'error';
            return 'pending';
        }

        if (index < currentStep) return 'done';
        if (index > currentStep) return 'pending';

        if (status === 'concluido') return 'done';

        return progress >= 100 ? 'done' : 'current';
    }

    function renderSteps(currentStep, totalSteps, currentLabel, progress, status) {
        if (!stepsContainer) return;

        const total = Math.max(Number(totalSteps) || 0, Number(currentStep) || 0);
        if (total < 2) {
            stepsContainer.classList.add('hidden');
            stepsContainer.innerHTML = '';
            return;
        }

        stepsContainer.classList.remove('hidden');
        stepsContainer.innerHTML = '';

        for (let index = 1; index <= total; index += 1) {
            const chip = document.createElement('div');
            const state = resolveStepState(index, Number(currentStep), Number(progress) || 0, status);
            const label = getStepLabel(index, total, currentStep, currentLabel);

            chip.className = 'etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400';
            chip.dataset.etapa = String(index);

            const icon = document.createElement('span');
            icon.className = 'etapa-icon flex items-center justify-center w-3.5 h-3.5';

            const text = document.createElement('span');
            text.textContent = label;

            chip.appendChild(icon);
            chip.appendChild(text);
            renderStepItem(chip, state);

            stepsContainer.appendChild(chip);
        }
    }

    function scheduleReload(delayMs) {
        stopPolling();
        window.setTimeout(() => {
            window.location.reload();
        }, delayMs);
    }

    async function pollResultReadiness() {
        if (!jsonUrl) return;

        stopPolling();

        try {
            const response = await fetch(jsonUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json().catch(() => ({}));
            const hasNota = !!data.nota;
            const hasResultados = Number(data.total_resultados || 0) > 0;
            const isErro = data.status_lote === 'erro';

            if ((response.ok && (hasNota || hasResultados)) || isErro) {
                scheduleReload(250);
                return;
            }
        } catch (_) {
            // Mantém o polling silencioso e tenta novamente.
        }

        currentPollHandle = window.setTimeout(pollResultReadiness, 3000);
    }

    function openProgressStream() {
        if (!tabId || !streamUrl) return;

        closeEventSource();

        currentEventSource = new EventSource(`${streamUrl}?tab_id=${encodeURIComponent(tabId)}`, { withCredentials: true });

        currentTimeoutHandle = window.setTimeout(() => {
            closeEventSource();
            scheduleReload(250);
        }, 120000);

        currentEventSource.onmessage = (event) => {
            let data;
            try {
                data = JSON.parse(event.data);
            } catch (_) {
                return;
            }

            const status = String(data.status || 'processando');
            const percent = Number(data.progresso || 0);
            const currentStep = Number(data.etapa || 0);
            const totalSteps = Number(data.total_etapas || 0);
            const currentLabel = data.etapa_label || data.mensagem || 'Processando...';
            rememberStepLabel(currentStep, data.etapa_label || null);

            if (status === 'processando') {
                updateProgress(percent, currentLabel);
                renderSteps(currentStep, totalSteps, data.etapa_label || null, percent, status);
                return;
            }

            if (status === 'concluido') {
                updateProgress(100, currentLabel);
                renderSteps(currentStep, totalSteps, data.etapa_label || null, 100, status);
                return;
            }

            if (status === 'finalizado') {
                updateProgress(100, 'Finalizado, carregando resultado...');
                renderSteps(totalSteps || currentStep, totalSteps || currentStep, data.etapa_label || null, 100, status);
                closeEventSource();

                if (awaitResult && jsonUrl) {
                    pollResultReadiness();
                    return;
                }

                scheduleReload(500);
                return;
            }

            if (status === 'erro' || status === 'timeout') {
                renderSteps(currentStep, totalSteps, data.etapa_label || null, percent, status);
                closeEventSource();
                scheduleReload(500);
            }
        };
    }

    if (statusInicial === 'processando' || statusInicial === 'pendente') {
        openProgressStream();
        return;
    }

    if (awaitResult && jsonUrl) {
        pollResultReadiness();
    }
}

window.initClearanceResultado = initClearanceResultado;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initClearanceResultado);
} else {
    initClearanceResultado();
}
