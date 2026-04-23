const TIER_STORAGE_KEY = 'clearance:tier';
const SELECTION_STORAGE_KEY = 'clearance:selection';
const SORT_SCROLL_STORAGE_KEY = 'clearance:sort-scroll-y';
const TIERS_VALIDOS = ['basico', 'full'];
const TIER_LABEL = { basico: 'Básico', full: 'Full' };

let idsUrl = '';
let validarUrl = '';
let temMaisPagina = false;
let saldoAtual = 0;
let custos = { basico: 10, full: 20 };

let selecionados = new Set();
let origens = new Map();
let tierSelecionado = 'basico';
let currentEventSource = null;
let currentSseTimeout = null;

function $(id) { return document.getElementById(id); }

function gerarTabId() {
    return 'clearance-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
}

function abrirModal(el) { if (el) el.classList.remove('hidden'); }
function fecharModal(el) { if (el) el.classList.add('hidden'); }

function mostrarProgresso(percent, etapa) {
    const bloco = $('clearance-progresso');
    if (!bloco) return;
    bloco.classList.remove('hidden');
    const value = Math.max(0, Math.min(100, Number(percent) || 0));
    const bar = $('clearance-progresso-bar');
    const pct = $('clearance-progresso-percent');
    const et = $('clearance-progresso-etapa');
    if (bar) bar.style.width = value + '%';
    if (pct) pct.textContent = value + '%';
    if (etapa && et) et.textContent = etapa;
}

function esconderProgresso() {
    const bloco = $('clearance-progresso');
    if (bloco) bloco.classList.add('hidden');
}

function fecharSse() {
    if (currentEventSource) {
        try { currentEventSource.close(); } catch (e) {}
        currentEventSource = null;
    }
    if (currentSseTimeout) {
        clearTimeout(currentSseTimeout);
        currentSseTimeout = null;
    }
}

function getSystemError(payload, action, overrides) {
    if (!window.SystemCriticalError) {
        return null;
    }

    return window.SystemCriticalError.fromPayload(payload || {}, {
        title: overrides && overrides.title,
        message: overrides && overrides.message,
        context: {
            action: action || 'clearance-notas',
            url: window.location.pathname + window.location.search
        }
    });
}

function abrirSseProgresso(tabId) {
    fecharSse();
    const url = '/app/consulta/progresso/stream?tab_id=' + encodeURIComponent(tabId);
    const es = new EventSource(url, { withCredentials: true });
    currentEventSource = es;

    currentSseTimeout = setTimeout(() => {
        fecharSse();
        esconderProgresso();
        showError(
            'A validação não foi concluída dentro do tempo esperado.',
            'clearance-sse-timeout',
            getSystemError({ status: 'timeout' }, 'clearance-sse-timeout')
        );
    }, 120000);

    es.onmessage = (event) => {
        let data;
        try { data = JSON.parse(event.data); } catch (_) { return; }
        const status = data.status || 'processando';
        const progresso = Number(data.progresso || 0);
        const etapa = data.etapa_label || data.mensagem || 'Processando...';

        if (status === 'processando') {
            mostrarProgresso(progresso, etapa);
            return;
        }

        if (status === 'concluido') {
            mostrarProgresso(100, etapa);
            return;
        }

        if (status === 'finalizado') {
            mostrarProgresso(100, 'Clearance finalizado, atualizando…');
            fecharSse();
            limparSelecaoStorage();
            window.location.reload();
            return;
        }
        if (status === 'erro') {
            fecharSse();
            esconderProgresso();
            showError(
                (getSystemError(data, 'clearance-sse-erro') || {}).message || 'Falha no processamento.',
                'clearance-sse-erro',
                getSystemError(data, 'clearance-sse-erro')
            );
            return;
        }
        if (status === 'timeout') {
            fecharSse();
            esconderProgresso();
            showError(
                (getSystemError(data, 'clearance-sse-timeout') || {}).message || 'A validação excedeu o tempo esperado.',
                'clearance-sse-timeout',
                getSystemError(data, 'clearance-sse-timeout')
            );
        }
    };

    es.onerror = () => {
        // Se o SSE cair mas ainda estiver processando, o timeout global cobre
    };
}

function tierInicial() {
    try {
        const saved = localStorage.getItem(TIER_STORAGE_KEY);
        if (saved && TIERS_VALIDOS.includes(saved)) return saved;
    } catch (e) {}
    return 'basico';
}

function persistirTier(tier) {
    try { localStorage.setItem(TIER_STORAGE_KEY, tier); } catch (e) {}
}

function carregarSelecaoDoStorage() {
    try {
        const raw = sessionStorage.getItem(SELECTION_STORAGE_KEY);
        if (!raw) return;
        const data = JSON.parse(raw);
        if (!data || !Array.isArray(data.ids)) return;
        data.ids.forEach((id) => selecionados.add(parseInt(id, 10)));
        const mapa = data.origens || {};
        Object.keys(mapa).forEach((id) => origens.set(parseInt(id, 10), mapa[id]));
    } catch (e) {}
}

function persistirSelecao() {
    try {
        if (selecionados.size === 0) {
            sessionStorage.removeItem(SELECTION_STORAGE_KEY);
            return;
        }
        const mapa = {};
        selecionados.forEach((id) => { mapa[id] = origens.get(id) || 'xml'; });
        sessionStorage.setItem(SELECTION_STORAGE_KEY, JSON.stringify({
            ids: Array.from(selecionados),
            origens: mapa,
        }));
    } catch (e) {}
}

function limparSelecaoStorage() {
    try { sessionStorage.removeItem(SELECTION_STORAGE_KEY); } catch (e) {}
}

function getCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function chkNotas() {
    return Array.from(document.querySelectorAll('.chk-nota'));
}

function queryFiltros() {
    const form = $('validacao-filtros-form');
    if (!form) return '';
    const fd = new FormData(form);
    const params = new URLSearchParams();
    for (const [k, v] of fd.entries()) if (v) params.append(k, v);
    return params.toString();
}

function sortLinks() {
    return Array.from(document.querySelectorAll('[data-clearance-preserve-scroll]'));
}

function storeSortScroll() {
    try {
        sessionStorage.setItem(SORT_SCROLL_STORAGE_KEY, String(window.scrollY || 0));
    } catch (e) {}
}

function restoreSortScroll() {
    try {
        const raw = sessionStorage.getItem(SORT_SCROLL_STORAGE_KEY);
        if (raw === null) return;

        sessionStorage.removeItem(SORT_SCROLL_STORAGE_KEY);

        const scrollY = parseInt(raw, 10);
        if (Number.isNaN(scrollY) || scrollY < 0) return;

        window.requestAnimationFrame(() => {
            window.scrollTo(0, scrollY);
        });
    } catch (e) {}
}

function showSortLoading() {
    const overlay = $('clearance-sort-loading');
    if (overlay) overlay.classList.remove('hidden');
}

function hideSortLoading() {
    const overlay = $('clearance-sort-loading');
    if (overlay) overlay.classList.add('hidden');
}

function origemDoCheckbox(chk) {
    const tr = chk.closest('tr');
    return tr && tr.dataset ? tr.dataset.origem : 'xml';
}

function registrarOrigens() {
    chkNotas().forEach((chk) => {
        const id = parseInt(chk.value, 10);
        origens.set(id, origemDoCheckbox(chk));
    });
}

function selecionadosArray() {
    return Array.from(selecionados);
}

function origensSelecionadas() {
    const map = {};
    selecionados.forEach((id) => {
        map[id] = origens.get(id) || 'xml';
    });
    return map;
}

function selecionarTier(tier) {
    if (!TIERS_VALIDOS.includes(tier)) return;
    tierSelecionado = tier;
    persistirTier(tier);

    TIERS_VALIDOS.forEach((t) => {
        const card = $('plan-card-' + t);
        if (!card) return;
        const ativo = t === tier;
        card.setAttribute('aria-checked', ativo ? 'true' : 'false');
        card.style.borderColor = ativo ? '#1f2937' : '#e5e7eb';
        const chip = card.querySelector('.plan-chip');
        if (chip) chip.classList.toggle('hidden', !ativo);
    });

    atualizarCusto();
}

function atualizarCusto() {
    const n = selecionados.size;

    document.querySelectorAll('.plan-total').forEach((el) => {
        const tier = el.dataset.tier;
        el.textContent = String(n * (custos[tier] || 0));
    });

    const total = n * (custos[tierSelecionado] || 0);
    const saldoApos = saldoAtual - total;
    const insuficiente = saldoApos < 0;

    const saldoAposLabel = $('saldo-apos-label');
    if (saldoAposLabel) {
        saldoAposLabel.textContent = `${saldoApos} créditos`;
        if (insuficiente) {
            saldoAposLabel.style.backgroundColor = '#fee2e2';
            saldoAposLabel.style.color = '#b91c1c';
        } else {
            saldoAposLabel.style.backgroundColor = '#e5e7eb';
            saldoAposLabel.style.color = '#374151';
        }
    }

    const btnValidar = $('btn-validar');
    if (btnValidar) {
        if (n === 0) {
            btnValidar.textContent = 'Validar';
            btnValidar.disabled = true;
        } else if (insuficiente) {
            btnValidar.textContent = 'Saldo insuficiente';
            btnValidar.disabled = true;
        } else {
            const label = TIER_LABEL[tierSelecionado] || tierSelecionado;
            btnValidar.textContent = `Validar ${n} nota(s) com Clearance ${label} · ${total} créditos`;
            btnValidar.disabled = false;
        }
    }
}

function atualizarSelecao() {
    chkNotas().forEach((chk) => {
        chk.checked = selecionados.has(parseInt(chk.value, 10));
    });
    const n = selecionados.size;

    const selLabel = $('selecao-label');
    if (selLabel) {
        selLabel.textContent = n === 0
            ? 'Nenhuma nota selecionada'
            : `${n} nota(s) selecionada(s)`;
    }

    const visiveis = chkNotas();
    const todasVisSelecionadas = visiveis.length > 0 && visiveis.every((c) => c.checked);
    const chkMaster = $('chk-master');
    if (chkMaster) {
        chkMaster.checked = todasVisSelecionadas;
        chkMaster.indeterminate = !todasVisSelecionadas && visiveis.some((c) => c.checked);
    }

    const btnSelTodas = $('btn-selecionar-todas');
    if (btnSelTodas) {
        btnSelTodas.textContent = n > 0 ? 'Desselecionar Todos' : 'Selecionar Todos';
        if (temMaisPagina || n > 0 || todasVisSelecionadas) {
            btnSelTodas.classList.remove('hidden');
        } else {
            btnSelTodas.classList.add('hidden');
        }
    }

    const planosContainer = $('clearance-planos');
    if (planosContainer) {
        planosContainer.classList.toggle('hidden', n === 0);
    }

    atualizarCusto();
}

function showError(message, action, criticalError) {
    const errorRegion = $('clearance-notas-error');
    if (window.showInlineError) {
        window.showInlineError(errorRegion, {
            message,
            criticalError: criticalError || undefined,
            context: {
                action,
                url: window.location.pathname + window.location.search,
            },
        });
        return;
    }

    alert(message);
}

function limparErroInline() {
    const errorRegion = $('clearance-notas-error');
    if (window.clearInlineError) {
        window.clearInlineError(errorRegion);
    }
}

async function executarClearance() {
    const ids = selecionadosArray();
    const modalConfirm = $('modal-confirmar-validacao');
    if (ids.length === 0) {
        fecharModal(modalConfirm);
        console.warn('[clearance] executarClearance abortado: seleção vazia', {
            sessionStorage: sessionStorage.getItem(SELECTION_STORAGE_KEY),
            tier: tierSelecionado,
        });
        showError('Sua seleção foi perdida. Selecione as notas novamente e tente de novo.', 'clearance-selecao-vazia');
        return;
    }

    const modalConfirmOk = $('modal-confirm-ok');
    const btnValidar = $('btn-validar');

    if (modalConfirmOk) modalConfirmOk.disabled = true;
    if (btnValidar) btnValidar.disabled = true;
    limparErroInline();

    const tabId = gerarTabId();
    try {
        const resp = await fetch(validarUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrf(),
            },
            body: JSON.stringify({
                nota_ids: ids,
                origens: origensSelecionadas(),
                tipo: tierSelecionado,
                tab_id: tabId,
            }),
        });
        const data = await resp.json();
        console.debug('[clearance] resposta do backend', {
            status: resp.status,
            ok: resp.ok,
            webhook_disparado: data?.webhook_disparado,
            creditos_utilizados: data?.creditos_utilizados,
            data,
        });
        if (resp.ok) {
            fecharModal(modalConfirm);
            if (data.webhook_disparado) {
                if (data.resultado_url) {
                    limparSelecaoStorage();
                    window.location.assign(data.resultado_url);
                    return;
                }

                mostrarProgresso(5, 'Clearance despachado, aguardando provedor...');
                abrirSseProgresso(data.tab_id || tabId);
            } else {
                const modalSucesso = $('modal-sucesso-validacao');
                const modalSucessoCreditos = $('modal-sucesso-creditos');
                if (modalSucessoCreditos) modalSucessoCreditos.textContent = String(data.creditos_utilizados ?? 0);
                abrirModal(modalSucesso);
            }
        } else if (resp.status === 402) {
            fecharModal(modalConfirm);
            showError(`Créditos insuficientes. Necessário: ${data.custo_necessario}. Saldo: ${data.saldo_atual}.`, 'clearance-validar');
        } else if (resp.status === 502) {
            fecharModal(modalConfirm);
            showError(data.error || 'Webhook de clearance indisponível. Créditos estornados.', 'clearance-webhook');
        } else {
            fecharModal(modalConfirm);
            showError(data.message || data.error || 'Falha ao validar notas.', 'clearance-validar');
        }
    } catch (err) {
        console.error('[clearance] falha no fetch de validar', err);
        fecharModal(modalConfirm);
        showError('Erro de rede ao validar.', 'clearance-validar');
    } finally {
        if (modalConfirmOk) modalConfirmOk.disabled = false;
        atualizarCusto();
    }
}

async function handleSelecionarTodas() {
    const btnSelTodas = $('btn-selecionar-todas');
    if (selecionados.size > 0) {
        selecionados.clear();
        persistirSelecao();
        atualizarSelecao();
        return;
    }
    if (btnSelTodas) btnSelTodas.disabled = true;
    try {
        const resp = await fetch(`${idsUrl}?${queryFiltros()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await resp.json();
        if (data.success) {
            const mapa = data.origens || {};
            (data.ids || []).forEach((id) => {
                selecionados.add(id);
                origens.set(id, mapa[id] || mapa[String(id)] || 'xml');
            });
            persistirSelecao();
            atualizarSelecao();
        }
    } finally {
        if (btnSelTodas) btnSelTodas.disabled = false;
    }
}

function handleValidarClick() {
    const ids = selecionadosArray();
    if (ids.length === 0) return;
    const total = ids.length * (custos[tierSelecionado] || 0);
    const saldoApos = saldoAtual - total;
    const label = TIER_LABEL[tierSelecionado] || tierSelecionado;

    const modalConfirmQtd = $('modal-confirm-qtd');
    const modalConfirmCusto = $('modal-confirm-custo');
    const modalConfirmTierLabel = $('modal-confirm-tier-label');
    const modalConfirmTierChip = $('modal-confirm-tier-chip');
    const modalConfirmSaldoApos = $('modal-confirm-saldo-apos');

    if (modalConfirmQtd) modalConfirmQtd.textContent = String(ids.length);
    if (modalConfirmCusto) modalConfirmCusto.textContent = String(total);
    if (modalConfirmTierLabel) modalConfirmTierLabel.textContent = `Clearance ${label}`;
    if (modalConfirmTierChip) modalConfirmTierChip.textContent = label;
    if (modalConfirmSaldoApos) {
        modalConfirmSaldoApos.textContent = `${saldoApos} créditos`;
        modalConfirmSaldoApos.style.color = saldoApos < 0 ? '#b91c1c' : '#111827';
    }

    abrirModal($('modal-confirmar-validacao'));
}

function handleChkMaster() {
    const chkMaster = $('chk-master');
    if (!chkMaster) return;
    chkNotas().forEach((chk) => {
        const id = parseInt(chk.value, 10);
        if (chkMaster.checked) {
            origens.set(id, origemDoCheckbox(chk));
            selecionados.add(id);
        } else {
            selecionados.delete(id);
        }
    });
    persistirSelecao();
    atualizarSelecao();
}

function onDocumentChange(e) {
    const target = e.target;
    if (!target || !target.matches) return;

    if (target.matches('.chk-nota')) {
        const id = parseInt(target.value, 10);
        origens.set(id, origemDoCheckbox(target));
        if (target.checked) selecionados.add(id);
        else selecionados.delete(id);
        persistirSelecao();
        atualizarSelecao();
        return;
    }

    if (target.id === 'chk-master') {
        handleChkMaster();
    }
}

function onDocumentClick(e) {
    const target = e.target;
    if (!target || !target.closest) return;

    const toggle = target.closest('.nota-details-toggle');
    if (toggle) {
        e.preventDefault();
        const id = toggle.dataset.notaId;
        if (!id) return;
        const row = document.querySelector(`tr.nota-expand-row[data-expand-for="${id}"]`);
        if (!row) return;
        row.classList.toggle('hidden');
        const isOpen = !row.classList.contains('hidden');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        const chevron = toggle.querySelector('.nota-details-chevron');
        if (chevron) chevron.style.transform = isOpen ? 'rotate(180deg)' : '';
        return;
    }

    if (target.closest('#btn-validar')) {
        handleValidarClick();
        return;
    }

    if (target.closest('#btn-selecionar-todas')) {
        handleSelecionarTodas();
        return;
    }

    if (target.closest('#modal-confirm-ok')) {
        console.debug('[clearance] modal-confirm-ok clicado', {
            selecionadosSize: selecionados.size,
            validarUrl,
            tier: tierSelecionado,
        });
        executarClearance();
        return;
    }

    if (target.closest('#modal-confirm-cancelar')) {
        fecharModal($('modal-confirmar-validacao'));
        return;
    }

    if (target.closest('#modal-sucesso-ok')) {
        fecharModal($('modal-sucesso-validacao'));
        limparSelecaoStorage();
        window.location.reload();
        return;
    }

    if (target.id === 'modal-confirmar-validacao') {
        fecharModal(target);
        return;
    }

    const cardBasico = target.closest('#plan-card-basico');
    if (cardBasico) { selecionarTier('basico'); return; }
    const cardFull = target.closest('#plan-card-full');
    if (cardFull) { selecionarTier('full'); return; }
}

function onDocumentKeydown(e) {
    if (e.key !== ' ' && e.key !== 'Enter') return;
    const target = e.target;
    if (!target || !target.closest) return;
    const cardBasico = target.closest('#plan-card-basico');
    if (cardBasico) { e.preventDefault(); selecionarTier('basico'); return; }
    const cardFull = target.closest('#plan-card-full');
    if (cardFull) { e.preventDefault(); selecionarTier('full'); return; }
}

function initClearanceNotas() {
    const root = $('validacao-notas-container');
    if (!root) return;

    fecharSse();
    hideSortLoading();

    idsUrl = root.dataset.idsUrl || '';
    validarUrl = root.dataset.validarUrl || '';
    temMaisPagina = root.dataset.temMaisPagina === '1';
    saldoAtual = parseInt(root.dataset.saldoAtual || '0', 10);
    custos = {
        basico: parseInt(root.dataset.custoBasico || '10', 10),
        full: parseInt(root.dataset.custoFull || '20', 10),
    };

    selecionados = new Set();
    origens = new Map();
    tierSelecionado = tierInicial();

    document.removeEventListener('change', onDocumentChange);
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onDocumentKeydown);
    document.addEventListener('change', onDocumentChange);
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onDocumentKeydown);

    sortLinks().forEach((link) => {
        link.removeEventListener('click', storeSortScroll);
        link.removeEventListener('click', showSortLoading);
        link.addEventListener('click', storeSortScroll);
        link.addEventListener('click', showSortLoading);
    });

    window._cleanupFunctions = window._cleanupFunctions || {};
    window._cleanupFunctions.clearanceNotas = () => {
        fecharSse();
        esconderProgresso();
        hideSortLoading();
        sortLinks().forEach((link) => {
            link.removeEventListener('click', storeSortScroll);
            link.removeEventListener('click', showSortLoading);
        });
    };

    carregarSelecaoDoStorage();
    restoreSortScroll();
    registrarOrigens();
    selecionarTier(tierSelecionado);
    atualizarSelecao();
}

window.initClearanceNotas = initClearanceNotas;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initClearanceNotas);
} else {
    initClearanceNotas();
}
