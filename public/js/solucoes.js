// Função específica para a página de soluções
window._solucoesSwiper = window._solucoesSwiper || null;
window._solucoesAccordionHandlers = window._solucoesAccordionHandlers || [];

function initSolucoes() {
    // Destruir instância Swiper anterior se existir
    if (_solucoesSwiper && typeof _solucoesSwiper.destroy === 'function') {
        try {
            _solucoesSwiper.destroy(true, true);
        } catch (error) {
            console.error('Erro ao destruir Swiper anterior:', error);
        }
        _solucoesSwiper = null;
    }
    
    // Verificar se o elemento existe antes de criar Swiper
    const swiperElement = document.querySelector('.solutions-swiper');
    if (!swiperElement) {
        return;
    }
    
    // Inicializar Swiper com scroll contínuo fluido profissional
    _solucoesSwiper = new Swiper('.solutions-swiper', {
        slidesPerView: 'auto',
        spaceBetween: 24,
        freeMode: true,
        freeModeMomentum: false,
        speed: 4000,
        autoplay: {
            delay: 0,
            disableOnInteraction: false,
            pauseOnMouseEnter: false,
            stopOnLastSlide: false,
        },
        loop: true,
        allowTouchMove: false,
        simulateTouch: false,
        grabCursor: false,
        breakpoints: {
            320: {
                slidesPerView: 1.2,
                spaceBetween: 16,
            },
            640: {
                slidesPerView: 2.2,
                spaceBetween: 20,
            },
            1024: {
                slidesPerView: 3.2,
                spaceBetween: 24,
            },
            1280: {
                slidesPerView: 4.2,
                spaceBetween: 24,
            }
        }
    });
    
    // Registrar Swiper no sistema de recursos
    if (window._spaResources) {
        window._spaResources.swipers.push(_solucoesSwiper);
    }
    
    // Inicializar accordion de soluções
    initSolucoesAccordion();
}

// Função para inicializar accordion de soluções
function initSolucoesAccordion() {
    const accordionItems = document.querySelectorAll('.solution-accordion-item');
    
    if (accordionItems.length === 0) return;
    
    // Remover listeners antigos se existirem
    _solucoesAccordionHandlers.forEach(({ element, handler }) => {
        if (element && handler) {
            element.removeEventListener('click', handler);
        }
    });
    _solucoesAccordionHandlers = [];
    
    accordionItems.forEach((item) => {
        const header = item.querySelector('.solution-accordion-header');
        const content = item.querySelector('.solution-accordion-content');
        const svg = header?.querySelector('svg');
        
        if (!header || !content) return;
        
        // Criar handler para este item
        const handler = function(e) {
            e.preventDefault();
            
            const isActive = item.classList.contains('active');
            const contentDiv = content.querySelector('div');
            
            // Toggle atual
            if (isActive) {
                item.classList.remove('active');
                content.style.maxHeight = '0';
                content.style.opacity = '0';
                if (svg) {
                    svg.style.transform = 'rotate(0deg)';
                }
            } else {
                item.classList.add('active');
                if (contentDiv) {
                    content.style.maxHeight = contentDiv.scrollHeight + 'px';
                } else {
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
                content.style.opacity = '1';
                if (svg) {
                    svg.style.transform = 'rotate(180deg)';
                }
            }
        };
        
        header.addEventListener('click', handler);
        _solucoesAccordionHandlers.push({ element: header, handler });
        
        // Estado inicial - fechado
        content.style.maxHeight = '0';
        content.style.opacity = '0';
        content.style.transition = 'max-height 0.3s ease, opacity 0.3s ease';
        if (svg) {
            svg.style.transition = 'transform 0.3s ease';
        }
    });
    
    // Expandir accordion se houver hash no URL
    const hash = window.location.hash;
    if (hash) {
        const targetId = hash.substring(1); // Remove o #
        const targetItem = document.getElementById(targetId);
        if (targetItem && targetItem.classList.contains('solution-accordion-item')) {
            const header = targetItem.querySelector('.solution-accordion-header');
            if (header) {
                // Simular clique após um pequeno delay para garantir que o DOM está pronto
                setTimeout(() => {
                    header.click();
                    // Scroll suave até o elemento
                    targetItem.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        }
    }
}

// Função de limpeza para recursos da página de soluções
function cleanupSolucoes() {
    if (_solucoesSwiper && typeof _solucoesSwiper.destroy === 'function') {
        try {
            _solucoesSwiper.destroy(true, true);
        } catch (error) {
            console.error('Erro ao destruir Swiper:', error);
        }
        _solucoesSwiper = null;
    }
    
    // Limpar handlers do accordion
    _solucoesAccordionHandlers.forEach(({ element, handler }) => {
        if (element && handler) {
            element.removeEventListener('click', handler);
        }
    });
    _solucoesAccordionHandlers = [];
}

// Registrar função de cleanup no sistema global
if (!window._cleanupFunctions) {
    window._cleanupFunctions = {};
}
window._cleanupFunctions.initSolucoes = cleanupSolucoes;
