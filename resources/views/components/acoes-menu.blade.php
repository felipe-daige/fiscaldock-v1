@props([
    'label' => 'Ações',
    'align' => 'right',
    'trigger' => 'default', // default = botão "Ações ▾" | kebab = ícone ⋮ (tabelas densas)
])

<div class="inline-block" data-acoes-menu data-acoes-align="{{ $align }}">
    @if ($trigger === 'kebab')
        <button type="button" data-acoes-trigger aria-haspopup="menu" aria-expanded="false" aria-label="{{ $label }}"
            class="inline-flex items-center justify-center rounded p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
            </svg>
        </button>
    @else
        <button type="button" data-acoes-trigger aria-haspopup="menu" aria-expanded="false"
            class="inline-flex items-center gap-1 rounded border border-gray-300 px-2.5 py-1.5 text-[13px] font-medium text-gray-700 hover:bg-gray-50">
            {{ $label }}
            <svg class="h-3.5 w-3.5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.085l3.71-3.855a.75.75 0 111.08 1.04l-4.25 4.41a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
            </svg>
        </button>
    @endif

    <div data-acoes-panel role="menu" hidden
        class="min-w-[170px] overflow-hidden rounded-md border border-gray-200 bg-white py-1 shadow-lg">
        {{ $slot }}
    </div>
</div>

@once
    <!-- acoes-menu-init-block : sai 1x por request (varios menus compartilham) -->
    <style>
        [data-acoes-panel] { position: fixed; z-index: 60; }
    </style>
    <script>
    (function () {
        if (window._acoesMenuInit) return;
        window._acoesMenuInit = true;

        var aberto = null; // painel atualmente aberto

        function fechar() {
            if (!aberto) return;
            aberto.hidden = true;
            var trg = aberto.parentElement.querySelector('[data-acoes-trigger]');
            if (trg) trg.setAttribute('aria-expanded', 'false');
            aberto = null;
        }

        function posicionar(painel, trigger) {
            var r = trigger.getBoundingClientRect();
            painel.hidden = false; // precisa estar visível p/ medir
            var pw = painel.offsetWidth;
            var ph = painel.offsetHeight;
            var align = (painel.closest('[data-acoes-menu]') || {}).dataset
                ? painel.closest('[data-acoes-menu]').dataset.acoesAlign : 'right';

            var left = align === 'left' ? r.left : (r.right - pw);
            // não deixar sair da viewport
            left = Math.max(8, Math.min(left, window.innerWidth - pw - 8));

            var top = r.bottom + 4;
            if (top + ph > window.innerHeight - 8) {
                top = Math.max(8, r.top - ph - 4); // abre p/ cima se não couber
            }
            painel.style.left = left + 'px';
            painel.style.top = top + 'px';
        }

        function abrir(trigger) {
            var menu = trigger.closest('[data-acoes-menu]');
            var painel = menu.querySelector('[data-acoes-panel]');
            if (aberto === painel) { fechar(); return; }
            fechar();
            posicionar(painel, trigger);
            trigger.setAttribute('aria-expanded', 'true');
            aberto = painel;
        }

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-acoes-trigger]');
            if (trigger) {
                e.preventDefault();
                abrir(trigger);
                return;
            }
            // clique dentro do painel (num item) → deixa navegar e fecha
            if (e.target.closest('[data-acoes-panel]')) {
                setTimeout(fechar, 0);
                return;
            }
            fechar(); // clique fora
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') fechar();
        });

        // painel é position:fixed → reposiciona/fecha em scroll e resize
        window.addEventListener('scroll', fechar, true);
        window.addEventListener('resize', fechar);
    })();
    </script>
@endonce
