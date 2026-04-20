// Dúvidas Accordion + Filtro por Categoria
let _duvidasHandlers = [];

function initDuvidas() {
    const duvidasItems = document.querySelectorAll('.duvidas-item');

    if (duvidasItems.length === 0) return;

    // Remover listeners antigos se existirem
    _duvidasHandlers.forEach(({ element, handler }) => {
        if (element && handler) {
            element.removeEventListener('click', handler);
        }
    });
    _duvidasHandlers = [];

    // ── Accordion ──

    duvidasItems.forEach((item) => {
        const question = item.querySelector('.duvidas-question');
        const answer = item.querySelector('.duvidas-answer');

        if (!question || !answer) return;

        const handler = function(e) {
            e.preventDefault();

            const isActive = item.classList.contains('active');
            const svg = question.querySelector('svg');

            // Fecha todos os outros
            duvidasItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                    const otherAnswer = otherItem.querySelector('.duvidas-answer');
                    const otherSvg = otherItem.querySelector('.duvidas-question svg');
                    if (otherAnswer) {
                        otherAnswer.style.display = 'none';
                        otherAnswer.style.maxHeight = '0';
                        otherAnswer.style.opacity = '0';
                    }
                    if (otherSvg) {
                        otherSvg.style.transform = 'rotate(0deg)';
                    }
                }
            });

            // Toggle atual
            if (isActive) {
                item.classList.remove('active');
                answer.style.display = 'none';
                answer.style.maxHeight = '0';
                answer.style.opacity = '0';
                if (svg) svg.style.transform = 'rotate(0deg)';
            } else {
                item.classList.add('active');
                answer.style.display = 'block';
                answer.style.maxHeight = answer.scrollHeight + 'px';
                answer.style.opacity = '1';
                if (svg) svg.style.transform = 'rotate(180deg)';
            }
        };

        question.addEventListener('click', handler);
        _duvidasHandlers.push({ element: question, handler });

        // Estado inicial
        answer.style.display = 'none';
        answer.style.maxHeight = '0';
        answer.style.opacity = '0';
        answer.style.transition = 'max-height 0.3s ease, opacity 0.3s ease';
    });

    // ── Filtro por Categoria ──

    const categoryBtns = document.querySelectorAll('.duvidas-cat-btn');
    const categoryHeaders = document.querySelectorAll('.duvidas-category-header');

    if (categoryBtns.length === 0) return;

    function closeAllItems() {
        duvidasItems.forEach(item => {
            item.classList.remove('active');
            const answer = item.querySelector('.duvidas-answer');
            const svg = item.querySelector('.duvidas-question svg');
            if (answer) {
                answer.style.display = 'none';
                answer.style.maxHeight = '0';
                answer.style.opacity = '0';
            }
            if (svg) svg.style.transform = 'rotate(0deg)';
        });
    }

    function setActiveTab(btn) {
        categoryBtns.forEach(b => {
            b.style.backgroundColor = '';
            b.style.color = '';
            b.classList.add('text-gray-600');
            b.classList.remove('text-white');
        });
        btn.style.backgroundColor = '#1e4fa0';
        btn.style.color = 'white';
        btn.classList.remove('text-gray-600');
        btn.classList.add('text-white');
    }

    function filterByCategory(category) {
        closeAllItems();

        duvidasItems.forEach(item => {
            if (category === 'todos' || item.dataset.category === category) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });

        categoryHeaders.forEach(h => {
            h.style.display = (category === 'todos') ? '' : 'none';
        });
    }

    categoryBtns.forEach(btn => {
        const catHandler = function(e) {
            e.preventDefault();
            setActiveTab(btn);
            filterByCategory(btn.dataset.category);
        };

        btn.addEventListener('click', catHandler);
        _duvidasHandlers.push({ element: btn, handler: catHandler });
    });
}

// Função de limpeza para recursos da página Dúvidas
function cleanupDuvidas() {
    _duvidasHandlers.forEach(({ element, handler }) => {
        if (element && handler) {
            element.removeEventListener('click', handler);
        }
    });
    _duvidasHandlers = [];
}

// Registrar função de cleanup no sistema global
if (!window._cleanupFunctions) {
    window._cleanupFunctions = {};
}
window._cleanupFunctions.initDuvidas = cleanupDuvidas;
