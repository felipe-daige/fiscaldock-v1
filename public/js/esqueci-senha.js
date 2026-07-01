function initEsqueciSenha() {
    const form = document.getElementById('esqueci-senha-form');
    if (!form) return;

    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    const freshForm = document.getElementById('esqueci-senha-form');
    const submitBtn = document.getElementById('esqueci-senha-submit-btn');
    if (!freshForm || !submitBtn) return;

    const originalButtonHTML = submitBtn.innerHTML;
    const alertBox = document.getElementById('esqueci-senha-alert');
    const statusBox = document.getElementById('esqueci-senha-status');

    function showAlert(message) {
        if (alertBox) {
            alertBox.textContent = message || 'Não foi possível enviar o link. Tente novamente.';
            alertBox.classList.remove('hidden');
        }
    }

    function showStatus(message) {
        if (statusBox) {
            statusBox.textContent = message;
            statusBox.classList.remove('hidden');
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
        submitBtn.innerHTML = '<span>Enviando...</span>';

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!token) {
            showAlert('Sessão expirada. Recarregue a página e tente novamente.');
            resetButton();
            return;
        }

        const formData = new FormData(e.target);

        fetch('/esqueci-senha', {
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
                    showStatus(data.message);
                    freshForm.reset();
                    resetButton();
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
                showAlert('Erro ao enviar o link. Tente novamente.');
                resetButton();
            });
    }

    freshForm.addEventListener('submit', handleSubmit, { once: false });
}
