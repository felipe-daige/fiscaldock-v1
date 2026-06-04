// Expand inline do histórico/drift do catálogo a partir do detalhe de uma nota.
// Handler DELEGADO em document → funciona tanto no drill-down AJAX do /app/notas
// (efd-inline injetado via innerHTML) quanto na página cheia /app/notas/efd/{id}.
// Gatilho: [data-cat-hist="<cod_item>"] [data-cat-cliente="<cliente_id>"], com um
// .cat-hist-panel logo após o botão. Reusa o endpoint /app/catalogo/historico/{cod}.
(function () {
    var cache = {};

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-cat-hist]');
        if (!btn) {
            return;
        }
        e.preventDefault();

        var panel = btn.nextElementSibling;
        if (!panel || !panel.classList.contains('cat-hist-panel')) {
            return;
        }

        if (!panel.classList.contains('hidden')) {
            panel.classList.add('hidden');
            return;
        }
        panel.classList.remove('hidden');

        var cod = btn.getAttribute('data-cat-hist');
        var cli = btn.getAttribute('data-cat-cliente') || '';
        var key = cod + '|' + cli;

        if (cache[key]) {
            panel.innerHTML = cache[key];
            return;
        }

        panel.innerHTML = '<p class="p-3 text-xs text-gray-400">Carregando catálogo…</p>';

        fetch('/app/catalogo/historico/' + encodeURIComponent(cod) + '?cliente_id=' + encodeURIComponent(cli), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                cache[key] = html;
                panel.innerHTML = html;
            })
            .catch(function () {
                panel.innerHTML = '<p class="p-3 text-xs text-red-500">Erro ao carregar o catálogo.</p>';
            });
    });
})();
