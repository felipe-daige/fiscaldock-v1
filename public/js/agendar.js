// Função específica para a página de agendamento
function initAgendar() {
    const registerForm = document.getElementById('registerForm');

    if (!registerForm) {
        return;
    }

    const newForm = registerForm.cloneNode(true);
    registerForm.parentNode.replaceChild(newForm, registerForm);

    const freshForm = document.getElementById('registerForm');

    if (!freshForm) {
        return;
    }

    if (typeof $ !== 'undefined') {
        $(document).ready(function() {
            $('#cnpj').mask('00.000.000/0000-00');
            $('#telefone').mask('(00) 00000-0000');
        });
    } else {
        setTimeout(() => {
            if (typeof $ !== 'undefined') {
                $('#cnpj').mask('00.000.000/0000-00');
                $('#telefone').mask('(00) 00000-0000');
            }
        }, 200);
    }
    function handleSubmit(e) {
        e.preventDefault();

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!token) {
            showToast('Erro: Token CSRF não encontrado. Recarregue a página.', 'error');
            return;
        }

        const formData = new FormData(e.target);

        fetch('/agendar', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showToast(data.message, 'error');

                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1200);
                }
            }
        })
        .catch(error => {
            showToast('Este fluxo não está mais disponível. Use os canais de contato da página.', 'error');
        });
    }

    freshForm.addEventListener('submit', handleSubmit);
}
