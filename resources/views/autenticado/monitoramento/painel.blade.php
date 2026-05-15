{{-- Monitoramento — Painel (DANFE Modernizado) --}}
@php
    $taxaCredito = 0.20; // R$ por crédito
    $coresPlano = [
        'gratuito' => '#6b7280',
        'validacao' => '#0891b2',
        'licitacao' => '#ea580c',
        'compliance' => '#7c3aed',
        'due_diligence' => '#9333ea',
        'enterprise' => '#1f2937',
    ];
@endphp
<div class="min-h-screen bg-gray-100" id="monitoramento-painel-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        {{-- Header (fixo, fora do dinâmico) --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Compliance Automático</h1>
                <p class="text-xs text-gray-500 mt-1">Checagem recorrente de compliance dos clientes e participantes selecionados.</p>
            </div>
            <button type="button"
                    id="btn-nova-assinatura"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded text-white text-xs font-semibold transition"
                    style="background-color: #047857;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nova assinatura
            </button>
        </div>

        {{-- Conteúdo dinâmico (substituído por XHR ao mudar sub-aba/filtros) --}}
        <div id="monitoramento-painel-dinamico">

            {{-- KPIs --}}
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo</span>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">
                    <div class="p-4 sm:p-6">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Ativas</p>
                        <p class="text-lg font-bold text-gray-900 font-mono">{{ $kpiAtivas }}</p>
                    </div>
                    <div class="p-4 sm:p-6">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Pausadas</p>
                        <p class="text-lg font-bold text-gray-900 font-mono">{{ $kpiPausadas }}</p>
                    </div>
                    <div class="p-4 sm:p-6">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Créditos no mês</p>
                        <p class="text-lg font-bold text-gray-900 font-mono">{{ $kpiCreditosMes }}</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">≈ R$ {{ number_format($kpiCreditosMes * $taxaCredito, 2, ',', '.') }}</p>
                    </div>
                    <div class="p-4 sm:p-6">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Custo estimado / ciclo</p>
                        <p class="text-lg font-bold text-gray-900 font-mono">{{ $kpiPrevisaoCiclo }} <span class="text-xs font-normal text-gray-500">créditos</span></p>
                        <p class="text-[10px] text-gray-500 mt-0.5">≈ R$ {{ number_format($kpiPrevisaoCiclo * $taxaCredito, 2, ',', '.') }} se todas executarem</p>
                    </div>
                </div>
            </div>

            @include('autenticado.monitoramento._sub-tabs-tipo', [
                'tipoAtivo' => $tipoAtivo,
                'contagens' => $contagens,
                'rota' => 'app.monitoramento.painel',
            ])

            {{-- Filtros --}}
            <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
                </div>
                <form method="GET" action="{{ route('app.monitoramento.painel') }}" class="p-4 flex flex-wrap items-end gap-3">
                    <input type="hidden" name="tipo" value="{{ $tipoAtivo }}">
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Status</label>
                        <select name="status" class="px-3 py-2 text-sm border border-gray-300 rounded">
                            <option value="">Todos</option>
                            <option value="ativo" @selected($filtros['status'] === 'ativo')>Ativo</option>
                            <option value="pausado" @selected($filtros['status'] === 'pausado')>Pausado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Plano</label>
                        <select name="plano_id" class="px-3 py-2 text-sm border border-gray-300 rounded">
                            <option value="">Todos</option>
                            @foreach ($planos as $p)
                                <option value="{{ $p->id }}" @selected($filtros['plano_id'] === $p->id)>{{ $p->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Busca</label>
                        <input type="text" name="busca" value="{{ $filtros['busca'] }}" placeholder="CNPJ ou razão social"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded">
                    </div>
                    <button type="submit" class="px-3 py-2 rounded bg-gray-800 hover:bg-gray-700 text-white text-xs font-semibold">Filtrar</button>
                </form>
            </div>

            {{-- Tabela --}}
            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                @if ($assinaturas->isEmpty())
                    <div class="p-10 text-center">
                        <p class="text-sm text-gray-600">Nenhuma assinatura encontrada.</p>
                        <p class="text-xs text-gray-500 mt-2">Crie a primeira pelo botão acima ou direto no detalhe de um cliente/participante.</p>
                    </div>
                @else
                    {{-- Desktop: tabela --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr class="text-left text-[10px] font-semibold text-gray-500 uppercase tracking-widest">
                                    <th class="px-3 py-2 w-8"><input type="checkbox" id="select-all-assinaturas" class="rounded border-gray-300"></th>
                                    <th class="px-4 py-2">Tipo</th>
                                    <th class="px-4 py-2">CNPJ</th>
                                    <th class="px-4 py-2">Razão Social</th>
                                    <th class="px-4 py-2">Plano</th>
                                    <th class="px-4 py-2">Freq.</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2">Última situação</th>
                                    <th class="px-4 py-2">Próx. exec.</th>
                                    <th class="px-4 py-2 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($assinaturas as $a)
                                    @php
                                        $alvoTipo = $a->alvoTipo();
                                        $alvo = $a->alvo();
                                        $corTipo = $alvoTipo === 'cliente' ? '#1e40af' : '#7c3aed';
                                        $corStatus = match ($a->status) {
                                            'ativo' => '#047857',
                                            'pausado' => '#d97706',
                                            default => '#6b7280',
                                        };
                                        $ultima = $ultimasConsultas[$a->id] ?? null;
                                        $href = $alvoTipo === 'cliente' ? "/app/cliente/{$alvo?->id}" : "/app/participante/{$alvo?->id}";
                                        $docFormatado = $alvoTipo === 'cliente' ? $alvo?->documento_formatado : $alvo?->cnpj_formatado;
                                        $corPlano = $coresPlano[$a->plano?->codigo] ?? '#374151';
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 w-8">
                                            @if ($a->status === 'ativo')
                                                <input type="checkbox" class="chk-assinatura rounded border-gray-300"
                                                       data-assinatura-id="{{ $a->id }}"
                                                       data-custo="{{ $a->plano?->custo_creditos ?? 0 }}">
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="text-[10px] font-semibold text-white uppercase px-2 py-1 rounded"
                                                  style="background-color: {{ $corTipo }};">{{ $alvoTipo }}</span>
                                        </td>
                                        <td class="px-4 py-2 font-mono text-xs whitespace-nowrap">{{ $docFormatado ?? $alvo?->documento ?? '—' }}</td>
                                        <td class="px-4 py-2">
                                            <a href="{{ $href }}" data-link class="text-gray-900 hover:underline">{{ $alvo?->razao_social ?? '—' }}</a>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            @if ($a->plano)
                                                <span class="text-[10px] font-semibold text-white uppercase px-2 py-1 rounded"
                                                      style="background-color: {{ $corPlano }};">{{ $a->plano->nome }}</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-xs">{{ $a->frequencia }}</td>
                                        <td class="px-4 py-2">
                                            <span class="text-[10px] font-semibold text-white uppercase px-2 py-1 rounded"
                                                  style="background-color: {{ $corStatus }};">{{ $a->status }}</span>
                                        </td>
                                        <td class="px-4 py-2 text-xs">
                                            {{ $ultima?->situacao_geral ?? '—' }}
                                        </td>
                                        <td class="px-4 py-2 text-xs whitespace-nowrap">
                                            @if ($a->proxima_execucao_em)
                                                <div class="font-mono text-gray-900">{{ $a->proxima_execucao_em->format('d/m/Y H:i') }}</div>
                                                <div class="text-[10px] text-gray-500">{{ $a->proxima_execucao_em->locale('pt_BR')->diffForHumans() }}</div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <div class="inline-flex gap-1 flex-wrap justify-end">
                                                @if ($a->status === 'ativo')
                                                    <button type="button" class="btn-consultar-agora text-xs px-2 py-1 rounded text-white"
                                                            style="background-color: #047857;"
                                                            data-assinatura-id="{{ $a->id }}"
                                                            data-custo="{{ $a->plano?->custo_creditos ?? 0 }}">Consultar agora</button>
                                                    <button type="button" class="btn-pausar text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50" data-assinatura-id="{{ $a->id }}">Pausar</button>
                                                @else
                                                    <button type="button" class="btn-reativar text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-50" data-assinatura-id="{{ $a->id }}">Reativar</button>
                                                @endif
                                                <button type="button" class="btn-cancelar text-xs px-2 py-1 rounded border border-red-300 text-red-700 hover:bg-red-50" data-assinatura-id="{{ $a->id }}">Cancelar</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile: cards --}}
                    <div class="divide-y divide-gray-200 md:hidden">
                        @foreach ($assinaturas as $a)
                            @php
                                $alvoTipo = $a->alvoTipo();
                                $alvo = $a->alvo();
                                $corTipo = $alvoTipo === 'cliente' ? '#1e40af' : '#7c3aed';
                                $corStatus = match ($a->status) {
                                    'ativo' => '#047857',
                                    'pausado' => '#d97706',
                                    default => '#6b7280',
                                };
                                $ultima = $ultimasConsultas[$a->id] ?? null;
                                $href = $alvoTipo === 'cliente' ? "/app/cliente/{$alvo?->id}" : "/app/participante/{$alvo?->id}";
                                $docFormatado = $alvoTipo === 'cliente' ? $alvo?->documento_formatado : $alvo?->cnpj_formatado;
                                $corPlano = $coresPlano[$a->plano?->codigo] ?? '#374151';
                            @endphp
                            <div class="p-4">
                                <div class="flex items-start gap-3 mb-2">
                                    @if ($a->status === 'ativo')
                                        <input type="checkbox" class="chk-assinatura mt-1 shrink-0 rounded border-gray-300"
                                               data-assinatura-id="{{ $a->id }}"
                                               data-custo="{{ $a->plano?->custo_creditos ?? 0 }}">
                                    @endif
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                            <span class="text-[10px] font-semibold text-white uppercase px-2 py-0.5 rounded"
                                                  style="background-color: {{ $corTipo }};">{{ $alvoTipo }}</span>
                                            <span class="text-[10px] font-semibold text-white uppercase px-2 py-0.5 rounded"
                                                  style="background-color: {{ $corStatus }};">{{ $a->status }}</span>
                                            @if ($a->plano)
                                                <span class="text-[10px] font-semibold text-white uppercase px-2 py-0.5 rounded"
                                                      style="background-color: {{ $corPlano }};">{{ $a->plano->nome }}</span>
                                            @endif
                                        </div>
                                        <a href="{{ $href }}" data-link class="text-sm font-medium text-gray-900 hover:underline block truncate">
                                            {{ $alvo?->razao_social ?? '—' }}
                                        </a>
                                        <p class="text-xs font-mono text-gray-500 mt-0.5">{{ $docFormatado ?? $alvo?->documento ?? '—' }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 mt-3 text-xs">
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Frequência</p>
                                        <p class="text-gray-900 mt-0.5">{{ $a->frequencia }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Custo / ciclo</p>
                                        <p class="text-gray-900 mt-0.5 font-mono">{{ $a->plano?->custo_creditos ?? 0 }} créditos</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Última situação</p>
                                        <p class="text-gray-900 mt-0.5">{{ $ultima?->situacao_geral ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Próxima execução</p>
                                        @if ($a->proxima_execucao_em)
                                            <p class="text-gray-900 mt-0.5 font-mono">{{ $a->proxima_execucao_em->format('d/m/Y H:i') }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $a->proxima_execucao_em->locale('pt_BR')->diffForHumans() }}</p>
                                        @else
                                            <p class="text-gray-900 mt-0.5">—</p>
                                        @endif
                                    </div>
                                </div>

                                @if ($a->status === 'ativo')
                                    <button type="button" class="btn-consultar-agora w-full text-xs px-3 py-2 rounded text-white font-semibold mt-3"
                                            style="background-color: #047857;"
                                            data-assinatura-id="{{ $a->id }}"
                                            data-custo="{{ $a->plano?->custo_creditos ?? 0 }}">
                                        Consultar agora
                                    </button>
                                @endif

                                <div class="flex gap-2 mt-2 pt-3 border-t border-gray-100">
                                    @if ($a->status === 'ativo')
                                        <button type="button" class="btn-pausar flex-1 text-xs px-3 py-2 rounded border border-gray-300 hover:bg-gray-50" data-assinatura-id="{{ $a->id }}">Pausar</button>
                                    @else
                                        <button type="button" class="btn-reativar flex-1 text-xs px-3 py-2 rounded border border-gray-300 hover:bg-gray-50" data-assinatura-id="{{ $a->id }}">Reativar</button>
                                    @endif
                                    <button type="button" class="btn-cancelar flex-1 text-xs px-3 py-2 rounded border border-red-300 text-red-700 hover:bg-red-50" data-assinatura-id="{{ $a->id }}">Cancelar</button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                        {{ $assinaturas->links() }}
                    </div>
                @endif
            </div>

        </div> {{-- /#monitoramento-painel-dinamico --}}
    </div>

    {{-- Barra fixa de seleção em massa --}}
    <div id="barra-selecao" class="hidden fixed bottom-0 inset-x-0 z-40 bg-gray-900 text-white px-4 py-3 shadow-lg">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs sm:text-sm">
                <strong id="barra-selecao-count">0</strong> selecionada(s) ·
                Custo total: <strong id="barra-selecao-custo">0</strong> créditos
                <span class="text-gray-400">(≈ R$ <span id="barra-selecao-reais">0,00</span>)</span>
            </div>
            <div class="flex gap-2">
                <button type="button" id="btn-limpar-selecao" class="px-3 py-2 rounded border border-gray-600 text-xs font-semibold hover:bg-gray-800">Limpar</button>
                <button type="button" id="btn-consultar-selecionados" class="px-4 py-2 rounded text-white text-xs font-semibold" style="background-color: #047857;">
                    Consultar selecionadas
                </button>
            </div>
        </div>
    </div>

    @include('autenticado.monitoramento._modal-nova-assinatura')

    {{-- Modal de confirmação de ação --}}
    <div id="modal-confirmar-acao" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded border border-gray-300 max-w-md w-full overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Confirmar ação</span>
            </div>
            <div class="p-4">
                <h2 id="modal-confirmar-titulo" class="text-sm font-semibold text-gray-900 mb-2">—</h2>
                <p id="modal-confirmar-mensagem" class="text-xs text-gray-600">—</p>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex justify-end gap-2">
                <button type="button" id="modal-confirmar-cancelar"
                        class="px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="button" id="modal-confirmar-ok"
                        class="px-4 py-2 rounded text-white text-xs font-semibold"
                        style="background-color: #1f2937;">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/js/monitoramento-modal-nova-assinatura.js?v={{ filemtime(public_path('js/monitoramento-modal-nova-assinatura.js')) }}"></script>

<script>
(function() {
    'use strict';

    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var container = document.getElementById('monitoramento-painel-container');
    if (!container) return;

    // Evita rebind duplicado se o painel for re-injetado em SPA externo
    if (container.dataset.bound === '1') return;
    container.dataset.bound = '1';

    var modal = document.getElementById('modal-confirmar-acao');
    var modalTitulo = document.getElementById('modal-confirmar-titulo');
    var modalMensagem = document.getElementById('modal-confirmar-mensagem');
    var modalCancelar = document.getElementById('modal-confirmar-cancelar');
    var modalOk = document.getElementById('modal-confirmar-ok');
    var pendingCallback = null;

    function abrirConfirmacao(opts) {
        modalTitulo.textContent = opts.titulo || 'Confirmar ação';
        modalMensagem.textContent = opts.mensagem || '';
        modalOk.textContent = opts.textoConfirmar || 'Confirmar';
        modalOk.style.backgroundColor = opts.destrutivo ? '#b91c1c' : '#1f2937';
        pendingCallback = opts.onConfirm || null;
        modal.classList.remove('hidden');
    }

    function fecharConfirmacao() {
        modal.classList.add('hidden');
        pendingCallback = null;
    }

    modalCancelar.addEventListener('click', fecharConfirmacao);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) fecharConfirmacao();
    });
    modalOk.addEventListener('click', function() {
        var cb = pendingCallback;
        fecharConfirmacao();
        if (typeof cb === 'function') cb();
    });

    function acaoAssinatura(url, method) {
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
        .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
        .then(function(r) {
            if (!r.ok) {
                throw new Error(r.data.error || r.data.message || 'Erro ao atualizar a assinatura');
            }
            window.showToast && window.showToast('Assinatura atualizada.', 'success');
            // Recarrega só o conteúdo dinâmico, sem perder scroll
            recarregarDinamico(window.location.href);
        })
        .catch(function(err) {
            if (window.showToast) {
                window.showToast(err.message || 'Erro ao atualizar a assinatura', 'error');
            } else {
                alert(err.message || 'Erro ao atualizar a assinatura');
            }
        });
    }

    // Event delegation no container — botões funcionam mesmo após re-injeção do HTML dinâmico
    container.addEventListener('click', function(e) {
        var pausarBtn = e.target.closest('.btn-pausar');
        if (pausarBtn) {
            var id = pausarBtn.dataset.assinaturaId;
            abrirConfirmacao({
                titulo: 'Pausar assinatura',
                mensagem: 'A checagem recorrente fica suspensa até você reativar. Nenhum crédito é cobrado enquanto estiver pausada.',
                textoConfirmar: 'Pausar',
                destrutivo: false,
                onConfirm: function() { acaoAssinatura('/app/monitoramento/assinatura/' + id + '/pausar', 'POST'); },
            });
            return;
        }

        var reativarBtn = e.target.closest('.btn-reativar');
        if (reativarBtn) {
            var id = reativarBtn.dataset.assinaturaId;
            abrirConfirmacao({
                titulo: 'Reativar assinatura',
                mensagem: 'A próxima execução é agendada conforme a frequência do plano. Créditos voltam a ser debitados a cada ciclo.',
                textoConfirmar: 'Reativar',
                destrutivo: false,
                onConfirm: function() { acaoAssinatura('/app/monitoramento/assinatura/' + id + '/reativar', 'POST'); },
            });
            return;
        }

        var cancelarBtn = e.target.closest('.btn-cancelar');
        if (cancelarBtn) {
            var id = cancelarBtn.dataset.assinaturaId;
            abrirConfirmacao({
                titulo: 'Cancelar assinatura',
                mensagem: 'A assinatura é desativada permanentemente. O histórico de execuções fica preservado, mas você precisa criar uma nova assinatura para retomar.',
                textoConfirmar: 'Cancelar assinatura',
                destrutivo: true,
                onConfirm: function() { acaoAssinatura('/app/monitoramento/assinatura/' + id, 'DELETE'); },
            });
            return;
        }

        var consultarBtn = e.target.closest('.btn-consultar-agora');
        if (consultarBtn) {
            var id = consultarBtn.dataset.assinaturaId;
            var custo = parseInt(consultarBtn.dataset.custo || '0', 10);
            var reais = (custo * 0.20).toFixed(2).replace('.', ',');
            abrirConfirmacao({
                titulo: 'Consultar agora',
                mensagem: 'Será disparada uma consulta imediata fora do ciclo agendado. Custo: ' + custo + ' créditos (≈ R$ ' + reais + '). A próxima execução automática é reagendada a partir de agora.',
                textoConfirmar: 'Consultar',
                destrutivo: false,
                onConfirm: function() { consultarAgora([id]); },
            });
            return;
        }

        // Sub-aba de tipo: navega sem scroll-to-top
        var subTab = e.target.closest('[data-sub-tab]');
        if (subTab) {
            e.preventDefault();
            recarregarDinamico(subTab.getAttribute('href'), true);
            return;
        }
    });

    // Seleção em massa (checkboxes)
    var barraSelecao = document.getElementById('barra-selecao');
    var barraCount = document.getElementById('barra-selecao-count');
    var barraCusto = document.getElementById('barra-selecao-custo');
    var barraReais = document.getElementById('barra-selecao-reais');

    function atualizarBarra() {
        var checks = container.querySelectorAll('.chk-assinatura:checked');
        var count = checks.length;
        if (count === 0) {
            barraSelecao.classList.add('hidden');
            return;
        }
        var custo = 0;
        checks.forEach(function(c) { custo += parseInt(c.dataset.custo || '0', 10); });
        barraCount.textContent = count;
        barraCusto.textContent = custo;
        barraReais.textContent = (custo * 0.20).toFixed(2).replace('.', ',');
        barraSelecao.classList.remove('hidden');
    }

    container.addEventListener('change', function(e) {
        if (e.target.matches('.chk-assinatura')) {
            atualizarBarra();
            return;
        }
        if (e.target.id === 'select-all-assinaturas') {
            var marcado = e.target.checked;
            container.querySelectorAll('.chk-assinatura').forEach(function(c) { c.checked = marcado; });
            atualizarBarra();
        }
    });

    document.getElementById('btn-limpar-selecao').addEventListener('click', function() {
        container.querySelectorAll('.chk-assinatura').forEach(function(c) { c.checked = false; });
        var selectAll = document.getElementById('select-all-assinaturas');
        if (selectAll) selectAll.checked = false;
        atualizarBarra();
    });

    document.getElementById('btn-consultar-selecionados').addEventListener('click', function() {
        var checks = container.querySelectorAll('.chk-assinatura:checked');
        if (checks.length === 0) return;
        var ids = Array.from(checks).map(function(c) { return c.dataset.assinaturaId; });
        var custo = 0;
        checks.forEach(function(c) { custo += parseInt(c.dataset.custo || '0', 10); });
        var reais = (custo * 0.20).toFixed(2).replace('.', ',');
        abrirConfirmacao({
            titulo: 'Consultar ' + ids.length + ' assinatura(s) agora',
            mensagem: 'Serão disparadas ' + ids.length + ' consultas imediatas fora do ciclo. Custo total: ' + custo + ' créditos (≈ R$ ' + reais + '). As próximas execuções automáticas são reagendadas a partir de agora.',
            textoConfirmar: 'Consultar todas',
            destrutivo: false,
            onConfirm: function() { consultarAgora(ids); },
        });
    });

    function consultarAgora(ids) {
        fetch('/app/monitoramento/consultar-agora', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ ids: ids }),
        })
        .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
        .then(function(r) {
            if (!r.ok) {
                throw new Error(r.data.error || 'Erro ao disparar consulta');
            }
            var msg = r.data.disparadas + ' consulta(s) disparada(s).';
            if (r.data.falhas && r.data.falhas.length) {
                msg += ' Falhas: ' + r.data.falhas.length + '.';
            }
            window.showToast && window.showToast(msg, 'success');
            recarregarDinamico(window.location.href);
            barraSelecao.classList.add('hidden');
        })
        .catch(function(err) {
            if (window.showToast) {
                window.showToast(err.message || 'Erro ao disparar consulta', 'error');
            } else {
                alert(err.message || 'Erro ao disparar consulta');
            }
        });
    }

    // popstate: back/forward funciona sem reload
    window.addEventListener('popstate', function() {
        recarregarDinamico(window.location.href, false);
    });

    function recarregarDinamico(url, atualizarHistorico) {
        var dinamico = document.getElementById('monitoramento-painel-dinamico');
        if (!dinamico) return;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin',
        })
        .then(function(res) {
            if (!res.ok) throw new Error('Falha ao carregar');
            return res.text();
        })
        .then(function(html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var novoDinamico = doc.getElementById('monitoramento-painel-dinamico');
            if (!novoDinamico) {
                // Resposta não tem o container esperado — provavelmente expirou sessão; fallback
                window.location.href = url;
                return;
            }
            dinamico.innerHTML = novoDinamico.innerHTML;
            if (atualizarHistorico) {
                history.pushState(null, '', url);
            }
        })
        .catch(function() {
            window.location.href = url;
        });
    }
})();
</script>
