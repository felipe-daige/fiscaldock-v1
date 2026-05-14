(function () {
    'use strict';

    const modal = document.getElementById('modal-nova-assinatura');
    if (!modal) return;

    const btnAbrir = document.getElementById('btn-nova-assinatura');
    const btnFechar = document.getElementById('modal-fechar');
    const btnAvancar = document.getElementById('modal-avancar');
    const btnVoltar = document.getElementById('modal-voltar');
    const btnCriar = document.getElementById('modal-criar');
    const etapa1 = document.getElementById('modal-etapa-1');
    const etapa2 = document.getElementById('modal-etapa-2');
    const erroBox = document.getElementById('modal-erro');
    const inputBusca = document.getElementById('modal-busca');
    const resultadosBox = document.getElementById('modal-resultados');
    const alvoEscolhidoBox = document.getElementById('modal-alvo-escolhido');
    const planoSelect = document.getElementById('modal-plano');
    const custoBox = document.getElementById('modal-custo');

    let alvoSelecionado = null;
    let buscaTimeout = null;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function abrir() {
        modal.classList.remove('hidden');
        resetar();
    }

    function fechar() {
        modal.classList.add('hidden');
    }

    function resetar() {
        alvoSelecionado = null;
        etapa1.classList.remove('hidden');
        etapa2.classList.add('hidden');
        erroBox.classList.add('hidden');
        inputBusca.value = '';
        resultadosBox.innerHTML = '';
        btnAvancar.disabled = true;
        atualizarCusto();
    }

    function mostrarErro(msg) {
        erroBox.textContent = msg;
        erroBox.classList.remove('hidden');
    }

    function tipoAtual() {
        return document.querySelector('input[name="tipo_alvo"]:checked')?.value || 'cliente';
    }

    async function buscar(q) {
        if (q.length < 2) {
            resultadosBox.innerHTML = '';
            return;
        }
        try {
            const r = await fetch(`/app/monitoramento/buscar-alvo?tipo=${tipoAtual()}&q=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json' },
            });
            if (!r.ok) throw new Error('Erro na busca');
            const data = await r.json();
            renderResultados(data.resultados || []);
        } catch (e) {
            resultadosBox.innerHTML = '<div class="p-3 text-xs text-red-600">Erro ao buscar.</div>';
        }
    }

    function renderResultados(items) {
        if (items.length === 0) {
            resultadosBox.innerHTML = '<div class="p-3 text-xs text-gray-500">Nenhum resultado.</div>';
            return;
        }
        resultadosBox.innerHTML = items.map(it => `
            <button type="button" class="resultado-item w-full text-left p-2 hover:bg-gray-50"
                    data-id="${it.id}" data-documento="${it.documento || ''}" data-razao="${(it.razao_social || '').replace(/"/g, '&quot;')}">
                <span class="text-xs font-mono text-gray-600">${it.documento || ''}</span>
                <span class="text-sm text-gray-900 ml-2">${it.razao_social || '—'}</span>
            </button>
        `).join('');
        resultadosBox.querySelectorAll('.resultado-item').forEach(b => b.addEventListener('click', () => {
            resultadosBox.querySelectorAll('.resultado-item').forEach(x => x.classList.remove('bg-gray-100'));
            b.classList.add('bg-gray-100');
            alvoSelecionado = { id: b.dataset.id, documento: b.dataset.documento, razao: b.dataset.razao };
            btnAvancar.disabled = false;
        }));
    }

    function avancar() {
        if (!alvoSelecionado) return;
        alvoEscolhidoBox.innerHTML = `<strong>${tipoAtual()}</strong> · ${alvoSelecionado.documento} — ${alvoSelecionado.razao}`;
        etapa1.classList.add('hidden');
        etapa2.classList.remove('hidden');
        atualizarCusto();
    }

    function voltar() {
        etapa2.classList.add('hidden');
        etapa1.classList.remove('hidden');
    }

    function atualizarCusto() {
        const opt = planoSelect?.selectedOptions[0];
        if (!opt) return;
        const creditos = parseInt(opt.dataset.creditos || '0', 10);
        if (custoBox) custoBox.textContent = creditos;
    }

    async function criar() {
        if (!alvoSelecionado) return;
        erroBox.classList.add('hidden');

        const body = new FormData();
        body.append('_token', csrf);
        body.append('plano_id', planoSelect.value);
        body.append('frequencia', document.getElementById('modal-frequencia').value);
        body.append('tipo_alvo', tipoAtual());
        if (tipoAtual() === 'cliente') {
            body.append('cliente_id', alvoSelecionado.id);
        } else {
            body.append('participante_id', alvoSelecionado.id);
        }

        try {
            const r = await fetch('/app/monitoramento/assinatura', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body,
            });
            const data = await r.json();
            if (!r.ok || data?.success === false) {
                mostrarErro(data?.message || 'Não foi possível criar a assinatura.');
                return;
            }
            window.location.reload();
        } catch (e) {
            mostrarErro('Erro de rede ao criar a assinatura.');
        }
    }

    btnAbrir?.addEventListener('click', abrir);
    btnFechar?.addEventListener('click', fechar);
    btnAvancar?.addEventListener('click', avancar);
    btnVoltar?.addEventListener('click', voltar);
    btnCriar?.addEventListener('click', criar);
    planoSelect?.addEventListener('change', atualizarCusto);
    document.querySelectorAll('input[name="tipo_alvo"]').forEach(r => r.addEventListener('change', () => {
        alvoSelecionado = null;
        btnAvancar.disabled = true;
        resultadosBox.innerHTML = '';
        inputBusca.value = '';
    }));
    inputBusca?.addEventListener('input', e => {
        clearTimeout(buscaTimeout);
        buscaTimeout = setTimeout(() => buscar(e.target.value.trim()), 250);
    });
})();
