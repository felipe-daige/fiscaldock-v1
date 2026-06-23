<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>@yield('titulo', 'Relatório FiscalDock')</title>
    <style>
        @page { margin: 96px 26px 56px 26px; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:"DejaVu Sans", sans-serif; font-size:9px; color:#111827; line-height:1.35; }
        .pdf-conteudo { position:relative; z-index:2; }
        .secao { border:1px solid #d1d5db; margin-bottom:10px; }
        .secao-header { background:#f9fafb; border-bottom:1px solid #e5e7eb; padding:6px 8px; font-size:9px; font-weight:bold; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
        .badge { color:#fff; padding:1px 6px; border-radius:3px; font-size:8px; font-weight:bold; text-transform:uppercase; }
        .mono { font-family:"DejaVu Sans Mono", monospace; }
        table { border-collapse:collapse; width:100%; }
    </style>
    @stack('estilos')
</head>
<body>
    @include('reports.partials._marca-dagua')
    @include('reports.partials._header')
    @include('reports.partials._footer')
    <main class="pdf-conteudo">
        @yield('conteudo')
    </main>
</body>
</html>
