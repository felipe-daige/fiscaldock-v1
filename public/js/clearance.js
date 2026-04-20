/**
 * Validacao Contabil Inteligente - Frontend Script
 * Vanilla JS following project patterns
 */

(function() {
    'use strict';

    // State
    let currentImportacaoId = null;
    let currentNotaIds = [];
    let currentTipo = 'completa';

    // DOM Elements
    const modal = document.getElementById('modal-validacao');
    const modalContent = document.getElementById('modal-content');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const modalCancelar = document.getElementById('modal-cancelar');
    const modalConfirmar = document.getElementById('modal-confirmar');
    const pageErrorRegion = document.getElementById('validacao-error-region');
    const notaErrorRegion = document.getElementById('validacao-nota-error-region');

    /**
     * Initialize page handlers
     */
    function init() {
        // Validar importacao buttons
        document.querySelectorAll('.btn-validar-importacao').forEach(btn => {
            btn.addEventListener('click', handleValidarImportacao);
        });

        // Salvar validacao button (nota detail page)
        const btnSalvar = document.getElementById('btn-salvar-validacao');
        if (btnSalvar) {
            btnSalvar.addEventListener('click', handleSalvarValidacao);
        }

        // Modal handlers
        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', closeModal);
        }
        if (modalCancelar) {
            modalCancelar.addEventListener('click', closeModal);
        }
        if (modalConfirmar) {
            modalConfirmar.addEventListener('click', handleConfirmarValidacao);
        }

        // Filter handlers
        const filtroNivel = document.getElementById('filtro-nivel');
        if (filtroNivel) {
            filtroNivel.addEventListener('change', handleFiltroChange);
        }

        const filtroCategoria = document.getElementById('filtro-categoria');
        if (filtroCategoria) {
            filtroCategoria.addEventListener('change', handleFiltroChange);
        }
    }

    /**
     * Handle validar importacao click
     */
    async function handleValidarImportacao(e) {
        const btn = e.currentTarget;
        currentImportacaoId = btn.dataset.id;
        const notasCount = btn.dataset.notas || 0;
        clearInlineError(pageErrorRegion);

        // Show modal with loading
        showModal();
        setModalContent('<p class="text-sm text-gray-600">Calculando custo...</p>');

        try {
            // First, get nota IDs for this import
            const custoResponse = await fetchWithCsrf(`/app/clearance/calcular-custo`, {
                method: 'POST',
                body: JSON.stringify({
                    importacao_id: currentImportacaoId,
                    tipo: 'completa'
                })
            });

            if (custoResponse.success === false) {
                closeModal();
                renderInlineError(pageErrorRegion, custoResponse.message || 'Erro ao calcular custo', {
                    action: 'validar-importacao',
                    url: '/app/clearance/dashboard'
                });
                return;
            }

            const custo = custoResponse.custo || { custo_total: 0, participantes_unicos: 0 };
            const saldo = custoResponse.saldo_atual || 0;
            const suficiente = custoResponse.saldo_suficiente !== false;

            let html = `
                <div class="space-y-4">
                    <div class="bg-gray-50 border border-gray-200 rounded p-4">
                        <p class="text-sm text-gray-600">Notas a validar: <span class="font-semibold text-gray-900">${notasCount}</span></p>
                        <p class="text-sm text-gray-600">Participantes unicos: <span class="font-semibold text-gray-900">${custo.participantes_unicos}</span></p>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Tipo de Validacao</label>
                        <select id="select-tipo-validacao" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400 focus:outline-none bg-white">
                            <option value="local">Regras Locais (Gratis)</option>
                            <option value="completa" selected>Validacao Completa (${custo.participantes_unicos} cr)</option>
                            <option value="deep">Deep Analysis (${custo.participantes_unicos * 3} cr)</option>
                        </select>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Custo:</span>
                            <span id="custo-display" class="font-semibold text-gray-900">${custo.custo_total} creditos</span>
                        </div>
                        <div class="flex justify-between text-sm mt-1">
                            <span class="text-gray-600">Seu saldo:</span>
                            <span class="font-semibold ${suficiente ? 'text-green-600' : 'text-red-600'}">${saldo} creditos</span>
                        </div>
                    </div>
            `;

            if (!suficiente && custo.custo_total > 0) {
                html += `
                    <div class="bg-white border border-gray-300 rounded p-3 border-l-4" style="border-left-color: #dc2626;">
                        <p class="text-sm text-red-700">Creditos insuficientes para a opcao selecionada.</p>
                    </div>
                `;
            }

            html += '</div>';

            setModalContent(html);

            // Update cost when type changes
            const selectTipo = document.getElementById('select-tipo-validacao');
            if (selectTipo) {
                selectTipo.addEventListener('change', (e) => {
                    currentTipo = e.target.value;
                    const custoDisplay = document.getElementById('custo-display');
                    let novoCusto = 0;

                    switch(currentTipo) {
                        case 'local':
                            novoCusto = 0;
                            break;
                        case 'completa':
                            novoCusto = custo.participantes_unicos;
                            break;
                        case 'deep':
                            novoCusto = custo.participantes_unicos * 3;
                            break;
                    }

                    if (custoDisplay) {
                        custoDisplay.textContent = `${novoCusto} creditos`;
                    }
                });
            }

        } catch (error) {
            console.error('Error calculating cost:', error);
            closeModal();
            renderInlineError(pageErrorRegion, 'Erro ao calcular custo. Tente novamente.', {
                action: 'validar-importacao',
                url: '/app/clearance/dashboard'
            });
        }
    }

    /**
     * Handle confirm validation
     */
    async function handleConfirmarValidacao() {
        if (!currentImportacaoId) return;

        const selectTipo = document.getElementById('select-tipo-validacao');
        const tipo = selectTipo ? selectTipo.value : 'completa';

        // Show loading state
        if (modalConfirmar) {
            modalConfirmar.disabled = true;
            modalConfirmar.textContent = 'Validando...';
        }

        try {
            clearInlineError(pageErrorRegion);
            const response = await fetchWithCsrf(`/app/clearance/importacao/${currentImportacaoId}/validar`, {
                method: 'POST',
                body: JSON.stringify({ tipo })
            });

            if (response.success) {
                closeModal();
                showNotification('success', response.message || 'Validacao concluida com sucesso');

                // Reload the page to show updated data
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                closeModal();
                renderInlineError(pageErrorRegion, response.message || 'Erro ao validar', {
                    action: 'validar-importacao',
                    url: '/app/clearance/dashboard'
                });
            }
        } catch (error) {
            console.error('Error validating:', error);
            closeModal();
            renderInlineError(pageErrorRegion, 'Erro ao executar validacao. Tente novamente.', {
                action: 'validar-importacao',
                url: '/app/clearance/dashboard'
            });
        } finally {
            if (modalConfirmar) {
                modalConfirmar.disabled = false;
                modalConfirmar.textContent = 'Confirmar';
            }
        }
    }

    /**
     * Handle save validation (from nota detail page)
     */
    async function handleSalvarValidacao(e) {
        const btn = e.currentTarget;
        const notaId = btn.dataset.notaId;

        if (!notaId) return;

        btn.disabled = true;
        btn.textContent = 'Salvando...';

        try {
            clearInlineError(notaErrorRegion);
            const response = await fetchWithCsrf('/app/clearance/notas/validar', {
                method: 'POST',
                body: JSON.stringify({
                    nota_ids: [parseInt(notaId)],
                    tipo: 'local' // Free validation
                })
            });

            if (response.success) {
                showNotification('success', 'Validacao salva com sucesso');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                renderInlineError(notaErrorRegion, response.message || 'Erro ao salvar validacao', {
                    action: 'salvar-validacao',
                    url: window.location.pathname
                });
                btn.disabled = false;
                btn.textContent = 'Salvar Validacao';
            }
        } catch (error) {
            console.error('Error saving validation:', error);
            renderInlineError(notaErrorRegion, 'Erro ao salvar validacao', {
                action: 'salvar-validacao',
                url: window.location.pathname
            });
            btn.disabled = false;
            btn.textContent = 'Salvar Validacao';
        }
    }

    /**
     * Handle filter change
     */
    function handleFiltroChange() {
        const nivel = document.getElementById('filtro-nivel')?.value || '';
        const categoria = document.getElementById('filtro-categoria')?.value || '';

        let url = '/app/clearance/alertas';
        const params = [];

        if (nivel) params.push(`nivel=${nivel}`);
        if (categoria) params.push(`categoria=${categoria}`);

        if (params.length > 0) {
            url += '?' + params.join('&');
        }

        // Use SPA navigation if available
        if (window.spa && typeof window.spa.navigateTo === 'function') {
            window.spa.navigateTo(url);
        } else {
            window.location.href = url;
        }
    }

    /**
     * Fetch with CSRF token
     */
    async function fetchWithCsrf(url, options = {}) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken || ''
            }
        };

        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...(options.headers || {})
            }
        };

        const response = await fetch(url, mergedOptions);
        return response.json();
    }

    /**
     * Show modal
     */
    function showModal() {
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    /**
     * Close modal
     */
    function closeModal() {
        if (modal) {
            modal.classList.add('hidden');
        }
        currentImportacaoId = null;
        currentNotaIds = [];
        currentTipo = 'completa';
    }

    /**
     * Set modal content
     */
    function setModalContent(html) {
        if (modalContent) {
            modalContent.innerHTML = html;
        }
    }

    /**
     * Show notification
     */
    function showNotification(type, message) {
        // Check if app has global notification system
        if (window.showNotification && typeof window.showNotification === 'function') {
            window.showNotification(type, message);
            return;
        }

        // Simple fallback notification
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 ${
            type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.add('translate-y-0', 'opacity-100');
        }, 10);

        // Remove after delay
        setTimeout(() => {
            notification.classList.add('opacity-0', '-translate-y-2');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    function renderInlineError(container, message, context) {
        if (window.showInlineError) {
            window.showInlineError(container, {
                message,
                context,
            });
            return;
        }

        showNotification('error', message);
    }

    function clearInlineError(container) {
        if (window.clearInlineError) {
            window.clearInlineError(container);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export for SPA re-initialization
    window.initValidacao = init;

})();
