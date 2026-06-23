{{-- Importação EFD - Detalhes --}}
@php
    [$badgeStyle, $badgeLabel] = match($importacao->status) {
        'concluido' => ['background-color: #047857', 'Concluído'],
        'processando' => ['background-color: #d97706', 'Processando'],
        'erro' => ['background-color: #dc2626', 'Erro'],
        default => ['background-color: #9ca3af', 'Pendente'],
    };

    $tipoStyle = str_contains(strtolower($importacao->tipo_efd ?? ''), 'pis')
        || $importacao->tipo_efd === 'efd-contrib'
        ? 'background-color: #0f766e'
        : 'background-color: #4338ca';

    $emProcessamento = in_array($importacao->status, ['processando', 'pendente'], true);
    $concluido = $importacao->status === 'concluido';

    $tipoEfdJs = (str_contains(strtolower($importacao->tipo_efd ?? ''), 'pis') || $importacao->tipo_efd === 'efd-contrib')
        ? 'contrib'
        : 'fiscal';
    $tabIdQuery = request()->query('tab_id', '');
@endphp

<div class="bg-gray-100 min-h-screen" id="efd-detalhes-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        @include('autenticado.importacao.efd-detalhes._header')
        @include('autenticado.importacao.efd-detalhes._info-card')

        @if($emProcessamento)
            <div id="efd-progresso-root"
                 class="mb-4"
                 data-tab-id="{{ $tabIdQuery }}"
                 data-tipo="{{ $tipoEfdJs }}"
                 data-importacao-id="{{ $importacao->id }}"
                 data-iniciado-em="{{ optional($importacao->created_at)->timestamp }}">

                <div id="efd-progresso-card" class="bg-white rounded border border-gray-300 overflow-hidden mb-4">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Andamento da Importação</span>
                        <span id="efd-progresso-percent" class="text-[10px] text-gray-500 font-mono">0%</span>
                    </div>
                    <div class="p-4">
                        <div class="w-full h-1.5 rounded-full overflow-hidden" style="background-color: #e5e7eb">
                            <div id="efd-progresso-bar" class="h-full" style="background-color: #1f2937; width: 0%; transition: width 350ms ease-out"></div>
                        </div>
                        <p id="efd-progresso-etapa" class="text-xs text-gray-600 mt-3">Preparando importação...</p>
                        <p id="efd-progresso-meta" class="text-[11px] text-gray-500 mt-1 hidden"></p>
                        @include('autenticado.partials.progresso-tempo', [
                            'prefixo' => 'efd-progresso',
                            'dica' => 'processamos o arquivo SPED no servidor — pode levar alguns minutos.',
                        ])
                        <div id="efd-progresso-steps" class="mt-3 flex flex-wrap gap-2">
                            @php
                                $etapas = $tipoEfdJs === 'fiscal'
                                    ? [
                                        ['key' => 'participantes', 'label' => 'Participantes'],
                                        ['key' => 'notas_mercadorias', 'label' => 'NF-e Mercadorias'],
                                        ['key' => 'notas_transportes', 'label' => 'CT-e'],
                                        ['key' => 'catalogo', 'label' => 'Catálogo'],
                                        ['key' => 'apuracao_icms', 'label' => 'Apuração ICMS'],
                                    ]
                                    : [
                                        ['key' => 'participantes', 'label' => 'Participantes'],
                                        ['key' => 'notas_servicos', 'label' => 'Notas Serviço'],
                                        ['key' => 'notas_mercadorias', 'label' => 'NF-e Mercadorias'],
                                        ['key' => 'catalogo', 'label' => 'Catálogo'],
                                        ['key' => 'apuracao_pis_cofins', 'label' => 'Apuração PIS/COFINS'],
                                        ['key' => 'retencoes_fonte', 'label' => 'Retenções'],
                                    ];
                            @endphp

                            @foreach($etapas as $etapa)
                                <div class="etapa-item inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs font-medium text-gray-400" data-etapa="{{ $etapa['key'] }}">
                                    <span class="etapa-icon flex items-center justify-center w-3.5 h-3.5">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                    </span>
                                    <span>{{ $etapa['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded border border-gray-300 p-6 text-center">
                <p class="text-sm text-gray-500">Os dados da importação aparecerão aqui assim que o processamento terminar.</p>
                <p class="text-xs text-gray-400 mt-1">Você pode fechar esta aba e voltar depois pelo histórico.</p>
            </div>
        @endif

        @if($importacao->status === 'erro')
            @include('autenticado.partials.system-critical-error', [
                'errorUi' => app(\App\Support\SystemCriticalError::class)->forAsyncFailure(
                    null,
                    null,
                    [
                        'context' => 'importacao-efd',
                        'url' => request()->getPathInfo(),
                        'reference' => 'Importação #'.$importacao->id,
                    ]
                ),
            ])
        @endif

        @if($concluido)
            @include('autenticado.importacao.efd-detalhes._sticky-nav')
            @include('autenticado.importacao.efd-detalhes._indicadores')
            @include('autenticado.importacao.efd-detalhes._resumo-tributario')
            @include('autenticado.importacao.efd-detalhes._cliente')
            @include('autenticado.importacao.efd-detalhes._participantes')
            @include('autenticado.importacao.efd-detalhes._resumo-final')
            @include('autenticado.importacao.efd-detalhes._catalogo')
            @include('autenticado.importacao.efd-detalhes._apuracao-icms')
            @include('autenticado.importacao.efd-detalhes._retencoes')
            @include('autenticado.importacao.efd-detalhes._apuracao-pis-cofins')

            @if(!empty($resumoFinal['analise_fiscal']) || !empty($resumoFinal['alertas']))
                @include('autenticado.importacao.efd-detalhes._analise-fiscal')
            @endif
        @endif
    </div>
</div>

@if($emProcessamento)
<script src="/js/progresso-automacao.js?v={{ @filemtime(public_path('js/progresso-automacao.js')) ?: time() }}"></script>
<script src="/js/efd-importacao-progresso.js?v={{ filemtime(public_path('js/efd-importacao-progresso.js')) }}"></script>
@endif

<div id="modal-excluir-importacao" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-md rounded-lg bg-white shadow-xl">
        <div class="border-b border-gray-200 px-5 py-4">
            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-900">Excluir importação</h3>
            <p class="mt-1 text-xs text-gray-500" id="excluir-arquivo"></p>
        </div>
        <div class="px-5 py-4 text-sm text-gray-700">
            <p class="mb-3">Esta ação é <strong>irreversível</strong>. Serão apagados:</p>
            <ul id="excluir-impacto" class="mb-4 space-y-1 text-xs text-gray-600"></ul>
            <label class="flex items-start gap-2 rounded border border-gray-200 p-3">
                <input type="checkbox" id="excluir-participantes" class="mt-0.5">
                <span class="text-xs text-gray-700">
                    Também excluir os participantes desta importação
                    <span id="excluir-part-detalhe" class="block text-gray-500"></span>
                </span>
            </label>
        </div>
        <div class="flex justify-end gap-2 border-t border-gray-200 px-5 py-3">
            <button type="button" id="excluir-cancelar" class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50">Cancelar</button>
            <button type="button" id="excluir-confirmar" class="rounded px-3 py-1.5 text-xs font-medium text-white" style="background-color:#dc2626">Excluir definitivamente</button>
        </div>
    </div>
</div>

<script>
(function () {
    if (window._excluirImportacaoInit) return;
    window._excluirImportacaoInit = true;

    var modal = document.getElementById('modal-excluir-importacao');
    if (!modal) return;
    var elArquivo = document.getElementById('excluir-arquivo');
    var elImpacto = document.getElementById('excluir-impacto');
    var elPartDet = document.getElementById('excluir-part-detalhe');
    var chkPart = document.getElementById('excluir-participantes');
    var btnConfirmar = document.getElementById('excluir-confirmar');
    var btnCancelar = document.getElementById('excluir-cancelar');
    var atual = { id: null, redirect: null, trigger: null };

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }
    function abrir() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function fechar() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

    function onClickExcluir(btn) {
        atual.id = btn.getAttribute('data-excluir-importacao');
        atual.redirect = btn.getAttribute('data-redirect');
        atual.trigger = btn;
        chkPart.checked = false;
        elArquivo.textContent = btn.getAttribute('data-filename') || '';
        elImpacto.innerHTML = '<li>Carregando prévia…</li>';
        elPartDet.textContent = '';
        abrir();

        fetch('/app/importacao/efd/' + atual.id + '/preview-exclusao', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            elImpacto.innerHTML =
                '<li>' + d.notas + ' notas e ' + d.itens + ' itens</li>' +
                '<li>' + d.catalogo + ' itens de catálogo</li>' +
                '<li>' + d.apuracoes + ' apurações · ' + d.retencoes + ' retenções · ' + d.divergencias + ' divergências</li>';
            var p = d.participantes || {};
            elPartDet.textContent = (p.orfaos || 0) + ' órfãos serão excluídos · ' + (p.compartilhados || 0) + ' compartilhados serão preservados';
        })
        .catch(function () { elImpacto.innerHTML = '<li style="color:#dc2626">Falha ao carregar prévia.</li>'; });
    }

    function confirmar() {
        if (!atual.id) return;
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = 'Excluindo…';
        fetch('/app/importacao/efd/' + atual.id, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ excluir_participantes: chkPart.checked })
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (res) {
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = 'Excluir definitivamente';
            if (!res.ok || !res.j.success) {
                elImpacto.innerHTML = '<li style="color:#dc2626">' + (res.j.error || 'Falha ao excluir.') + '</li>';
                return;
            }
            fechar();
            if (atual.redirect) {
                window.location.href = atual.redirect;
            } else if (atual.trigger) {
                var row = atual.trigger.closest('tr, [data-importacao-card]');
                if (row) row.remove();
            }
        })
        .catch(function () {
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = 'Excluir definitivamente';
            elImpacto.innerHTML = '<li style="color:#dc2626">Erro de rede ao excluir.</li>';
        });
    }

    function handler(e) {
        var btn = e.target.closest('[data-excluir-importacao]');
        if (btn && !btn.disabled) { e.preventDefault(); onClickExcluir(btn); }
    }
    document.addEventListener('click', handler);
    btnCancelar.addEventListener('click', fechar);
    btnConfirmar.addEventListener('click', confirmar);
    modal.addEventListener('click', function (e) { if (e.target === modal) fechar(); });

    window._cleanupFunctions = window._cleanupFunctions || {};
    window._cleanupFunctions.excluirImportacao = function () {
        document.removeEventListener('click', handler);
        window._excluirImportacaoInit = false;
    };
})();
</script>

@if(! $emProcessamento)
{{-- Modal: exportar planilha (tudo em ZIP ou um único CSV) --}}
<div id="modal-exportar-planilha" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-md rounded-lg bg-white shadow-xl">
        <div class="border-b border-gray-200 px-5 py-4">
            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-900">Exportar planilha</h3>
            <p class="mt-1 text-xs text-gray-500">Baixe tudo de uma vez ou apenas um dataset. CSV abre no Excel/Google Sheets.</p>
        </div>
        <div class="px-5 py-4">
            <a href="/app/importacao/efd/{{ $importacao->id }}/exportar" data-exportar-opcao
                class="flex items-center justify-between rounded border border-blue-200 bg-blue-50 px-3 py-2.5 hover:bg-blue-100">
                <span class="text-sm font-semibold text-blue-800">Tudo (ZIP de CSVs)</span>
                <span class="text-[11px] font-medium text-blue-600">.zip</span>
            </a>
            <p class="mt-4 mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">Ou um único CSV</p>
            <div class="space-y-1">
                @foreach(\App\Services\Efd\EfdPlanilhaExportService::DATASETS as $key => $label)
                    <a href="/app/importacao/efd/{{ $importacao->id }}/exportar?dataset={{ $key }}" data-exportar-opcao
                        class="flex items-center justify-between rounded border border-gray-200 px-3 py-2 hover:bg-gray-50">
                        <span class="text-sm text-gray-700">{{ $label }}</span>
                        <span class="text-[11px] text-gray-400">.csv</span>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="flex justify-end border-t border-gray-200 px-5 py-3">
            <button type="button" id="exportar-fechar" class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50">Fechar</button>
        </div>
    </div>
</div>

<script>
(function () {
    if (window._exportarPlanilhaInit) return;
    window._exportarPlanilhaInit = true;

    var modal = document.getElementById('modal-exportar-planilha');
    if (!modal) return;

    function abrir() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function fechar() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

    function handler(e) {
        var btn = e.target.closest('[data-exportar-planilha]');
        if (btn) { e.preventDefault(); abrir(); }
    }
    document.addEventListener('click', handler);
    document.getElementById('exportar-fechar').addEventListener('click', fechar);
    modal.addEventListener('click', function (e) { if (e.target === modal) fechar(); });
    // Após disparar o download (browser baixa sem navegar), fecha o modal.
    modal.querySelectorAll('[data-exportar-opcao]').forEach(function (a) {
        a.addEventListener('click', function () { setTimeout(fechar, 400); });
    });

    window._cleanupFunctions = window._cleanupFunctions || {};
    window._cleanupFunctions.exportarPlanilha = function () {
        document.removeEventListener('click', handler);
        window._exportarPlanilhaInit = false;
    };
})();
</script>
@endif

@if($concluido)
<script>
function _efdFormatBRL(valor) {
    return 'R$ ' + Number(valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function _efdFormatDate(val) {
    if (!val) return '-';
    var p = val.split('T')[0].split('-');
    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : val;
}

function _efdRenderNotas(contentDiv, notas, biHtml, cache, pid) {
    cache[pid] = notas;
    var notasHtml = '';
    if (notas && notas.length > 0) {
        notasHtml = '<div class="overflow-x-auto mt-2"><table class="w-full text-xs border border-gray-300 rounded">' +
            '<thead class="bg-gray-50"><tr>' +
            '<th class="px-2 py-1 text-left text-gray-500 uppercase tracking-wide">Nº Doc</th>' +
            '<th class="px-2 py-1 text-left text-gray-500 uppercase tracking-wide">Série</th>' +
            '<th class="px-2 py-1 text-left text-gray-500 uppercase tracking-wide">Modelo</th>' +
            '<th class="px-2 py-1 text-left text-gray-500 uppercase tracking-wide">Emissão</th>' +
            '<th class="px-2 py-1 text-center text-gray-500 uppercase tracking-wide">Tipo</th>' +
            '<th class="px-2 py-1 text-right text-gray-500 uppercase tracking-wide">Valor</th>' +
            '</tr></thead><tbody class="divide-y divide-gray-200">' +
            notas.slice(0, 50).map(function(n) {
                var tipoHtml = n.tipo_operacao === 'entrada'
                    ? '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">Entrada</span>'
                    : '<span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #d97706">Saída</span>';
                return '<tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location=\'/app/notas/efd/' + n.id + '\'">' +
                    '<td class="px-2 py-1 font-mono text-gray-900">' + (n.numero || '-') + '</td>' +
                    '<td class="px-2 py-1 text-gray-700">' + (n.serie || '-') + '</td>' +
                    '<td class="px-2 py-1 text-gray-700">' + (n.modelo || '-') + '</td>' +
                    '<td class="px-2 py-1 text-gray-700">' + _efdFormatDate(n.data_emissao) + '</td>' +
                    '<td class="px-2 py-1 text-center">' + tipoHtml + '</td>' +
                    '<td class="px-2 py-1 text-right text-gray-900 font-mono">' + _efdFormatBRL(n.valor_total) + '</td>' +
                    '</tr>';
            }).join('') +
            '</tbody></table>' +
            (notas.length > 50 ? '<p class="text-xs text-gray-400 mt-1">Mostrando 50 de ' + notas.length + ' notas.</p>' : '') +
            '</div>';
    } else {
        notasHtml = '<p class="text-xs text-gray-400 mt-2">Nenhuma nota disponível.</p>';
    }
    contentDiv.innerHTML = biHtml + notasHtml;
}

function _efdInitCollapseToggles() {
    document.querySelectorAll('.efd-collapse-toggle').forEach(function(btn) {
        if (btn._efdBound) return;
        btn._efdBound = true;
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var target = document.getElementById(targetId);
            if (!target) return;
            var isHidden = target.classList.contains('hidden');
            target.classList.toggle('hidden');
            var chevron = this.querySelector('.efd-chevron');
            if (chevron) {
                chevron.style.transform = isHidden ? 'rotate(90deg)' : '';
            }
        });
    });
}

function _efdInitScrollSpy() {
    // Idempotente: remove um scroll listener anterior antes de registrar (esta página não
    // tem guard de re-entrada e pode re-inicializar no SPA).
    if (window._cleanupFunctions && window._cleanupFunctions.efdDetalhesScroll) {
        window._cleanupFunctions.efdDetalhesScroll();
    }
    var nav = document.getElementById('efd-sticky-nav');
    if (!nav) return;
    var links = nav.querySelectorAll('.efd-nav-link');
    if (!links.length) return;

    var sections = [];
    links.forEach(function(link) {
        var id = link.getAttribute('href');
        if (id && id.startsWith('#')) {
            var el = document.getElementById(id.substring(1));
            if (el) {
                sections.push({ link: link, el: el });
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    var headerOffset = 100;
                    var elementPosition = el.getBoundingClientRect().top;
                    var offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    window.scrollTo({ top: offsetPosition, behavior: 'smooth' });
                    if (history.pushState) {
                        history.pushState(null, null, id);
                    }
                });
            }
        }
    });

    function updateActive() {
        var scrollY = window.scrollY + 120;
        var active = null;
        for (var i = sections.length - 1; i >= 0; i--) {
            if (sections[i].el.offsetTop <= scrollY) {
                active = sections[i].link;
                break;
            }
        }
        links.forEach(function(l) {
            l.classList.remove('bg-gray-800', 'text-white', 'border-gray-800');
            l.classList.add('text-gray-600');
        });
        if (active) {
            active.classList.add('bg-gray-800', 'text-white', 'border-gray-800');
            active.classList.remove('text-gray-600');
        }

        var btnTopo = document.getElementById('btn-voltar-topo');
        var btnSticky = document.getElementById('btn-voltar-sticky');
        if (btnTopo && btnSticky) {
            var topoRect = btnTopo.getBoundingClientRect();
            if (topoRect.bottom < 0) {
                btnSticky.classList.remove('opacity-0', 'pointer-events-none', 'translate-x-4');
                btnSticky.classList.add('opacity-100', 'translate-x-0');
            } else {
                btnSticky.classList.remove('opacity-100', 'translate-x-0');
                btnSticky.classList.add('opacity-0', 'pointer-events-none', 'translate-x-4');
            }
        }
    }

    window.addEventListener('scroll', updateActive, { passive: true });
    updateActive();
    // SPA: remover o listener de scroll de window ao navegar (spa.js → limparRecursos),
    // senão acumula um novo a cada visita à página de detalhes.
    if (!window._cleanupFunctions) window._cleanupFunctions = {};
    window._cleanupFunctions.efdDetalhesScroll = function () {
        window.removeEventListener('scroll', updateActive);
    };
}

function _efdInitCatalogoSearch() {
    var input = document.getElementById('busca-catalogo');
    if (!input || input._efdBound) return;
    input._efdBound = true;
    input.addEventListener('input', function() {
        var q = this.value.toLowerCase().trim();
        var rows = document.querySelectorAll('#tbody-catalogo tr');
        var cards = document.querySelectorAll('#mobile-catalogo > div');
        var zero = document.getElementById('zero-state-catalogo');
        var visible = 0;
        function filterEl(el) {
            var cod = el.getAttribute('data-cod') || '';
            var desc = el.getAttribute('data-desc') || '';
            var ncm = el.getAttribute('data-ncm') || '';
            var match = !q || cod.includes(q) || desc.includes(q) || ncm.includes(q);
            el.style.display = match ? '' : 'none';
            if (match) visible++;
        }
        rows.forEach(filterEl);
        cards.forEach(filterEl);
        if (zero) zero.classList.toggle('hidden', visible > 0 || !q);
    });
}

window.asyncLoadEFD = function(url, sections) {
    sections.forEach(function(id) {
        var sec = document.getElementById(id);
        if (sec) {
            sec.style.opacity = '0.5';
            sec.style.pointerEvents = 'none';
        }
    });

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
        .then(function(res) { return res.text(); })
        .then(function(html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var updatedOne = false;

            sections.forEach(function(id) {
                var oldSec = document.getElementById(id);
                var newSec = doc.getElementById(id);
                if (oldSec && newSec) {
                    oldSec.innerHTML = newSec.innerHTML;
                    oldSec.style.opacity = '';
                    oldSec.style.pointerEvents = '';
                    updatedOne = true;
                }
            });

            if (updatedOne) {
                if (window.initImportacao) window.initImportacao();
                if (history.pushState) history.pushState(null, '', url);
            } else {
                window.location.href = url;
            }
        })
        .catch(function() {
            window.location.href = url;
        });
};

window.initImportacao = function() {
    function navigateToHref(el) {
        var href = el.getAttribute('data-href');
        if (!href) return;
        var link = document.createElement('a');
        link.setAttribute('data-link', '');
        link.href = href;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    document.querySelectorAll('[data-href]').forEach(function(row) {
        if (row._efdRowBound) return;
        row._efdRowBound = true;
        row.addEventListener('click', function() { navigateToHref(this); });
    });

    function bindAsyncPagination(sectionId, targetSections) {
        var section = document.getElementById(sectionId);
        if (!section || section._efdAsyncBound) return;
        section._efdAsyncBound = true;

        section.addEventListener('click', function(e) {
            var a = e.target.closest('a[data-async-pagination]');
            if (a && section.contains(a)) {
                e.preventDefault();
                e.stopPropagation();
                window.asyncLoadEFD(a.href, targetSections);
            }
        });
    }

    bindAsyncPagination('participantes-section', ['participantes-section', 'resumo-final-section']);
    bindAsyncPagination('resumo-final-section', ['participantes-section', 'resumo-final-section']);
    bindAsyncPagination('catalogo-section', ['catalogo-section']);

    var notasCache = {};
    var container = document.getElementById('tabela-notas-participantes-detalhes');
    if (container && !container._efdInitDone) {
        container._efdInitDone = true;
        container.addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-expand-notas-detalhes');
            if (!btn) return;
            e.stopPropagation();

            var pid = parseInt(btn.dataset.participanteId);
            var importacaoId = parseInt(btn.dataset.importacaoId);
            var notaIds = [];
            var bi = {};
            try { notaIds = JSON.parse(btn.dataset.notaIds || '[]'); } catch (x) {}
            try { bi = JSON.parse(btn.dataset.bi || '{}'); } catch (x) {}

            var parentTr = btn.closest('tr');
            if (!parentTr) return;

            var existingRow = parentTr.nextElementSibling;
            if (existingRow && existingRow.classList.contains('expand-notas-row-detalhes')) {
                existingRow.remove();
                btn.textContent = '\u25B6';
                return;
            }
            btn.textContent = '\u25BC';

            var expandTr = document.createElement('tr');
            expandTr.className = 'expand-notas-row-detalhes bg-gray-50';
            expandTr.innerHTML = '<td colspan="6" class="px-4 py-3"><div class="expand-content text-sm"><div class="text-gray-500 text-xs">Carregando notas...</div></div></td>';
            parentTr.after(expandTr);
            var contentDiv = expandTr.querySelector('.expand-content');

            var biHtml = '';
            if (bi && Object.keys(bi).length > 0) {
                biHtml = '<div class="flex flex-wrap gap-4 mb-2">' +
                    Object.entries(bi).map(function(kv) {
                        return '<span class="text-xs text-gray-600"><span class="font-medium text-gray-700">' + kv[0].replace(/_/g, ' ') + ':</span> ' + kv[1] + '</span>';
                    }).join('') + '</div>';
            }

            if (notasCache[pid] !== undefined) {
                _efdRenderNotas(contentDiv, notasCache[pid], biHtml, notasCache, pid);
                return;
            }

            var url = notaIds.length > 0
                ? '/app/importacao/efd/notas?' + notaIds.map(function(id) { return 'ids[]=' + id; }).join('&')
                : '/app/importacao/efd/notas-participante?participante_id=' + pid + '&importacao_id=' + importacaoId;

            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.ok ? r.json() : []; })
                .catch(function() { return []; })
                .then(function(notas) { _efdRenderNotas(contentDiv, notas, biHtml, notasCache, pid); });
        });
    }

    _efdInitCollapseToggles();
    _efdInitScrollSpy();
    _efdInitCatalogoSearch();

    var inputSearch = document.getElementById('busca-participantes-efd');
    if (!inputSearch || inputSearch._efdBound) return;
    inputSearch._efdBound = true;
    inputSearch.addEventListener('input', function() {
        var q = this.value.toLowerCase().trim();
        var rows = document.querySelectorAll('#tbody-participantes-efd tr');
        var cards = document.querySelectorAll('#mobile-participantes-efd > div');
        var zeroBusca = document.getElementById('zero-state-busca');
        var visible = 0;
        function filterEl(el) {
            var razao = el.getAttribute('data-razao') || '';
            var doc = el.getAttribute('data-doc') || '';
            var match = !q || razao.includes(q) || doc.includes(q);
            el.style.display = match ? '' : 'none';
            if (match) visible++;
        }
        rows.forEach(filterEl);
        cards.forEach(filterEl);
        if (zeroBusca) zeroBusca.classList.toggle('hidden', visible > 0 || !q);
    });
};

window.initImportacao();
</script>
@endif
