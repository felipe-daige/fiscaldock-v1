// Sistema de Toast Global
function showToast(message, type = 'info', duration = 5000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    const toastId = 'toast-' + Date.now();
    
    // Cores baseadas no tipo
    const colors = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-black',
        info: 'bg-blue-500 text-white'
    };

    // Ícones baseados no tipo
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    toast.id = toastId;
    toast.className = `${colors[type]} px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 transform translate-x-full transition-all duration-300 ease-in-out`;
    
    toast.innerHTML = `
        <span class="text-lg font-bold">${icons[type]}</span>
        <span class="flex-1">${message}</span>
        <button onclick="closeToast('${toastId}')" class="text-white hover:text-gray-200 text-xl font-bold">&times;</button>
    `;

    container.appendChild(toast);

    // Animar entrada
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);

    // Auto remover
    if (duration > 0) {
        setTimeout(() => {
            closeToast(toastId);
        }, duration);
    }
}

function closeToast(toastId) {
    const toast = document.getElementById(toastId);
    if (toast) {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}

// Função para limpar todos os toasts
function clearAllToasts() {
    const container = document.getElementById('toast-container');
    if (container) {
        container.innerHTML = '';
    }
}
