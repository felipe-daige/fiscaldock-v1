window.initRecarga = function () {
    var root = document.getElementById('recarga-modal');
    if (!root) return;
    if (root.__init) return;
    root.__init = true;

    var mp = (window.MercadoPago && window.__MP_PUBLIC_KEY)
        ? new MercadoPago(window.__MP_PUBLIC_KEY, { locale: 'pt-BR' })
        : null;
    var bricks = mp ? mp.bricks() : null;
    var controller = null;

    function mostrarErro(msg) {
        var el = document.getElementById('recarga-erro');
        if (!el) return;
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function fechar() {
        root.classList.add('hidden');
        root.classList.remove('flex');
        if (controller && controller.unmount) { try { controller.unmount(); } catch (e) {} controller = null; }
    }

    function pacoteSelecionado() {
        var sel = document.getElementById('recarga-pacote');
        if (!sel) return null;
        var opt = sel.options[sel.selectedIndex];
        return { slug: sel.value, valor: parseFloat(opt.getAttribute('data-valor')) || 0 };
    }

    function abrir() {
        if (!bricks) { mostrarErro('Pagamento indisponível no momento.'); return; }
        var pac = pacoteSelecionado();
        if (!pac) return;

        root.classList.remove('hidden');
        root.classList.add('flex');
        document.getElementById('recarga-erro').classList.add('hidden');
        document.getElementById('recarga-brick').innerHTML = '';
        if (controller && controller.unmount) { try { controller.unmount(); } catch (e) {} }

        bricks.create('cardPayment', 'recarga-brick', {
            initialization: { amount: pac.valor },
            callbacks: {
                onReady: function () {},
                onError: function () { mostrarErro('Erro ao carregar o cartão.'); },
                onSubmit: function (formData) { return enviar(pac.slug, formData.token); },
            },
        }).then(function (c) { controller = c; });
    }

    function enviar(pacote, token) {
        return fetch(window.__RECARGA_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.__CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ pacote: pacote, token: token }),
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                if (!res.ok) { mostrarErro(res.j.error || 'Falha ao ativar a recarga.'); return; }
                window.location.reload();
            }).catch(function () { mostrarErro('Falha de rede.'); });
    }

    var ativarBtn = document.getElementById('recarga-ativar');
    if (ativarBtn) ativarBtn.addEventListener('click', abrir);

    var fecharBtn = document.getElementById('recarga-fechar');
    if (fecharBtn) fecharBtn.addEventListener('click', fechar);

    var cancelarBtn = document.getElementById('recarga-cancelar');
    if (cancelarBtn) {
        cancelarBtn.addEventListener('click', function () {
            if (!confirm('Cancelar a recarga automática? Você pode reativar quando quiser.')) return;
            fetch(window.__RECARGA_CANCELAR_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.__CSRF, 'Accept': 'application/json' },
            }).then(function () { window.location.reload(); });
        });
    }

    window._cleanupFunctions = window._cleanupFunctions || {};
    window._cleanupFunctions.recarga = function () {
        if (controller && controller.unmount) { try { controller.unmount(); } catch (e) {} }
        controller = null;
        if (root) { root.__init = false; }
    };
};

if (document.getElementById('recarga-modal')) { window.initRecarga(); }
