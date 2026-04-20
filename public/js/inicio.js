// Função específica para a página de início
// Usar propriedades em window para permitir múltiplas execuções do script sem erro de redeclaração
window._inicioInitialized = window._inicioInitialized || false;
window._countdownInterval = window._countdownInterval || null;
window._officialSourcesResizeHandler = window._officialSourcesResizeHandler || null;
window._officialSourcesRaf = window._officialSourcesRaf || null;

function initInicio() {
    // Limpar recursos anteriores se já foi inicializado
    if (window._inicioInitialized) {
        cleanupInicio();
    }
    
    // Countdown Timer - Atualizado para nova estrutura
    function initCountdown() {
        const daysElement = document.getElementById('days');
        const hoursElement = document.getElementById('hours');
        const minutesElement = document.getElementById('minutes');
        const secondsElement = document.getElementById('seconds');
        
        if (!daysElement || !hoursElement || !minutesElement || !secondsElement) {
            return; // Se não existir, não inicializa
        }

        const targetDate = new Date('2026-01-01T00:00:00').getTime();
        
        function updateCountdown() {
            const now = Date.now();
            const distance = targetDate - now;

            if (distance < 0) {
                daysElement.textContent = '0';
                hoursElement.textContent = '00';
                minutesElement.textContent = '00';
                if (secondsElement) secondsElement.textContent = '00';
                return;
            }

            const days = Math.floor(distance / 86400000);
            const hours = Math.floor((distance % 86400000) / 3600000);
            const minutes = Math.floor((distance % 3600000) / 60000);
            const seconds = Math.floor((distance % 60000) / 1000);

            // Dias sem zeros à esquerda quando forem 1 ou 2 dígitos (ex.: 7, 63). Para 100+, mantém natural (ex.: 124).
            daysElement.textContent = days.toString();
            hoursElement.textContent = hours.toString().padStart(2, '0');
            minutesElement.textContent = minutes.toString().padStart(2, '0');
            if (secondsElement) secondsElement.textContent = seconds.toString().padStart(2, '0');
        }

        updateCountdown();
        window._countdownInterval = setInterval(updateCountdown, 1000);
        
        // Registrar intervalo no sistema de recursos
        if (window._spaResources) {
            window._spaResources.intervals.push(window._countdownInterval);
        }
    }

    initCountdown();

    function initOfficialSourcesMarquee() {
        const marquee = document.querySelector('.official-sources-marquee');
        const track = marquee?.querySelector('.official-sources-track');
        const sourceGroup = track?.querySelector('[data-official-sources-group]');

        if (!marquee || !track || !sourceGroup) {
            return;
        }

        track.querySelectorAll('[data-official-sources-clone="true"]').forEach((clone) => clone.remove());
        track.style.removeProperty('--official-sources-cycle-width');
        track.style.removeProperty('--official-sources-duration');

        // Measure the rendered group width after layout so the loop includes the seam spacing.
        const cycleWidth = Math.ceil(sourceGroup.getBoundingClientRect().width);
        const marqueeWidth = Math.ceil(marquee.getBoundingClientRect().width);

        if (!cycleWidth || !marqueeWidth) {
            return;
        }

        const minTrackWidth = marqueeWidth + (cycleWidth * 2);
        let currentWidth = cycleWidth;
        let cloneIndex = 0;

        while (currentWidth < minTrackWidth) {
            const clone = sourceGroup.cloneNode(true);
            clone.setAttribute('aria-hidden', 'true');
            clone.dataset.officialSourcesClone = 'true';
            cloneIndex += 1;
            clone.dataset.officialSourcesCloneIndex = String(cloneIndex);
            track.appendChild(clone);
            currentWidth += cycleWidth;
        }

        const pixelsPerSecond = 72;
        const duration = cycleWidth / pixelsPerSecond;

        track.style.setProperty('--official-sources-cycle-width', `${cycleWidth}px`);
        track.style.setProperty('--official-sources-duration', `${duration}s`);
    }

    function scheduleOfficialSourcesMarquee() {
        if (window._officialSourcesRaf) {
            cancelAnimationFrame(window._officialSourcesRaf);
        }

        window._officialSourcesRaf = requestAnimationFrame(() => {
            initOfficialSourcesMarquee();
            window._officialSourcesRaf = null;
        });
    }

    scheduleOfficialSourcesMarquee();

    if (document.fonts && typeof document.fonts.ready?.then === 'function') {
        document.fonts.ready.then(() => {
            scheduleOfficialSourcesMarquee();
        });
    }

    window._officialSourcesResizeHandler = function() {
        scheduleOfficialSourcesMarquee();
    };

    window.addEventListener('resize', window._officialSourcesResizeHandler);

    // Inicializar FAQ se a função existir
    if (typeof initFaq === 'function') {
        initFaq();
    }

    // Contact Form
    const contactForm = document.getElementById('contact-form');
    if (contactForm) {
        // Remover listener antigo se existir
        if (window._contactFormHandler) {
            contactForm.removeEventListener('submit', window._contactFormHandler);
        }
        
        window._contactFormHandler = function(e) {
            e.preventDefault();
            alert('Mensagem enviada com sucesso! Entraremos em contato em breve.');
            this.reset();
        };
        
        contactForm.addEventListener('submit', window._contactFormHandler);
    }
    
    // Scroll Indicator
    window.scrollToSolucoes = function() {
        const funcionalidades = document.getElementById('funcionalidades');
        if (funcionalidades) {
            funcionalidades.scrollIntoView({behavior: 'smooth'});
        }
    };

    window._inicioInitialized = true;
}

// Função de limpeza para recursos da página de início
function cleanupInicio() {
    // Limpar intervalos
    if (window._countdownInterval) {
        clearInterval(window._countdownInterval);
        window._countdownInterval = null;
    }

    if (window._officialSourcesRaf) {
        cancelAnimationFrame(window._officialSourcesRaf);
        window._officialSourcesRaf = null;
    }

    if (window._officialSourcesResizeHandler) {
        window.removeEventListener('resize', window._officialSourcesResizeHandler);
        window._officialSourcesResizeHandler = null;
    }

    // Remover handler do formulário
    if (window._contactFormHandler) {
        const contactForm = document.getElementById('contact-form');
        if (contactForm) {
            contactForm.removeEventListener('submit', window._contactFormHandler);
        }
        window._contactFormHandler = null;
    }

    document
        .querySelectorAll('.official-sources-track [data-official-sources-clone="true"]')
        .forEach((clone) => clone.remove());
    
    window._inicioInitialized = false;
}

// Registrar função de cleanup no sistema global
if (!window._cleanupFunctions) {
    window._cleanupFunctions = {};
}
window._cleanupFunctions.initInicio = cleanupInicio;

// Inicialização é feita pelo spa.js
