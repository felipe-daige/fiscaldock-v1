{{-- Monitoramento - Grupos de Participantes --}}
<div class="bg-gray-100 min-h-screen" id="monitoramento-grupos-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="space-y-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Grupos</h1>
                    <p class="mt-1 text-xs text-gray-500">Organização operacional dos participantes com expansão, paginação e ações rápidas.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/app/dashboard" class="inline-flex items-center gap-2 px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-medium transition hover:bg-gray-50" data-link>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Voltar
                    </a>
                    <button type="button" id="btn-criar-grupo" class="inline-flex items-center gap-2 px-4 py-2 rounded bg-gray-800 text-white text-sm font-medium transition hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Novo Grupo
                    </button>
                </div>
            </div>

            <div id="monitoramento-grupos-error-region"></div>

            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Resumo Operacional</span>
                </div>
                <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-gray-200">
                    <div class="px-4 py-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Total</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format($grupos->total() ?? 0, 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Grupos cadastrados</p>
                    </div>
                    <div class="px-4 py-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Manuais</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format(collect($grupos->items())->where('is_auto', false)->count(), 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Geridos pela equipe</p>
                    </div>
                    <div class="px-4 py-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Automáticos</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format(collect($grupos->items())->where('is_auto', true)->count(), 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Criados pelo sistema</p>
                    </div>
                    <div class="px-4 py-4">
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Participantes</p>
                        <p class="text-lg font-bold text-gray-900">{{ number_format(collect($grupos->items())->sum('participantes_count'), 0, ',', '.') }}</p>
                        <p class="text-[11px] text-gray-500 mt-1">Na página atual</p>
                    </div>
                </div>
            </div>

            <form method="GET" action="/app/monitoramento/grupos" class="bg-white rounded border border-gray-300 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 block">Buscar</label>
                            <input
                                type="text"
                                name="busca"
                                value="{{ $filtros['busca'] ?? '' }}"
                                placeholder="Nome do grupo..."
                                class="w-full border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400"
                            >
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 block">Tipo</label>
                            <select name="tipo" class="w-full border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                                <option value="">Todos</option>
                                <option value="manual" {{ ($filtros['tipo'] ?? '') === 'manual' ? 'selected' : '' }}>Manual</option>
                                <option value="auto" {{ ($filtros['tipo'] ?? '') === 'auto' ? 'selected' : '' }}>Automático</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-4">
                        <button type="submit" class="bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium px-4 py-2">Filtrar</button>
                        <a href="/app/monitoramento/grupos" data-link class="bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium px-4 py-2">Limpar</a>
                    </div>
                </div>
            </form>

            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                @if(($grupos ?? null) && $grupos->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="w-12 px-3 py-2.5 text-left bg-gray-50"></th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Grupo</th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Participantes</th>
                                    <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Cor</th>
                                    <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($grupos as $grupo)
                                    <tr class="hover:bg-gray-50/50 transition-colors grupo-row" data-grupo-id="{{ $grupo->id }}">
                                        <td class="px-3 py-3">
                                            <button
                                                type="button"
                                                class="grupo-expand-btn text-gray-400 hover:text-gray-700 transition-colors"
                                                data-grupo-id="{{ $grupo->id }}"
                                                data-expand-url="/app/monitoramento/grupos/{{ $grupo->id }}/participantes"
                                                title="Ver participantes do grupo"
                                            >
                                                <svg class="w-4 h-4 grupo-expand-icon transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </button>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex items-center justify-center w-3 h-3 rounded-full" style="background-color: {{ $grupo->cor ?? '#374151' }}"></span>
                                                <div>
                                                    <div class="text-sm text-gray-700">{{ $grupo->nome }}</div>
                                                    <div class="text-[11px] text-gray-500 mt-1">{{ $grupo->descricao ?: 'Sem descrição informada' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $grupo->is_auto ? '#9ca3af' : '#374151' }}">
                                                {{ $grupo->is_auto ? 'Auto' : 'Manual' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-700">{{ number_format($grupo->participantes_count ?? 0, 0, ',', '.') }}</td>
                                        <td class="px-3 py-3">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $grupo->cor ?? '#374151' }}">
                                                {{ $grupo->cor ?? '#374151' }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <div class="inline-flex items-center gap-1">
                                                <button
                                                    type="button"
                                                    class="btn-editar-grupo p-2 rounded text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                                                    data-grupo-id="{{ $grupo->id }}"
                                                    data-grupo-nome="{{ $grupo->nome }}"
                                                    data-grupo-cor="{{ $grupo->cor }}"
                                                    data-grupo-descricao="{{ $grupo->descricao }}"
                                                    title="Editar grupo"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn-excluir-grupo p-2 rounded text-gray-400 hover:text-gray-900 hover:bg-gray-100 transition-colors"
                                                    data-grupo-id="{{ $grupo->id }}"
                                                    data-grupo-nome="{{ $grupo->nome }}"
                                                    title="Excluir grupo"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($grupos->hasPages())
                        <div class="border-t border-gray-300 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide">
                                    Mostrando {{ $grupos->firstItem() }}-{{ $grupos->lastItem() }} de {{ $grupos->total() }}
                                </p>
                                <div>
                                    {{ $grupos->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="px-6 py-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum grupo encontrado</h3>
                        <p class="text-sm text-gray-600 mb-4">Crie um grupo para organizar sua base de participantes.</p>
                        <button type="button" id="btn-criar-grupo-empty" class="inline-flex items-center gap-2 px-4 py-2 rounded bg-gray-800 text-white text-sm font-medium transition hover:bg-gray-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Criar Grupo
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div id="modal-grupo" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded border border-gray-300 max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900" id="modal-grupo-titulo">Novo Grupo</h3>
                <button type="button" class="modal-close text-gray-400 hover:text-gray-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form id="form-grupo">
            <input type="hidden" name="grupo_id" id="input-grupo-id" value="">
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Grupo *</label>
                    <input type="text" name="nome" id="input-grupo-nome" class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400" placeholder="Ex: Fornecedores Prioritários" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cor do Badge</label>
                    <div class="flex flex-wrap gap-2" id="cores-grupo">
                        @foreach($coresPredefinidas ?? [] as $index => $cor)
                            <label class="cursor-pointer">
                                <input type="radio" name="cor" value="{{ $cor }}" class="sr-only cor-radio" {{ $index === 0 ? 'checked' : '' }}>
                                <span class="block w-8 h-8 rounded-full border-2 border-transparent transition-all hover:scale-110 cor-preview" style="background-color: {{ $cor }}" data-cor="{{ $cor }}"></span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                    <textarea name="descricao" id="input-grupo-descricao" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400" placeholder="Descreva o propósito deste grupo..."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
                <button type="button" class="modal-close px-3 py-2 rounded border border-gray-300 bg-white text-gray-700 text-xs font-semibold transition hover:bg-gray-50">Cancelar</button>
                <button type="submit" id="btn-salvar-grupo" class="px-3 py-2 rounded bg-gray-800 text-white text-xs font-semibold transition hover:bg-gray-700">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    'use strict';

    function initMonitoramentoGrupos() {
        var container = document.getElementById('monitoramento-grupos-container');
        if (!container || container.dataset.initialized === '1') return;
        container.dataset.initialized = '1';

        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var errorRegion = document.getElementById('monitoramento-grupos-error-region');
        var modalGrupo = document.getElementById('modal-grupo');
        var formGrupo = document.getElementById('form-grupo');
        var modalTitulo = document.getElementById('modal-grupo-titulo');
        var inputGrupoId = document.getElementById('input-grupo-id');
        var inputGrupoNome = document.getElementById('input-grupo-nome');
        var inputGrupoDescricao = document.getElementById('input-grupo-descricao');
        var btnCriarGrupo = document.getElementById('btn-criar-grupo');
        var btnCriarGrupoEmpty = document.getElementById('btn-criar-grupo-empty');

        function abrirModalCriar() {
            modalTitulo.textContent = 'Novo Grupo';
            inputGrupoId.value = '';
            inputGrupoNome.value = '';
            inputGrupoDescricao.value = '';
            document.querySelectorAll('.cor-preview').forEach(function(preview, index) {
                preview.classList.toggle('ring-2', index === 0);
                preview.classList.toggle('ring-gray-400', index === 0);
            });
            var firstRadio = document.querySelector('.cor-radio');
            if (firstRadio) firstRadio.checked = true;
            modalGrupo.classList.remove('hidden');
        }

        function fecharModal() {
            modalGrupo.classList.add('hidden');
        }

        if (btnCriarGrupo) btnCriarGrupo.addEventListener('click', abrirModalCriar);
        if (btnCriarGrupoEmpty) btnCriarGrupoEmpty.addEventListener('click', abrirModalCriar);
        document.querySelectorAll('.modal-close').forEach(function(btn) {
            btn.addEventListener('click', fecharModal);
        });

        document.querySelectorAll('.cor-preview').forEach(function(preview) {
            preview.addEventListener('click', function() {
                document.querySelectorAll('.cor-preview').forEach(function(other) {
                    other.classList.remove('ring-2', 'ring-gray-400');
                });
                preview.classList.add('ring-2', 'ring-gray-400');
                var radio = preview.parentElement.querySelector('.cor-radio');
                if (radio) radio.checked = true;
            });
        });

        document.querySelectorAll('.btn-editar-grupo').forEach(function(btn) {
            btn.addEventListener('click', function() {
                modalTitulo.textContent = 'Editar Grupo';
                inputGrupoId.value = btn.dataset.grupoId;
                inputGrupoNome.value = btn.dataset.grupoNome;
                inputGrupoDescricao.value = btn.dataset.grupoDescricao || '';
                document.querySelectorAll('.cor-preview').forEach(function(preview) {
                    var active = preview.dataset.cor === btn.dataset.grupoCor;
                    preview.classList.toggle('ring-2', active);
                    preview.classList.toggle('ring-gray-400', active);
                    var radio = preview.parentElement.querySelector('.cor-radio');
                    if (radio) radio.checked = active;
                });
                modalGrupo.classList.remove('hidden');
            });
        });

        if (formGrupo) {
            formGrupo.addEventListener('submit', async function(event) {
                event.preventDefault();

                var grupoId = inputGrupoId.value;
                var submitBtn = document.getElementById('btn-salvar-grupo');
                var formData = new FormData(formGrupo);
                var payload = Object.fromEntries(formData.entries());
                var url = grupoId ? '/app/monitoramento/grupos/' + grupoId : '/app/monitoramento/grupos';
                var method = grupoId ? 'PUT' : 'POST';

                submitBtn.disabled = true;
                submitBtn.textContent = 'Salvando...';

                try {
                    clearInlineError();
                    var response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });
                    var data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'Erro ao salvar grupo');
                    }

                    if (window.showToast) window.showToast(data.message || 'Grupo salvo com sucesso.', 'success');
                    window.location.reload();
                } catch (err) {
                    showInlineError(err.message || 'Erro ao salvar grupo', 'monitoramento-grupos-salvar');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Salvar';
                }
            });
        }

        document.querySelectorAll('.btn-excluir-grupo').forEach(function(btn) {
            btn.addEventListener('click', async function() {
                var grupoId = btn.dataset.grupoId;
                var grupoNome = btn.dataset.grupoNome;

                if (!window.confirm('Tem certeza que deseja excluir o grupo "' + grupoNome + '"?\n\nOs participantes não serão excluídos, apenas a associação com o grupo.')) {
                    return;
                }

                try {
                    clearInlineError();
                    var response = await fetch('/app/monitoramento/grupos/' + grupoId, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });
                    var data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'Erro ao excluir grupo');
                    }
                    if (window.showToast) window.showToast(data.message || 'Grupo excluído com sucesso.', 'success');
                    var row = container.querySelector('tr[data-grupo-id="' + grupoId + '"]');
                    if (row) {
                        var nextRow = row.nextElementSibling;
                        row.remove();
                        if (nextRow && nextRow.classList.contains('grupo-expand-row')) nextRow.remove();
                    }
                } catch (err) {
                    showInlineError(err.message || 'Erro ao excluir grupo', 'monitoramento-grupos-excluir');
                }
            });
        });

        function toggleExpandGrupo(grupoId, url) {
            var row = container.querySelector('tr[data-grupo-id="' + grupoId + '"]');
            if (!row) return;

            var nextRow = row.nextElementSibling;
            if (nextRow && nextRow.classList.contains('grupo-expand-row')) {
                nextRow.remove();
                var openIcon = row.querySelector('.grupo-expand-icon');
                if (openIcon) openIcon.classList.remove('rotate-90');
                return;
            }

            var expandRow = document.createElement('tr');
            expandRow.className = 'grupo-expand-row bg-gray-50';
            expandRow.innerHTML = '<td colspan="6" class="px-4 py-4"><div class="text-sm text-gray-500">Carregando participantes...</div></td>';
            row.after(expandRow);

            var icon = row.querySelector('.grupo-expand-icon');
            if (icon) icon.classList.add('rotate-90');

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            })
                .then(function(res) { return res.text(); })
                .then(function(html) {
                    var cell = expandRow.querySelector('td');
                    if (cell) cell.innerHTML = html;
                })
                .catch(function() {
                    var cell = expandRow.querySelector('td');
                    if (cell) cell.innerHTML = '<div class="text-sm text-red-600">Erro ao carregar participantes do grupo.</div>';
                });
        }

        container.addEventListener('click', function(event) {
            var expandBtn = event.target.closest('.grupo-expand-btn');
            if (expandBtn) {
                event.preventDefault();
                toggleExpandGrupo(expandBtn.dataset.grupoId, expandBtn.dataset.expandUrl);
                return;
            }

            var pageBtn = event.target.closest('.js-related-page');
            if (pageBtn) {
                event.preventDefault();
                if (pageBtn.disabled) return;
                var row = pageBtn.closest('.grupo-expand-row');
                if (!row) return;
                var cell = row.querySelector('td');
                if (cell) cell.innerHTML = '<div class="text-sm text-gray-500">Carregando participantes...</div>';
                fetch(pageBtn.dataset.url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                    },
                })
                    .then(function(res) { return res.text(); })
                    .then(function(html) {
                        if (cell) cell.innerHTML = html;
                    })
                    .catch(function() {
                        if (cell) cell.innerHTML = '<div class="text-sm text-red-600">Erro ao carregar participantes do grupo.</div>';
                    });
            }
        });
    }

    window.initMonitoramentoGrupos = initMonitoramentoGrupos;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMonitoramentoGrupos, { once: true });
    } else {
        initMonitoramentoGrupos();
    }
})();
</script>
        function showInlineError(message, action) {
            if (window.showInlineError) {
                window.showInlineError(errorRegion, {
                    message: message,
                    context: {
                        action: action || 'monitoramento-grupos',
                        url: window.location.pathname + window.location.search,
                    },
                });
                return;
            }

            if (window.showToast) window.showToast(message, 'error');
        }

        function clearInlineError() {
            if (window.clearInlineError) {
                window.clearInlineError(errorRegion);
            }
        }
