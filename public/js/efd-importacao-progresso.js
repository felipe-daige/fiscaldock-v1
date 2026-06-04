(function () {
    'use strict';

    const container = document.getElementById('efd-progresso-root');
    if (!container || container.dataset.initialized === '1') return;
    container.dataset.initialized = '1';

    const tabId = container.dataset.tabId || '';
    const tipoEfd = container.dataset.tipo || '';
    const importacaoId = container.dataset.importacaoId || '';

    if (!importacaoId) {
        console.error('[EFD progresso] importacao_id ausente — abortando SSE');
        return;
    }

    const MAX_RECONEXOES = 3;
    const DELAY_RECONEXAO_BASE = 3000;

    const barra = document.getElementById('efd-progresso-bar');
    const porcentagem = document.getElementById('efd-progresso-percent');
    const etapaAtual = document.getElementById('efd-progresso-etapa');
    const metaEtapa = document.getElementById('efd-progresso-meta');

    let eventSource = null;
    let reconnectAttempts = 0;
    let reconnectTimer = null;
    let finalizado = false;
    let blocoAtual = null;

    let currentProgress = 0;
    let targetProgress = 0;
    let animFrameId = null;

    function animar() {
        if (currentProgress < targetProgress) {
            currentProgress = Math.min(currentProgress + 0.4, targetProgress);
            const pct = Math.round(currentProgress);
            if (barra) barra.style.width = pct + '%';
            if (porcentagem) porcentagem.textContent = pct + '%';
            animFrameId = requestAnimationFrame(animar);
        } else if (currentProgress > targetProgress) {
            currentProgress = targetProgress;
            const pct = Math.round(currentProgress);
            if (barra) barra.style.width = pct + '%';
            if (porcentagem) porcentagem.textContent = pct + '%';
            animFrameId = null;
        } else {
            animFrameId = null;
        }
    }

    function getBlocosPermitidos() {
        if (tipoEfd === 'fiscal') return ['participantes', 'notas_mercadorias', 'notas_transportes', 'catalogo', 'apuracao_icms'];
        if (tipoEfd === 'contrib') return ['participantes', 'notas_servicos', 'notas_mercadorias', 'catalogo', 'apuracao_pis_cofins', 'retencoes_fonte'];
        return ['participantes', 'notas_servicos', 'notas_mercadorias', 'notas_transportes', 'catalogo', 'apuracao_icms', 'retencoes_fonte', 'apuracao_pis_cofins'];
    }

    function renderEtapa(etapa, status) {
        const item = document.querySelector('.etapa-item[data-etapa="' + etapa + '"]');
        if (!item) return;

        const visual = (status === 'processando' || status === 'inicio')
            ? 'loading'
            : (status || 'pendente');

        if (item.dataset.renderedStatus === visual) return;

        const iconEl = item.querySelector('.etapa-icon');
        const svgSpinner = '<svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>';
        const svgCheck = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
        const svgDash = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>';

        const estados = {
            pendente: { pill: 'bg-gray-100 text-gray-400', icon: svgDash, style: '' },
            loading: { pill: 'bg-gray-200 text-gray-700', icon: svgSpinner, style: '' },
            concluido: { pill: 'text-white', icon: svgCheck, style: 'background-color: #047857' },
            erro: { pill: 'text-white', icon: '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>', style: 'background-color: #b91c1c' },
            skip: { pill: 'bg-gray-100 text-gray-400', icon: svgDash, style: '' },
        };

        const estado = estados[visual] || estados.pendente;
        item.className = 'etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium ' + estado.pill;
        item.style.cssText = estado.style;
        if (iconEl) iconEl.innerHTML = estado.icon;
        item.dataset.renderedStatus = visual;
    }

    function atualizarEtapas(payload) {
        const blocos = Object.assign({}, payload.notas_blocos || {});
        if (blocos['0'] && !blocos.participantes) blocos.participantes = blocos['0'];
        if (blocos['0200'] && !blocos.catalogo) blocos.catalogo = blocos['0200'];
        if (blocos['A'] && !blocos.notas_servicos) blocos.notas_servicos = blocos['A'];
        if (blocos['C'] && !blocos.notas_mercadorias) blocos.notas_mercadorias = blocos['C'];
        if (blocos['D'] && !blocos.notas_transportes) blocos.notas_transportes = blocos['D'];

        const isFinalConcluido = payload.status === 'concluido';
        const isFinalErro = payload.status === 'erro' || payload.status === 'timeout';
        const permitidos = getBlocosPermitidos();

        permitidos.forEach(function (b) {
            if (b === 'participantes') {
                const bp = blocos.participantes;
                if (bp) {
                    if (isFinalErro && (payload.bloco === 'participantes' || !payload.bloco)) {
                        renderEtapa('participantes', 'erro');
                        return;
                    }
                    const s = (bp.status === 'concluido' || parseInt(bp.progresso) === 100 || isFinalConcluido)
                        ? 'concluido' : bp.status;
                    renderEtapa('participantes', s);
                } else {
                    const temOutro = Object.keys(blocos).some(function (k) {
                        return k !== 'participantes' && permitidos.indexOf(k) !== -1;
                    });
                    renderEtapa('participantes', temOutro ? 'concluido' : 'processando');
                }
                return;
            }
            if (blocos[b]) {
                if (isFinalErro && payload.bloco === b) {
                    renderEtapa(b, 'erro');
                    return;
                }
                const eff = (blocos[b].status !== 'skip' && (isFinalConcluido || blocos[b].status === 'concluido' || parseInt(blocos[b].progresso) === 100))
                    ? 'concluido'
                    : blocos[b].status;
                renderEtapa(b, eff);
            } else if (isFinalConcluido) {
                renderEtapa(b, 'concluido');
            }
        });
    }

    function aplicar(payload) {
        const progresso = parseInt(payload.progresso) || 0;
        const status = payload.status || 'processando';
        const msg = payload.mensagem || 'Processando...';
        const dados = payload.dados || {};

        const bloco = payload.bloco || null;
        if (bloco !== blocoAtual) {
            blocoAtual = bloco;
            if (animFrameId !== null) {
                cancelAnimationFrame(animFrameId);
                animFrameId = null;
            }
            currentProgress = 0;
            if (barra) barra.style.width = '0%';
            if (porcentagem) porcentagem.textContent = '0%';
        }

        targetProgress = progresso;
        if (animFrameId === null) animFrameId = requestAnimationFrame(animar);

        if (etapaAtual) {
            etapaAtual.textContent = msg;
        }

        if (metaEtapa) {
            const tipo = dados.tipo_documento || '';
            const periodo = dados.data_inicial_do_documento && dados.data_final_do_documento
                ? dados.data_inicial_do_documento + ' - ' + dados.data_final_do_documento
                : '';
            const empresa = dados.nome_empresa || '';
            const texto = [empresa, tipo, periodo].filter(Boolean).join(' • ');
            metaEtapa.textContent = texto;
            metaEtapa.classList.toggle('hidden', !texto);
        }

        if (barra) {
            barra.style.backgroundColor = (status === 'erro' || status === 'timeout') ? '#b91c1c' : '#1f2937';
        }

        atualizarEtapas(payload);
    }

    function finalizar() {
        if (finalizado) return;
        finalizado = true;
        if (eventSource) {
            eventSource.close();
            eventSource = null;
        }
        if (reconnectTimer) {
            clearTimeout(reconnectTimer);
            reconnectTimer = null;
        }
        window.location.reload();
    }

    function conectar() {
        if (eventSource) eventSource.close();

        const url = '/app/importacao/efd/progresso/stream?importacao_id=' + encodeURIComponent(importacaoId);
        eventSource = new EventSource(url);

        eventSource.onopen = function () {
            reconnectAttempts = 0;
        };

        eventSource.onmessage = function (event) {
            try {
                const payload = JSON.parse(event.data);
                aplicar(payload);

                if (payload.status === 'concluido') {
                    const temResumo = !!(payload.resumo_final || (payload.dados && payload.dados.resumo_final));
                    if (!temResumo) return;
                    finalizar();
                } else if (payload.status === 'erro' || payload.status === 'timeout') {
                    finalizar();
                }
            } catch (e) {
                console.error('[EFD progresso] erro ao parsear SSE', e);
            }
        };

        eventSource.onerror = function () {
            if (finalizado) return;

            const tentativa = reconnectAttempts;
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }

            if (tentativa < MAX_RECONEXOES) {
                reconnectAttempts++;
                const delay = DELAY_RECONEXAO_BASE * Math.pow(2, tentativa);
                reconnectTimer = setTimeout(function () {
                    reconnectTimer = null;
                    if (!finalizado) conectar();
                }, delay);
            } else {
                if (etapaAtual) {
                    etapaAtual.textContent = 'Conexão perdida. Atualize a página para recarregar o progresso.';
                }
                if (barra) {
                    barra.style.backgroundColor = '#b91c1c';
                }
            }
        };
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden || finalizado) return;
        if (!eventSource || eventSource.readyState === EventSource.CLOSED) {
            reconnectAttempts = 0;
            conectar();
        }
    });

    conectar();
})();
