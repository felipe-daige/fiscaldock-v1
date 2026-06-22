function initCriarConta() {
    const signupForm = document.getElementById('signup-form');

    if (!signupForm) {
        return;
    }

    const newForm = signupForm.cloneNode(true);
    signupForm.parentNode.replaceChild(newForm, signupForm);

    const freshForm = document.getElementById('signup-form');
    const submitBtn = document.getElementById('signup-submit-btn');

    if (!freshForm || !submitBtn) {
        return;
    }

    const originalButtonHTML = submitBtn.innerHTML;
    const alertBox = document.getElementById('signup-alert');

    function inputFor(key) {
        return freshForm.querySelector(`[name="${key}"]`);
    }

    function clearFieldError(name) {
        if (!name) {
            return;
        }
        const slot = freshForm.querySelector(`.field-error[data-error="${name}"]`);
        if (slot) {
            slot.textContent = '';
        }
        freshForm.querySelectorAll(`[name="${name}"]`).forEach((el) => {
            el.classList.remove('border-red-500');
            el.removeAttribute('data-invalid');
        });
    }

    function clearErrors() {
        if (alertBox) {
            alertBox.classList.add('hidden');
            alertBox.textContent = '';
        }
        freshForm.querySelectorAll('.field-error').forEach((el) => {
            el.textContent = '';
        });
        freshForm.querySelectorAll('[data-invalid]').forEach((el) => {
            el.classList.remove('border-red-500');
            el.removeAttribute('data-invalid');
        });
    }

    // Mostra cada erro inline, abaixo do campo correspondente. Erros sem campo
    // conhecido caem no alerta de topo. Foca/rola até o primeiro problema.
    function showErrors(errors) {
        let first = null;

        Object.keys(errors).forEach((key) => {
            const value = errors[key];
            const msg = Array.isArray(value) ? value[0] : value;
            const slot = freshForm.querySelector(`.field-error[data-error="${key}"]`);
            const input = inputFor(key);

            if (slot) {
                slot.textContent = msg;
            } else if (alertBox) {
                alertBox.textContent = msg;
                alertBox.classList.remove('hidden');
            }

            if (input && input.type !== 'radio' && input.type !== 'checkbox') {
                input.classList.add('border-red-500');
                input.setAttribute('data-invalid', '');
            }

            if (!first) {
                first = input || slot;
            }
        });

        if (first) {
            if (typeof first.scrollIntoView === 'function') {
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            if (typeof first.focus === 'function') {
                try {
                    first.focus({ preventScroll: true });
                } catch (e) {
                    /* noop */
                }
            }
        }
    }

    function showGenericError(message) {
        const texto = message || 'Erro ao criar a conta. Tente novamente.';
        if (alertBox) {
            alertBox.textContent = texto;
            alertBox.classList.remove('hidden');
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else if (typeof showToast === 'function') {
            showToast(texto, 'error');
        }
    }

    // Botão "ver senha": alterna entre password/text e troca o ícone (olho/olho cortado).
    freshForm.querySelectorAll('.senha-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            if (!input) {
                return;
            }
            const revelar = input.type === 'password';
            input.type = revelar ? 'text' : 'password';
            btn.setAttribute('aria-label', revelar ? 'Ocultar senha' : 'Mostrar senha');

            const eye = btn.querySelector('.icon-eye');
            const eyeOff = btn.querySelector('.icon-eye-off');
            if (eye && eyeOff) {
                eye.classList.toggle('hidden', revelar);
                eyeOff.classList.toggle('hidden', !revelar);
            }
        });
    });

    // Limpa o erro do campo assim que o usuário começa a corrigi-lo.
    const limparAoEditar = (e) => clearFieldError(e.target && e.target.name);
    freshForm.addEventListener('input', limparAoEditar);
    freshForm.addEventListener('change', limparAoEditar);

    function applyMasks() {
        if (typeof $ === 'undefined') {
            return;
        }

        const documentoBehavior = function (val) {
            return val.replace(/\D/g, '').length <= 11 ? '000.000.000-009' : '00.000.000/0000-00';
        };

        const documentoOptions = {
            onKeyPress: function (val, e, field, options) {
                field.mask(documentoBehavior.apply({}, arguments), options);
            },
        };

        $('#documento').mask(documentoBehavior, documentoOptions);
        $('#telefone').mask('(00) 00000-0000');
    }

    function resetButton() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalButtonHTML;
    }

    // Modal de boas-vindas (bloqueante, 2 etapas): créditos -> confirmação de termos.
    // O aceite legal já ocorreu no form; a etapa 2 reconfirma e registra consent_log.
    // Só redireciona pro painel ao concluir. Best-effort: se o POST falhar, segue mesmo assim.
    function abrirModalBoasVindas(redirect) {
        const modal = document.getElementById('signup-modal');
        if (!modal) {
            window.location.href = redirect;
            return;
        }

        const step1 = modal.querySelector('[data-step="1"]');
        const step2 = modal.querySelector('[data-step="2"]');
        const btnCiente = document.getElementById('signup-modal-ciente');
        const btnContinuar = document.getElementById('signup-modal-continuar');
        const chkTerms = document.getElementById('signup-modal-terms');
        const erro = document.getElementById('signup-modal-error');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (btnCiente) {
            btnCiente.addEventListener('click', () => {
                if (step1) step1.classList.add('hidden');
                if (step2) step2.classList.remove('hidden');
            }, { once: true });
        }

        if (btnContinuar) {
            btnContinuar.addEventListener('click', () => {
                if (chkTerms && !chkTerms.checked) {
                    if (erro) erro.textContent = 'Marque a confirmação para continuar.';
                    return;
                }
                if (erro) erro.textContent = '';
                btnContinuar.disabled = true;
                btnContinuar.innerHTML = '<span>Aguarde...</span>';

                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                fetch('/app/onboarding/confirmar-termos', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token || '',
                        'Accept': 'application/json',
                    },
                })
                    .then(() => { window.location.href = redirect; })
                    .catch(() => { window.location.href = redirect; });
            });
        }
    }

    applyMasks();
    setupPersonaToggle(freshForm);

    freshForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (submitBtn.disabled) {
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!token) {
            showGenericError('Sessão expirada. Recarregue a página e tente novamente.');
            return;
        }

        clearErrors();
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Criando conta...</span>';

        fetch('/criar-conta', {
            method: 'POST',
            body: new FormData(freshForm),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    abrirModalBoasVindas(data.redirect || '/app/dashboard');
                    return;
                }

                if (data.errors && Object.keys(data.errors).length) {
                    showErrors(data.errors);
                } else {
                    showGenericError(data.message);
                }

                resetButton();
            })
            .catch(() => {
                showGenericError('Erro ao criar a conta. Tente novamente.');
                resetButton();
            });
    });
}

// Persona da conta (orientação no signup): "minha própria empresa" x "contador/escritório".
// Apenas relabela campos e mostra ajuda — o backend segue usando empresa/cargo/documento.
// Objetivo: evitar que um contador cadastre um CLIENTE como se fosse a própria empresa.
function setupPersonaToggle(form) {
    const radios = form.querySelectorAll('input[name="perfil_conta"]');
    if (!radios.length) {
        return;
    }

    const empresaLabel = form.querySelector('label[for="empresa"]');
    const empresaInput = form.querySelector('#empresa');
    const documentoAjuda = form.querySelector('#documento-ajuda');
    const personaAjuda = form.querySelector('#persona-ajuda');
    const cargoInput = form.querySelector('#cargo');

    const COPY = {
        empresa: {
            empresaLabel: 'Empresa',
            empresaPlaceholder: 'Nome da empresa',
            persona: 'Cadastre os dados da sua própria empresa — é ela que você vai monitorar.',
            documento: 'CPF ou CNPJ da sua empresa.',
        },
        contador: {
            empresaLabel: 'Escritório de contabilidade',
            empresaPlaceholder: 'Razão social do seu escritório',
            persona: 'Cadastre o seu escritório. Cada empresa que você atende é adicionada depois em Cadastros › Clientes — não use os dados do cliente aqui.',
            documento: 'CPF ou CNPJ do escritório — não o do cliente.',
        },
    };

    function aplicar(valor) {
        const c = COPY[valor] || COPY.empresa;
        if (empresaLabel) empresaLabel.textContent = c.empresaLabel;
        if (empresaInput) empresaInput.setAttribute('placeholder', c.empresaPlaceholder);
        if (personaAjuda) personaAjuda.textContent = c.persona;
        if (documentoAjuda) documentoAjuda.textContent = c.documento;

        // Realça o cartão selecionado (borda/anel mais escuros).
        form.querySelectorAll('.persona-opt').forEach((opt) => {
            const input = opt.querySelector('input[name="perfil_conta"]');
            const checked = !!(input && input.checked);
            opt.classList.toggle('border-gray-800', checked);
            opt.classList.toggle('ring-1', checked);
            opt.classList.toggle('ring-gray-800', checked);
            opt.classList.toggle('border-gray-300', !checked);
        });

        // Conveniência: contador quase sempre tem cargo "Contador". Só preenche se vazio.
        if (valor === 'contador' && cargoInput && !cargoInput.value.trim()) {
            cargoInput.value = 'Contador';
        }
    }

    radios.forEach((r) => r.addEventListener('change', () => aplicar(r.value)));

    const inicial = form.querySelector('input[name="perfil_conta"]:checked');
    aplicar(inicial ? inicial.value : 'empresa');
}
