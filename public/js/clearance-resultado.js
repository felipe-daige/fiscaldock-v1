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
    const pollResult = root.dataset.pollResult === '1';
    const initialProgressSnapshot = parseProgressSnapshot(root.dataset.progressSnapshot || '');

    const progressBar = document.getElementById('clearance-resultado-bar');
    const progressPercent = document.getElementById('clearance-resultado-percent');
    const progressMessage = document.getElementById('clearance-resultado-mensagem');
    const progressStepLabel = document.getElementById('clearance-resultado-etapa-label');
    const stepsContainer = document.getElementById('clearance-resultado-steps');
    const knownStepLabels = {};

    let currentEventSource = null;
    let currentTimeoutHandle = null;
    let currentPollHandle = null;
    let reloadScheduled = false;
    let lastProgressPercent = readCurrentProgressPercent();
    let lastProgressMessage = progressMessage ? progressMessage.textContent : '';
    let lastProgressStepLabel = progressStepLabel && !progressStepLabel.classList.contains('hidden')
        ? progressStepLabel.textContent
        : '';

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

    function updateProgress(percent, message, stepLabel) {
        const value = Math.max(0, Math.min(100, Number(percent) || 0));
        lastProgressPercent = value;

        if (progressBar) progressBar.style.width = value + '%';
        if (progressPercent) progressPercent.textContent = value + '%';
        if (message && progressMessage) {
            progressMessage.textContent = message;
            lastProgressMessage = message;
        }

        if (!progressStepLabel) return;

        if (stepLabel) {
            progressStepLabel.textContent = stepLabel;
            progressStepLabel.classList.remove('hidden');
            lastProgressStepLabel = stepLabel;
            return;
        }

        progressStepLabel.textContent = '';
        progressStepLabel.classList.add('hidden');
    }

    function readCurrentProgressPercent() {
        if (!progressPercent) return 0;

        const value = parseInt(String(progressPercent.textContent || '').replace('%', ''), 10);
        return Number.isNaN(value) ? 0 : Math.max(0, Math.min(100, value));
    }

    function getDefaultStepLabel(index, totalSteps) {
        switch (Number(totalSteps)) {
        case 4:
            switch (Number(index)) {
            case 1: return 'Preparando consulta';
            case 2: return 'Consultando NF-e na Receita Federal';
            case 3: return 'Consultando CT-e na Receita Federal';
            case 0: return 'Preparando resultados';
            default: return `Etapa ${index}`;
            }
        case 3:
            switch (Number(index)) {
            case 1: return 'Preparando consulta';
            case 2: return 'Consultando NF-e na Receita Federal';
            case 3: return 'Consultando CT-e na Receita Federal';
            default: return `Etapa ${index}`;
            }
        default:
            return `Etapa ${index}`;
        }
    }

    function rememberStepLabel(step, label) {
        const stepNumber = Number(step);
        if (Number.isNaN(stepNumber) || !label) return;
        knownStepLabels[String(stepNumber)] = String(label);
    }

    function getStepSequence(totalSteps) {
        if (Number(totalSteps) === 4) {
            return [1, 2, 3, 0];
        }

        const total = Math.max(Number(totalSteps) || 0, 0);

        return Array.from({ length: total }, (_, index) => index + 1);
    }

    function getStepPosition(step, totalSteps) {
        const sequence = getStepSequence(totalSteps);
        const stepNumber = Number(step);
        const index = sequence.indexOf(stepNumber);

        return index === -1 ? null : index;
    }

    function parseProgressSnapshot(raw) {
        if (!raw) return null;

        try {
            const parsed = JSON.parse(raw);
            return parsed && typeof parsed === 'object' ? parsed : null;
        } catch (_) {
            return null;
        }
    }

    function resolveProgressPercent(data) {
        const status = String(data && data.status ? data.status : 'processando');
        if (status === 'finalizado') return 100;

        if (!data || data.progresso === undefined || data.progresso === null || data.progresso === '') {
            return lastProgressPercent;
        }

        const rawPercent = Number(data.progresso);
        if (Number.isNaN(rawPercent)) return lastProgressPercent;

        return Math.max(0, Math.min(100, Math.round(rawPercent)));
    }

    function resolveProgressMessage(data) {
        if (data && typeof data.mensagem === 'string' && data.mensagem.trim() !== '') {
            return data.mensagem;
        }

        return lastProgressMessage || 'Processando...';
    }

    function resolveProgressStepLabel(data) {
        if (data && typeof data.etapa_label === 'string' && data.etapa_label.trim() !== '') {
            return data.etapa_label;
        }

        return lastProgressStepLabel || '';
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
            skipped: { pill: 'bg-gray-50 text-gray-300', icon: svgDash, style: '' },
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

    function normalizeTrailSteps(trailSteps) {
        if (!Array.isArray(trailSteps)) return [];

        return trailSteps
            .map((step) => {
                const etapa = Number(step && step.etapa);
                if (Number.isNaN(etapa)) return null;

                return {
                    etapa,
                    etapa_label: String(step && step.etapa_label ? step.etapa_label : getDefaultStepLabel(etapa, 4)),
                    status: String(step && step.status ? step.status : 'pending'),
                };
            })
            .filter(Boolean)
            .sort((a, b) => {
                const order = [1, 2, 3, 0];
                return order.indexOf(a.etapa) - order.indexOf(b.etapa);
            });
    }

    function renderTrailSteps(trailSteps) {
        if (!stepsContainer) return false;

        const normalized = normalizeTrailSteps(trailSteps);
        if (normalized.length < 2) return false;

        stepsContainer.classList.remove('hidden');
        stepsContainer.innerHTML = '';

        normalized.forEach((step) => {
            const chip = document.createElement('div');
            chip.className = 'etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400';
            chip.dataset.etapa = String(step.etapa);

            const icon = document.createElement('span');
            icon.className = 'etapa-icon flex items-center justify-center w-3.5 h-3.5';

            const text = document.createElement('span');
            text.textContent = step.etapa_label;

            chip.appendChild(icon);
            chip.appendChild(text);
            renderStepItem(chip, step.status);

            stepsContainer.appendChild(chip);
        });

        return true;
    }

    function resolveStepState(index, currentStep, progress, status, skippedSteps, totalSteps) {
        const skippedSet = skippedSteps instanceof Set ? skippedSteps : new Set();
        const stepPosition = getStepPosition(index, totalSteps);
        const currentPosition = getStepPosition(currentStep, totalSteps);

        switch (status) {
        case 'finalizado':
            return skippedSet.has(index) ? 'skipped' : 'done';
        case 'erro':
            if (skippedSet.has(index)) return 'skipped';
            if (currentPosition !== null && stepPosition !== null && stepPosition < currentPosition) return 'done';
            if (index === currentStep) return 'error';
            return 'pending';
        case 'concluido':
            if (skippedSet.has(index)) return 'skipped';
            if (currentPosition !== null && stepPosition !== null && stepPosition < currentPosition) return 'done';
            if (currentPosition !== null && stepPosition !== null && stepPosition > currentPosition) return 'pending';
            return 'done';
        default:
            if (skippedSet.has(index)) return 'skipped';
            if (currentPosition !== null && stepPosition !== null && stepPosition < currentPosition) return 'done';
            if (currentPosition !== null && stepPosition !== null && stepPosition > currentPosition) return 'pending';
            return progress >= 100 ? 'done' : 'current';
        }
    }

    function renderSteps(currentStep, totalSteps, currentLabel, progress, status, skippedSteps = []) {
        if (!stepsContainer) return;

        const sequence = getStepSequence(totalSteps);
        if (sequence.length < 2) {
            stepsContainer.classList.add('hidden');
            stepsContainer.innerHTML = '';
            return;
        }

        stepsContainer.classList.remove('hidden');
        stepsContainer.innerHTML = '';
        const skippedSet = new Set(
            Array.isArray(skippedSteps)
                ? skippedSteps.map((step) => Number(step)).filter((step) => !Number.isNaN(step))
                : []
        );

        for (const index of sequence) {
            const chip = document.createElement('div');
            const state = resolveStepState(index, Number(currentStep), Number(progress) || 0, status, skippedSet, totalSteps);
            const label = getStepLabel(index, totalSteps, Number(currentStep), currentLabel);

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
        if (reloadScheduled) return;
        reloadScheduled = true;
        stopPolling();
        window.setTimeout(() => {
            window.location.reload();
        }, delayMs);
    }

    function handleProgressSnapshot(data) {
        if (!data || typeof data !== 'object') return;

        const status = String(data.status || 'processando');
        const percent = resolveProgressPercent(data);
        const currentStep = Number(data.etapa || 0);
        const totalSteps = Number(data.total_etapas || 0);
        const currentMessage = resolveProgressMessage(data);
        const currentStepLabel = resolveProgressStepLabel(data);
        const skippedSteps = Array.isArray(data.etapas_puladas) ? data.etapas_puladas : [];
        const trailSteps = Array.isArray(data.trilha_etapas) ? data.trilha_etapas : null;
        rememberStepLabel(currentStep, data.etapa_label || null);

        if (status === 'processando') {
            updateProgress(percent, currentMessage, currentStepLabel);
            if (!renderTrailSteps(trailSteps)) {
                renderSteps(currentStep, totalSteps, currentStepLabel || null, percent, status, skippedSteps);
            }
            return;
        }

        if (status === 'concluido') {
            updateProgress(percent, currentMessage, currentStepLabel);
            if (!renderTrailSteps(trailSteps)) {
                renderSteps(currentStep, totalSteps, currentStepLabel || null, 100, status, skippedSteps);
            }
            return;
        }

        if (status === 'finalizado') {
            updateProgress(100, currentMessage || 'Resultados prontos', currentStepLabel);
            if (!renderTrailSteps(trailSteps)) {
                renderSteps(currentStep, totalSteps || currentStep, currentStepLabel || null, 100, status, skippedSteps);
            }
            closeEventSource();

            if (awaitResult && jsonUrl) {
                pollResultReadiness();
                return;
            }

            scheduleReload(500);
            return;
        }

        if (status === 'erro' || status === 'timeout') {
            updateProgress(percent, currentMessage, currentStepLabel);
            if (!renderTrailSteps(trailSteps)) {
                renderSteps(currentStep, totalSteps, currentStepLabel || null, percent, status, skippedSteps);
            }
            closeEventSource();
            scheduleReload(500);
        }
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
            const resultadoPronto = data.resultado_pronto === true;
            const isErro = data.status_lote === 'erro';

            if ((response.ok && resultadoPronto) || isErro) {
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

            handleProgressSnapshot(data);
        };
    }

    if (statusInicial === 'processando' || statusInicial === 'pendente') {
        handleProgressSnapshot(initialProgressSnapshot);
        openProgressStream();
        if (pollResult && jsonUrl) {
            pollResultReadiness();
        }
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
