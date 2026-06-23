<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>@yield('titulo', 'Relatório FiscalDock')</title>
    <style>
        @page { margin: 92px 32px 52px 32px; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:"DejaVu Sans", sans-serif; font-size:9px; color:#111827; line-height:1.4; }
        .pdf-conteudo { position:relative; z-index:2; }

        /* Componente Seção: barra slate + corpo aberto (sem caixa) */
        .secao { margin-bottom:14px; }
        .secao-header {
            background:#1f2937; color:#fff;
            padding:5px 8px; font-size:9px; font-weight:bold;
            text-transform:uppercase; letter-spacing:.1em;
        }
        .secao-header .meta { float:right; font-weight:normal; color:#cbd5e1; letter-spacing:.04em; text-transform:none; }
        .secao-body { padding:8px 2px; }

        .badge { color:#fff; padding:1px 6px; border-radius:3px; font-size:8px; font-weight:bold; text-transform:uppercase; }
        .mono { font-family:"DejaVu Sans Mono", monospace; }
        table { border-collapse:collapse; width:100%; }
    </style>
    @stack('estilos')
</head>
<body>
    @include('reports.partials._header')
    @include('reports.partials._footer')
    <main class="pdf-conteudo">
        @yield('conteudo')
    </main>
</body>
</html>
