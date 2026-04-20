// Função específica para a página de preços
let _precosHandlers = [];
let _precosInitialized = false;

function initPrecos() {
    // Limpar listeners anteriores se já foi inicializado
    if (_precosInitialized) {
        cleanupPrecos();
    }
    
    const periodButtons = document.querySelectorAll('.period-btn');
    
    if (periodButtons.length === 0) {
        return; // Se não existir, não inicializa
    }
    
    let currentPeriod = 'anual'; // Período padrão

    // Dados dos preços por período e plano
    const prices = {
        light: {
            anual: { price: 'R$ 159', savings: 'R$ 480', total: 'R$ 1.908/ano' },
            semestral: { price: 'R$ 189', savings: 'R$ 60', total: 'R$ 1.134/semestre' },
            mensal: { price: 'R$ 199', savings: null, total: null }
        },
        plus: {
            anual: { price: 'R$ 239', savings: 'R$ 720', total: 'R$ 2.868/ano' },
            semestral: { price: 'R$ 284', savings: 'R$ 90', total: 'R$ 1.704/semestre' },
            mensal: { price: 'R$ 299', savings: null, total: null }
        },
        premium: {
            anual: { price: 'R$ 367', savings: 'R$ 1.104', total: 'R$ 4.404/ano' },
            semestral: { price: 'R$ 436', savings: 'R$ 138', total: 'R$ 2.616/semestre' },
            mensal: { price: 'R$ 459', savings: null, total: null }
        }
    };

    // Função para atualizar preços baseado no período selecionado
    function updatePrices(period) {
        currentPeriod = period;

        // Atualizar botões de período
        periodButtons.forEach(btn => {
            if (btn.dataset.period === period) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Atualizar preços em cada card de plano
        Object.keys(prices).forEach(planName => {
            const planData = prices[planName][period];
            const planCard = document.querySelector(`[data-plan="${planName}"]`);
            
            if (!planCard) return;

            // Atualizar preço principal
            const priceElements = planCard.querySelectorAll('.plan-price');
            priceElements.forEach(el => {
                if (el.dataset.period === period) {
                    el.textContent = planData.price;
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            });

            // Atualizar informações de economia
            const savingsElements = planCard.querySelectorAll('.plan-savings');
            savingsElements.forEach(el => {
                if (el.dataset.period === period) {
                    if (planData.savings) {
                        el.innerHTML = `Pagamento único de ${planData.total}<br><span class="text-green-700">Você economiza ${planData.savings}</span>`;
                        el.classList.remove('hidden');
                        el.classList.add('text-green-600', 'font-semibold');
                        el.classList.remove('text-gray-500');
                    } else {
                        el.innerHTML = 'pagamento mensal<br>sem desconto';
                        el.classList.remove('hidden');
                        el.classList.add('text-gray-500');
                        el.classList.remove('text-green-600', 'font-semibold');
                    }
                } else {
                    el.classList.add('hidden');
                }
            });
        });
    }

    // Remover listeners antigos se existirem
    _precosHandlers.forEach(({ element, handler }) => {
        if (element && handler) {
            element.removeEventListener('click', handler);
        }
    });
    _precosHandlers = [];

    // Adicionar event listeners aos botões de período
    periodButtons.forEach(button => {
        const handler = function() {
            const period = this.dataset.period;
            updatePrices(period);
        };
        
        button.addEventListener('click', handler);
        _precosHandlers.push({ element: button, handler });
    });

    // Inicializar com período anual
    updatePrices('anual');
    
    _precosInitialized = true;
}

// Função de limpeza para recursos da página de preços
function cleanupPrecos() {
    _precosHandlers.forEach(({ element, handler }) => {
        if (element && handler) {
            element.removeEventListener('click', handler);
        }
    });
    _precosHandlers = [];
    _precosInitialized = false;
}

// Registrar função de cleanup no sistema global
if (!window._cleanupFunctions) {
    window._cleanupFunctions = {};
}
window._cleanupFunctions.initPrecos = cleanupPrecos;
