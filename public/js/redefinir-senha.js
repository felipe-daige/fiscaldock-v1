function initRedefinirSenha() {
    const form = document.getElementById('redefinir-senha-form');
    if (!form) return;

    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    const freshForm = document.getElementById('redefinir-senha-form');
    const submitBtn = document.getElementById('redefinir-senha-submit-btn');
    if (!freshForm || !submitBtn) return;

    const originalButtonHTML = submitBtn.innerHTML;
    const alertBox = document.getElementById('redefinir-senha-alert');

    function showAlert(message) {
        if (alertBox) {
            alertBox.textContent = message || 'Não foi possível redefinir a senha. Tente novamente.';
            alertBox.classList.remove('hidden');
        }
    }

    function resetButton() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalButtonHTML;
        submitBtn.blur();
    }

    function handleSubmit(e) {
        e.preventDefault();
        e.stopPropagation();

        if (submitBtn.disabled) return;

        if (alertBox) alertBox.classList.add('hidden');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Redefinindo...</span>';

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            showAlert('Sessão expirada. Recarregue a página e tente novamente.');
            resetButton();
            return;
        }

        const formData = new FormData(e.target);

        fetch('/redefinir-senha', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast(data.message, 'success');
                    }
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1200);
                    return;
                }

                let mensagem = data.message;
                if (data.errors) {
                    const grupo = Object.values(data.errors)[0];
                    mensagem = Array.isArray(grupo) ? grupo[0] : (data.message || mensagem);
                }
                showAlert(mensagem);
                resetButton();
            })
            .catch(() => {
                showAlert('Erro ao redefinir a senha. Tente novamente.');
                resetButton();
            });
    }

    freshForm.addEventListener('submit', handleSubmit, { once: false });
}
