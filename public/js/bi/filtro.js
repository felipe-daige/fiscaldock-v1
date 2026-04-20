// public/js/bi/filtro.js
(function () {
    'use strict';

    function init() {
        var select = document.getElementById('bi-periodo-select');
        var camposDatas = document.getElementById('bi-datas-personalizadas');
        var form = document.getElementById('bi-filtro-form');

        if (!select || !camposDatas || !form) return;

        function toggleDatas() {
            if (select.value === 'personalizado') {
                camposDatas.style.display = 'flex';
            } else {
                camposDatas.style.display = 'none';
            }
        }

        select.addEventListener('change', toggleDatas);
        toggleDatas(); // estado inicial

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var params = new URLSearchParams();
            params.set('periodo', select.value);

            if (select.value === 'personalizado') {
                var inicio = document.getElementById('bi-data-inicio').value;
                var fim = document.getElementById('bi-data-fim').value;
                if (inicio) params.set('data_inicio', inicio);
                if (fim) params.set('data_fim', fim);
            }

            window.location.href = '/app/bi/dashboard?' + params.toString();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
