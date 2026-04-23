(function() {
    'use strict';

    function parseEtapas(raw) {
        if (!raw) return [];

        try {
            var parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [];
        } catch (_) {
            return [];
        }
    }

    function getEtapasFromDom(container) {
        if (!container) return [];

        return Array.prototype.map.call(container.querySelectorAll('.etapa-item'), function(item) {
            var labelNode = item.querySelector('span:last-child');

            return {
                numero: normalizarEtapa(item.dataset.etapa),
                label: labelNode ? labelNode.textContent : ''
            };
        }).filter(function(etapa) {
            return etapa.numero !== null;
        });
    }

    function normalizarEtapa(valor) {
        var etapa = parseInt(valor, 10);
        if (isNaN(etapa) || etapa < 0) return null;
        return etapa;
    }

    function getSequenciaEtapas(etapas) {
        return (Array.isArray(etapas) ? etapas : []).reduce(function(lista, etapa) {
            var numero = normalizarEtapa(etapa && etapa.numero);
            if (numero !== null) {
                lista.push(numero);
            }
            return lista;
        }, []);
    }

    function getEtapaPosicaoNaSequencia(etapa, sequencia) {
        var numero = normalizarEtapa(etapa);
        if (numero === null) return null;

        var posicao = sequencia.indexOf(numero);
        if (posicao !== -1) {
            return posicao;
        }

        if (sequencia.length && numero > 0) {
            var positivos = sequencia.filter(function(item) { return item > 0; });
            if (positivos.length) {
                var ultimaEtapaPositiva = positivos[positivos.length - 1];
                if (numero > ultimaEtapaPositiva) {
                    return sequencia.indexOf(ultimaEtapaPositiva);
                }
            }
        }

        return numero === 0 ? 999999 : numero;
    }

    function getEtapaAnteriorNaSequencia(etapa, sequencia) {
        var numero = normalizarEtapa(etapa);
        if (numero === null) return null;

        var posicao = sequencia.indexOf(numero);
        if (posicao > 0) {
            return sequencia[posicao - 1];
        }

        if (numero > 1) {
            return numero - 1;
        }

        return null;
    }

    function renderStepItem(item, status) {
        if (!item) return;

        var svgSpinner = '<svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>';
        var svgCheck = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>';
        var svgDash = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>';
        var svgX = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>';

        var estados = {
            pending: { pill: 'bg-gray-100 text-gray-400', icon: svgDash, style: '' },
            current: { pill: 'bg-gray-200 text-gray-700', icon: svgSpinner, style: '' },
            done: { pill: 'text-white', icon: svgCheck, style: 'background-color: #047857' },
            error: { pill: 'text-white', icon: svgX, style: 'background-color: #b91c1c' }
        };

        var estado = estados[status] || estados.pending;

        item.className = 'etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium ' + estado.pill;
        item.style.cssText = estado.style;

        var iconEl = item.querySelector('.etapa-icon');
        if (iconEl) iconEl.innerHTML = estado.icon;
    }

    function initConsultaLoteDetalhe() {
        var root = document.getElementById('consulta-lote-detalhe-root');
        if (!root) return;
        if (root.dataset.initialized === '1') return;
        root.dataset.initialized = '1';

        var etapas = parseEtapas(root.dataset.etapas);
        var sequenciaEtapas = getSequenciaEtapas(etapas);
        var statusInicial = String(root.dataset.status || 'pendente');
        var tabId = root.dataset.tabId || '';
        var streamUrl = root.dataset.streamUrl || '';
        var statusUrl = root.dataset.statusUrl || '';
        var resultadosUrl = root.dataset.resultadosUrl || '';
        var detailUrl = root.dataset.detailUrl || window.location.pathname;
        var awaitResult = root.dataset.awaitResult === '1';

        var progressBar = document.getElementById('consulta-lote-bar');
        var progressPercent = document.getElementById('consulta-lote-percent');
        var progressMessage = document.getElementById('consulta-lote-mensagem');
        var progressEtapa = document.getElementById('consulta-lote-etapa');
        var stepsContainer = document.getElementById('consulta-lote-steps');

        if (!etapas.length) {
            etapas = getEtapasFromDom(stepsContainer);
            sequenciaEtapas = getSequenciaEtapas(etapas);
        }

        var state = {
            eventSource: null,
            statusPollHandle: null,
            resultsPollHandle: null,
            ultimaEtapaConcluida: null,
            knownStepLabels: {},
            progressoAtual: statusInicial === 'finalizado' ? 100 : 0,
            mensagemAtual: '',
            etapaLabelAtual: ''
        };

        window._cleanupFunctions = window._cleanupFunctions || {};
        window._cleanupFunctions.consultaLoteDetalhe = function() {
            if (state.eventSource) {
                try { state.eventSource.close(); } catch (_) {}
                state.eventSource = null;
            }

            if (state.statusPollHandle) {
                clearTimeout(state.statusPollHandle);
                state.statusPollHandle = null;
            }

            if (state.resultsPollHandle) {
                clearTimeout(state.resultsPollHandle);
                state.resultsPollHandle = null;
            }
        };

        function closeEventSource() {
            if (!state.eventSource) return;

            try { state.eventSource.close(); } catch (_) {}
            state.eventSource = null;
        }

        function stopStatusPolling() {
            if (!state.statusPollHandle) return;
            clearTimeout(state.statusPollHandle);
            state.statusPollHandle = null;
        }

        function stopResultsPolling() {
            if (!state.resultsPollHandle) return;
            clearTimeout(state.resultsPollHandle);
            state.resultsPollHandle = null;
        }

        function resolveProgressMessage(snapshot, fallback) {
            if (snapshot && snapshot.mensagem) return snapshot.mensagem;
            if (snapshot && snapshot.etapa_label) return snapshot.etapa_label;
            if (state.mensagemAtual) return state.mensagemAtual;
            return fallback || '';
        }

        function resolveProgressStepLabel(snapshot, fallback) {
            if (snapshot && snapshot.etapa_label) return snapshot.etapa_label;
            if (state.etapaLabelAtual) return state.etapaLabelAtual;
            return fallback || '';
        }

        function resolveProgressPercent(snapshot, fallback) {
            if (snapshot && snapshot.status === 'concluido') return 100;
            if (snapshot && snapshot.progresso !== null && snapshot.progresso !== undefined && snapshot.progresso !== '') {
                return snapshot.progresso;
            }
            return state.progressoAtual !== null && state.progressoAtual !== undefined
                ? state.progressoAtual
                : (fallback || 0);
        }

        function updateProgress(percent, message, stepLabel) {
            var value = Math.max(0, Math.min(100, Number(percent) || 0));
            state.progressoAtual = value;
            if (message) state.mensagemAtual = message;
            if (stepLabel) state.etapaLabelAtual = stepLabel;

            if (progressBar) progressBar.style.width = value + '%';
            if (progressPercent) progressPercent.textContent = value + '%';
            if (progressMessage) progressMessage.textContent = message || 'Processando...';
            if (progressEtapa) {
                if (stepLabel) {
                    progressEtapa.textContent = stepLabel;
                    progressEtapa.classList.remove('hidden');
                } else {
                    progressEtapa.textContent = '';
                    progressEtapa.classList.add('hidden');
                }
            }
        }

        function rememberStepLabel(step, label) {
            var numero = normalizarEtapa(step);
            if (numero === null || !label) return;
            state.knownStepLabels[String(numero)] = String(label);
        }

        function getStepLabel(etapa) {
            var numero = normalizarEtapa(etapa && etapa.numero);
            if (numero !== null && state.knownStepLabels[String(numero)]) {
                return state.knownStepLabels[String(numero)];
            }

            return etapa && etapa.label ? etapa.label : ('Etapa ' + (numero !== null ? numero : '?'));
        }

        function atualizarUltimaEtapaConcluida(valor) {
            var etapa = normalizarEtapa(valor);
            if (etapa === null) return state.ultimaEtapaConcluida;

            var atualPosicao = getEtapaPosicaoNaSequencia(state.ultimaEtapaConcluida, sequenciaEtapas);
            var novaPosicao = getEtapaPosicaoNaSequencia(etapa, sequenciaEtapas);

            if (state.ultimaEtapaConcluida === null || (novaPosicao !== null && novaPosicao > atualPosicao)) {
                state.ultimaEtapaConcluida = etapa;
            }

            return state.ultimaEtapaConcluida;
        }

        function inferirUltimaEtapaConcluida(snapshot) {
            var etapaExplicita = normalizarEtapa(snapshot.ultima_etapa_concluida);
            if (etapaExplicita !== null) {
                return atualizarUltimaEtapaConcluida(etapaExplicita);
            }

            var etapaAtual = normalizarEtapa(snapshot.etapa);
            var totalEtapas = normalizarEtapa(snapshot.total_etapas);

            if (snapshot.status === 'concluido' && etapaAtual !== null) {
                return atualizarUltimaEtapaConcluida(etapaAtual);
            }

            if (snapshot.status === 'finalizado') {
                var etapaFinal = etapaAtual !== null ? etapaAtual : totalEtapas;
                if (etapaFinal !== null) {
                    return atualizarUltimaEtapaConcluida(etapaFinal);
                }

                return state.ultimaEtapaConcluida;
            }

            if ((snapshot.status === 'processando' || snapshot.status === 'erro') && etapaAtual !== null) {
                var etapaAnterior = etapaAtual === 0
                    ? (totalEtapas !== null ? totalEtapas : getEtapaAnteriorNaSequencia(etapaAtual, sequenciaEtapas))
                    : getEtapaAnteriorNaSequencia(etapaAtual, sequenciaEtapas);

                if (etapaAnterior !== null) {
                    return atualizarUltimaEtapaConcluida(etapaAnterior);
                }
            }

            return state.ultimaEtapaConcluida;
        }

        function renderSteps(snapshot) {
            if (!stepsContainer) return;

            if (!Array.isArray(etapas) || etapas.length < 2) {
                stepsContainer.classList.add('hidden');
                stepsContainer.innerHTML = '';
                return;
            }

            stepsContainer.classList.remove('hidden');
            stepsContainer.innerHTML = '';

            var etapaAtual = normalizarEtapa(snapshot.etapa);
            var ultimaEtapaConcluida = inferirUltimaEtapaConcluida(snapshot);
            var etapaAtualPosicao = getEtapaPosicaoNaSequencia(etapaAtual, sequenciaEtapas);
            var ultimaEtapaPosicao = getEtapaPosicaoNaSequencia(ultimaEtapaConcluida, sequenciaEtapas);
            var percentual = Math.max(0, Math.min(100, Number(snapshot.progresso) || 0));

            etapas.forEach(function(etapa) {
                var numero = normalizarEtapa(etapa && etapa.numero);
                var posicao = getEtapaPosicaoNaSequencia(numero, sequenciaEtapas);
                var chip = document.createElement('div');
                var icon = document.createElement('span');
                var text = document.createElement('span');
                var estado = 'pending';

                chip.className = 'etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400';
                chip.dataset.etapa = String(numero);

                icon.className = 'etapa-icon flex items-center justify-center w-3.5 h-3.5';
                text.textContent = getStepLabel(etapa);

                if (snapshot.status === 'finalizado') {
                    estado = 'done';
                } else if (snapshot.status === 'erro' || snapshot.status === 'timeout') {
                    if (ultimaEtapaPosicao !== null && posicao !== null && posicao <= ultimaEtapaPosicao) {
                        estado = 'done';
                    } else if (etapaAtualPosicao !== null && posicao === etapaAtualPosicao) {
                        estado = 'error';
                    }
                } else if (ultimaEtapaPosicao !== null && posicao !== null && posicao <= ultimaEtapaPosicao) {
                    estado = 'done';
                } else if (etapaAtualPosicao !== null && posicao === etapaAtualPosicao) {
                    estado = percentual >= 100 || snapshot.status === 'concluido' ? 'done' : 'current';
                }

                chip.appendChild(icon);
                chip.appendChild(text);
                renderStepItem(chip, estado);
                stepsContainer.appendChild(chip);
            });
        }

        function scheduleReload(delayMs) {
            stopStatusPolling();
            stopResultsPolling();
            closeEventSource();

            window.setTimeout(function() {
                if (typeof window.navigateTo === 'function') {
                    window.navigateTo(detailUrl, { updateHistory: false });
                    return;
                }

                window.location.href = detailUrl;
            }, delayMs);
        }

        async function resultsReady() {
            if (!resultadosUrl) return false;

            try {
                var response = await fetch(resultadosUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                var data = await response.json().catch(function() { return {}; });
                return response.ok && data.success && Number(data.total || 0) > 0;
            } catch (_) {
                return false;
            }
        }

        async function pollResultsReadiness() {
            stopResultsPolling();

            var ready = await resultsReady();
            if (ready) {
                scheduleReload(250);
                return;
            }

            state.resultsPollHandle = window.setTimeout(pollResultsReadiness, 3000);
        }

        function handleSnapshot(snapshot) {
            if (!snapshot || typeof snapshot !== 'object') return;

            rememberStepLabel(snapshot.etapa, snapshot.etapa_label || null);
            renderSteps(snapshot);

            if (snapshot.status === 'finalizado') {
                updateProgress(100, 'Finalizado, carregando resultado...', resolveProgressStepLabel(snapshot));
                pollResultsReadiness();
                return;
            }

            if (snapshot.status === 'erro' || snapshot.status === 'timeout') {
                updateProgress(
                    resolveProgressPercent(snapshot),
                    snapshot.error_message || resolveProgressMessage(snapshot, 'Erro no processamento.'),
                    resolveProgressStepLabel(snapshot)
                );
                scheduleReload(500);
                return;
            }

            var progressoAtual = resolveProgressPercent(snapshot);
            updateProgress(
                progressoAtual,
                resolveProgressMessage(snapshot, 'Processando...'),
                resolveProgressStepLabel(snapshot)
            );
        }

        function scheduleStatusPolling(delayMs) {
            stopStatusPolling();
            state.statusPollHandle = window.setTimeout(pollStatusOnce, delayMs);
        }

        async function pollStatusOnce() {
            if (!statusUrl) return;

            try {
                var response = await fetch(statusUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                var data = await response.json().catch(function() { return {}; });
                if (!response.ok || !data.success) {
                    scheduleStatusPolling(5000);
                    return;
                }

                handleSnapshot(data);

                if (!['finalizado', 'erro', 'timeout'].includes(String(data.status || ''))) {
                    scheduleStatusPolling(5000);
                }
            } catch (_) {
                scheduleStatusPolling(5000);
            }
        }

        function openProgressStream() {
            if (!tabId || !streamUrl) return;

            closeEventSource();
            state.eventSource = new EventSource(streamUrl + '?tab_id=' + encodeURIComponent(tabId), { withCredentials: true });

            state.eventSource.onmessage = function(event) {
                var raw = event.data;
                if (!raw || raw === ':ping') return;

                try {
                    handleSnapshot(JSON.parse(raw));
                } catch (_) {
                    // ignora payload malformado
                }
            };

            state.eventSource.onerror = function() {
                closeEventSource();
            };
        }

        renderSteps({
            status: statusInicial,
            progresso: statusInicial === 'finalizado' ? 100 : 0,
            etapa: null,
            total_etapas: null
        });

        if (statusInicial === 'pendente' || statusInicial === 'processando') {
            openProgressStream();
            scheduleStatusPolling(5000);
            return;
        }

        if (statusInicial === 'finalizado' && awaitResult) {
            updateProgress(100, 'Finalizado, carregando resultado...', '');
            pollResultsReadiness();
        }
    }

    window.initConsultaLoteDetalhe = initConsultaLoteDetalhe;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initConsultaLoteDetalhe);
    } else {
        initConsultaLoteDetalhe();
    }
})();
