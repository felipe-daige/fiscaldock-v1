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

    applyMasks();

    freshForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (submitBtn.disabled) {
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!token) {
            showToast('Erro: Token CSRF não encontrado. Recarregue a página.', 'error');
            return;
        }

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
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1200);
                    return;
                }

                if (data.errors) {
                    const firstErrorGroup = Object.values(data.errors)[0];
                    const firstError = Array.isArray(firstErrorGroup) ? firstErrorGroup[0] : data.message;
                    showToast(firstError || data.message, 'error');
                } else {
                    showToast(data.message || 'Erro ao criar a conta.', 'error');
                }

                resetButton();
            })
            .catch(() => {
                showToast('Erro ao criar a conta. Tente novamente.', 'error');
                resetButton();
            });
    });
}
