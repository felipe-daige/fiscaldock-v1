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
@endphp

<div class="bg-gray-100 min-h-screen" id="efd-detalhes-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        @include('autenticado.importacao.efd-detalhes._header')
        @include('autenticado.importacao.efd-detalhes._sticky-nav')

        @if($importacao->status === 'erro')
            <div class="bg-white rounded border border-gray-300 p-4 border-l-4 border-l-red-500 mb-6">
                <p class="text-sm font-semibold text-gray-900">Esta importação terminou com erro</p>
                <p class="text-sm text-gray-700 mt-1">Verifique o arquivo enviado e tente novamente.</p>
            </div>
        @endif

        @include('autenticado.importacao.efd-detalhes._info-card')
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
    </div>
</div>

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
                return '<tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location=\'/app/notas-fiscais/efd/' + n.id + '\'">' +
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
            var a = e.target.closest('a[data-link]');
            if (a && section.contains(a) && (a.textContent.trim() === 'Anterior' || a.textContent.trim() === 'Próxima')) {
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
