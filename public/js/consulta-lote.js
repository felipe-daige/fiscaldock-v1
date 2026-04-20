/**
 * Consulta Lote - JavaScript
 * Gerencia selecao de participantes e execucao de consultas em lote.
 */
(function() {
    'use strict';

    // Guard: previne re-execução da IIFE que sobrescreveria closures
    if (window._consultaLoteModuleLoaded) return;
    window._consultaLoteModuleLoaded = true;

    // Nomes amigaveis das consultas
    const CONSULTA_NOMES = {
        situacao_cadastral: 'Situacao Cadastral',
        dados_cadastrais: 'Dados Cadastrais',
        endereco: 'Endereco',
        cnaes: 'CNAEs',
        qsa: 'Quadro Societario (QSA)',
        simples_nacional: 'Simples Nacional',
        mei: 'MEI',
        sintegra: 'SINTEGRA',
        tcu_consolidada: 'TCU Consolidada',
        cnd_federal: 'CND Federal (PGFN)',
        crf_fgts: 'CRF/FGTS',
        cnd_estadual: 'CND Estadual',
        cndt: 'CNDT (Trabalhista)',
        protestos: 'Protestos',
        lista_devedores_pgfn: 'Lista Devedores PGFN',
        trabalho_escravo: 'Trabalho Escravo',
        ibama_autuacoes: 'IBAMA Autuacoes',
        processos_cnj: 'Processos CNJ'
    };

    const ORIGEM_BADGES = {
        NFE: { label: 'NF-e', hex: '#374151' },
        NFSE: { label: 'NFS-e', hex: '#0f766e' },
        CTE: { label: 'CT-e', hex: '#4338ca' },
        SPED_EFD_FISCAL: { label: 'EFD', hex: '#4338ca', detail: 'Fiscal' },
        SPED_EFD_CONTRIB: { label: 'EFD', hex: '#0f766e', detail: 'Contrib.' },
        MANUAL: { label: 'Manual', hex: '#9ca3af' }
    };

    // Estado global
    const state = {
        selectedIds: new Set(),
        selectedClienteIds: new Set(),
        selectedGrupoIds: new Set(),
        currentPage: 1,
        perPage: 50,
        totalPages: 1,
        totalItems: 0,
        allIdsCurrentFilter: [],
        filters: {
            grupo_id: '',
            cliente_id: '',
            origem_tipo: '',
            tipo_documento: '',
            situacao_cadastral: '',
            uf: '',
            busca: ''
        },
        clientesFilters: {
            busca: '',
            tipo_pessoa: '',
            situacao_cadastral: '',
            uf: '',
            faixa_participantes: ''
        },
        activeTab: 'participantes',
        filterContext: null, // { type: 'cliente'|'grupo', id: int, label: string }
        expandedClienteDetailsId: null, // ID do cliente com detalhes expandidos
        expandedClienteId: null, // ID do cliente expandido inline (null = nenhum)
        expandedClientePagination: null, // { clienteId, clienteLabel, currentPage, lastPage, perPage, total }
        expandedParticipanteId: null, // ID do participante com metadados expandidos
        tabId: generateUUID(),
        consultaLoteId: null,
        etapas: [],
        etapaAtual: null,
        eventSource: null,
        credits: window.consultaData?.credits || 0,
        isExecuting: false,
        activeAlertTooltipId: null,
        alertTooltipCloseTimer: null,
        bulkSelectionState: {
            participantes: false,
            clientes: false,
            grupos: false,
        },
        bulkSelectionLoading: {
            participantes: false,
            clientes: false,
            grupos: false,
        }
    };

    // Estado de paginação dos resultados
    var resultadosPaginaAtual = 1;
    var resultadosPerPage = 25;
    var todosResultados = [];

    // Elementos DOM
    let elements = {};

    /**
     * Inicializa o modulo quando o DOM estiver pronto.
     */
    function init() {
        cacheElements();
        bindEvents();

        // Pre-selecionar participantes da URL (vindos da lista de participantes)
        const urlParams = new URLSearchParams(window.location.search);
        const participantesParam = urlParams.get('participantes');
        if (participantesParam) {
            participantesParam.split(',').forEach(function(id) {
                const numId = parseInt(id, 10);
                if (!isNaN(numId)) {
                    state.selectedIds.add(numId);
                }
            });
            console.log('[Consulta Lote] Pre-selecionados da URL:', state.selectedIds.size, 'participantes');
        }

        loadParticipantes();
        updatePlanoStyles();
        updateConsultasIncluidas();
        updateResumo();
        syncBulkActionButtons();
    }

    /**
     * Cache de elementos DOM.
     */
    function cacheElements() {
        elements = {
            tabelaBody: document.getElementById('tabela-participantes'),
            loadingRow: document.getElementById('loading-row'),
            checkboxTodos: document.getElementById('checkbox-todos'),
            totalSelecionados: document.getElementById('total-selecionados'),

            // Filtros
            filtroOrigem: document.getElementById('filtro-origem'),
            filtroBusca: document.getElementById('filtro-busca'),
            filtroTipoDocumento: document.getElementById('filtro-tipo-documento'),
            filtroSituacaoCadastral: document.getElementById('filtro-situacao-cadastral'),
            filtroUf: document.getElementById('filtro-uf'),
            filtroCliente: document.getElementById('filtro-cliente'),
            filtroGrupo: document.getElementById('filtro-grupo'),
            btnLimparFiltrosParticipantes: document.getElementById('btn-limpar-filtros-participantes'),

            // Abas
            searchTabs: document.querySelectorAll('.search-tab'),
            viewParticipantes: document.getElementById('view-participantes'),
            viewClientes: document.getElementById('view-clientes'),
            viewGrupos: document.getElementById('view-grupos'),
            listaClientes: document.getElementById('lista-clientes'),
            checkboxTodosClientes: document.getElementById('checkbox-todos-clientes'),
            listaGrupos: document.getElementById('lista-grupos'),
            totalClientesSelecionados: document.getElementById('total-clientes-selecionados'),
            btnSelecionarTodosClientesBarra: document.getElementById('btn-selecionar-todos-clientes-barra'),
            btnLimparSelecaoClientes: document.getElementById('btn-limpar-selecao-clientes'),
            btnAtualizarClientes: document.getElementById('btn-atualizar-clientes'),
            totalGruposSelecionados: document.getElementById('total-grupos-selecionados'),
            btnSelecionarTodosGrupos: document.getElementById('btn-selecionar-todos-grupos'),
            btnLimparSelecaoGrupos: document.getElementById('btn-limpar-selecao-grupos'),
            btnAtualizarGrupos: document.getElementById('btn-atualizar-grupos'),
            buscaClientes: document.getElementById('busca-clientes'),
            filtroClientesTipoPessoa: document.getElementById('filtro-clientes-tipo-pessoa'),
            filtroClientesSituacaoCadastral: document.getElementById('filtro-clientes-situacao-cadastral'),
            filtroClientesUf: document.getElementById('filtro-clientes-uf'),
            filtroClientesFaixaParticipantes: document.getElementById('filtro-clientes-faixa-participantes'),
            btnLimparFiltrosClientes: document.getElementById('btn-limpar-filtros-clientes'),
            participantesContext: document.getElementById('participantes-context'),
            filterContextLabel: document.getElementById('filter-context-label'),
            btnClearFilterContext: document.getElementById('btn-clear-filter-context'),
            btnRemoveFilterChip: document.getElementById('btn-remove-filter-chip'),

            // Botoes
            btnSelecionarTodos: document.getElementById('btn-selecionar-todos'),
            btnLimparSelecao: document.getElementById('btn-limpar-selecao'),
            btnGerarRelatorio: document.getElementById('btn-gerar-relatorio'),
            btnPagAnterior: document.getElementById('btn-pag-anterior'),
            btnPagProximo: document.getElementById('btn-pag-proximo'),

            // Paginacao
            pagInicio: document.getElementById('pag-inicio'),
            pagFim: document.getElementById('pag-fim'),
            pagTotal: document.getElementById('pag-total'),
            pagAtual: document.getElementById('pag-atual'),

            // Resumo
            resumoParticipantes: document.getElementById('resumo-participantes'),
            resumoCustoUnitario: document.getElementById('resumo-custo-unitario'),
            resumoCustoTotal: document.getElementById('resumo-custo-total'),
            resumoSaldo: document.getElementById('resumo-saldo'),
            alertaCreditosInsuficientes: document.getElementById('alerta-creditos-insuficientes'),

            // Progresso inline
            consultaInlineErrorRegion: document.getElementById('consulta-inline-error-region'),
            consultaFormSection: document.getElementById('consulta-form-section'),
            consultaProgressoSection: document.getElementById('consulta-progresso-section'),
            progressoTitulo: document.getElementById('progresso-titulo'),
            progressoMensagem: document.getElementById('progresso-mensagem'),
            progressoBarra: document.getElementById('progresso-barra'),
            progressoPercentual: document.getElementById('progresso-percentual'),
            consultaProgressoIcon: document.getElementById('consulta-progresso-icon'),
            consultaProgressoCard: document.getElementById('consulta-progresso-card'),
            consultaProgressoErro: document.getElementById('consulta-progresso-erro'),
            consultaProgressoErroMsg: document.getElementById('consulta-progresso-erro-msg'),
            consultaProgressoSuporteLink: document.getElementById('consulta-progresso-suporte-link'),
            btnTentarNovamente: document.getElementById('btn-tentar-novamente'),
            resultadoConsulta: document.getElementById('resultado-consulta'),
            resultadoConsultaInfo: document.getElementById('resultado-consulta-info'),
            linkDownloadRelatorio: document.getElementById('link-download-relatorio'),
            btnNovaConsulta: document.getElementById('btn-nova-consulta'),
            resultadosTableContainer: document.getElementById('resultados-table-container'),
            resultadosLoading: document.getElementById('resultados-loading'),
            resultadosTableWrapper: document.getElementById('resultados-table-wrapper'),

            // Adicionar CNPJ
            inputAdicionarCnpj: document.getElementById('input-adicionar-cnpj'),
            selectClienteAssociar: document.getElementById('select-cliente-associar'),
            btnAdicionarCnpj: document.getElementById('btn-adicionar-cnpj'),
            feedbackAdicionarCnpj: document.getElementById('feedback-adicionar-cnpj'),

        };
    }

    /**
     * Vincula eventos aos elementos.
     */
    function bindEvents() {
        // Filtros (apenas origem e busca; grupo/cliente agora via abas)
        if (elements.filtroOrigem) elements.filtroOrigem.addEventListener('change', onFilterChange);
        if (elements.filtroBusca) {
            elements.filtroBusca.addEventListener('input', debounce(onFilterChange, 300));
        }
        if (elements.filtroTipoDocumento) elements.filtroTipoDocumento.addEventListener('change', onFilterChange);
        if (elements.filtroSituacaoCadastral) elements.filtroSituacaoCadastral.addEventListener('change', onFilterChange);
        if (elements.filtroUf) elements.filtroUf.addEventListener('change', onFilterChange);
        if (elements.filtroCliente) elements.filtroCliente.addEventListener('change', onFilterChange);
        if (elements.filtroGrupo) elements.filtroGrupo.addEventListener('change', onFilterChange);
        if (elements.btnLimparFiltrosParticipantes) {
            elements.btnLimparFiltrosParticipantes.addEventListener('click', resetParticipantesFilters);
        }

        // Abas (event delegation - robusto para SPA re-navigation)
        var searchTabsContainer = document.getElementById('search-tabs');
        if (searchTabsContainer && !searchTabsContainer._tabDelegated) {
            searchTabsContainer._tabDelegated = true;
            searchTabsContainer.addEventListener('click', function(e) {
                var tab = e.target.closest('.search-tab');
                if (tab && tab.dataset.tab) {
                    switchTab(tab.dataset.tab);
                }
            });
        }

        // Selecao de todos clientes (checkbox header)
        if (elements.checkboxTodosClientes) {
            elements.checkboxTodosClientes.addEventListener('change', toggleTodosClientes);
        }
        if (elements.btnSelecionarTodosClientesBarra) {
            elements.btnSelecionarTodosClientesBarra.addEventListener('click', function() {
                if (elements.checkboxTodosClientes) {
                    elements.checkboxTodosClientes.checked = true;
                }
                toggleTodosClientes(true);
            });
        }
        if (elements.btnLimparSelecaoClientes) {
            elements.btnLimparSelecaoClientes.addEventListener('click', clearSelectedClientes);
        }
        if (elements.btnAtualizarClientes) {
            elements.btnAtualizarClientes.addEventListener('click', loadClientes);
        }

        // Busca clientes (dentro da aba Clientes)
        if (elements.buscaClientes) {
            elements.buscaClientes.addEventListener('input', debounce(loadClientes, 300));
        }
        if (elements.filtroClientesTipoPessoa) elements.filtroClientesTipoPessoa.addEventListener('change', loadClientes);
        if (elements.filtroClientesSituacaoCadastral) elements.filtroClientesSituacaoCadastral.addEventListener('change', loadClientes);
        if (elements.filtroClientesUf) elements.filtroClientesUf.addEventListener('change', loadClientes);
        if (elements.filtroClientesFaixaParticipantes) elements.filtroClientesFaixaParticipantes.addEventListener('change', loadClientes);
        if (elements.btnLimparFiltrosClientes) {
            elements.btnLimparFiltrosClientes.addEventListener('click', resetClientesFilters);
        }

        // Limpar filtro de contexto
        if (elements.btnClearFilterContext) {
            elements.btnClearFilterContext.addEventListener('click', clearFilterContext);
        }
        if (elements.btnRemoveFilterChip) {
            elements.btnRemoveFilterChip.addEventListener('click', clearFilterContext);
        }
        if (elements.btnSelecionarTodosGrupos) {
            elements.btnSelecionarTodosGrupos.addEventListener('click', selectAllVisibleGrupos);
        }
        if (elements.btnLimparSelecaoGrupos) {
            elements.btnLimparSelecaoGrupos.addEventListener('click', clearSelectedGrupos);
        }
        if (elements.btnAtualizarGrupos) {
            elements.btnAtualizarGrupos.addEventListener('click', loadGrupos);
        }

        // Selecao
        if (elements.checkboxTodos) elements.checkboxTodos.addEventListener('change', toggleTodosPagina);
        if (elements.btnSelecionarTodos) elements.btnSelecionarTodos.addEventListener('click', selecionarTodosFilter);
        if (elements.btnLimparSelecao) elements.btnLimparSelecao.addEventListener('click', limparSelecao);

        // Planos
        document.querySelectorAll('input[name="plano_id"]').forEach(radio => {
            radio.addEventListener('change', () => {
                updatePlanoStyles();
                updateConsultasIncluidas();
                updateResumo();
            });
        });

        // Acoes
        if (elements.btnGerarRelatorio) {
            elements.btnGerarRelatorio.addEventListener('click', executarConsulta);

            // Efeito hover no botao
            elements.btnGerarRelatorio.addEventListener('mouseenter', function() {
                if (!this.disabled) {
                    this.style.backgroundColor = '#1f2937'; // gray-800
                }
            });
            elements.btnGerarRelatorio.addEventListener('mouseleave', function() {
                if (!this.disabled) {
                    this.style.backgroundColor = '#111827'; // gray-900
                }
            });
        }

        // Paginacao
        if (elements.btnPagAnterior) elements.btnPagAnterior.addEventListener('click', () => changePage(-1));
        if (elements.btnPagProximo) elements.btnPagProximo.addEventListener('click', () => changePage(1));

        // Adicionar CNPJ
        if (elements.inputAdicionarCnpj) {
            elements.inputAdicionarCnpj.addEventListener('input', function() {
                this.value = applyCnpjMask(this.value);
            });
            elements.inputAdicionarCnpj.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    adicionarCnpj();
                }
            });
        }
        if (elements.btnAdicionarCnpj) {
            elements.btnAdicionarCnpj.addEventListener('click', adicionarCnpj);
        }

        // Progresso inline
        if (elements.btnTentarNovamente) elements.btnTentarNovamente.addEventListener('click', voltarParaFormulario);
        if (elements.btnNovaConsulta) elements.btnNovaConsulta.addEventListener('click', voltarParaFormulario);

        document.addEventListener('click', handleAlertTooltipDocumentClick, true);
        document.addEventListener('keydown', handleAlertTooltipKeydown);
        window.addEventListener('resize', closeAlertTooltip);
        window.addEventListener('scroll', closeAlertTooltip, true);
    }

    /**
     * Carrega participantes do servidor.
     */
    async function loadParticipantes() {
        showLoading(true);

        const params = new URLSearchParams({
            page: state.currentPage,
            per_page: state.perPage
        });

        if (state.filters.grupo_id) params.append('grupo_id', state.filters.grupo_id);
        if (state.filters.cliente_id) params.append('cliente_id', state.filters.cliente_id);
        if (state.filters.origem_tipo) params.append('origem_tipo', state.filters.origem_tipo);
        if (state.filters.tipo_documento) params.append('tipo_documento', state.filters.tipo_documento);
        if (state.filters.situacao_cadastral) params.append('situacao_cadastral', state.filters.situacao_cadastral);
        if (state.filters.uf) params.append('uf', state.filters.uf);
        if (state.filters.busca) params.append('busca', state.filters.busca);

        try {
            const response = await fetch(`${window.consultaData.routes.getParticipantes}?${params}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Erro ao carregar participantes');

            const data = await response.json();

            if (data.success) {
                renderParticipantes(data.data);
                updatePaginacao(data.pagination);
            } else {
                showError('Erro ao carregar participantes');
            }
        } catch (error) {
            console.error('Erro:', error);
            showError('Erro ao carregar participantes');
        } finally {
            showLoading(false);
        }
    }

    /**
     * Renderiza lista de participantes na tabela.
     */
    function renderParticipantes(participantes) {
        if (!elements.tabelaBody) return;

        closeAlertTooltip();
        elements.tabelaBody.innerHTML = '';

        if (!participantes || participantes.length === 0) {
            elements.tabelaBody.innerHTML = `
                <tr>
                    <td colspan="2" class="px-4 py-8 text-center text-sm text-gray-500">
                        Nenhum participante encontrado.
                    </td>
                </tr>
            `;
            return;
        }

        participantes.forEach(p => {
            const canSelect = participantePodeConsultar(p);
            const isSelected = state.selectedIds.has(p.id);
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 transition flex flex-col gap-1 px-4 py-3 md:table-row md:px-0 md:py-0 md:gap-0' + (isSelected ? ' bg-gray-50' : '');
            tr.dataset.id = p.id;

            const documentoFormatado = formatDocumento(
                p.documento_formatado || p.documento || p.cnpj || p.cnpj_formatado
            );
            const escNome = (p.razao_social || '').replace(/"/g, '&quot;');
            const alertaIconHtml = getAlertaIconHtml(p, 'lista');

            const situacaoBadge = getSituacaoBadgeHtml(p.situacao_cadastral, !p.ultima_consulta_em);
            const cndBadge = getCndBadgeHtml(p);
            const consultaBadge = getConsultaStatusBadgeHtml(p);
            const origemDetails = getOrigemDetailsHtml(p);

            const isExpanded = state.expandedParticipanteId === p.id;
            const metaWrapperHtml = `
                <div class="${isExpanded ? 'hidden md:block' : 'hidden'} mt-2 rounded border border-gray-200 bg-gray-50 px-3 py-3 participante-meta-panel">
                    ${buildParticipanteDetailsPanel(p, {
                        documentoFormatado: documentoFormatado,
                        cndBadge: cndBadge,
                        situacaoBadge: situacaoBadge,
                        consultaBadge: consultaBadge,
                        origemDetails: origemDetails
                    })}
                </div>
            `;
            const detailsButtonHtml = `
                <button
                    type="button"
                    class="hidden md:inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-900 transition participante-details-toggle"
                    data-participante-id="${p.id}"
                    data-expanded="${isExpanded ? '1' : '0'}"
                    title="${isExpanded ? 'Ocultar detalhes' : 'Ver detalhes'}"
                >
                    <span>Detalhes</span>
                    <svg class="w-3.5 h-3.5 transition-transform participante-details-chevron ${isExpanded ? 'rotate-90' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            `;
            if (!canSelect) {
                state.selectedIds.delete(p.id);
            }

            tr.innerHTML = `
                <td class="hidden md:table-cell md:w-10 md:px-4 md:py-3">
                    <input type="checkbox" class="checkbox-participante w-4 h-4 text-gray-600 rounded border-gray-300 disabled:cursor-not-allowed disabled:opacity-50" data-id="${p.id}" ${isSelected && canSelect ? 'checked' : ''} ${canSelect ? '' : 'disabled'}>
                </td>
                <td class="block md:table-cell md:px-3 md:py-3 md:max-w-0 overflow-hidden">
                    <div class="flex items-start justify-between gap-3 min-w-0 max-w-full overflow-hidden">
                        <div class="min-w-0 max-w-full overflow-hidden flex-1">
                            <div class="flex items-center gap-2 min-w-0 max-w-full">
                                <input type="checkbox" class="checkbox-participante w-4 h-4 text-gray-600 rounded border-gray-300 md:hidden flex-shrink-0 disabled:cursor-not-allowed disabled:opacity-50" data-id="${p.id}" ${isSelected && canSelect ? 'checked' : ''} ${canSelect ? '' : 'disabled'}>
                                <a href="/app/participante/${p.id}" class="block max-w-full truncate text-sm font-semibold text-gray-900 hover:text-gray-600 hover:underline" title="${escNome}" onclick="event.stopPropagation()">${p.razao_social || '-'}</a>
                                ${alertaIconHtml}
                                ${canSelect ? '' : '<span class="inline-flex shrink-0 items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">CPF</span>'}
                            </div>
                        </div>
                        ${detailsButtonHtml}
                    </div>
                    ${metaWrapperHtml}
                </td>
            `;

            // Evento de checkbox (desktop + mobile duplicados, manter sincronizados)
            const checkboxes = tr.querySelectorAll('.checkbox-participante');
            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    if (!canSelect) {
                        cb.checked = false;
                        return;
                    }
                    checkboxes.forEach(other => { other.checked = cb.checked; });
                    toggleParticipante(p.id, cb.checked);
                });
            });

            const detailsBtn = tr.querySelector('.participante-details-toggle');
            if (detailsBtn) {
                detailsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const currentId = parseInt(this.dataset.participanteId, 10);
                    state.expandedParticipanteId = state.expandedParticipanteId === currentId ? null : currentId;
                    renderParticipantes(participantes);
                });
            }

            elements.tabelaBody.appendChild(tr);
        });

        initAlertTooltipTriggers(elements.tabelaBody);
        updateCheckboxTodos();
        syncBulkActionButtons();
    }

    /**
     * Atualiza controles de paginacao.
     */
    function updatePaginacao(pagination) {
        if (!pagination) return;

        state.currentPage = pagination.current_page;
        state.totalPages = pagination.last_page;
        state.totalItems = pagination.total;

        const inicio = (pagination.current_page - 1) * pagination.per_page + 1;
        const fim = Math.min(pagination.current_page * pagination.per_page, pagination.total);

        if (elements.pagInicio) elements.pagInicio.textContent = pagination.total > 0 ? inicio : 0;
        if (elements.pagFim) elements.pagFim.textContent = fim;
        if (elements.pagTotal) elements.pagTotal.textContent = pagination.total;
        if (elements.pagAtual) elements.pagAtual.textContent = `${pagination.current_page} / ${pagination.last_page}`;

        if (elements.btnPagAnterior) elements.btnPagAnterior.disabled = pagination.current_page <= 1;
        if (elements.btnPagProximo) elements.btnPagProximo.disabled = pagination.current_page >= pagination.last_page;
    }

    /**
     * Muda de pagina.
     */
    function changePage(delta) {
        const newPage = state.currentPage + delta;
        if (newPage >= 1 && newPage <= state.totalPages) {
            state.currentPage = newPage;
            loadParticipantes();
        }
    }

    /**
     * Handler de mudanca de filtros.
     */
    function onFilterChange() {
        state.bulkSelectionState.participantes = false;
        state.filters.origem_tipo = elements.filtroOrigem?.value || '';
        state.filters.busca = elements.filtroBusca?.value || '';
        state.filters.tipo_documento = elements.filtroTipoDocumento?.value || '';
        state.filters.situacao_cadastral = elements.filtroSituacaoCadastral?.value || '';
        state.filters.uf = elements.filtroUf?.value || '';
        state.filters.cliente_id = elements.filtroCliente?.value || '';
        state.filters.grupo_id = elements.filtroGrupo?.value || '';
        state.filterContext = null;
        if (elements.participantesContext) {
            elements.participantesContext.classList.add('hidden');
        }
        state.currentPage = 1;
        loadParticipantes();
        syncBulkActionButtons();
    }

    /**
     * Alterna selecao de um participante.
     */
    function toggleParticipante(id, isSelected) {
        const row = elements.tabelaBody?.querySelector(`.checkbox-participante[data-id="${id}"]`);
        if (row && row.disabled) {
            state.selectedIds.delete(id);
            state.bulkSelectionState.participantes = false;
            updateContadorSelecionados();
            updateResumo();
            updateCheckboxTodos();
            syncBulkActionButtons();
            return;
        }

        if (isSelected) {
            state.selectedIds.add(id);
        } else {
            state.selectedIds.delete(id);
            state.bulkSelectionState.participantes = false;
        }
        updateContadorSelecionados();
        updateResumo();
        updateCheckboxTodos();
        updateRowHighlight(id, isSelected);
        syncBulkActionButtons();
    }

    /**
     * Alterna selecao de todos na pagina atual.
     */
    function toggleTodosPagina() {
        const isChecked = elements.checkboxTodos.checked;
        state.bulkSelectionState.participantes = false;
        document.querySelectorAll('.checkbox-participante').forEach(cb => {
            if (cb.disabled) {
                cb.checked = false;
                return;
            }
            const id = parseInt(cb.dataset.id);
            cb.checked = isChecked;
            if (isChecked) {
                state.selectedIds.add(id);
            } else {
                state.selectedIds.delete(id);
            }
            updateRowHighlight(id, isChecked);
        });
        updateContadorSelecionados();
        updateResumo();
        updateCheckboxTodos();
        syncBulkActionButtons();
    }

    /**
     * Seleciona todos os participantes do filtro atual (todas as paginas).
     */
    async function selecionarTodosFilter() {
        if (state.bulkSelectionLoading.participantes) return;

        try {
            setBulkActionLoading('participantes', true);
            let page = 1;
            let lastPage = 1;

            while (page <= lastPage) {
                const params = new URLSearchParams({
                    page: page,
                    per_page: 100
                });

                if (state.filters.grupo_id) params.append('grupo_id', state.filters.grupo_id);
                if (state.filters.cliente_id) params.append('cliente_id', state.filters.cliente_id);
                if (state.filters.origem_tipo) params.append('origem_tipo', state.filters.origem_tipo);
                if (state.filters.tipo_documento) params.append('tipo_documento', state.filters.tipo_documento);
                if (state.filters.situacao_cadastral) params.append('situacao_cadastral', state.filters.situacao_cadastral);
                if (state.filters.uf) params.append('uf', state.filters.uf);
                if (state.filters.busca) params.append('busca', state.filters.busca);

                const response = await fetch(`${window.consultaData.routes.getParticipantes}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) throw new Error('Erro ao buscar participantes');

                const data = await response.json();

                if (data.success && data.data) {
                    data.data.forEach(p => {
                        if (participantePodeConsultar(p)) {
                            state.selectedIds.add(p.id);
                        }
                    });
                    lastPage = data.pagination?.last_page || 1;
                } else {
                    break;
                }

                page++;
            }

            // Atualizar checkboxes visiveis na pagina atual
            document.querySelectorAll('.checkbox-participante').forEach(cb => {
                const id = parseInt(cb.dataset.id);
                if (cb.disabled) {
                    cb.checked = false;
                    updateRowHighlight(id, false);
                    return;
                }
                cb.checked = true;
                updateRowHighlight(id, true);
            });

            state.bulkSelectionState.participantes = true;
            updateContadorSelecionados();
            updateResumo();
            updateCheckboxTodos();
            syncBulkActionButtons();
        } catch (error) {
            console.error('Erro ao selecionar todos:', error);
        } finally {
            setBulkActionLoading('participantes', false);
        }
    }

    /**
     * Limpa toda a selecao.
     */
    function limparSelecao() {
        state.selectedIds.clear();
        state.selectedClienteIds.clear();
        state.selectedGrupoIds.clear();
        state.bulkSelectionState.participantes = false;
        state.bulkSelectionState.clientes = false;
        state.bulkSelectionState.grupos = false;
        document.querySelectorAll('.checkbox-participante').forEach(cb => {
            cb.checked = false;
            const id = parseInt(cb.dataset.id);
            updateRowHighlight(id, false);
        });
        if (elements.checkboxTodos) elements.checkboxTodos.checked = false;
        if (elements.checkboxTodosClientes) {
            elements.checkboxTodosClientes.checked = false;
            elements.checkboxTodosClientes.indeterminate = false;
        }
        // Uncheck visible client checkboxes
        document.querySelectorAll('.checkbox-cliente').forEach(function(cb) { cb.checked = false; });
        if (elements.listaGrupos) {
            elements.listaGrupos.querySelectorAll('.checkbox-grupo').forEach(function(cb) {
                cb.checked = false;
                cb.closest('.grupo-item')?.classList.remove('bg-gray-50');
            });
        }
        updateContadorSelecionados();
        updateResumo();
        updateCheckboxTodos();
        updateCheckboxTodosClientes();
        updateGruposSelectionSummary();
        syncBulkActionButtons();
    }

    /**
     * Atualiza highlight da linha.
     */
    function updateRowHighlight(id, isSelected) {
        const tr = elements.tabelaBody?.querySelector(`tr[data-id="${id}"]`);
        if (tr) {
            if (isSelected) {
                tr.classList.add('bg-gray-50');
            } else {
                tr.classList.remove('bg-gray-50');
            }
        }
    }

    /**
     * Atualiza estado do checkbox "todos".
     */
    function updateCheckboxTodos() {
        if (!elements.checkboxTodos) return;

        const checkboxes = document.querySelectorAll('.checkbox-participante');
        const elegiveis = Array.from(checkboxes).filter(cb => !cb.disabled);
        if (elegiveis.length === 0) {
            elements.checkboxTodos.checked = false;
            elements.checkboxTodos.indeterminate = false;
            elements.checkboxTodos.disabled = true;
            return;
        }

        const checkedCount = elegiveis.filter(cb => cb.checked).length;
        elements.checkboxTodos.disabled = false;

        if (checkedCount === 0) {
            elements.checkboxTodos.checked = false;
            elements.checkboxTodos.indeterminate = false;
        } else if (checkedCount === elegiveis.length) {
            elements.checkboxTodos.checked = true;
            elements.checkboxTodos.indeterminate = false;
        } else {
            elements.checkboxTodos.checked = false;
            elements.checkboxTodos.indeterminate = true;
        }
    }

    /**
     * Atualiza contador de selecionados.
     */
    function updateContadorSelecionados() {
        if (elements.totalSelecionados) {
            elements.totalSelecionados.textContent = state.selectedIds.size;
        }
    }

    function setBulkActionLoading(tab, loading) {
        state.bulkSelectionLoading[tab] = loading;
        syncBulkActionButtons();
    }

    function syncBulkActionButtons() {
        syncBulkButtonState(elements.btnSelecionarTodos, state.bulkSelectionState.participantes, state.bulkSelectionLoading.participantes);
        syncBulkButtonState(elements.btnSelecionarTodosClientesBarra, state.bulkSelectionState.clientes, state.bulkSelectionLoading.clientes);
        syncBulkButtonState(elements.btnSelecionarTodosGrupos, state.bulkSelectionState.grupos, state.bulkSelectionLoading.grupos);
    }

    function syncBulkButtonState(button, isAllSelected, isLoading) {
        if (!button) return;

        if (!button.dataset.defaultLabel) {
            button.dataset.defaultLabel = button.textContent.trim();
        }

        // Keep the button visually immediate even while an async bulk action is in flight.
        button.disabled = false;
        button.classList.remove('opacity-60', 'cursor-not-allowed');
        button.classList.toggle('font-semibold', Boolean(isAllSelected));
        button.textContent = isAllSelected ? 'Todos selecionados' : button.dataset.defaultLabel;
    }

    /**
     * Atualiza estilos visuais dos labels de plano (selected vs unselected).
     */
    function updatePlanoStyles() {
        document.querySelectorAll('.plano-label').forEach(label => {
            const radio = label.querySelector('input[type="radio"]');
            label.classList.remove(
                'border-gray-300',
                'border-gray-400',
                'border-gray-800',
                'bg-gray-50',
                'hover:border-gray-400',
                'hover:bg-gray-50',
                'border-blue-500',
                'bg-blue-50/60',
                'ring-2',
                'ring-blue-100',
                'ring-gray-900/10',
                'shadow-sm',
                'border-gray-200',
                'hover:border-gray-300',
                'hover:bg-gray-500/8'
            );

            if (radio.checked) {
                label.classList.add('border-gray-800', 'bg-gray-50', 'ring-2', 'ring-gray-900/10', 'shadow-sm');
            } else {
                label.classList.add('border-gray-300', 'hover:border-gray-400', 'hover:bg-gray-50');
            }
        });
    }

    /**
     * Atualiza lista de consultas incluidas no card lateral.
     */
    function updateConsultasIncluidas() {
        const container = document.getElementById('lista-consultas-incluidas');
        if (!container) return;

        const planoRadio = document.querySelector('input[name="plano_id"]:checked');
        if (!planoRadio) return;

        const planoId = planoRadio.value;
        const planoData = window.consultaData?.planos?.[planoId];
        if (!planoData || !planoData.consultas) {
            container.innerHTML = '<p class="text-xs text-gray-400">Nenhuma consulta disponivel.</p>';
            return;
        }

        const checkSvg = '<svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';

        container.innerHTML = planoData.consultas.map(consulta => {
            const nome = CONSULTA_NOMES[consulta] || consulta;

            return `<div class="flex items-center gap-2 py-1">
                ${checkSvg}
                <span class="text-xs text-gray-700 flex-1">${nome}</span>
            </div>`;
        }).join('');
    }

    /**
     * Atualiza resumo de custos.
     */
    function updateResumo() {
        const totalParticipantes = state.selectedIds.size;
        const planoSelecionado = document.querySelector('input[name="plano_id"]:checked');
        const custoUnitario = planoSelecionado ? parseInt(planoSelecionado.dataset.custo) : 0;
        const isGratuito = planoSelecionado && planoSelecionado.dataset.gratuito === '1';
        const custoTotal = isGratuito ? 0 : totalParticipantes * custoUnitario;
        const creditosSuficientes = isGratuito || state.credits >= custoTotal;

        if (elements.resumoParticipantes) elements.resumoParticipantes.textContent = totalParticipantes;

        if (isGratuito) {
            if (elements.resumoCustoUnitario) elements.resumoCustoUnitario.textContent = 'Gratis';
            if (elements.resumoCustoTotal) elements.resumoCustoTotal.textContent = 'Gratis';
        } else {
            if (elements.resumoCustoUnitario) elements.resumoCustoUnitario.textContent = `${custoUnitario} ${custoUnitario === 1 ? 'crédito' : 'créditos'}`;
            if (elements.resumoCustoTotal) elements.resumoCustoTotal.textContent = `${custoTotal} ${custoTotal === 1 ? 'crédito' : 'créditos'}`;
        }

        // Alerta de creditos insuficientes
        if (elements.alertaCreditosInsuficientes) {
            elements.alertaCreditosInsuficientes.classList.toggle('hidden', creditosSuficientes);
        }

        // Habilitar/desabilitar botao
        if (elements.btnGerarRelatorio) {
            const shouldDisable = totalParticipantes === 0 || !creditosSuficientes;
            elements.btnGerarRelatorio.disabled = shouldDisable;

            // Atualizar estilos inline do botao
            if (shouldDisable) {
                elements.btnGerarRelatorio.style.backgroundColor = '#d1d5db'; // gray-300
                elements.btnGerarRelatorio.style.color = '#6b7280'; // gray-500
                elements.btnGerarRelatorio.style.cursor = 'not-allowed';
            } else {
                elements.btnGerarRelatorio.style.backgroundColor = '#111827'; // gray-900
                elements.btnGerarRelatorio.style.color = '#ffffff'; // white
                elements.btnGerarRelatorio.style.cursor = 'pointer';
            }
        }
    }

    /**
     * Executa a consulta.
     */
    async function executarConsulta() {
        if (state.isExecuting) return;

        const participanteIds = Array.from(state.selectedIds);
        const planoId = document.querySelector('input[name="plano_id"]:checked')?.value;
        const clienteId = state.filters.cliente_id || null;
        clearInlineError();

        if (participanteIds.length === 0) {
            showInlineErrorMessage('Selecione pelo menos um participante.', null, 'executar-consulta');
            return;
        }

        if (!planoId) {
            showInlineErrorMessage('Selecione um tipo de analise.', null, 'executar-consulta');
            return;
        }

        state.isExecuting = true;
        if (elements.btnGerarRelatorio) {
            elements.btnGerarRelatorio.disabled = true;
            elements.btnGerarRelatorio.style.backgroundColor = '#d1d5db';
            elements.btnGerarRelatorio.style.color = '#6b7280';
            elements.btnGerarRelatorio.style.cursor = 'not-allowed';
        }

        // Mostrar progresso inline
        mostrarProgressoInline();

        try {
            const response = await fetch(window.consultaData.routes.executar, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.consultaData.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    participante_ids: participanteIds,
                    plano_id: parseInt(planoId),
                    cliente_id: clienteId ? parseInt(clienteId) : null,
                    tab_id: state.tabId
                })
            });

            if (response.status === 419) {
                window.location.href = '/login';
                return;
            }

            // Parse JSON - handle invalid JSON response
            let data;
            try {
                data = await response.json();
            } catch (e) {
                console.error('Consulta Lote: resposta invalida do servidor', e);
                onConsultaErro('Resposta invalida do servidor.');
                return;
            }

            // Verificar sucesso (HTTP status + JSON success field)
            if (!response.ok || !data.success) {
                const errorMsg = data?.error || `Erro ${response.status}: ${response.statusText}`;
                console.error('Consulta Lote erro:', errorMsg, data);
                state.isExecuting = false;
                if (elements.btnGerarRelatorio) elements.btnGerarRelatorio.disabled = false;
                onConsultaErro(errorMsg);
                return;
            }

            // Sucesso
            state.consultaLoteId = data.consulta_lote_id;
            state.credits = data.novo_saldo;
            state.etapas = Array.isArray(data.etapas) ? data.etapas : [];
            state.etapaAtual = null;
            if (elements.resumoSaldo) elements.resumoSaldo.textContent = `${data.novo_saldo} créditos`;

            renderEtapasStrip(state.etapas);

            // Iniciar SSE para progresso
            iniciarSSE();

        } catch (error) {
            console.error('Consulta Lote excecao:', error);
            state.isExecuting = false;
            if (elements.btnGerarRelatorio) elements.btnGerarRelatorio.disabled = false;
            onConsultaErro(error.message || 'Erro de conexao. Tente novamente.');
        }
    }

    /**
     * Inicia SSE para acompanhar progresso.
     */
    function iniciarSSE() {
        if (state.eventSource) {
            state.eventSource.close();
            state.eventSource = null;
        }

        var reconnectAttempts = 0;
        var maxReconnectAttempts = 3;
        var reconnectDelays = [3000, 6000, 12000];
        var lastDataHash = null;
        var lastUpdate = 0;
        var throttleMs = 500;
        var pollingInterval = null;

        function pararPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        }

        function connect() {
            var url = window.consultaData.routes.progressoStream + '?tab_id=' + state.tabId;
            state.eventSource = new EventSource(url);

            state.eventSource.onmessage = function(event) {
                // Heartbeat: ignorar ping/vazio
                var raw = event.data;
                if (!raw || raw === ':ping') return;

                // Hash deduplication
                var hash = simpleHash(raw);
                if (hash === lastDataHash) return;
                lastDataHash = hash;

                // Reset contador de reconexao em mensagem bem-sucedida
                reconnectAttempts = 0;

                var data;
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    console.error('Erro ao processar SSE:', e);
                    return;
                }

                // Sempre processar estados terminais (sem throttle)
                if (data.status === 'concluido') {
                    updateProgresso(data.progresso, data.mensagem);
                    state.eventSource.close();
                    state.eventSource = null;
                    pararPolling();
                    onConsultaConcluida();
                    return;
                }
                if (data.status === 'erro') {
                    state.eventSource.close();
                    state.eventSource = null;
                    pararPolling();
                    if (data.etapa) marcarEtapaStatus(data.etapa, 'erro');
                    onConsultaErro(data.error_message || 'Erro desconhecido');
                    return;
                }
                if (data.status === 'timeout') {
                    state.eventSource.close();
                    state.eventSource = null;
                    pararPolling();
                    onConsultaErro(data.mensagem || 'Tempo limite atingido. Verifique o histórico.');
                    return;
                }

                // Throttle de 0.5s para atualizacoes intermediarias
                var now = Date.now();
                if (now - lastUpdate < throttleMs) return;
                lastUpdate = now;

                if (data.etapa) atualizarEtapasProcessando(data.etapa);
                updateProgresso(data.progresso, data.mensagem);
            };

            state.eventSource.onerror = function() {
                console.error('Erro na conexao SSE (tentativa ' + (reconnectAttempts + 1) + ')');
                state.eventSource.close();
                state.eventSource = null;

                if (reconnectAttempts < maxReconnectAttempts) {
                    var delay = reconnectDelays[reconnectAttempts];
                    reconnectAttempts++;
                    setTimeout(connect, delay);
                } else {
                    onConsultaErro('Conexao perdida. Verifique sua internet e tente novamente.');
                }
            };
        }

        connect();

        // Polling fallback: checa status no DB a cada 5s
        if (state.consultaLoteId && window.consultaData.routes.loteStatus) {
            pollingInterval = setInterval(function() {
                var statusUrl = window.consultaData.routes.loteStatus.replace('{id}', state.consultaLoteId);
                fetch(statusUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (!data.success) return;
                        if (data.status === 'concluido') {
                            pararPolling();
                            if (state.eventSource) { state.eventSource.close(); state.eventSource = null; }
                            onConsultaConcluida();
                        } else if (data.status === 'erro') {
                            pararPolling();
                            if (state.eventSource) { state.eventSource.close(); state.eventSource = null; }
                            onConsultaErro('Erro no processamento.');
                        } else {
                            updateProgresso(data.progresso, null);
                        }
                    })
                    .catch(function() {}); // silenciar erros de rede
            }, 5000);

            // Guardar referência para parar no voltarParaFormulario
            state._pollingInterval = pollingInterval;
            state._pararPolling = pararPolling;
        }
    }

    /**
     * Atualiza indicadores de progresso.
     */
    function updateProgresso(percentual, mensagem) {
        if (elements.progressoBarra) elements.progressoBarra.style.width = `${percentual}%`;
        if (elements.progressoPercentual) elements.progressoPercentual.textContent = `${percentual}%`;
        if (elements.progressoMensagem && mensagem) elements.progressoMensagem.textContent = mensagem;
    }

    /**
     * Monta o strip de etapas com base no array retornado por /executar.
     * Esconde o strip quando o plano tem apenas 1 etapa (não agrega valor visual).
     */
    function renderEtapasStrip(etapas) {
        const card = document.getElementById('etapas-consulta-card');
        if (!card) return;

        card.innerHTML = '';

        if (!Array.isArray(etapas) || etapas.length < 2) {
            card.classList.add('hidden');
            return;
        }

        const svgSep = '<svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';

        etapas.forEach(function(etapa, idx) {
            if (idx > 0) card.insertAdjacentHTML('beforeend', svgSep);
            const item = document.createElement('div');
            item.className = 'etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400';
            item.dataset.etapa = String(etapa.numero);
            item.innerHTML =
                '<span class="etapa-icon flex items-center justify-center w-3.5 h-3.5"></span>' +
                '<span>' + escapeHtml(etapa.label || ('Etapa ' + etapa.numero)) + '</span>';
            card.appendChild(item);
            renderEtapa(item, 'pendente');
        });

        card.classList.remove('hidden');
    }

    function renderEtapa(item, status) {
        if (!item) return;
        const svgSpinner = '<svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>';
        const svgCheck   = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
        const svgDash    = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>';
        const svgX       = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>';

        const estados = {
            pendente:   { pill: 'bg-gray-100 text-gray-400',     icon: svgDash,    style: '' },
            processando:{ pill: 'bg-gray-200 text-gray-700',     icon: svgSpinner, style: '' },
            concluido:  { pill: 'text-white',                    icon: svgCheck,   style: 'background-color: #047857' },
            erro:       { pill: 'text-white',                    icon: svgX,       style: 'background-color: #b91c1c' },
        };

        const estado = estados[status] || estados.pendente;
        if (item.dataset.renderedStatus === status) return;

        item.className = 'etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium ' + estado.pill;
        item.style.cssText = estado.style;
        const iconEl = item.querySelector('.etapa-icon');
        if (iconEl) iconEl.innerHTML = estado.icon;
        item.dataset.renderedStatus = status;
    }

    function atualizarEtapasProcessando(etapaAtual) {
        const card = document.getElementById('etapas-consulta-card');
        if (!card || card.classList.contains('hidden')) return;
        state.etapaAtual = etapaAtual;

        card.querySelectorAll('.etapa-item').forEach(function(item) {
            const numero = parseInt(item.dataset.etapa, 10);
            if (numero < etapaAtual) renderEtapa(item, 'concluido');
            else if (numero === etapaAtual) renderEtapa(item, 'processando');
            else renderEtapa(item, 'pendente');
        });
    }

    function marcarEtapaStatus(etapa, status) {
        const card = document.getElementById('etapas-consulta-card');
        if (!card || card.classList.contains('hidden')) return;
        const item = card.querySelector('.etapa-item[data-etapa="' + etapa + '"]');
        if (item) renderEtapa(item, status);
    }

    function marcarTodasEtapasConcluidas() {
        const card = document.getElementById('etapas-consulta-card');
        if (!card || card.classList.contains('hidden')) return;
        card.querySelectorAll('.etapa-item').forEach(function(item) {
            renderEtapa(item, 'concluido');
        });
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * Atualiza ícone e estado visual do card de progresso da consulta.
     */
    function atualizarIconeConsulta(status, errorMessage) {
        const icon = elements.consultaProgressoIcon;
        const card = elements.consultaProgressoCard;
        const barra = elements.progressoBarra;
        const erroDiv = elements.consultaProgressoErro;
        const erroMsg = elements.consultaProgressoErroMsg;
        const suporteLink = elements.consultaProgressoSuporteLink;

        if (!icon || !card) return;

        card.className = 'bg-white border rounded-lg p-4 shadow-sm';

        if (status === 'concluido') {
            icon.className = 'w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0';
            icon.innerHTML = '<svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            card.classList.add('border-green-200');
            if (barra) barra.className = 'bg-green-600 h-full rounded-full transition-all duration-500 ease-out';
            if (erroDiv) erroDiv.classList.add('hidden');
        } else if (status === 'erro') {
            icon.className = 'w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0';
            icon.innerHTML = '<svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
            card.classList.add('border-red-200');
            if (barra) barra.className = 'bg-red-600 h-full rounded-full transition-all duration-500 ease-out';
            if (erroDiv) erroDiv.classList.remove('hidden');
            if (erroMsg && errorMessage) erroMsg.textContent = errorMessage;
            if (suporteLink) {
                suporteLink.href = buildSupportHref(errorMessage || 'Erro na consulta.', 'consulta-lote');
            }
        } else {
            icon.className = 'w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0';
            icon.innerHTML = '<svg class="w-5 h-5 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>';
            card.classList.add('border-gray-200');
            if (barra) barra.className = 'bg-blue-600 h-full rounded-full transition-all duration-500 ease-out';
            if (erroDiv) erroDiv.classList.add('hidden');
        }
    }

    /**
     * Handler de consulta concluida.
     */
    function onConsultaConcluida() {
        updateProgresso(100, 'Concluído');
        marcarTodasEtapasConcluidas();
        atualizarIconeConsulta('concluido');

        // Mostrar seção de resultado inline
        if (elements.resultadoConsulta) {
            elements.resultadoConsulta.classList.remove('hidden');
        }

        // Link de download e auto-download
        if (state.consultaLoteId) {
            const downloadUrl = window.consultaData.routes.baixarLote.replace('{id}', state.consultaLoteId);
            if (elements.linkDownloadRelatorio) {
                elements.linkDownloadRelatorio.href = downloadUrl;
            }
            if (elements.resultadoConsultaInfo) {
                const total = state.selectedIds.size;
                elements.resultadoConsultaInfo.textContent = total + ' participante' + (total !== 1 ? 's' : '');
            }
        }

        // Limpar selecao
        limparSelecao();

        // Carregar tabela de resultados inline
        if (state.consultaLoteId && window.consultaData.routes.resultadosLote) {
            carregarResultados(state.consultaLoteId);
        }
    }

    /**
     * Carrega os resultados do lote e renderiza a tabela inline.
     */
    function carregarResultados(loteId) {
        var url = window.consultaData.routes.resultadosLote.replace('{id}', loteId);

        if (elements.resultadosLoading) elements.resultadosLoading.classList.remove('hidden');
        if (elements.resultadosTableWrapper) elements.resultadosTableWrapper.classList.add('hidden');

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': window.consultaData.csrfToken
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (elements.resultadosLoading) elements.resultadosLoading.classList.add('hidden');
            if (data.success && data.resultados && data.resultados.length > 0) {
                resultadosPaginaAtual = 1;
                renderResultadosTable(data.resultados);
            } else {
                if (elements.resultadosLoading) {
                    elements.resultadosLoading.textContent = 'Nenhum resultado disponível.';
                    elements.resultadosLoading.classList.remove('hidden');
                }
            }
        })
        .catch(function() {
            if (elements.resultadosLoading) {
                elements.resultadosLoading.textContent = 'Não foi possível carregar os resultados.';
                elements.resultadosLoading.classList.remove('hidden');
            }
        });
    }

    /**
     * Renderiza a tabela de resultados no container (com paginação).
     */
    function renderResultadosTable(resultados) {
        if (!elements.resultadosTableWrapper) return;

        todosResultados = resultados;
        resultadosPaginaAtual = 1;
        renderResultadosPagina(1);
    }

    function formatRegularidade(dado) {
        if (dado === null || dado === undefined) return '<span class="text-gray-400">-</span>';
        if (typeof dado === 'object' && dado !== null) {
            var situacao = dado.situacao || dado.status || dado.regularidade || '';
            situacao = String(situacao).toLowerCase();
            if (situacao.includes('regular') && !situacao.includes('irregular')) {
                return '<span class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Regular</span>';
            }
            if (situacao.includes('irregular') || situacao.includes('devedor') || situacao.includes('negativa')) {
                return '<span class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Irregular</span>';
            }
            return '<span class="text-gray-500 text-xs">' + (situacao || '-') + '</span>';
        }
        var val = String(dado).toLowerCase();
        if (val === 'true' || val === 'sim' || val === 'regular') {
            return '<span class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Sim</span>';
        }
        if (val === 'false' || val === 'nao' || val === 'não' || val === 'irregular') {
            return '<span class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Não</span>';
        }
        return '<span class="text-gray-700 text-xs">' + dado + '</span>';
    }

    function formatCnpj(cnpj) {
        if (!cnpj) return '-';
        var d = String(cnpj).replace(/\D/g, '');
        if (d.length === 14) {
            return d.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        }
        return cnpj;
    }

    /**
     * Renderiza a página N dos resultados (usa todosResultados global).
     */
    function renderResultadosPagina(pagina) {
        if (!elements.resultadosTableWrapper) return;

        var total = todosResultados.length;
        var totalPaginas = Math.ceil(total / resultadosPerPage);
        pagina = Math.max(1, Math.min(pagina, totalPaginas || 1));
        resultadosPaginaAtual = pagina;

        var inicio = (pagina - 1) * resultadosPerPage;
        var fim = Math.min(inicio + resultadosPerPage, total);
        var pagResultados = todosResultados.slice(inicio, fim);

        var rows = pagResultados.map(function(r) {
            var statusBadge = r.status === 'sucesso'
                ? '<span class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Sucesso</span>'
                : '<span class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">' + (r.status || 'Erro') + '</span>';

            return '<tr class="border-b border-gray-100 hover:bg-gray-50">'
                + '<td class="px-3 py-2 text-xs text-gray-700 whitespace-nowrap">' + formatCnpj(r.participante && r.participante.cnpj) + '</td>'
                + '<td class="px-3 py-2 text-xs text-gray-700">' + (r.participante ? '<a href="/app/participante/' + r.participante.id + '" class="text-blue-700 hover:text-blue-900 hover:underline" title="Ver perfil">' + (r.participante.razao_social || '-') + '</a>' : '-') + '</td>'
                + '<td class="px-3 py-2 text-xs text-gray-500 text-center">' + ((r.participante && r.participante.uf) || '-') + '</td>'
                + '<td class="px-3 py-2 text-xs text-center">' + (r.situacao_cadastral ? '<span class="text-xs text-gray-700">' + r.situacao_cadastral + '</span>' : '<span class="text-gray-400">-</span>') + '</td>'
                + '<td class="px-3 py-2 text-xs text-center">' + formatRegularidade(r.simples_nacional) + '</td>'
                + '<td class="px-3 py-2 text-xs text-center">' + formatRegularidade(r.mei) + '</td>'
                + '<td class="px-3 py-2 text-xs text-center">' + formatRegularidade(r.cnd_federal) + '</td>'
                + '<td class="px-3 py-2 text-xs text-center">' + formatRegularidade(r.crf_fgts) + '</td>'
                + '<td class="px-3 py-2 text-xs text-center">' + formatRegularidade(r.cndt) + '</td>'
                + '<td class="px-3 py-2 text-xs text-center">' + statusBadge + '</td>'
                + '</tr>';
        }).join('');

        var paginacaoHtml = '';
        if (total > resultadosPerPage) {
            var anteriorDisabled = pagina <= 1 ? ' disabled' : '';
            var proximoDisabled = pagina >= totalPaginas ? ' disabled' : '';
            paginacaoHtml = '<div class="flex items-center justify-between px-3 py-2 text-xs text-gray-500 border-t border-gray-100">'
                + '<span>Exibindo ' + (inicio + 1) + '–' + fim + ' de ' + total + '</span>'
                + '<div class="flex items-center gap-2">'
                + '<button id="res-pag-anterior" class="px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"' + anteriorDisabled + '>← Anterior</button>'
                + '<span>Página ' + pagina + ' de ' + totalPaginas + '</span>'
                + '<button id="res-pag-proximo" class="px-2 py-1 rounded border border-gray-200 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"' + proximoDisabled + '>Próxima →</button>'
                + '</div>'
                + '</div>';
        }

        elements.resultadosTableWrapper.innerHTML = '<table class="min-w-full text-left">'
            + '<thead><tr class="border-b border-gray-200 bg-gray-50">'
            + '<th class="px-3 py-2 text-xs font-semibold text-gray-600 whitespace-nowrap">CNPJ</th>'
            + '<th class="px-3 py-2 text-xs font-semibold text-gray-600">Razão Social</th>'
            + '<th class="px-3 py-2 text-xs font-semibold text-gray-600 text-center">UF</th>'
            + '<th class="px-3 py-2 text-xs font-semibold text-gray-600 text-center">Sit. Cadastral</th>'
            + '<th class="px-3 py-2 text-xs font-semibold text-gray-600 text-center">Simples</th>'
            + '<th class="px-3 py-2 text-xs font-semibold text-gray-600 text-center">MEI</th>'
            + '<th class="px-3 py-2 text-xs font-semibold text-gray-600 text-center">CND Federal</th>'
            + '<th class="px-3 py-2 text-xs font-semibold text-gray-600 text-center">FGTS</th>'
            + '<th class="px-3 py-2 text-xs font-semibold text-gray-600 text-center">CNDT</th>'
            + '<th class="px-3 py-2 text-xs font-semibold text-gray-600 text-center">Status</th>'
            + '</tr></thead>'
            + '<tbody>' + rows + '</tbody>'
            + '</table>'
            + paginacaoHtml;

        elements.resultadosTableWrapper.classList.remove('hidden');

        var btnAnterior = document.getElementById('res-pag-anterior');
        var btnProximo = document.getElementById('res-pag-proximo');
        if (btnAnterior) {
            btnAnterior.addEventListener('click', function() {
                renderResultadosPagina(resultadosPaginaAtual - 1);
            });
        }
        if (btnProximo) {
            btnProximo.addEventListener('click', function() {
                renderResultadosPagina(resultadosPaginaAtual + 1);
            });
        }
    }

    /**
     * Handler de erro na consulta.
     */
    function onConsultaErro(mensagem) {
        atualizarIconeConsulta('erro', mensagem);
        showInlineErrorMessage(mensagem, voltarParaFormulario, 'consulta-lote');
    }

    /**
     * Mostra a seção de progresso inline e oculta o formulário.
     */
    function mostrarProgressoInline() {
        // Reset estado visual
        clearInlineError();
        atualizarIconeConsulta('processando');
        updateProgresso(0, 'Iniciando consulta...');
        if (elements.consultaProgressoErro) elements.consultaProgressoErro.classList.add('hidden');
        if (elements.resultadoConsulta) elements.resultadoConsulta.classList.add('hidden');

        // Trocar seções (usar fallback direto ao DOM)
        var formSec = elements.consultaFormSection || document.getElementById('consulta-form-section');
        var progressSec = elements.consultaProgressoSection || document.getElementById('consulta-progresso-section');
        if (formSec) formSec.style.display = 'none';
        if (progressSec) { progressSec.classList.remove('hidden'); progressSec.style.display = 'block'; }
        window.scrollTo({ top: 0, behavior: 'instant' });
    }

    /**
     * Volta para o formulário e reseta o estado de progresso.
     */
    function voltarParaFormulario() {
        state.isExecuting = false;
        state.tabId = generateUUID();
        if (elements.btnGerarRelatorio) elements.btnGerarRelatorio.disabled = false;

        // Parar polling fallback
        if (state._pararPolling) { state._pararPolling(); state._pararPolling = null; }
        if (state._pollingInterval) { clearInterval(state._pollingInterval); state._pollingInterval = null; }

        // Fechar SSE se aberta
        if (state.eventSource) {
            state.eventSource.close();
            state.eventSource = null;
        }

        // Reset visual
        clearInlineError();
        atualizarIconeConsulta('processando');
        updateProgresso(0, 'Iniciando...');
        if (elements.consultaProgressoErro) elements.consultaProgressoErro.classList.add('hidden');
        if (elements.resultadoConsulta) elements.resultadoConsulta.classList.add('hidden');

        const etapasCard = document.getElementById('etapas-consulta-card');
        if (etapasCard) {
            etapasCard.innerHTML = '';
            etapasCard.classList.add('hidden');
        }
        state.etapas = [];
        state.etapaAtual = null;
        state.consultaLoteId = null;

        // Trocar seções (usar fallback direto ao DOM)
        var formSec = elements.consultaFormSection || document.getElementById('consulta-form-section');
        var progressSec = elements.consultaProgressoSection || document.getElementById('consulta-progresso-section');
        if (progressSec) { progressSec.classList.add('hidden'); progressSec.style.display = ''; }
        if (formSec) formSec.style.display = '';
        updateResumo();
    }

    /**
     * Hash simples para deduplicação de mensagens SSE.
     */
    function simpleHash(str) {
        var hash = 0;
        for (var i = 0; i < str.length; i++) {
            hash = ((hash << 5) - hash) + str.charCodeAt(i);
            hash |= 0;
        }
        return hash;
    }

    /**
     * Mostra/esconde loading.
     */
    function showLoading(show) {
        if (elements.loadingRow) {
            elements.loadingRow.style.display = show ? '' : 'none';
        }
    }

    /**
     * Mostra erro generico.
     */
    function showError(message) {
        showInlineErrorMessage(message, null, 'consulta-listagem');
        if (elements.tabelaBody) {
            elements.tabelaBody.innerHTML = '';
        }
    }

    function showInlineErrorMessage(message, retryFn, action) {
        if (window.showInlineError) {
            window.showInlineError(elements.consultaInlineErrorRegion, {
                message: message,
                retryFn: typeof retryFn === 'function' ? retryFn : undefined,
                context: {
                    action: action || 'consulta-lote',
                    url: window.location.pathname + window.location.search
                }
            });
            return;
        }

        alert(message);
    }

    function clearInlineError() {
        if (window.clearInlineError) {
            window.clearInlineError(elements.consultaInlineErrorRegion);
        }
    }

    function buildSupportHref(message, action) {
        var params = new URLSearchParams();
        params.set('contexto', action || 'consulta-lote');
        params.set('url', window.location.pathname + window.location.search);
        params.set('mensagem', message || 'Erro na consulta.');

        return '/app/suporte?' + params.toString();
    }

    /**
     * Formata CNPJ.
     */
    function formatCnpj(cnpj) {
        if (!cnpj) return '-';
        const numeros = cnpj.replace(/\D/g, '');
        if (numeros.length !== 14) return cnpj;
        return numeros.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    }

    function formatDocumento(documento) {
        if (!documento) return '-';
        const numeros = String(documento).replace(/\D/g, '');
        if (numeros.length === 14) {
            return numeros.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        }
        if (numeros.length === 11) {
            return numeros.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }
        return documento;
    }

    function getOrigemBadge(origemTipo) {
        if (!origemTipo) {
            return '<span class="text-xs text-gray-400">-</span>';
        }

        const chave = String(origemTipo).toUpperCase();
        const origem = ORIGEM_BADGES[chave];

        if (origem) {
            const detalhe = origem.detail ? '<span class="ml-1 text-[10px] text-gray-500">' + origem.detail + '</span>' : '';
            return '<span class="inline-flex items-center"><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: ' + origem.hex + '">' + origem.label + '</span>' + detalhe + '</span>';
        }

        return '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">' + String(origemTipo) + '</span>';
    }

    function getSituacaoBadgeHtml(situacao, naoConsultada) {
        if (naoConsultada) {
            return '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">Não consultada</span>';
        }

        if (!situacao) {
            return '<span class="text-xs text-gray-400">-</span>';
        }

        var situacaoUpper = String(situacao).toUpperCase();
        var hex = '#6b7280';
        var label = situacao;

        if (situacaoUpper === 'ATIVA') {
            hex = '#047857';
            label = 'Ativa';
        } else if (['BAIXADA', 'INAPTA', 'SUSPENSA'].includes(situacaoUpper)) {
            hex = '#d97706';
            label = situacao;
        }

        return '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: ' + hex + '">' + escapeHtml(label) + '</span>';
    }

    function getCndBadgeHtml(participante) {
        return '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: '
            + (participante.cnd_federal_status_hex || '#9ca3af') + '">'
            + escapeHtml(participante.cnd_federal_status_label || 'Não consultada') + '</span>';
    }

    function getConsultaStatusBadgeHtml(participante) {
        return '<span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: '
            + (participante.consulta_status_hex || '#9ca3af') + '">'
            + escapeHtml(participante.consulta_status_label || 'Nunca foi consultado') + '</span>';
    }

    function getOrigemDetailsHtml(participante) {
        var origemBadge = getOrigemBadge(participante.origem_tipo || participante.origem || participante.origem_label);
        return origemBadge + '<span class="text-[11px] text-gray-500">Base: ' + escapeHtml(participante.created_at_formatado || '') + '</span>';
    }

    function getAlertaIconHtml(participante, contextKey) {
        var nivel = participante.alerta_nivel || 'grave';
        var label = participante.alerta_label || 'Nunca consultado';
        var detalhe = participante.alerta_detalhe || '';
        var hex = participante.alerta_hex || '#ea580c';
        var tooltipId = 'alerta-tooltip-' + (contextKey || 'participante') + '-' + participante.id;
        var path = '';

        if (nivel === 'super_grave') {
            // X no círculo (vermelho)
            path = '<circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" fill="none"/><line x1="9" y1="9" x2="15" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="15" y1="9" x2="9" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>';
        } else if (nivel === 'medio') {
            // i no círculo (cinza)
            path = '<circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" fill="none"/><line x1="12" y1="12" x2="12" y2="15.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="9.5" r="1" fill="currentColor"/>';
        } else if (nivel === 'ok') {
            // Check no círculo (verde)
            path = '<circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" fill="none"/><polyline points="8.5,12.5 10.7,14.7 15.5,9.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>';
        } else {
            // ! no círculo (laranja)
            path = '<circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.8" fill="none"/><line x1="12" y1="8.5" x2="12" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="15.5" r="1" fill="currentColor"/>';
        }

        return '<button type="button"'
            + ' class="participante-alert-trigger inline-flex shrink-0 items-center justify-center rounded-sm focus:outline-none focus:ring-1 focus:ring-gray-400"'
            + ' data-alert-tooltip-id="' + escapeHtml(tooltipId) + '"'
            + ' data-alert-title="' + escapeHtml(label) + '"'
            + ' data-alert-detail="' + escapeHtml(detalhe) + '"'
            + ' data-alert-color="' + escapeHtml(hex) + '"'
            + ' aria-label="' + escapeHtml(label + (detalhe ? '. ' + detalhe : '')) + '"'
            + ' aria-expanded="false"'
            + ' aria-haspopup="dialog"'
            + ' style="color: ' + hex + ';">'
            + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">' + path + '</svg>'
            + '</button>';
    }

    function buildParticipanteDetailsPanel(participante, options) {
        options = options || {};
        var documentoFormatado = options.documentoFormatado || formatDocumento(participante.documento_formatado || participante.documento || participante.cnpj || participante.cnpj_formatado);
        var cndBadge = options.cndBadge || getCndBadgeHtml(participante);
        var situacaoBadge = options.situacaoBadge || getSituacaoBadgeHtml(participante.situacao_cadastral, !participante.ultima_consulta_em);
        var consultaBadge = options.consultaBadge || getConsultaStatusBadgeHtml(participante);
        var origemDetails = options.origemDetails || getOrigemDetailsHtml(participante);
        var alertaIcon = getAlertaIconHtml(participante, 'detalhes');
        var cliente = participante.cliente && participante.cliente.razao_social ? escapeHtml(participante.cliente.razao_social) : 'Sem vínculo com cliente';
        var assinatura = participante.assinatura_label
            ? '<div class="mt-2"><span class="inline-flex items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: '
                + (participante.assinatura_hex || '#6b7280') + '">'
                + escapeHtml(participante.assinatura_label) + '</span></div>'
            : '';
        var wrapperClass = 'grid grid-cols-1 md:grid-cols-2 gap-2';
        var itemClass = 'rounded border border-gray-200 bg-white px-3 py-2.5';

        return '<div class="' + wrapperClass + '">'
            + '<div class="md:col-span-2 rounded border border-gray-200 bg-white px-3 py-2.5"><div class="flex items-start gap-2"><span class="mt-0.5">' + alertaIcon + '</span><div><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Alerta</p><p class="text-sm font-semibold text-gray-800">' + escapeHtml(participante.alerta_label || 'Nunca consultado') + '</p><p class="text-[11px] text-gray-500 mt-0.5 leading-tight">' + escapeHtml(participante.alerta_detalhe || 'Participante sem histórico de consulta.') + '</p></div></div></div>'
            + '<div class="' + itemClass + '"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Documento</p><p class="text-sm font-mono text-gray-700">' + escapeHtml(documentoFormatado) + '</p></div>'
            + '<div class="' + itemClass + '"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CND</p>' + cndBadge + '<p class="text-[11px] text-gray-500 mt-1 leading-tight">' + escapeHtml(participante.cnd_federal_meta || 'CND: não consultada') + '</p></div>'
            + '<div class="' + itemClass + '"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Situação cadastral</p>' + situacaoBadge + '<p class="text-[11px] text-gray-500 mt-1 leading-tight">' + escapeHtml(participante.ultima_consulta_em ? 'Dados da última consulta' : 'Situação cadastral: não consultada') + '</p></div>'
            + '<div class="' + itemClass + '"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Status de consulta</p>' + consultaBadge + '<p class="text-[11px] text-gray-500 mt-1 leading-tight">' + escapeHtml(participante.consulta_status_meta || 'Nunca foi consultado') + '</p>' + assinatura + '</div>'
            + '<div class="' + itemClass + '"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Origem</p><div class="flex flex-wrap items-center gap-1.5">' + origemDetails + '</div><p class="text-[11px] text-gray-500 mt-1 leading-tight">Cliente: ' + cliente + '</p></div>'
            + '</div>';
    }

    function buildClienteDetailsPanel(cliente) {
        var alertaIcon = getAlertaIconHtml(cliente, 'cliente-detalhes');
        var documentoFormatado = formatDocumento(cliente.documento);
        var tipoPessoa = cliente.tipo_pessoa || '-';
        var situacao = cliente.situacao_cadastral || 'Não informada';
        var uf = cliente.uf || '-';
        var totalParticipantes = cliente.participantes_count + ' participante' + (cliente.participantes_count !== 1 ? 's' : '');
        var nomeSecundario = cliente.nome ? escapeHtml(cliente.nome) : 'Não informado';
        var empresaPropria = cliente.is_empresa_propria ? 'Sim' : 'Não';
        var itemClass = 'rounded border border-gray-200 bg-white px-3 py-2.5';

        return '<div class="grid grid-cols-1 md:grid-cols-2 gap-2">'
            + '<div class="md:col-span-2 rounded border border-gray-200 bg-white px-3 py-2.5"><div class="flex items-start gap-2"><span class="mt-0.5">' + alertaIcon + '</span><div><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Alerta</p><p class="text-sm font-semibold text-gray-800">' + escapeHtml(cliente.alerta_label || 'Nunca consultado') + '</p><p class="text-[11px] text-gray-500 mt-0.5 leading-tight">' + escapeHtml(cliente.alerta_detalhe || 'Cliente sem histórico consultável.') + '</p></div></div></div>'
            + '<div class="' + itemClass + '"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Documento</p><p class="text-sm font-mono text-gray-700">' + escapeHtml(documentoFormatado) + '</p></div>'
            + '<div class="' + itemClass + '"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Tipo</p><p class="text-sm text-gray-700">' + escapeHtml(tipoPessoa) + '</p></div>'
            + '<div class="' + itemClass + '"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Situação cadastral</p><p class="text-sm text-gray-700">' + escapeHtml(situacao) + '</p></div>'
            + '<div class="' + itemClass + '"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">UF</p><p class="text-sm text-gray-700">' + escapeHtml(uf) + '</p></div>'
            + '<div class="' + itemClass + '"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Participantes vinculados</p><p class="text-sm text-gray-700">' + escapeHtml(totalParticipantes) + '</p></div>'
            + '<div class="' + itemClass + '"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Empresa própria</p><p class="text-sm text-gray-700">' + escapeHtml(empresaPropria) + '</p></div>'
            + '<div class="md:col-span-2 ' + itemClass + '"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Nome complementar</p><p class="text-sm text-gray-700">' + nomeSecundario + '</p></div>'
            + '</div>';
    }

    /**
     * Gera UUID v4.
     */
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    /**
     * Debounce function.
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ==========================================
    // Adicionar CNPJ
    // ==========================================

    /**
     * Aplica mascara de CNPJ: XX.XXX.XXX/XXXX-XX
     */
    function applyCnpjMask(value) {
        var digits = value.replace(/\D/g, '').substring(0, 14);
        if (digits.length <= 2) return digits;
        if (digits.length <= 5) return digits.replace(/(\d{2})(\d+)/, '$1.$2');
        if (digits.length <= 8) return digits.replace(/(\d{2})(\d{3})(\d+)/, '$1.$2.$3');
        if (digits.length <= 12) return digits.replace(/(\d{2})(\d{3})(\d{3})(\d+)/, '$1.$2.$3/$4');
        return digits.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d+)/, '$1.$2.$3/$4-$5');
    }

    /**
     * Adiciona um CNPJ como participante via AJAX.
     */
    async function adicionarCnpj() {
        if (!elements.inputAdicionarCnpj) return;

        var rawValue = elements.inputAdicionarCnpj.value;
        var cnpj = rawValue.replace(/\D/g, '');

        if (cnpj.length !== 14) {
            showFeedbackCnpj('error', 'Informe um CNPJ válido com 14 dígitos.');
            return;
        }

        // Associacao opcional a cliente existente
        var clienteId = elements.selectClienteAssociar?.value || null;

        hideFeedbackCnpj();
        setBtnAdicionarLoading(true);

        try {
            var bodyPayload = { cnpj: cnpj };
            if (clienteId) {
                bodyPayload.cliente_id = parseInt(clienteId);
            }

            var response = await fetch(window.consultaData.routes.adicionarCnpj, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.consultaData.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(bodyPayload)
            });

            var data;
            try {
                data = await response.json();
            } catch (e) {
                showFeedbackCnpj('error', 'Resposta inválida do servidor.');
                return;
            }

            if (!response.ok || !data.success) {
                showFeedbackCnpj('error', data.error || 'Erro ao adicionar CNPJ.');
                return;
            }

            // Sucesso
            var participanteId = data.participante.id;
            state.selectedIds.add(participanteId);

            // Limpar input
            elements.inputAdicionarCnpj.value = '';

            if (data.is_new) {
                showFeedbackCnpj('success', data.message);
                // Reload tabela na pagina 1 para mostrar o novo participante
                state.currentPage = 1;
                await loadParticipantes();
            } else {
                showFeedbackCnpj('info', data.message);
                // Tentar selecionar na tabela visivel
                var checkbox = elements.tabelaBody?.querySelector('.checkbox-participante[data-id="' + participanteId + '"]');
                if (checkbox) {
                    checkbox.checked = true;
                    updateRowHighlight(participanteId, true);
                } else {
                    // Se nao esta na pagina visivel, reload
                    state.currentPage = 1;
                    await loadParticipantes();
                }
            }

            updateContadorSelecionados();
            updateResumo();
            updateCheckboxTodos();

        } catch (error) {
            console.error('Erro ao adicionar CNPJ:', error);
            showFeedbackCnpj('error', 'Erro de conexão. Tente novamente.');
        } finally {
            setBtnAdicionarLoading(false);
        }
    }

    /**
     * Mostra feedback do card de adicionar CNPJ.
     */
    function showFeedbackCnpj(type, message) {
        var el = elements.feedbackAdicionarCnpj;
        if (!el) return;

        el.classList.remove('hidden', 'bg-green-50', 'text-green-700', 'border-green-200',
            'bg-blue-50', 'text-blue-700', 'border-blue-200',
            'bg-red-50', 'text-red-700', 'border-red-200');

        if (type === 'success') {
            el.classList.add('bg-green-50', 'text-green-700', 'border', 'border-green-200');
        } else if (type === 'info') {
            el.classList.add('bg-blue-50', 'text-blue-700', 'border', 'border-blue-200');
        } else {
            el.classList.add('bg-red-50', 'text-red-700', 'border', 'border-red-200');
        }

        el.textContent = message;
        el.classList.remove('hidden');

        // Auto-hide after 5s
        clearTimeout(el._hideTimeout);
        el._hideTimeout = setTimeout(function() { hideFeedbackCnpj(); }, 5000);
    }

    /**
     * Esconde feedback do card de adicionar CNPJ.
     */
    function hideFeedbackCnpj() {
        var el = elements.feedbackAdicionarCnpj;
        if (el) {
            el.classList.add('hidden');
            clearTimeout(el._hideTimeout);
        }
    }

    /**
     * Toggle loading state no botao de adicionar.
     */
    function setBtnAdicionarLoading(loading) {
        var btn = elements.btnAdicionarCnpj;
        if (!btn) return;

        if (loading) {
            btn.disabled = true;
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Adicionando...';
            btn.classList.add('opacity-75');
        } else {
            btn.disabled = false;
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
            }
            btn.classList.remove('opacity-75');
        }
    }

    // ==========================================
    // Inline Expansion de Participantes no tab Clientes
    // ==========================================

    /**
     * Escapa HTML para prevenir XSS ao inserir texto no DOM.
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function ensureAlertTooltipElement() {
        var tooltip = document.getElementById('consulta-alert-tooltip');

        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'consulta-alert-tooltip';
            tooltip.className = 'hidden fixed z-50 max-w-[240px] rounded border border-gray-300 bg-white px-3 py-2 shadow-lg';
            tooltip.setAttribute('role', 'dialog');
            tooltip.setAttribute('aria-live', 'polite');
            tooltip.innerHTML = '<div class="flex items-start gap-2">'
                + '<span class="consulta-alert-tooltip-dot mt-1 h-2 w-2 shrink-0 rounded-full" style="background-color: #ea580c"></span>'
                + '<div class="min-w-0">'
                + '<p class="consulta-alert-tooltip-title text-xs font-semibold text-gray-800"></p>'
                + '<p class="consulta-alert-tooltip-detail mt-0.5 text-[11px] leading-tight text-gray-500"></p>'
                + '</div>'
                + '</div>';

            tooltip.addEventListener('mouseenter', function() {
                clearAlertTooltipCloseTimer();
            });

            tooltip.addEventListener('mouseleave', function() {
                scheduleAlertTooltipClose();
            });

            tooltip.addEventListener('click', function(event) {
                event.stopPropagation();
            });

            document.body.appendChild(tooltip);
        }

        return tooltip;
    }

    function initAlertTooltipTriggers(root) {
        if (!root) return;

        root.querySelectorAll('.participante-alert-trigger').forEach(function(trigger) {
            if (trigger.dataset.tooltipBound === '1') return;

            trigger.dataset.tooltipBound = '1';

            trigger.addEventListener('mouseenter', function() {
                if (window.matchMedia && window.matchMedia('(hover: hover)').matches) {
                    openAlertTooltip(trigger);
                }
            });

            trigger.addEventListener('mouseleave', function() {
                if (window.matchMedia && window.matchMedia('(hover: hover)').matches) {
                    scheduleAlertTooltipClose();
                }
            });

            trigger.addEventListener('focus', function() {
                openAlertTooltip(trigger);
            });

            trigger.addEventListener('blur', function() {
                scheduleAlertTooltipClose();
            });

            trigger.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                if (state.activeAlertTooltipId === trigger.dataset.alertTooltipId) {
                    closeAlertTooltip();
                    return;
                }

                openAlertTooltip(trigger);
            });
        });
    }

    function clearAlertTooltipCloseTimer() {
        if (state.alertTooltipCloseTimer) {
            clearTimeout(state.alertTooltipCloseTimer);
            state.alertTooltipCloseTimer = null;
        }
    }

    function scheduleAlertTooltipClose() {
        clearAlertTooltipCloseTimer();
        state.alertTooltipCloseTimer = setTimeout(function() {
            closeAlertTooltip();
        }, 120);
    }

    function openAlertTooltip(trigger) {
        if (!trigger) return;

        clearAlertTooltipCloseTimer();

        var tooltip = ensureAlertTooltipElement();
        var title = trigger.dataset.alertTitle || 'Alerta';
        var detail = trigger.dataset.alertDetail || '';
        var color = trigger.dataset.alertColor || '#ea580c';
        var tooltipId = trigger.dataset.alertTooltipId || 'consulta-alert-tooltip';

        tooltip.querySelector('.consulta-alert-tooltip-title').textContent = title;
        tooltip.querySelector('.consulta-alert-tooltip-detail').textContent = detail;
        tooltip.querySelector('.consulta-alert-tooltip-dot').style.backgroundColor = color;

        tooltip.classList.remove('hidden');
        tooltip.dataset.activeTooltipId = tooltipId;
        state.activeAlertTooltipId = tooltipId;

        document.querySelectorAll('.participante-alert-trigger[aria-expanded="true"]').forEach(function(activeTrigger) {
            activeTrigger.setAttribute('aria-expanded', activeTrigger === trigger ? 'true' : 'false');
        });
        trigger.setAttribute('aria-expanded', 'true');

        positionAlertTooltip(trigger, tooltip);
    }

    function positionAlertTooltip(trigger, tooltip) {
        if (!trigger || !tooltip) return;

        var rect = trigger.getBoundingClientRect();
        var tooltipRect = tooltip.getBoundingClientRect();
        var margin = 8;
        var top = rect.bottom + margin;
        var left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);

        if (left < margin) {
            left = margin;
        }

        if (left + tooltipRect.width > window.innerWidth - margin) {
            left = window.innerWidth - tooltipRect.width - margin;
        }

        if (top + tooltipRect.height > window.innerHeight - margin) {
            top = rect.top - tooltipRect.height - margin;
        }

        if (top < margin) {
            top = margin;
        }

        tooltip.style.top = top + 'px';
        tooltip.style.left = left + 'px';
    }

    function closeAlertTooltip() {
        clearAlertTooltipCloseTimer();

        var tooltip = document.getElementById('consulta-alert-tooltip');
        if (tooltip) {
            tooltip.classList.add('hidden');
            tooltip.dataset.activeTooltipId = '';
        }

        document.querySelectorAll('.participante-alert-trigger[aria-expanded="true"]').forEach(function(trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        });

        state.activeAlertTooltipId = null;
    }

    function handleAlertTooltipDocumentClick(event) {
        var clickedTrigger = event.target.closest('.participante-alert-trigger');
        var tooltip = document.getElementById('consulta-alert-tooltip');

        if (clickedTrigger) return;
        if (tooltip && tooltip.contains(event.target)) return;

        closeAlertTooltip();
    }

    function handleAlertTooltipKeydown(event) {
        if (event.key === 'Escape') {
            closeAlertTooltip();
        }
    }

    /**
     * Alterna expansao inline de participantes de um cliente.
     */
    async function toggleClienteExpansion(clienteId, clienteLabel) {
        if (state.expandedClienteId === clienteId) {
            collapseClienteExpansion();
            return;
        }

        collapseClienteDetails();

        // Colapsar anterior se houver
        collapseClienteExpansion();

        state.expandedClienteId = clienteId;

        // Highlight a row e rotacionar chevron
        var clienteRow = elements.listaClientes?.querySelector('tr[data-cliente-id="' + clienteId + '"]');
        if (clienteRow) {
            var chevron = clienteRow.querySelector('.chevron-icon');
            if (chevron) chevron.classList.add('rotate-90');
        }

        // Criar row de expansao com loading
        var expansionRow = document.createElement('tr');
        expansionRow.id = 'cliente-expansion-row';
        expansionRow.innerHTML = '<td colspan="2" class="bg-gray-50 border-y border-gray-200 px-5 py-4">'
            + '<div class="flex items-center justify-center gap-2 py-4 text-sm text-gray-500">'
            + '<svg class="animate-spin w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>'
            + 'Carregando participantes...'
            + '</div>'
            + '</td>';

        if (clienteRow && clienteRow.parentNode) {
            clienteRow.parentNode.insertBefore(expansionRow, clienteRow.nextSibling);
        }

        await loadClienteExpansionPage(clienteId, clienteLabel, 1);
    }

    async function loadClienteExpansionPage(clienteId, clienteLabel, page) {
        try {
            var params = new URLSearchParams({
                cliente_id: clienteId,
                per_page: 50,
                page: page || 1
            });

            var response = await fetch(window.consultaData.routes.getParticipantes + '?' + params, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Erro ao carregar participantes');

            var data = await response.json();

            // Verificar se ainda esta expandido (usuario pode ter clicado em outro)
            if (state.expandedClienteId !== clienteId) return;

            if (data.success) {
                state.expandedClientePagination = {
                    clienteId: clienteId,
                    clienteLabel: clienteLabel,
                    currentPage: data.pagination?.current_page || 1,
                    lastPage: data.pagination?.last_page || 1,
                    perPage: data.pagination?.per_page || 50,
                    total: data.pagination?.total || data.data.length
                };
                renderClienteExpansionContent(clienteId, clienteLabel, data.data, data.pagination);
            } else {
                renderClienteExpansionError('Erro ao carregar participantes.');
            }
        } catch (error) {
            console.error('Erro ao carregar participantes do cliente:', error);
            if (state.expandedClienteId === clienteId) {
                renderClienteExpansionError('Erro ao carregar participantes. Tente novamente.');
            }
        }
    }

    /**
     * Colapsa a expansao inline de participantes.
     */
    function collapseClienteExpansion() {
        var expansionRow = document.getElementById('cliente-expansion-row');
        if (expansionRow) {
            expansionRow.remove();
        }

        // Resetar visual da row anterior
        if (state.expandedClienteId && elements.listaClientes) {
            var prevRow = elements.listaClientes.querySelector('tr[data-cliente-id="' + state.expandedClienteId + '"]');
            if (prevRow) {
                var chevron = prevRow.querySelector('.chevron-icon');
                if (chevron) chevron.classList.remove('rotate-90');
            }
        }

        state.expandedClienteId = null;
        state.expandedClientePagination = null;
    }

    function toggleClienteDetails(cliente) {
        if (state.expandedClienteDetailsId === cliente.id) {
            collapseClienteDetails();
            return;
        }

        collapseClienteExpansion();
        collapseClienteDetails();

        state.expandedClienteDetailsId = cliente.id;

        var clienteRow = elements.listaClientes?.querySelector('tr[data-cliente-id="' + cliente.id + '"]');
        if (!clienteRow || !clienteRow.parentNode) return;

        var detailsRow = document.createElement('tr');
        detailsRow.id = 'cliente-details-row';
        detailsRow.innerHTML = '<td colspan="2" class="bg-gray-50 border-y border-gray-200 px-5 py-4">'
            + buildClienteDetailsPanel(cliente)
            + '</td>';

        clienteRow.parentNode.insertBefore(detailsRow, clienteRow.nextSibling);
        initAlertTooltipTriggers(detailsRow);
    }

    function collapseClienteDetails() {
        var detailsRow = document.getElementById('cliente-details-row');
        if (detailsRow) {
            detailsRow.remove();
        }

        state.expandedClienteDetailsId = null;
    }

    /**
     * Renderiza conteudo do painel de expansao com participantes.
     */
    function renderClienteExpansionContent(clienteId, clienteLabel, participantes, pagination) {
        var expansionRow = document.getElementById('cliente-expansion-row');
        if (!expansionRow) return;

        closeAlertTooltip();
        var totalCount = pagination ? pagination.total : participantes.length;

        if (!participantes || participantes.length === 0) {
            expansionRow.innerHTML = '<td colspan="2" class="bg-gray-50 border-y border-gray-200 px-5 py-4">'
                + '<div class="flex items-center justify-between">'
                + '<span class="text-sm text-gray-500">Nenhum participante vinculado a este cliente.</span>'
                + '<button type="button" class="expansion-close text-gray-400 hover:text-gray-600 p-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>'
                + '</div>'
                + '</td>';

            expansionRow.querySelector('.expansion-close')?.addEventListener('click', collapseClienteExpansion);
            return;
        }

        // Verificar quantos estao selecionados deste cliente
        var selectedCount = participantes.filter(function(p) { return state.selectedIds.has(p.id); }).length;
        var participantesElegiveis = participantes.filter(function(p) { return participantePodeConsultar(p); });
        var allSelected = participantesElegiveis.length > 0 && selectedCount === participantesElegiveis.length;

        var html = '<td colspan="2" class="bg-gray-50 border-y border-gray-200 px-5 py-4">'
            // Header
            + '<div class="flex items-center justify-between mb-3">'
            + '<div class="flex items-center gap-3">'
            + '<span class="text-sm font-medium text-gray-700">Participantes de ' + escapeHtml(clienteLabel) + '</span>'
            + '<span class="text-xs text-gray-400">' + totalCount + ' encontrado' + (totalCount !== 1 ? 's' : '') + '</span>'
            + '</div>'
            + '<div class="flex items-center gap-3">'
            + '<button type="button" class="expansion-select-all text-xs font-medium text-blue-600 hover:text-blue-800 transition">'
            + (allSelected ? 'Desmarcar todos' : 'Selecionar todos')
            + '</button>'
            + '<button type="button" class="expansion-close text-gray-400 hover:text-gray-600 p-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>'
            + '</div>'
            + '</div>'
            // Lista de participantes
            + '<div class="max-h-80 overflow-y-auto space-y-0.5">';

        participantes.forEach(function(p) {
            var isSelected = state.selectedIds.has(p.id);
            var canSelect = participantePodeConsultar(p);
            var alertaIconHtml = getAlertaIconHtml(p, 'cliente');

            html += '<label class="flex items-start gap-3 px-3 py-2 rounded-md hover:bg-white cursor-pointer transition'
                + (isSelected ? ' bg-white ring-1 ring-blue-200' : '') + (canSelect ? '' : ' opacity-70') + '">'
                + '<input type="checkbox" class="expansion-checkbox w-4 h-4 text-blue-600 rounded border-gray-300 disabled:cursor-not-allowed disabled:opacity-50" data-participante-id="' + p.id + '"'
                + (isSelected && canSelect ? ' checked' : '') + (canSelect ? '' : ' disabled') + '>'
                + '<span class="flex items-center gap-2 min-w-0 flex-1 overflow-hidden">'
                + '<a href="/app/participante/' + p.id + '" class="text-sm text-gray-900 hover:text-gray-600 hover:underline truncate" title="Ver perfil" onclick="event.stopPropagation()">' + escapeHtml(p.razao_social || '-') + '</a>'
                + alertaIconHtml
                + (canSelect ? '' : '<span class="inline-flex shrink-0 items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">CPF</span>')
                + '</span>'
                + '</label>';
        });

        html += '</div>';

        var currentPage = pagination?.current_page || 1;
        var lastPage = pagination?.last_page || 1;
        var perPage = pagination?.per_page || participantes.length || 1;
        var totalItems = pagination?.total || participantes.length;
        var startItem = totalItems > 0 ? ((currentPage - 1) * perPage) + 1 : 0;
        var endItem = totalItems > 0 ? Math.min(currentPage * perPage, totalItems) : 0;

        html += '<div class="mt-3 pt-3 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">'
            + '<span class="text-[10px] text-gray-500 uppercase tracking-wide">Mostrando ' + startItem + ' a ' + endItem + ' de ' + totalItems + '</span>'
            + '<div class="flex items-center gap-2">'
            + '<button type="button" class="expansion-prev px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-40"'
            + (currentPage <= 1 ? ' disabled' : '') + '>Anterior</button>'
            + '<span class="text-[10px] text-gray-500 uppercase tracking-wide">' + currentPage + ' / ' + lastPage + '</span>'
            + '<button type="button" class="expansion-next px-3 py-1.5 text-[10px] text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-40"'
            + (currentPage >= lastPage ? ' disabled' : '') + '>Próximo</button>'
            + '</div>'
            + '</div>';

        html += '</td>';

        expansionRow.innerHTML = html;
        initAlertTooltipTriggers(expansionRow);

        // Bind eventos nos checkboxes
        expansionRow.querySelectorAll('.expansion-checkbox').forEach(function(cb) {
            cb.addEventListener('change', function() {
                if (cb.disabled) {
                    cb.checked = false;
                    return;
                }
                var pid = parseInt(cb.dataset.participanteId);
                toggleParticipante(pid, cb.checked);

                // Atualizar visual do label
                var label = cb.closest('label');
                if (label) {
                    if (cb.checked) {
                        label.classList.add('bg-white', 'ring-1', 'ring-blue-200');
                    } else {
                        label.classList.remove('bg-white', 'ring-1', 'ring-blue-200');
                    }
                }

                // Atualizar texto do "Selecionar todos"
                updateExpansionSelectAllText(expansionRow, participantes);
            });
        });

        // Bind "Selecionar todos"
        var selectAllBtn = expansionRow.querySelector('.expansion-select-all');
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                var currentlyAllSelected = participantesElegiveis.length > 0 && participantesElegiveis.every(function(p) { return state.selectedIds.has(p.id); });

                participantesElegiveis.forEach(function(p) {
                    if (currentlyAllSelected) {
                        state.selectedIds.delete(p.id);
                    } else {
                        state.selectedIds.add(p.id);
                    }
                });

                updateContadorSelecionados();
                updateResumo();
                updateCheckboxTodos();

                // Re-render o conteudo para refletir o novo estado
                renderClienteExpansionContent(clienteId, clienteLabel, participantes, pagination);
            });
        }

        var prevBtn = expansionRow.querySelector('.expansion-prev');
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                if (prevBtn.disabled) return;
                loadClienteExpansionPage(clienteId, clienteLabel, currentPage - 1);
            });
        }

        var nextBtn = expansionRow.querySelector('.expansion-next');
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                if (nextBtn.disabled) return;
                loadClienteExpansionPage(clienteId, clienteLabel, currentPage + 1);
            });
        }

        // Bind fechar
        expansionRow.querySelector('.expansion-close')?.addEventListener('click', collapseClienteExpansion);
    }

    /**
     * Atualiza o texto do botao "Selecionar todos" / "Desmarcar todos".
     */
    function updateExpansionSelectAllText(expansionRow, participantes) {
        var btn = expansionRow.querySelector('.expansion-select-all');
        if (!btn) return;
        var participantesElegiveis = participantes.filter(function(p) { return participantePodeConsultar(p); });
        var allSelected = participantesElegiveis.length > 0 && participantesElegiveis.every(function(p) { return state.selectedIds.has(p.id); });
        btn.disabled = participantesElegiveis.length === 0;
        btn.classList.toggle('opacity-50', participantesElegiveis.length === 0);
        btn.textContent = allSelected ? 'Desmarcar todos' : 'Selecionar todos';
    }

    /**
     * Renderiza mensagem de erro no painel de expansao.
     */
    function renderClienteExpansionError(message) {
        var expansionRow = document.getElementById('cliente-expansion-row');
        if (!expansionRow) return;

        expansionRow.innerHTML = '<td colspan="2" class="bg-gray-50 border-y border-gray-200 px-5 py-4">'
            + '<div class="flex items-center justify-between">'
            + '<span class="text-sm text-red-500">' + escapeHtml(message) + '</span>'
            + '<button type="button" class="expansion-close text-gray-400 hover:text-gray-600 p-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>'
            + '</div>'
            + '</td>';

        expansionRow.querySelector('.expansion-close')?.addEventListener('click', collapseClienteExpansion);
    }

    // ==========================================
    // Client Selection (checkbox-based bulk selection)
    // ==========================================

    /**
     * Alterna selecao de um cliente (adiciona/remove todos seus participantes).
     */
    async function toggleClienteSelection(clienteId, checked) {
        if (state.bulkSelectionLoading.clientes) return;

        try {
            var response = await fetch(window.consultaData.routes.participantesPorClientes, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.consultaData.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ cliente_ids: [clienteId] })
            });

            var data = await response.json();
            if (!data.success) return;

            var ids = data.ids || [];

            if (checked) {
                state.selectedClienteIds.add(clienteId);
                ids.forEach(function(id) { state.selectedIds.add(id); });
            } else {
                state.selectedClienteIds.delete(clienteId);
                ids.forEach(function(id) { state.selectedIds.delete(id); });
                state.bulkSelectionState.clientes = false;
            }

            // Atualizar checkboxes visiveis na aba participantes
            document.querySelectorAll('.checkbox-participante').forEach(function(cb) {
                var pid = parseInt(cb.dataset.id);
                cb.checked = state.selectedIds.has(pid);
                updateRowHighlight(pid, cb.checked);
            });

            updateContadorSelecionados();
            updateResumo();
            updateCheckboxTodos();
            updateCheckboxTodosClientes();
            updateClientesSelectionSummary();
            syncBulkActionButtons();
        } catch (error) {
            console.error('Erro ao selecionar cliente:', error);
        }
    }

    /**
     * Alterna selecao de todos os clientes visiveis.
     */
    async function toggleTodosClientes(forceChecked) {
        if (state.bulkSelectionLoading.clientes) return;

        var isChecked = typeof forceChecked === 'boolean'
            ? forceChecked
            : (elements.checkboxTodosClientes ? elements.checkboxTodosClientes.checked : false);

        // Coletar IDs de clientes visiveis no DOM (dedup: cada cliente tem 2 checkboxes mobile/desktop)
        var clienteIdSet = new Set();
        document.querySelectorAll('.checkbox-cliente').forEach(function(cb) {
            clienteIdSet.add(parseInt(cb.dataset.clienteId));
        });
        var clienteIds = Array.from(clienteIdSet);

        if (clienteIds.length === 0) return;

        // Reflect the intended selection immediately; participant IDs sync after the request.
        state.bulkSelectionState.clientes = isChecked;
        document.querySelectorAll('.checkbox-cliente').forEach(function(cb) {
            cb.checked = isChecked;
        });
        updateCheckboxTodosClientes();
        updateClientesSelectionSummary();
        syncBulkActionButtons();

        try {
            setBulkActionLoading('clientes', true);
            var response = await fetch(window.consultaData.routes.participantesPorClientes, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.consultaData.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ cliente_ids: clienteIds })
            });

            var data = await response.json();
            if (!data.success) return;

            var ids = data.ids || [];

            if (isChecked) {
                clienteIds.forEach(function(cid) { state.selectedClienteIds.add(cid); });
                ids.forEach(function(id) { state.selectedIds.add(id); });
            } else {
                clienteIds.forEach(function(cid) { state.selectedClienteIds.delete(cid); });
                ids.forEach(function(id) { state.selectedIds.delete(id); });
            }
            state.bulkSelectionState.clientes = isChecked;

            // Atualizar checkboxes de participantes visiveis
            document.querySelectorAll('.checkbox-participante').forEach(function(cb) {
                var pid = parseInt(cb.dataset.id);
                cb.checked = state.selectedIds.has(pid);
                updateRowHighlight(pid, cb.checked);
            });

            updateContadorSelecionados();
            updateResumo();
            updateCheckboxTodos();
            updateCheckboxTodosClientes();
            updateClientesSelectionSummary();
            syncBulkActionButtons();
        } catch (error) {
            state.bulkSelectionState.clientes = false;
            document.querySelectorAll('.checkbox-cliente').forEach(function(cb) {
                cb.checked = false;
            });
            updateCheckboxTodosClientes();
            updateClientesSelectionSummary();
            syncBulkActionButtons();
            console.error('Erro ao selecionar todos clientes:', error);
        } finally {
            setBulkActionLoading('clientes', false);
        }
    }

    /**
     * Atualiza estado do checkbox "todos clientes" (header).
     */
    function updateCheckboxTodosClientes() {
        if (!elements.checkboxTodosClientes) return;

        var checkboxes = document.querySelectorAll('.checkbox-cliente');
        if (checkboxes.length === 0) {
            elements.checkboxTodosClientes.checked = false;
            elements.checkboxTodosClientes.indeterminate = false;
            state.bulkSelectionState.clientes = false;
            return;
        }

        var checkedCount = Array.from(checkboxes).filter(function(cb) { return cb.checked; }).length;

        if (checkedCount === 0) {
            elements.checkboxTodosClientes.checked = false;
            elements.checkboxTodosClientes.indeterminate = false;
            state.bulkSelectionState.clientes = false;
        } else if (checkedCount === checkboxes.length) {
            elements.checkboxTodosClientes.checked = true;
            elements.checkboxTodosClientes.indeterminate = false;
            state.bulkSelectionState.clientes = true;
        } else {
            elements.checkboxTodosClientes.checked = false;
            elements.checkboxTodosClientes.indeterminate = true;
            state.bulkSelectionState.clientes = false;
        }

        updateClientesSelectionSummary();
        syncBulkActionButtons();
    }

    function updateClientesSelectionSummary() {
        if (elements.totalClientesSelecionados) {
            elements.totalClientesSelecionados.textContent = state.selectedClienteIds.size;
        }
    }

    async function clearSelectedClientes() {
        if (state.selectedClienteIds.size === 0) {
            state.bulkSelectionState.clientes = false;
            updateClientesSelectionSummary();
            updateCheckboxTodosClientes();
            syncBulkActionButtons();
            return;
        }

        try {
            var clienteIds = Array.from(state.selectedClienteIds);
            var response = await fetch(window.consultaData.routes.participantesPorClientes, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.consultaData.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ cliente_ids: clienteIds })
            });

            var data = await response.json();
            if (!data.success) return;

            (data.ids || []).forEach(function(id) { state.selectedIds.delete(id); });
            state.selectedClienteIds.clear();
            state.bulkSelectionState.clientes = false;

            document.querySelectorAll('.checkbox-cliente').forEach(function(cb) {
                cb.checked = false;
            });
            document.querySelectorAll('.checkbox-participante').forEach(function(cb) {
                var pid = parseInt(cb.dataset.id);
                cb.checked = state.selectedIds.has(pid);
                updateRowHighlight(pid, cb.checked);
            });

            updateContadorSelecionados();
            updateResumo();
            updateCheckboxTodos();
            updateCheckboxTodosClientes();
            updateClientesSelectionSummary();
            syncBulkActionButtons();
        } catch (error) {
            console.error('Erro ao limpar selecao de clientes:', error);
        }
    }

    // ==========================================
    // Tab Switching (Participantes / Clientes / Grupos)
    // ==========================================

    function switchTab(tabName) {
        // Colapsar expansion ao sair do tab Clientes
        if (state.activeTab === 'clientes' && tabName !== 'clientes') {
            collapseClienteExpansion();
            collapseClienteDetails();
        }

        state.activeTab = tabName;

        // Atualizar visuais das abas
        elements.searchTabs?.forEach(function(t) {
            if (t.dataset.tab === tabName) {
                t.classList.add('bg-gray-800', 'text-white');
                t.classList.remove('text-gray-600', 'text-gray-500', 'hover:text-gray-900', 'hover:text-gray-700', 'bg-white', 'text-gray-900', 'shadow-sm');
            } else {
                t.classList.remove('bg-gray-800', 'text-white', 'bg-white', 'text-gray-900', 'shadow-sm');
                t.classList.add('text-gray-600', 'hover:text-gray-900');
            }
        });

        // Mostrar/esconder views
        document.querySelectorAll('.search-view').forEach(function(v) { v.classList.add('hidden'); });

        if (tabName === 'participantes') {
            if (elements.viewParticipantes) elements.viewParticipantes.classList.remove('hidden');
            updateCheckboxTodos();
        } else if (tabName === 'clientes') {
            if (elements.viewClientes) elements.viewClientes.classList.remove('hidden');
            loadClientes();
        } else if (tabName === 'grupos') {
            if (elements.viewGrupos) elements.viewGrupos.classList.remove('hidden');
            loadGrupos();
        }

        syncBulkActionButtons();
    }

    async function loadClientes() {
        state.clientesFilters.busca = elements.buscaClientes?.value || '';
        state.clientesFilters.tipo_pessoa = elements.filtroClientesTipoPessoa?.value || '';
        state.clientesFilters.situacao_cadastral = elements.filtroClientesSituacaoCadastral?.value || '';
        state.clientesFilters.uf = elements.filtroClientesUf?.value || '';
        state.clientesFilters.faixa_participantes = elements.filtroClientesFaixaParticipantes?.value || '';

        var params = new URLSearchParams();
        if (state.clientesFilters.busca) params.append('busca', state.clientesFilters.busca);
        if (state.clientesFilters.tipo_pessoa) params.append('tipo_pessoa', state.clientesFilters.tipo_pessoa);
        if (state.clientesFilters.situacao_cadastral) params.append('situacao_cadastral', state.clientesFilters.situacao_cadastral);
        if (state.clientesFilters.uf) params.append('uf', state.clientesFilters.uf);
        if (state.clientesFilters.faixa_participantes) params.append('faixa_participantes', state.clientesFilters.faixa_participantes);

        try {
            var response = await fetch(window.consultaData.routes.getClientes + '?' + params, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            var data = await response.json();
            if (data.success) {
                renderClientes(data.data);
            }
        } catch (error) {
            console.error('Erro ao carregar clientes:', error);
            if (elements.listaClientes) {
                elements.listaClientes.innerHTML = '<tr><td colspan="2" class="px-4 py-8 text-center text-sm text-red-500">Erro ao carregar clientes.</td></tr>';
            }
        }
    }

    function renderClientes(clientes) {
        if (!elements.listaClientes) return;

        // Rebuild do tbody colapsa a expansion naturalmente
        state.expandedClienteId = null;
        state.expandedClienteDetailsId = null;

        if (!clientes || clientes.length === 0) {
            elements.listaClientes.innerHTML = '<tr><td colspan="2" class="px-4 py-8 text-center text-sm text-gray-500">Nenhum cliente encontrado.</td></tr>';
            updateCheckboxTodosClientes();
            updateClientesSelectionSummary();
            return;
        }

        elements.listaClientes.innerHTML = '';

        clientes.forEach(function(c) {
            var isClienteSelected = state.selectedClienteIds.has(c.id);
            var alertaIconHtml = getAlertaIconHtml(c, 'cliente-row');
            var tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 cursor-pointer transition flex flex-col gap-1 px-4 py-3 md:table-row md:px-0 md:py-0 md:gap-0' + (isClienteSelected ? ' bg-gray-50' : '');
            tr.dataset.clienteId = c.id;
            tr.dataset.clienteLabel = (c.razao_social || '').replace(/"/g, '&quot;');

            var tipoBadge = c.tipo_pessoa === 'PJ'
                ? '<span class="inline-flex shrink-0 items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">PJ</span>'
                : '<span class="inline-flex shrink-0 items-center whitespace-nowrap px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #9ca3af">PF</span>';
            var propriaDot = c.is_empresa_propria
                ? '<span class="shrink-0 w-2 h-2 rounded-full bg-green-500" title="Empresa propria"></span>'
                : '';
            var nomeTitle = (c.razao_social || '-') + (c.is_empresa_propria ? ' (Empresa propria)' : '');
            var totalParticipantesLabel = c.participantes_count + ' participante' + (c.participantes_count !== 1 ? 's' : '');
            var isExpanded = state.expandedClienteId === c.id;
            var isDetailsExpanded = state.expandedClienteDetailsId === c.id;
            var chevronSvg = '<svg class="chevron-icon w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200' + (isExpanded ? ' rotate-90' : '') + '" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>';
            var detailsChevronSvg = '<svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform duration-200' + (isDetailsExpanded ? ' rotate-90' : '') + '" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>';

            tr.innerHTML =
                '<td class="hidden md:table-cell md:w-10 md:px-4 md:py-3">'
                + '<input type="checkbox" class="checkbox-cliente w-4 h-4 text-gray-600 rounded border-gray-300" data-cliente-id="' + c.id + '"' + (isClienteSelected ? ' checked' : '') + '>'
                + '</td>'
                + '<td class="block overflow-hidden md:table-cell md:px-3 md:py-3 md:max-w-0">'
                + '<div class="flex items-start justify-between gap-3 min-w-0 max-w-full overflow-hidden">'
                + '<div class="min-w-0 max-w-full overflow-hidden flex-1">'
                + '<div class="flex items-center gap-2 min-w-0 max-w-full">'
                + '<input type="checkbox" class="checkbox-cliente w-4 h-4 text-gray-600 rounded border-gray-300 md:hidden flex-shrink-0" data-cliente-id="' + c.id + '"' + (isClienteSelected ? ' checked' : '') + '>'
                + propriaDot
                + '<a href="/app/cliente/' + c.id + '" class="block max-w-full truncate text-sm font-semibold text-gray-900 hover:text-gray-600 hover:underline" title="' + nomeTitle.replace(/"/g, '&quot;') + '" onclick="event.stopPropagation()">' + (c.razao_social || '-') + '</a>'
                + alertaIconHtml
                + tipoBadge
                + '</div>'
                + '<div class="mt-1 flex items-center gap-2 text-xs text-gray-500 min-w-0 overflow-hidden">'
                + '<span class="truncate">' + totalParticipantesLabel + '</span>'
                + '</div>'
                + (c.nome ? '<div class="mt-0.5 text-xs text-gray-500 truncate">' + c.nome + '</div>' : '')
                + '</div>'
                + '<div class="hidden md:flex flex-col items-end gap-1 shrink-0">'
                + '<button type="button" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-900 transition cliente-details-toggle" data-cliente-id="' + c.id + '" title="' + (isDetailsExpanded ? 'Ocultar detalhes' : 'Ver detalhes') + '">'
                + '<span>Detalhes</span>'
                + detailsChevronSvg
                + '</button>'
                + '<button type="button" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-900 transition cliente-expand-toggle" data-cliente-id="' + c.id + '" title="' + (isExpanded ? 'Ocultar participantes' : 'Ver participantes') + '">'
                + '<span>Participantes</span>'
                + chevronSvg
                + '</button>'
                + '</div>'
                + '</div>'
                + '</td>'
                ;

            // Checkbox click: select/deselect all participantes of this client (desktop + mobile)
            var clienteCheckboxes = tr.querySelectorAll('.checkbox-cliente');
            clienteCheckboxes.forEach(function(cb) {
                cb.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent row click (expansion)
                });
                cb.addEventListener('change', function() {
                    clienteCheckboxes.forEach(function(other) { other.checked = cb.checked; });
                    toggleClienteSelection(c.id, cb.checked);
                });
            });

            var expandBtn = tr.querySelector('.cliente-expand-toggle');
            if (expandBtn) {
                expandBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleClienteExpansion(c.id, c.razao_social || '');
                });
            }

            var detailsBtn = tr.querySelector('.cliente-details-toggle');
            if (detailsBtn) {
                detailsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleClienteDetails(c);
                });
            }

            elements.listaClientes.appendChild(tr);
        });

        initAlertTooltipTriggers(elements.listaClientes);
        state.bulkSelectionState.clientes = clientes.length > 0 && clientes.every(function(c) {
            return state.selectedClienteIds.has(c.id);
        });
        updateCheckboxTodosClientes();
        updateClientesSelectionSummary();
        syncBulkActionButtons();
    }

    async function loadGrupos() {
        try {
            var response = await fetch(window.consultaData.routes.getGrupos, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            var data = await response.json();
            if (data.success) {
                renderGrupos(data.data);
            }
        } catch (error) {
            console.error('Erro ao carregar grupos:', error);
            if (elements.listaGrupos) {
                elements.listaGrupos.innerHTML = '<div class="px-5 py-8 text-center text-sm text-red-500">Erro ao carregar grupos.</div>';
            }
        }
    }

    function renderGrupos(grupos) {
        if (!elements.listaGrupos) return;

        if (!grupos || grupos.length === 0) {
            elements.listaGrupos.innerHTML = '<div class="px-5 py-8 text-center text-sm text-gray-500">Nenhum grupo criado.</div>';
            updateGruposSelectionSummary();
            return;
        }

        elements.listaGrupos.innerHTML = grupos.map(function(g) {
            var cor = g.cor || '#3B82F6';
            var isSelected = state.selectedGrupoIds.has(g.id);
            return '<button type="button" class="w-full px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition text-left grupo-item' + (isSelected ? ' bg-gray-50' : '') + '" data-grupo-id="' + g.id + '" data-grupo-label="' + (g.nome || '').replace(/"/g, '&quot;') + '">'
                + '<div class="flex items-center gap-2.5 min-w-0 flex-1">'
                + '<input type="checkbox" class="checkbox-grupo w-4 h-4 text-gray-600 rounded border-gray-300 flex-shrink-0" data-grupo-id="' + g.id + '"' + (isSelected ? ' checked' : '') + '>'
                + '<span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: ' + cor + '"></span>'
                + '<span class="text-sm font-medium text-gray-900 truncate">' + (g.nome || '-') + '</span>'
                + '</div>'
                + '<span class="text-xs text-gray-400 flex-shrink-0 whitespace-nowrap">' + g.participantes_count + ' participante' + (g.participantes_count !== 1 ? 's' : '') + '</span>'
                + '</button>';
        }).join('');

        // Bind click events
        elements.listaGrupos.querySelectorAll('.grupo-item').forEach(function(item) {
            var checkbox = item.querySelector('.checkbox-grupo');
            if (checkbox) {
                checkbox.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
                checkbox.addEventListener('change', function(e) {
                    e.stopPropagation();
                    var grupoId = parseInt(checkbox.dataset.grupoId);
                    if (checkbox.checked) {
                        state.selectedGrupoIds.add(grupoId);
                        item.classList.add('bg-gray-50');
                    } else {
                        state.selectedGrupoIds.delete(grupoId);
                        item.classList.remove('bg-gray-50');
                        state.bulkSelectionState.grupos = false;
                    }
                    updateGruposSelectionSummary();
                    syncBulkActionButtons();
                });
            }
            item.addEventListener('click', function() {
                var grupoId = parseInt(item.dataset.grupoId);
                var label = item.dataset.grupoLabel;
                setFilterContext('grupo', grupoId, label);
            });
        });

        state.bulkSelectionState.grupos = grupos.length > 0 && grupos.every(function(g) {
            return state.selectedGrupoIds.has(g.id);
        });
        updateGruposSelectionSummary();
        syncBulkActionButtons();
    }

    function selectAllVisibleGrupos() {
        if (!elements.listaGrupos) return;
        setBulkActionLoading('grupos', true);
        elements.listaGrupos.querySelectorAll('.checkbox-grupo').forEach(function(cb) {
            cb.checked = true;
            state.selectedGrupoIds.add(parseInt(cb.dataset.grupoId));
            cb.closest('.grupo-item')?.classList.add('bg-gray-50');
        });
        state.bulkSelectionState.grupos = true;
        updateGruposSelectionSummary();
        setBulkActionLoading('grupos', false);
    }

    function clearSelectedGrupos() {
        state.selectedGrupoIds.clear();
        state.bulkSelectionState.grupos = false;
        if (!elements.listaGrupos) {
            updateGruposSelectionSummary();
            syncBulkActionButtons();
            return;
        }
        elements.listaGrupos.querySelectorAll('.checkbox-grupo').forEach(function(cb) {
            cb.checked = false;
            cb.closest('.grupo-item')?.classList.remove('bg-gray-50');
        });
        updateGruposSelectionSummary();
        syncBulkActionButtons();
    }

    function updateGruposSelectionSummary() {
        if (elements.totalGruposSelecionados) {
            elements.totalGruposSelecionados.textContent = state.selectedGrupoIds.size;
        }
    }

    function participantePodeConsultar(participante) {
        if (typeof participante?.pode_consultar !== 'undefined') {
            return Boolean(participante.pode_consultar);
        }

        const documento = String(
            participante?.documento_normalizado
            || participante?.documento
            || participante?.cnpj
            || ''
        ).replace(/\D/g, '');

        return documento.length === 14;
    }

    function setFilterContext(type, id, label) {
        // Clientes agora usam inline expansion; apenas grupos passam por aqui
        if (type !== 'grupo') return;

        state.filterContext = { type: type, id: id, label: label };

        state.filters.grupo_id = id;
        state.filters.cliente_id = '';
        if (elements.filtroGrupo) elements.filtroGrupo.value = String(id);
        if (elements.filtroCliente) elements.filtroCliente.value = '';

        state.currentPage = 1;

        // Voltar para aba participantes com barra de contexto
        switchTab('participantes');
        showFilterContext(label, type);
        loadParticipantes();
    }

    function showFilterContext(label, type) {
        if (elements.participantesContext) {
            elements.participantesContext.classList.remove('hidden');
        }
        if (elements.filterContextLabel) {
            var prefix = type === 'cliente' ? 'Cliente:' : 'Grupo:';
            elements.filterContextLabel.textContent = prefix + ' ' + label;
        }
    }

    function clearFilterContext() {
        state.filterContext = null;
        state.filters.cliente_id = '';
        state.filters.grupo_id = '';
        if (elements.filtroCliente) elements.filtroCliente.value = '';
        if (elements.filtroGrupo) elements.filtroGrupo.value = '';
        state.currentPage = 1;

        if (elements.participantesContext) {
            elements.participantesContext.classList.add('hidden');
        }

        loadParticipantes();
    }

    function resetParticipantesFilters() {
        state.filters = {
            grupo_id: '',
            cliente_id: '',
            origem_tipo: '',
            tipo_documento: '',
            situacao_cadastral: '',
            uf: '',
            busca: ''
        };
        state.filterContext = null;
        state.currentPage = 1;

        if (elements.filtroBusca) elements.filtroBusca.value = '';
        if (elements.filtroOrigem) elements.filtroOrigem.value = '';
        if (elements.filtroTipoDocumento) elements.filtroTipoDocumento.value = '';
        if (elements.filtroSituacaoCadastral) elements.filtroSituacaoCadastral.value = '';
        if (elements.filtroUf) elements.filtroUf.value = '';
        if (elements.filtroCliente) elements.filtroCliente.value = '';
        if (elements.filtroGrupo) elements.filtroGrupo.value = '';
        if (elements.participantesContext) elements.participantesContext.classList.add('hidden');

        loadParticipantes();
    }

    function resetClientesFilters() {
        state.clientesFilters = {
            busca: '',
            tipo_pessoa: '',
            situacao_cadastral: '',
            uf: '',
            faixa_participantes: ''
        };

        if (elements.buscaClientes) elements.buscaClientes.value = '';
        if (elements.filtroClientesTipoPessoa) elements.filtroClientesTipoPessoa.value = '';
        if (elements.filtroClientesSituacaoCadastral) elements.filtroClientesSituacaoCadastral.value = '';
        if (elements.filtroClientesUf) elements.filtroClientesUf.value = '';
        if (elements.filtroClientesFaixaParticipantes) elements.filtroClientesFaixaParticipantes.value = '';

        loadClientes();
    }

    // ==========================================
    // Modal Carousel de Planos (Lote)
    // ==========================================
    var swiperPlanosLote = null;
    var modalPlanosLote = null;

    function initCarouselPlanos() {
        modalPlanosLote = document.getElementById('modal-planos-carousel-lote');
        if (!modalPlanosLote) return;

        var planosData = window.consultaData?.planosDetalhados || [];
        var totalPlanos = planosData.length;
        if (totalPlanos === 0) return;

        // Precompute footer button color classes from corClasses
        var corClassesData = window.consultaData?.corClasses || {};
        var footerBtnColors = planosData.map(function(pd) {
            var cc = corClassesData[pd.cor];
            return cc ? cc.btn : 'bg-blue-600 hover:bg-blue-700';
        });
        var allFooterBtnClasses = ['bg-green-600', 'hover:bg-green-700', 'bg-blue-600', 'hover:bg-blue-700', 'bg-purple-600', 'hover:bg-purple-700', 'bg-amber-600', 'hover:bg-amber-700', 'bg-slate-700', 'hover:bg-slate-800'];

        function showPlanosModalLote(startIndex) {
            if (!modalPlanosLote) return;
            modalPlanosLote.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            setTimeout(function() {
                if (swiperPlanosLote && !swiperPlanosLote.destroyed) {
                    swiperPlanosLote.slideToLoop(startIndex || 0, 0);
                    swiperPlanosLote.update();
                    updateCounterLote(startIndex || 0);
                    updateFooterButtonLote(startIndex || 0);
                    return;
                }

                swiperPlanosLote = new Swiper('#swiper-planos-lote', {
                    slidesPerView: 1,
                    spaceBetween: 0,
                    loop: true,
                    initialSlide: startIndex || 0,
                    navigation: {
                        prevEl: '#swiper-planos-prev-lote',
                        nextEl: '#swiper-planos-next-lote',
                    },
                    pagination: {
                        el: '#swiper-planos-pagination-lote',
                        clickable: true,
                    },
                    on: {
                        slideChange: function() {
                            updateCounterLote(this.realIndex);
                            updateFooterButtonLote(this.realIndex);
                        },
                    },
                });

                updateCounterLote(startIndex || 0);
                updateFooterButtonLote(startIndex || 0);
            }, 50);
        }

        function hidePlanosModalLote() {
            if (!modalPlanosLote) return;
            modalPlanosLote.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function updateCounterLote(index) {
            var counter = document.getElementById('carousel-counter-lote');
            if (counter) {
                counter.textContent = (index + 1) + ' / ' + totalPlanos;
            }
        }

        function updateFooterButtonLote(index) {
            var btn = document.getElementById('btn-selecionar-plano-footer-lote');
            if (!btn) return;
            var pd = planosData[index] || {};
            btn.dataset.planoIndex = index;
            allFooterBtnClasses.forEach(function(c) { btn.classList.remove(c); });
            var corStr = pd.locked ? 'bg-slate-700 hover:bg-slate-800' : (footerBtnColors[index] || 'bg-blue-600 hover:bg-blue-700');
            corStr.split(' ').forEach(function(c) { btn.classList.add(c); });
            btn.disabled = !!pd.locked;
            btn.classList.toggle('opacity-60', !!pd.locked);
            btn.classList.toggle('cursor-not-allowed', !!pd.locked);
            btn.innerHTML = pd.locked
                ? '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2h-1V9a5 5 0 00-10 0v2H6a2 2 0 00-2 2v6a2 2 0 002 2zm3-10V9a3 3 0 116 0v2"></path></svg> Faça a primeira recarga'
                : '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Selecionar este produto';
        }

        // Info button clicks -> open modal at specific slide
        document.querySelectorAll('.btn-info-plano-lote').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var idx = parseInt(this.dataset.slideIndex) || 0;
                showPlanosModalLote(idx);
            });
        });

        // "Ver detalhes" header button
        var btnVerDetalhes = document.getElementById('btn-ver-detalhes-planos-lote');
        if (btnVerDetalhes) {
            btnVerDetalhes.addEventListener('click', function() {
                showPlanosModalLote(0);
            });
        }

        // Close modal: overlay click
        modalPlanosLote.addEventListener('click', function(e) {
            if (e.target === modalPlanosLote) {
                hidePlanosModalLote();
            }
        });

        // Close modal: ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalPlanosLote && !modalPlanosLote.classList.contains('hidden')) {
                hidePlanosModalLote();
            }
        });

        // Close modal: X button
        var btnFechar = document.getElementById('btn-fechar-carousel-lote');
        if (btnFechar) {
            btnFechar.addEventListener('click', hidePlanosModalLote);
        }

        // "Selecionar este plano" footer button
        var btnSelecionarFooter = document.getElementById('btn-selecionar-plano-footer-lote');
        if (btnSelecionarFooter) {
            btnSelecionarFooter.addEventListener('click', function() {
                var idx = parseInt(this.dataset.planoIndex) || 0;
                var pd = planosData[idx];
                if (!pd) return;
                if (pd.locked) {
                    window.location.href = '/app/creditos';
                    return;
                }

                // Find the matching plano_id from window.consultaData.planos
                var planoId = null;
                var planosMap = window.consultaData?.planos || {};
                for (var id in planosMap) {
                    if (planosMap[id].codigo === pd.codigo) {
                        planoId = id;
                        break;
                    }
                }

                if (planoId) {
                    var radio = document.querySelector('input[name="plano_id"][value="' + planoId + '"]');
                    if (radio) {
                        radio.checked = true;
                        updatePlanoStyles();
                        updateConsultasIncluidas();
                        updateResumo();
                    }
                }

                hidePlanosModalLote();
            });
        }
    }

    // Expor funcao de inicializacao para SPA
    window.initConsultaLote = function() {
        // Debounce: prevent double-init from SPA + inline auto-init
        var now = Date.now();
        if (window._consultaLoteLastInit && (now - window._consultaLoteLastInit) < 100) return;
        window._consultaLoteLastInit = now;

        // Reset state for SPA re-navigation
        state.selectedIds = new Set();
        state.selectedClienteIds = new Set();
        state.selectedGrupoIds = new Set();
        state.currentPage = 1;
        state.totalPages = 1;
        state.totalItems = 0;
        state.allIdsCurrentFilter = [];
        state.filters = { grupo_id: '', cliente_id: '', origem_tipo: '', tipo_documento: '', situacao_cadastral: '', uf: '', busca: '' };
        state.clientesFilters = { busca: '', tipo_pessoa: '', situacao_cadastral: '', uf: '', faixa_participantes: '' };
        state.activeTab = 'participantes';
        state.filterContext = null;
        state.expandedClienteDetailsId = null;
        state.expandedClienteId = null;
        voltarParaFormulario();
        state.credits = window.consultaData?.credits || 0;

        init();
        initCarouselPlanos();
    };

    window.reloadParticipantes = function() {
        loadParticipantes();
    };
})();
