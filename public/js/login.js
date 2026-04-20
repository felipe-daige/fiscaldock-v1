// Função específica para a página de login
function initLogin() {
    const loginForm = document.getElementById('login-form');
    
    if (loginForm) {
        // Remover listeners antigos se existirem
        const newForm = loginForm.cloneNode(true);
        loginForm.parentNode.replaceChild(newForm, loginForm);
        
        // Agora pegar a referência do novo formulário
        const freshForm = document.getElementById('login-form');
        const submitBtn = document.getElementById('login-submit-btn');
        
        if (!freshForm || !submitBtn) return;
        
        // Guardar o HTML original do botão
        const originalButtonHTML = submitBtn.innerHTML;
        
        // Função para resetar o botão ao estado original
        function resetButton() {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalButtonHTML;
                submitBtn.blur(); // Remove o foco para evitar estado visual bugado
            }
        }
        
        // Função interna para o handler do login
        function handleLogin(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (!submitBtn) return;
            
            // Verificar se já está processando
            if (submitBtn.disabled) {
                return;
            }
            
            // Desabilitar botão durante o envio
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Entrando...</span>';
            
            // 🔥 BUSCAR TOKEN CSRF MAIS RECENTE A CADA TENTATIVA
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            if (!token) {
                showToast('Erro: Token CSRF não encontrado. Recarregue a página.', 'error');
                resetButton();
                return;
            }
            
            // Coletar dados do formulário
            const formData = new FormData(e.target);
            
            // Enviar para o Laravel
            fetch('/login', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token
                }
            })
            .then(response => {
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('Login realizado com sucesso!', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                    resetButton();
                }
            })
            .catch(error => {
                console.error('Erro completo:', error);
                showToast('Erro ao fazer login. Tente novamente.', 'error');
                resetButton();
            });
        }
        
        // Adicionar apenas UM event listener
        freshForm.addEventListener('submit', handleLogin, { once: false });
        
        // Prevenir múltiplos cliques no botão diretamente
        submitBtn.addEventListener('click', function(e) {
            if (this.disabled) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    }
}