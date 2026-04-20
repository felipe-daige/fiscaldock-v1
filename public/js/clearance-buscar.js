function initClearanceBuscar() {
    const root = document.getElementById('buscar-nfe-container');
    if (!root) return;
    if (root.dataset.clearanceBuscarInitialized === '1') return;
    root.dataset.clearanceBuscarInitialized = '1';

    const config = window.BUSCAR_NFE_CONFIG || {};
    const CUSTO = Number(config.custo || 14);
    const ENDPOINTS = config.endpoints || {};
    const BADGE_CORES = config.cores || {};
    const DEFAULT_CLIENTE_ID = config.defaultClienteId ? String(config.defaultClienteId) : '';
    const POSSUI_CLIENTES_DISPONIVEIS = config.possuiClientesDisponiveis === true;

    const input = document.getElementById('nfe-chave');
    const button = document.getElementById('btn-consultar-nfe');
    const clienteSelect = document.getElementById('nfe-cliente-id');
    const feedback = document.getElementById('nfe-chave-feedback');
    const count = document.getElementById('nfe-chave-count');
    const documentTypeInputs = Array.from(document.querySelectorAll('.documento-tipo'));
    const documentTypeCards = Array.from(document.querySelectorAll('.documento-tipo-card'));
    const saldoLabel = document.getElementById('saldo-atual-label');
    const saldoBadge = document.getElementById('saldo-badge');

    const blocoProgresso = document.getElementById('bloco-progresso');
    const progressoBar = document.getElementById('progresso-bar');
    const progressoPercent = document.getElementById('progresso-percent');
    const progressoEtapa = document.getElementById('progresso-etapa');

    const blocoErro = document.getElementById('bloco-erro');
    const erroTitulo = document.getElementById('erro-titulo');
    const erroMensagem = document.getElementById('erro-mensagem');
    const erroRefund = document.getElementById('erro-refund');

    const blocoResultado = document.getElementById('bloco-resultado');
    const resultStatusBadge = document.getElementById('resultado-status-badge');
    const resultTipo = document.getElementById('resultado-tipo');
    const resultSituacao = document.getElementById('resultado-situacao');
    const resultValor = document.getElementById('resultado-valor');
    const resultEmissao = document.getElementById('resultado-emissao');
    const resultEmitente = document.getElementById('resultado-emitente');
    const resultDestinatario = document.getElementById('resultado-destinatario');
    const resultChave = document.getElementById('resultado-chave');
    const resultCliente = document.getElementById('resultado-cliente');
    const btnDetalhe = document.getElementById('btn-resultado-detalhe');
    const btnReconsultar = document.getElementById('btn-resultado-reconsultar');

    const defaultButtonLabel = button ? button.textContent.trim() : 'Consultar documento';

    let currentEventSource = null;
    let currentTimeoutHandle = null;
    let inFlight = false;

    function onlyDigits(value) {
        return (value || '').replace(/\D/g, '').slice(0, 44);
    }

    function selectedDocumentTypeKey() {
        const selected = documentTypeInputs.find((item) => item.checked && !item.disabled);
        return selected ? selected.value : 'nfe';
    }

    function selectedCliente() {
        if (!clienteSelect || !clienteSelect.value) {
            return { id: null, nome: 'Sem cliente associado' };
        }
        return {
            id: clienteSelect.value,
            nome: clienteSelect.options[clienteSelect.selectedIndex].text.trim(),
        };
    }

    function updateSelectedCard() {
        const key = selectedDocumentTypeKey();
        documentTypeCards.forEach((card) => {
            if (card.classList.contains('is-disabled')) return;
            card.classList.toggle('is-selected', card.dataset.documentTypeCard === key);
        });
    }

    function setButtonLoading(isLoading) {
        if (!button) return;
        if (isLoading) {
            button.disabled = true;
            button.textContent = 'Consultando...';
        } else {
            button.textContent = defaultButtonLabel;
            updateState();
        }
    }

    function hide(el) {
        if (el) el.classList.add('hidden');
    }

    function show(el) {
        if (el) el.classList.remove('hidden');
    }

    function resetEstadosVisuais() {
        hide(blocoProgresso);
        hide(blocoErro);
        hide(blocoResultado);
        hide(erroRefund);
        if (progressoBar) progressoBar.style.width = '8%';
        if (progressoPercent) progressoPercent.textContent = '0%';
        if (progressoEtapa) progressoEtapa.textContent = 'Iniciando consulta...';
    }

    function setProgresso(percent, etapa) {
        const value = Math.max(0, Math.min(100, Number(percent) || 0));
        if (progressoBar) progressoBar.style.width = value + '%';
        if (progressoPercent) progressoPercent.textContent = value + '%';
        if (etapa && progressoEtapa) progressoEtapa.textContent = etapa;
    }

    function mostrarErro(titulo, mensagem, refund) {
        fecharSseEexpirar();
        hide(blocoProgresso);
        hide(blocoResultado);
        if (erroTitulo) erroTitulo.textContent = titulo || 'Não foi possível consultar';
        if (erroMensagem) erroMensagem.textContent = mensagem || '-';
        if (refund) {
            show(erroRefund);
        } else {
            hide(erroRefund);
        }
        show(blocoErro);
        blocoErro && blocoErro.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function fecharSseEexpirar() {
        if (currentEventSource) {
            try { currentEventSource.close(); } catch (_) {}
            currentEventSource = null;
        }
        if (currentTimeoutHandle) {
            clearTimeout(currentTimeoutHandle);
            currentTimeoutHandle = null;
        }
    }

    function atualizarSaldo(novoSaldo) {
        if (typeof novoSaldo !== 'number') return;
        if (saldoLabel) saldoLabel.textContent = novoSaldo.toLocaleString('pt-BR');
        if (saldoBadge) {
            const suficiente = novoSaldo >= CUSTO;
            saldoBadge.style.backgroundColor = suficiente ? '#047857' : '#dc2626';
            saldoBadge.textContent = suficiente ? 'Saldo suficiente' : 'Saldo insuficiente';
        }
    }

    function renderResultado(nota) {
        if (!nota) return;

        const situacao = String(nota.situacao || 'INDETERMINADO').toUpperCase();
        const cor = BADGE_CORES[situacao] || '#374151';

        if (resultStatusBadge) {
            resultStatusBadge.textContent = situacao;
            resultStatusBadge.style.backgroundColor = cor;
        }
        if (resultTipo) resultTipo.textContent = (nota.tipo_documento || 'NFE').toUpperCase();
        if (resultSituacao) resultSituacao.textContent = situacao;
        if (resultValor) {
            const valor = Number(nota.valor_total || 0);
            resultValor.textContent = valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }
        if (resultEmissao) resultEmissao.textContent = nota.data_emissao || '-';
        if (resultEmitente) resultEmitente.textContent = nota.emit || '-';
        if (resultDestinatario) resultDestinatario.textContent = nota.dest || '-';
        if (resultChave) resultChave.textContent = nota.nfe_id || '-';
        if (resultCliente) resultCliente.textContent = nota.cliente_nome || 'Sem cliente associado';
        if (btnDetalhe) {
            if (nota.detalhe_url) {
                btnDetalhe.setAttribute('href', nota.detalhe_url);
                btnDetalhe.classList.remove('opacity-50', 'pointer-events-none');
                btnDetalhe.removeAttribute('aria-disabled');
            } else {
                btnDetalhe.setAttribute('href', '#');
                btnDetalhe.classList.add('opacity-50', 'pointer-events-none');
                btnDetalhe.setAttribute('aria-disabled', 'true');
            }
        }

        hide(blocoProgresso);
        hide(blocoErro);
        show(blocoResultado);
        blocoResultado && blocoResultado.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    async function buscarResultado(consultaLoteId) {
        try {
            const url = ENDPOINTS.resultado.replace(/\/$/, '') + '/' + encodeURIComponent(consultaLoteId);
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            if (response.status === 404) {
                mostrarErro(
                    'Nota ainda não persistida',
                    'A consulta concluiu, mas o webhook n8n ainda não gravou o resultado em nfe_consultas/cte_consultas. Tente novamente em alguns segundos.',
                    false
                );
                return;
            }
            if (!response.ok) {
                mostrarErro('Erro ao carregar resultado', 'Resposta HTTP ' + response.status, false);
                return;
            }
            const data = await response.json();
            renderResultado(data.nota);
        } catch (err) {
            mostrarErro('Erro ao carregar resultado', err.message || 'Falha de rede', false);
        }
    }

    function abrirSse(tabId, consultaLoteId) {
        if (!ENDPOINTS.sse) return;

        const url = ENDPOINTS.sse + '?tab_id=' + encodeURIComponent(tabId);
        const es = new EventSource(url, { withCredentials: true });
        currentEventSource = es;

        currentTimeoutHandle = setTimeout(() => {
            mostrarErro(
                'Tempo esgotado',
                'A consulta não retornou em 60 segundos. Verifique o histórico mais tarde.',
                false
            );
        }, 60000);

        es.onmessage = (event) => {
            let data;
            try {
                data = JSON.parse(event.data);
            } catch (_) {
                return;
            }

            const status = data.status || 'processando';
            const progresso = Number(data.progresso || 0);
            const etapa = data.etapa_label || data.mensagem || data.etapa || 'Processando...';

            if (status === 'processando') {
                setProgresso(progresso, etapa);
                return;
            }

            if (status === 'concluido') {
                setProgresso(100, 'Concluído, carregando resultado...');
                fecharSseEexpirar();
                buscarResultado(consultaLoteId);
                inFlight = false;
                setButtonLoading(false);
                return;
            }

            if (status === 'erro') {
                const refund = data.refund_credits === true || data.refund_aplicado === true;
                if (refund && data.refund_amount) {
                    atualizarSaldo(Number(data.saldo_atual || 0) + Number(data.refund_amount || 0));
                }
                mostrarErro(
                    'Consulta falhou',
                    data.mensagem || data.error_message || 'O provedor retornou erro.',
                    refund
                );
                inFlight = false;
                setButtonLoading(false);
                return;
            }

            if (status === 'timeout') {
                mostrarErro(
                    'Tempo esgotado',
                    data.mensagem || 'A consulta excedeu o limite do servidor.',
                    false
                );
                inFlight = false;
                setButtonLoading(false);
                return;
            }
        };

        es.onerror = () => {
            // Se o SSE cair mas o status ainda estiver processando, deixa o timeout global cuidar
        };
    }

    async function enviarConsulta() {
        if (inFlight) return;
        if (!button || button.disabled) return;

        const chave = onlyDigits(input.value);
        const tipo = selectedDocumentTypeKey();
        const cliente = selectedCliente();
        const tabId = 'dfe-' + tipo + '-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);

        resetEstadosVisuais();
        show(blocoProgresso);
        setProgresso(5, 'Enviando requisição...');
        setButtonLoading(true);
        inFlight = true;

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        try {
            const response = await fetch(ENDPOINTS.consultar, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    tipo_documento: tipo,
                    chave_acesso: chave,
                    cliente_id: cliente.id,
                    tab_id: tabId,
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (response.status === 422) {
                const msg = data.message
                    || (data.errors ? Object.values(data.errors).flat().join(' · ') : 'Dados inválidos.');
                mostrarErro('Dados inválidos', msg, false);
                inFlight = false;
                setButtonLoading(false);
                return;
            }

            if (response.status === 402) {
                mostrarErro(
                    'Créditos insuficientes',
                    `Esta consulta custa ${data.custo_necessario || CUSTO} créditos. Saldo atual: ${data.saldo_atual ?? 0}.`,
                    false
                );
                inFlight = false;
                setButtonLoading(false);
                return;
            }

            if (response.status === 403) {
                mostrarErro('Acesso negado', data.error || 'Cliente não pertence ao seu usuário.', false);
                inFlight = false;
                setButtonLoading(false);
                return;
            }

            if (response.status === 502) {
                mostrarErro(
                    'Integração indisponível',
                    data.error || 'O webhook n8n não respondeu. Seus créditos foram estornados.',
                    true
                );
                if (typeof data.novo_saldo === 'number') atualizarSaldo(data.novo_saldo);
                inFlight = false;
                setButtonLoading(false);
                return;
            }

            if (!response.ok) {
                mostrarErro(
                    'Erro interno',
                    data.error || ('HTTP ' + response.status),
                    data.refund_aplicado === true
                );
                if (typeof data.novo_saldo === 'number') atualizarSaldo(data.novo_saldo);
                inFlight = false;
                setButtonLoading(false);
                return;
            }

            if (typeof data.novo_saldo === 'number') atualizarSaldo(data.novo_saldo);
            setProgresso(15, 'Consulta iniciada, aguardando provedor...');
            abrirSse(data.tab_id || tabId, data.consulta_lote_id);

            if (btnReconsultar) {
                btnReconsultar.onclick = () => enviarConsulta();
            }
        } catch (err) {
            mostrarErro('Falha de rede', err.message || 'Não foi possível contatar o servidor.', false);
            inFlight = false;
            setButtonLoading(false);
        }
    }

    function updateState() {
        if (!POSSUI_CLIENTES_DISPONIVEIS) {
            button.disabled = true;
            feedback.textContent = 'Cadastre ou regularize a empresa própria para consultar um documento.';
            feedback.className = 'text-[11px] text-amber-700';
            count.textContent = '0';
            return;
        }

        const digits = onlyDigits(input.value);
        const clienteId = clienteSelect ? String(clienteSelect.value || '') : '';
        input.value = digits;
        updateSelectedCard();

        const length = digits.length;
        count.textContent = String(length);

        if (!clienteId) {
            button.disabled = true;
            feedback.textContent = 'Selecione o cliente associado antes de consultar.';
            feedback.className = 'text-[11px] text-amber-700';
            return;
        }

        if (length === 0) {
            button.disabled = true;
            feedback.textContent = 'Cole a chave com 44 dígitos para consultar o documento do cliente selecionado.';
            feedback.className = 'text-[11px] text-gray-500';
            return;
        }

        if (length < 44) {
            button.disabled = true;
            feedback.textContent = `Chave incompleta: faltam ${44 - length} dígito(s).`;
            feedback.className = 'text-[11px] text-amber-700';
            return;
        }

        button.disabled = false;
        feedback.textContent = 'Chave válida (44 dígitos) e cliente associado. Pronto para consultar.';
        feedback.className = 'text-[11px] text-green-700';
    }

    input.addEventListener('input', updateState);
    input.addEventListener('paste', () => window.setTimeout(updateState, 0));
    if (clienteSelect) {
        if (DEFAULT_CLIENTE_ID && !clienteSelect.value) {
            clienteSelect.value = DEFAULT_CLIENTE_ID;
        }
        clienteSelect.addEventListener('change', updateState);
    }
    documentTypeInputs.forEach((item) => {
        if (!item.disabled) item.addEventListener('change', updateState);
    });

    button.addEventListener('click', enviarConsulta);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !button.disabled) {
            e.preventDefault();
            enviarConsulta();
        }
    });

    updateState();
}

window.initClearanceBuscar = initClearanceBuscar;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initClearanceBuscar);
} else {
    initClearanceBuscar();
}
