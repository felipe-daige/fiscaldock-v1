{{-- Monitoramento - Histórico de Consultas (DANFE Modernizado) --}}
<div class="min-h-screen bg-gray-100" id="monitoramento-historico-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        {{-- Header --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Histórico de Consultas</h1>
                <p class="text-xs text-gray-500 mt-1">Visualize todas as consultas realizadas.</p>
            </div>
            <a href="/app/dashboard" data-link
               class="inline-flex items-center gap-2 px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Voltar
            </a>
        </div>

        {{-- KPIs --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo</span>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-3 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Total de Consultas</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $totalConsultas ?? 0 }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Consultas realizadas</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Créditos Utilizados</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $totalCreditos ?? 0 }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Créditos consumidos</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Taxa de Sucesso</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $taxaSucesso ?? 0 }}%</p>
                    <p class="text-[11px] text-gray-500 mt-1">Consultas com sucesso</p>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
            </div>
            <div class="p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Tipo</label>
                    <select id="filtro-tipo" class="px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todos</option>
                        <option value="avulso">Avulso</option>
                        <option value="assinatura">Assinatura</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Status</label>
                    <select id="filtro-status" class="px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todos</option>
                        <option value="sucesso">Sucesso</option>
                        <option value="pendente">Pendente</option>
                        <option value="processando">Processando</option>
                        <option value="erro">Erro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Plano</label>
                    <select id="filtro-plano" class="px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <option value="">Todos</option>
                        <option value="basico">Básico</option>
                        <option value="cadastral_plus">Cadastral+</option>
                        <option value="fiscal_federal">Fiscal Federal</option>
                        <option value="fiscal_completo">Fiscal Completo</option>
                        <option value="due_diligence">Due Diligence</option>
                        <option value="esg">ESG</option>
                        <option value="completo">Completo</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[240px]">
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Buscar</label>
                    <div class="relative">
                        <input type="text" id="busca-historico" placeholder="Buscar por CNPJ..."
                               class="w-full px-3 py-2 pl-9 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lista de Consultas --}}
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Consultas</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Data</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNPJ</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Razão Social</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Plano</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>
                            <th class="px-3 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Status</th>
                            <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Créditos</th>
                            <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="historico-tbody">
                        @forelse($consultas ?? [] as $consulta)
                            <tr class="hover:bg-gray-50/50 transition-colors" data-consulta-id="{{ $consulta->id }}">
                                <td class="px-3 py-3 text-sm text-gray-700 font-mono whitespace-nowrap">
                                    {{ $consulta->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-3 py-3 text-sm font-mono text-gray-900 whitespace-nowrap">
                                    {{ preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $consulta->participante->cnpj ?? '') }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-900 max-w-xs truncate">
                                    {{ $consulta->participante->razao_social ?? '-' }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700 whitespace-nowrap">
                                    {{ $consulta->plano->nome ?? '-' }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-center">
                                    @if($consulta->tipo === 'avulso')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #374151">Avulso</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #4338ca">Assinatura</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-center">
                                    @if($consulta->status === 'sucesso')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Sucesso</span>
                                    @elseif($consulta->status === 'pendente')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">Pendente</span>
                                    @elseif($consulta->status === 'processando')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #4338ca">Processando</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #b91c1c">Erro</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700 font-mono whitespace-nowrap">
                                    {{ $consulta->creditos_cobrados }}
                                </td>
                                <td class="px-3 py-3 text-right whitespace-nowrap text-xs">
                                    <button type="button"
                                            class="btn-ver-resultado text-gray-600 hover:text-gray-900 hover:underline"
                                            data-consulta-id="{{ $consulta->id }}">
                                        Ver resultado
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                    </svg>
                                    <h3 class="mt-4 text-sm font-semibold text-gray-900 uppercase tracking-wide">Nenhuma consulta realizada</h3>
                                    <p class="mt-2 text-xs text-gray-500">Suas consultas aparecerão aqui após serem realizadas.</p>
                                    <a href="/app/monitoramento/avulso" data-link
                                       class="mt-4 inline-flex items-center gap-2 px-3 py-2 rounded bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        Fazer Consulta Avulsa
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($consultas) && $consultas->hasPages())
                <div class="border-t border-gray-300 px-4 py-3">
                    {{ $consultas->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

{{-- Modal Ver Resultado --}}
<div id="modal-ver-resultado" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded border border-gray-300 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resultado da Consulta</span>
            <button type="button" class="modal-close text-gray-400 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-5" id="modal-resultado-content"></div>
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex justify-end">
            <button type="button" class="modal-close px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 transition">
                Fechar
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    function initMonitoramentoHistorico() {
        const container = document.getElementById('monitoramento-historico-container');
        if (!container) return;

        if (container.dataset.initialized === '1') return;
        container.dataset.initialized = '1';

        console.log('[Monitoramento Historico] Inicializando...');

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const modalVerResultado = document.getElementById('modal-ver-resultado');
        const modalResultadoContent = document.getElementById('modal-resultado-content');
        const buscaInput = document.getElementById('busca-historico');
        const filtroTipo = document.getElementById('filtro-tipo');
        const filtroStatus = document.getElementById('filtro-status');
        const filtroPlano = document.getElementById('filtro-plano');

        // Funcao para filtrar tabela
        function filtrarTabela() {
            const busca = buscaInput ? buscaInput.value.toLowerCase().trim() : '';
            const tipo = filtroTipo ? filtroTipo.value : '';
            const status = filtroStatus ? filtroStatus.value : '';
            const plano = filtroPlano ? filtroPlano.value : '';

            const linhas = document.querySelectorAll('#historico-tbody tr[data-consulta-id]');

            linhas.forEach(function(linha) {
                const cnpj = linha.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const razaoSocial = linha.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const tipoCell = linha.querySelector('td:nth-child(5)').textContent.toLowerCase();
                const statusCell = linha.querySelector('td:nth-child(6)').textContent.toLowerCase();
                const planoCell = linha.querySelector('td:nth-child(4)').textContent.toLowerCase();

                let mostrar = true;

                // Filtro de busca
                if (busca && !cnpj.includes(busca) && !razaoSocial.includes(busca)) {
                    mostrar = false;
                }

                // Filtro de tipo
                if (tipo && !tipoCell.includes(tipo)) {
                    mostrar = false;
                }

                // Filtro de status
                if (status && !statusCell.includes(status)) {
                    mostrar = false;
                }

                // Filtro de plano
                if (plano) {
                    const planoNomes = {
                        'basico': 'basico',
                        'cadastral_plus': 'cadastral',
                        'fiscal_federal': 'fiscal federal',
                        'fiscal_completo': 'fiscal completo',
                        'due_diligence': 'due diligence',
                        'esg': 'esg',
                        'completo': 'completo',
                    };
                    if (!planoCell.includes(planoNomes[plano] || plano)) {
                        mostrar = false;
                    }
                }

                linha.style.display = mostrar ? '' : 'none';
            });
        }

        // Event listeners para filtros
        [buscaInput, filtroTipo, filtroStatus, filtroPlano].forEach(function(el) {
            if (el) {
                el.addEventListener('input', filtrarTabela);
                el.addEventListener('change', filtrarTabela);
            }
        });

        // Botoes ver resultado
        document.querySelectorAll('.btn-ver-resultado').forEach(function(btn) {
            btn.addEventListener('click', async function() {
                const consultaId = this.dataset.consultaId;

                modalResultadoContent.innerHTML = '<div class="flex items-center justify-center py-8"><svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>';
                modalVerResultado.classList.remove('hidden');
                document.body.style.overflow = 'hidden';

                try {
                    const response = await fetch('/app/monitoramento/consulta/' + consultaId, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('Erro ao carregar resultado');
                    }

                    const data = await response.json();
                    renderizarResultado(data);
                } catch (err) {
                    console.error('[Monitoramento Historico] Erro:', err);
                    modalResultadoContent.innerHTML = '<div class="text-center py-8 text-red-600">Erro ao carregar resultado. Tente novamente.</div>';
                }
            });
        });

        // Funcao para renderizar resultado no modal
        function renderizarResultado(data) {
            if (!data || !data.resultado) {
                modalResultadoContent.innerHTML = '<div class="text-center py-8 text-gray-500">Resultado nao disponivel.</div>';
                return;
            }

            const r = data.resultado;
            const cnpjFormatado = r.cnpj ? r.cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5') : '-';

            let html = '<div class="space-y-4">';

            // Header
            html += '<div class="border-b border-gray-200 pb-4">';
            html += '<h4 class="text-lg font-semibold text-gray-900">' + (r.razao_social || 'Razao Social nao informada') + '</h4>';
            html += '<p class="text-sm text-gray-600 font-mono">' + cnpjFormatado + '</p>';
            html += '</div>';

            // Informacoes basicas
            html += '<div class="grid grid-cols-2 gap-4">';
            html += '<div><p class="text-xs text-gray-500">Situacao Cadastral</p><p class="text-sm font-semibold text-gray-900">' + (r.situacao_cadastral || '-') + '</p></div>';
            html += '<div><p class="text-xs text-gray-500">Regime Tributario</p><p class="text-sm font-semibold text-gray-900">' + (r.regime_tributario || '-') + '</p></div>';
            html += '</div>';

            // Detalhes adicionais (se houver)
            if (r.detalhes && Object.keys(r.detalhes).length > 0) {
                html += '<div class="border-t border-gray-200 pt-4">';
                html += '<h5 class="text-sm font-semibold text-gray-900 mb-3">Detalhes da Consulta</h5>';
                html += '<div class="grid grid-cols-2 gap-4">';

                if (r.detalhes.cnd_federal) {
                    const cndClass = r.detalhes.cnd_federal.status === 'NEGATIVA' ? 'text-green-600' : 'text-red-600';
                    html += '<div class="bg-gray-50 rounded border border-gray-200 p-3">';
                    html += '<p class="text-xs text-gray-500">CND Federal</p>';
                    html += '<p class="text-sm font-semibold ' + cndClass + '">' + r.detalhes.cnd_federal.status + '</p>';
                    if (r.detalhes.cnd_federal.validade) {
                        html += '<p class="text-xs text-gray-500 mt-1">Validade: ' + r.detalhes.cnd_federal.validade + '</p>';
                    }
                    html += '</div>';
                }

                if (r.detalhes.fgts) {
                    const fgtsClass = r.detalhes.fgts.status === 'REGULAR' ? 'text-green-600' : 'text-red-600';
                    html += '<div class="bg-gray-50 rounded border border-gray-200 p-3">';
                    html += '<p class="text-xs text-gray-500">FGTS</p>';
                    html += '<p class="text-sm font-semibold ' + fgtsClass + '">' + r.detalhes.fgts.status + '</p>';
                    if (r.detalhes.fgts.validade) {
                        html += '<p class="text-xs text-gray-500 mt-1">Validade: ' + r.detalhes.fgts.validade + '</p>';
                    }
                    html += '</div>';
                }

                if (r.detalhes.cndt) {
                    const cndtClass = r.detalhes.cndt.status === 'NEGATIVA' ? 'text-green-600' : 'text-red-600';
                    html += '<div class="bg-gray-50 rounded border border-gray-200 p-3">';
                    html += '<p class="text-xs text-gray-500">CNDT (Trabalhista)</p>';
                    html += '<p class="text-sm font-semibold ' + cndtClass + '">' + r.detalhes.cndt.status + '</p>';
                    html += '</div>';
                }

                if (r.detalhes.protestos !== undefined) {
                    const protestosClass = r.detalhes.protestos === 0 ? 'text-green-600' : 'text-red-600';
                    html += '<div class="bg-gray-50 rounded border border-gray-200 p-3">';
                    html += '<p class="text-xs text-gray-500">Protestos</p>';
                    html += '<p class="text-sm font-semibold ' + protestosClass + '">' + r.detalhes.protestos + ' registro(s)</p>';
                    html += '</div>';
                }

                html += '</div>';
                html += '</div>';
            }

            // Metadados
            html += '<div class="border-t border-gray-200 pt-4 text-xs text-gray-500">';
            html += '<p>Consulta realizada em: ' + (data.executado_em || data.created_at || '-') + '</p>';
            html += '<p>Creditos utilizados: ' + (data.creditos_cobrados || 0) + '</p>';
            html += '</div>';

            html += '</div>';

            modalResultadoContent.innerHTML = html;
        }

        // Fechar modais
        document.querySelectorAll('.modal-close').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const modal = btn.closest('[id^="modal-"]');
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        });

        // Fechar modal clicando fora
        if (modalVerResultado) {
            modalVerResultado.addEventListener('click', function(e) {
                if (e.target === modalVerResultado) {
                    modalVerResultado.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        }

        console.log('[Monitoramento Historico] Inicializacao concluida');
    }

    // Expor globalmente para SPA
    window.initMonitoramentoHistorico = initMonitoramentoHistorico;

    // Auto-inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMonitoramentoHistorico, { once: true });
    } else {
        initMonitoramentoHistorico();
    }
})();
</script>
