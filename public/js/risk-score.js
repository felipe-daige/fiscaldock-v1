/**
 * Risk Score - JavaScript
 * Gerencia a interface de score de risco
 */

(function() {
    'use strict';

    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // Inicializacao
    function init() {
        setupFilters();
        setupConsultarButtons();
    }

    // Configura filtros
    function setupFilters() {
        const filtroClassificacao = document.getElementById('filtro-classificacao');
        const buscaParticipante = document.getElementById('busca-participante');

        if (filtroClassificacao) {
            filtroClassificacao.addEventListener('change', function() {
                applyFilters();
            });
        }

        if (buscaParticipante) {
            let debounceTimer;
            buscaParticipante.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    applyFilters();
                }, 500);
            });
        }
    }

    // Aplica filtros via navegacao
    function applyFilters() {
        const classificacao = document.getElementById('filtro-classificacao')?.value || 'todos';
        const busca = document.getElementById('busca-participante')?.value || '';

        const params = new URLSearchParams();
        if (classificacao !== 'todos') params.append('classificacao', classificacao);
        if (busca) params.append('busca', busca);

        const url = '/app/score-fiscal' + (params.toString() ? '?' + params.toString() : '');

        // Usa o SPA router se disponivel
        if (window.spaNavigate) {
            window.spaNavigate(url);
        } else {
            window.location.href = url;
        }
    }

    // Configura botoes de consultar
    function setupConsultarButtons() {
        // Botoes na lista
        document.querySelectorAll('.btn-consultar').forEach(btn => {
            btn.addEventListener('click', function() {
                const participanteId = this.dataset.id;
                consultarScore(participanteId, this);
            });
        });

        // Botao na pagina de detalhes
        const btnConsultar = document.getElementById('btn-consultar');
        if (btnConsultar) {
            btnConsultar.addEventListener('click', function() {
                const participanteId = this.dataset.id;
                consultarScore(participanteId, this);
            });
        }
    }

    // Consulta o score de um participante
    async function consultarScore(participanteId, buttonElement) {
        if (!participanteId) return;

        // Desabilita botao e mostra loading
        const originalText = buttonElement.textContent;
        buttonElement.disabled = true;
        buttonElement.textContent = 'Consultando...';

        try {
            const response = await fetch(`/app/score-fiscal/participante/${participanteId}/consultar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const data = await response.json();

            if (data.success) {
                showToast('Score atualizado com sucesso!', 'success');

                // Recarrega a pagina para mostrar os novos dados
                if (window.spaNavigate) {
                    window.spaNavigate(window.location.pathname + window.location.search);
                } else {
                    window.location.reload();
                }
            } else {
                showToast(data.message || 'Erro ao consultar score', 'error');
            }
        } catch (error) {
            console.error('Erro:', error);
            showToast('Erro ao consultar score', 'error');
        } finally {
            buttonElement.disabled = false;
            buttonElement.textContent = originalText;
        }
    }

    // Mostra notificacao toast
    function showToast(message, type = 'info') {
        // Usa o sistema de toast global se disponivel
        if (window.showToast) {
            window.showToast(message, type);
            return;
        }

        // Fallback simples
        const container = document.getElementById('toast-container') || createToastContainer();

        const toast = document.createElement('div');
        toast.className = `px-4 py-3 rounded-lg shadow-lg text-white text-sm font-medium animate-fade-in ${
            type === 'success' ? 'bg-green-600' :
            type === 'error' ? 'bg-red-600' :
            type === 'warning' ? 'bg-yellow-600' :
            'bg-blue-600'
        }`;
        toast.textContent = message;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('opacity-0', 'transition-opacity');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
        return container;
    }

    // Inicializa quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expoe funcao de inicializacao para SPA
    window.initRiskScore = init;
})();
