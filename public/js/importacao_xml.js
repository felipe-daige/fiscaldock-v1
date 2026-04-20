(function() {
    'use strict';

    const API_BASE = '/api/xml';
    let selectedFiles = [];
    let currentPage = 1;

    // Elementos DOM
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('xml-files');
    const filesPreview = document.getElementById('files-preview');
    const filesList = document.getElementById('files-list');
    const btnUpload = document.getElementById('btn-upload');
    const btnProcessar = document.getElementById('btn-processar');
    const documentosTableBody = document.getElementById('documentos-table-body');
    const filterStatus = document.getElementById('filter-status');
    const modalAjuste = document.getElementById('modal-ajuste');
    const formAjuste = document.getElementById('form-ajuste');
    const btnFecharModal = document.getElementById('btn-fechar-modal');
    const btnCancelarAjuste = document.getElementById('btn-cancelar-ajuste');
    const modalNovaRegra = document.getElementById('modal-nova-regra');
    const formNovaRegra = document.getElementById('form-nova-regra');
    const btnNovaRegra = document.getElementById('btn-nova-regra');
    const btnFecharModalRegra = document.getElementById('btn-fechar-modal-regra');
    const btnCancelarRegra = document.getElementById('btn-cancelar-regra');
    const regrasList = document.getElementById('regras-list');
    const processSteps = document.getElementById('process-steps');
    const timeCounterContainer = document.getElementById('time-counter-container');
    const timeManual = document.getElementById('time-manual');
    const timeRubi = document.getElementById('time-rubi');
    const errorRegion = document.getElementById('importacao-xml-error-region');

    // Inicialização
    function init() {
        setupUpload();
        setupModals();
        loadDocumentos();
        loadRegras();
        setupFilters();
        setupScrollAnimations();
    }

    // Setup Upload
    function setupUpload() {
        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        // File input
        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        // Upload button
        btnUpload.addEventListener('click', uploadFiles);
    }

    // Handle files selection
    function handleFiles(files) {
        const xmlFiles = Array.from(files).filter(file => {
            return file.name.toLowerCase().endsWith('.xml') || 
                   file.type === 'application/xml' || 
                   file.type === 'text/xml';
        });

        if (xmlFiles.length === 0) {
            showErrorMessage('Por favor, selecione apenas arquivos XML', 'importacao-xml-validacao');
            return;
        }

        selectedFiles = xmlFiles;
        
        // Animação de absorção quando arquivo é solto
        animateFileAbsorption();
        
        // Mostrar animações de processamento
        setTimeout(() => {
            showProcessingAnimation();
        }, 500);
        
        updateFilesPreview();
    }

    // Animação de arquivo sendo absorvido
    function animateFileAbsorption() {
        const uploadAreaContent = uploadArea.querySelector('p');
        if (uploadAreaContent) {
            uploadAreaContent.classList.add('file-absorb');
            setTimeout(() => {
                uploadAreaContent.classList.remove('file-absorb');
            }, 1000);
        }
    }

    // Mostrar animação de processamento (checkmarks e contador)
    function showProcessingAnimation() {
        // Mostrar container de passos
        if (processSteps) {
            processSteps.classList.remove('hidden');
        }

        // Mostrar container de contador de tempo
        if (timeCounterContainer) {
            timeCounterContainer.classList.remove('hidden');
        }

        // Animar contador de tempo manual para Rubi
        if (timeManual && timeRubi) {
            animateTimeCounter();
        }

        // Animar checkmarks sequencialmente
        animateCheckmarks();
    }

    // Variável para controlar o intervalo do contador
    let timeCounterInterval = null;

    // Animar contador de tempo
    function animateTimeCounter() {
        if (!timeManual || !timeRubi) return;

        // Limpar intervalo anterior se existir
        if (timeCounterInterval) {
            clearInterval(timeCounterInterval);
        }

        // Resetar para 4 horas
        if (timeManual) {
            timeManual.textContent = '4 horas';
            timeManual.classList.remove('animating');
        }

        // Começar com 4 horas
        let totalSeconds = 4 * 3600; // 4 horas em segundos

        // Animar contagem regressiva rápida (simulação)
        timeCounterInterval = setInterval(() => {
            totalSeconds = Math.max(0, totalSeconds - 120); // Decrementar 120 segundos por vez para animação rápida
            
            const currentHours = Math.floor(totalSeconds / 3600);
            const currentMinutes = Math.floor((totalSeconds % 3600) / 60);
            const currentSeconds = totalSeconds % 60;

            if (timeManual) {
                if (currentHours > 0) {
                    timeManual.textContent = `${currentHours}h ${currentMinutes}min`;
                } else if (currentMinutes > 0) {
                    timeManual.textContent = `${currentMinutes}min ${currentSeconds}s`;
                } else {
                    timeManual.textContent = `${currentSeconds}s`;
                }
                timeManual.classList.add('animating');
            }

            // Quando chegar a 0, mostrar 30 segundos
            if (totalSeconds <= 0) {
                clearInterval(timeCounterInterval);
                timeCounterInterval = null;
                setTimeout(() => {
                    if (timeManual) {
                        timeManual.textContent = '30 segundos';
                        timeManual.classList.remove('animating');
                    }
                    if (timeRubi) {
                        timeRubi.classList.add('animating');
                        setTimeout(() => {
                            timeRubi.classList.remove('animating');
                        }, 500);
                    }
                }, 300);
            }
        }, 80); // Atualizar a cada 80ms para animação suave mas rápida
    }

    // Animar checkmarks sequencialmente
    function animateCheckmarks() {
        const stepClassificado = document.getElementById('step-classificado');
        const stepLancado = document.getElementById('step-lancado');
        const stepValidado = document.getElementById('step-validado');

        // Resetar checkmarks antes de animar
        [stepClassificado, stepLancado, stepValidado].forEach(step => {
            if (step) {
                step.classList.remove('show');
            }
        });

        if (stepClassificado) {
            setTimeout(() => {
                stepClassificado.classList.add('show');
            }, 800);
        }

        if (stepLancado) {
            setTimeout(() => {
                stepLancado.classList.add('show');
            }, 1500);
        }

        if (stepValidado) {
            setTimeout(() => {
                stepValidado.classList.add('show');
            }, 2200);
        }
    }

    // Setup scroll animations
    function setupScrollAnimations() {
        const sections = document.querySelectorAll('.section-fade-in');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        sections.forEach(section => {
            observer.observe(section);
        });
    }

    // Update files preview
    function updateFilesPreview() {
        filesList.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            const div = document.createElement('div');
            div.className = 'file-preview';
            div.innerHTML = `
                <span class="flex-1 text-sm text-gray-700">${file.name}</span>
                <span class="text-xs text-gray-500">${formatFileSize(file.size)}</span>
                <button class="text-red-500 hover:text-red-700" onclick="removeFile(${index})">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            filesList.appendChild(div);
        });

        filesPreview.classList.remove('hidden');
        uploadArea.classList.add('has-files');
    }

    // Remove file
    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        updateFilesPreview();
        
        if (selectedFiles.length === 0) {
            filesPreview.classList.add('hidden');
            uploadArea.classList.remove('has-files');
        }
    };

    // Upload files
    async function uploadFiles() {
        if (selectedFiles.length === 0) {
            showErrorMessage('Selecione pelo menos um arquivo', 'importacao-xml-upload');
            return;
        }

        clearInlineError();

        btnUpload.disabled = true;
        btnUpload.textContent = 'Enviando...';

        // Mostrar animações durante upload
        if (processSteps) {
            processSteps.classList.remove('hidden');
        }
        if (timeCounterContainer) {
            timeCounterContainer.classList.remove('hidden');
        }
        if (timeManual && timeRubi) {
            animateTimeCounter();
        }
        animateCheckmarks();

        const formData = new FormData();
        selectedFiles.forEach(file => {
            formData.append('xmls[]', file);
        });

        try {
            const response = await fetch(`${API_BASE}/upload`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                selectedFiles = [];
                filesPreview.classList.add('hidden');
                uploadArea.classList.remove('has-files');
                fileInput.value = '';
                
                // Resetar animações
                if (processSteps) {
                    processSteps.classList.add('hidden');
                    document.querySelectorAll('.process-step').forEach(step => {
                        step.classList.remove('show');
                    });
                }
                if (timeCounterContainer) {
                    timeCounterContainer.classList.add('hidden');
                }
                
                loadDocumentos();
            } else {
                showErrorMessage(data.message || 'Erro ao fazer upload', 'importacao-xml-upload');
                if (data.erros && data.erros.length > 0) {
                    console.error('Erros:', data.erros);
                }
            }
        } catch (error) {
            console.error('Erro:', error);
            showErrorMessage('Erro ao fazer upload. Tente novamente.', 'importacao-xml-upload');
        } finally {
            btnUpload.disabled = false;
            btnUpload.textContent = 'Enviar Arquivos';
        }
    }

    // Load documentos
    async function loadDocumentos(page = 1) {
        const status = filterStatus.value;
        const params = new URLSearchParams({ page });
        if (status) params.append('status', status);

        try {
            const response = await fetch(`${API_BASE}/documentos?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                renderDocumentos(data.documentos.data || []);
                renderPagination(data.documentos);
            }
        } catch (error) {
            console.error('Erro ao carregar documentos:', error);
        }
    }

    // Render documentos table
    function renderDocumentos(documentos) {
        if (documentos.length === 0) {
            documentosTableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        Nenhum documento encontrado
                    </td>
                </tr>
            `;
            return;
        }

        documentosTableBody.innerHTML = documentos.map(doc => {
            const lancamento = doc.lancamentos && doc.lancamentos.length > 0 ? doc.lancamentos[0] : null;
            const dataEmissao = new Date(doc.data_emissao).toLocaleDateString('pt-BR');
            const valor = parseFloat(doc.valor_total).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });

            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${dataEmissao}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${doc.cnpj_emitente || '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${valor}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${doc.cfop || '-'}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">${lancamento ? lancamento.natureza_operacao : '-'}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="status-badge status-${doc.status}">${doc.status}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        ${doc.status === 'processado' && lancamento ? `
                            <button onclick="aceitarLancamento(${lancamento.id})" class="text-green-600 hover:text-green-900 mr-2">
                                Aceitar
                            </button>
                            <button onclick="abrirModalAjuste(${lancamento.id}, '${lancamento.natureza_operacao}', '${lancamento.conta_debito || ''}', '${lancamento.conta_credito || ''}')" class="text-blue-600 hover:text-blue-900">
                                Ajustar
                            </button>
                        ` : doc.status === 'pendente' ? `
                            <span class="text-gray-400">Aguardando processamento</span>
                        ` : ''}
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Render pagination
    function renderPagination(pagination) {
        const paginationDiv = document.getElementById('pagination');
        if (!pagination || pagination.last_page <= 1) {
            paginationDiv.innerHTML = '';
            return;
        }

        let html = '<div class="flex gap-2">';
        
        if (pagination.current_page > 1) {
            html += `<button onclick="loadDocumentos(${pagination.current_page - 1})" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Anterior</button>`;
        }

        for (let i = 1; i <= pagination.last_page; i++) {
            if (i === pagination.current_page) {
                html += `<span class="px-3 py-1 bg-blue-500 text-white rounded text-sm">${i}</span>`;
            } else {
                html += `<button onclick="loadDocumentos(${i})" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">${i}</button>`;
            }
        }

        if (pagination.current_page < pagination.last_page) {
            html += `<button onclick="loadDocumentos(${pagination.current_page + 1})" class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Próxima</button>`;
        }

        html += '</div>';
        paginationDiv.innerHTML = html;
    }

    // Processar documentos pendentes
    btnProcessar.addEventListener('click', async () => {
        btnProcessar.disabled = true;
        btnProcessar.textContent = 'Processando...';
        clearInlineError();

        try {
            const response = await fetch(`${API_BASE}/processar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                loadDocumentos();
            } else {
                showErrorMessage(data.message || 'Erro ao processar', 'importacao-xml-processar');
            }
        } catch (error) {
            console.error('Erro:', error);
            showErrorMessage('Erro ao processar documentos', 'importacao-xml-processar');
        } finally {
            btnProcessar.disabled = false;
            btnProcessar.textContent = 'Processar Pendentes';
        }
    });

    // Aceitar lançamento
    window.aceitarLancamento = async function(lancamentoId) {
        try {
            clearInlineError();
            const response = await fetch(`${API_BASE}/aceitar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ lancamento_id: lancamentoId })
            });

            const data = await response.json();

            if (data.success) {
                showToast('Lançamento aceito com sucesso', 'success');
                loadDocumentos();
            } else {
                showErrorMessage(data.message || 'Erro ao aceitar lançamento', 'importacao-xml-aceitar');
            }
        } catch (error) {
            console.error('Erro:', error);
            showErrorMessage('Erro ao aceitar lançamento', 'importacao-xml-aceitar');
        }
    };

    // Abrir modal de ajuste
    window.abrirModalAjuste = function(lancamentoId, natureza, contaDebito, contaCredito) {
        document.getElementById('ajuste-lancamento-id').value = lancamentoId;
        document.getElementById('ajuste-natureza').value = natureza;
        document.getElementById('ajuste-conta-debito').value = contaDebito || '';
        document.getElementById('ajuste-conta-credito').value = contaCredito || '';
        modalAjuste.classList.remove('hidden');
    };

    // Setup modals
    function setupModals() {
        // Modal ajuste
        btnFecharModal.addEventListener('click', () => {
            modalAjuste.classList.add('hidden');
        });

        btnCancelarAjuste.addEventListener('click', () => {
            modalAjuste.classList.add('hidden');
        });

        formAjuste.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = {
                lancamento_id: document.getElementById('ajuste-lancamento-id').value,
                natureza_operacao: document.getElementById('ajuste-natureza').value,
                conta_debito: document.getElementById('ajuste-conta-debito').value,
                conta_credito: document.getElementById('ajuste-conta-credito').value,
                salvar_como_regra: document.getElementById('ajuste-salvar-regra').checked
            };

            try {
                clearInlineError();
                const response = await fetch(`${API_BASE}/ajustar`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Ajuste salvo com sucesso', 'success');
                    modalAjuste.classList.add('hidden');
                    loadDocumentos();
                    loadRegras();
                } else {
                    showErrorMessage(data.message || 'Erro ao salvar ajuste', 'importacao-xml-ajuste');
                }
            } catch (error) {
                console.error('Erro:', error);
                showErrorMessage('Erro ao salvar ajuste', 'importacao-xml-ajuste');
            }
        });

        // Modal nova regra
        btnNovaRegra.addEventListener('click', () => {
            modalNovaRegra.classList.remove('hidden');
        });

        btnFecharModalRegra.addEventListener('click', () => {
            modalNovaRegra.classList.add('hidden');
        });

        btnCancelarRegra.addEventListener('click', () => {
            modalNovaRegra.classList.add('hidden');
        });

        formNovaRegra.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(formNovaRegra);
            const data = {
                nome_regra: formData.get('nome_regra'),
                condicoes: {
                    cnpj_fornecedor: formData.get('condicoes[cnpj_fornecedor]') || null,
                    cfop: formData.get('condicoes[cfop]') || null,
                    regime_tributario: formData.get('condicoes[regime_tributario]') || null
                },
                acao: {
                    natureza_operacao: formData.get('acao[natureza_operacao]'),
                    conta_debito: formData.get('acao[conta_debito]') || null,
                    conta_credito: formData.get('acao[conta_credito]') || null
                },
                prioridade: parseInt(formData.get('prioridade')) || 50
            };

            try {
                clearInlineError();
                const response = await fetch(`${API_BASE}/regras`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Regra criada com sucesso', 'success');
                    modalNovaRegra.classList.add('hidden');
                    formNovaRegra.reset();
                    loadRegras();
                } else {
                    showErrorMessage(result.message || 'Erro ao criar regra', 'importacao-xml-regra');
                }
            } catch (error) {
                console.error('Erro:', error);
                showErrorMessage('Erro ao criar regra', 'importacao-xml-regra');
            }
        });
    }

    // Load regras
    async function loadRegras() {
        try {
            const response = await fetch(`${API_BASE}/regras`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                renderRegras(data.regras || []);
            }
        } catch (error) {
            console.error('Erro ao carregar regras:', error);
            regrasList.innerHTML = '<p class="text-sm text-red-500">Erro ao carregar regras</p>';
        }
    }

    // Render regras
    function renderRegras(regras) {
        if (regras.length === 0) {
            regrasList.innerHTML = '<p class="text-sm text-gray-500">Nenhuma regra cadastrada</p>';
            return;
        }

        regrasList.innerHTML = regras.map(regra => `
            <div class="p-3 bg-gray-50 rounded border border-gray-200">
                <div class="flex justify-between items-start mb-2">
                    <h4 class="font-semibold text-sm text-gray-900">${regra.nome_regra}</h4>
                    <span class="text-xs text-gray-500">${regra.vezes_usada}x usado</span>
                </div>
                <p class="text-xs text-gray-600 mb-1">
                    <strong>Ação:</strong> ${regra.acao.natureza_operacao}
                </p>
                ${regra.ativo ? '' : '<span class="text-xs text-red-500">Inativa</span>'}
            </div>
        `).join('');
    }

    // Setup filters
    function setupFilters() {
        filterStatus.addEventListener('change', () => {
            loadDocumentos(1);
        });
    }

    // Helper functions
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    function showToast(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            alert(message);
        }
    }

    function showErrorMessage(message, action) {
        if (window.showInlineError) {
            window.showInlineError(errorRegion, {
                message: message,
                context: {
                    action: action || 'importacao-xml',
                    url: window.location.pathname + window.location.search,
                },
            });
            return;
        }

        showToast(message, 'error');
    }

    function clearInlineError() {
        if (window.clearInlineError) {
            window.clearInlineError(errorRegion);
        }
    }

    // Expor função global para paginação
    window.loadDocumentos = loadDocumentos;

    // Expor função init para o SPA
    window.initImportacaoXml = init;

    // Inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
