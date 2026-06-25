<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>@yield('titulo', 'Relatório FiscalDock')</title>
    <style>
        @page { margin: 88px 32px 52px 32px; }
        /* dompdf 3.x: resetar margin/padding em `*` OU em html/body ZERA a margem do @page
           (conteúdo full-bleed colide com o header fixo). Resetar só tags internas — nunca html/body. */
        * { box-sizing:border-box; }
        table, tr, td, th, div, p, h1, h2, h3, h4, ul, ol, li { margin:0; padding:0; }
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

        .badge { color:#fff; padding:1px 6px; border-radius:3px; font-size:8px; font-weight:bold; text-transform:uppercase; white-space:nowrap; }
        .mono { font-family:"DejaVu Sans Mono", monospace; }
        table { border-collapse:collapse; width:100%; }

        /* ── Helpers de conteúdo PADRÃO (compartilhados por todos os PDFs) ── */
        .muted { color:#6b7280; }
        .small { font-size:7px; }
        .right { text-align:right; }
        .center { text-align:center; }
        /* Tabela padrão: th claro com régua slate, td hairline, zebra leve */
        .table th { background:#f9fafb; border-bottom:1.5px solid #1f2937; padding:6px 5px; text-align:left; font-size:7.5px; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
        .table th.right { text-align:right; }
        .table th.center { text-align:center; }
        .table td { border-bottom:1px solid #f3f4f6; padding:5px; vertical-align:top; font-size:8px; color:#374151; }
        .table tbody tr:nth-child(even) td { background:#fbfbfc; }
        /* Card padrão: hairline + faixa-topo slate (sem caixa pesada) */
        .card-slate { border:1px solid #e5e7eb; border-top:2px solid #1f2937; padding:8px 10px; }
    </style>
    @stack('estilos')
</head>
<body>
    @include('reports.partials._header')
    @include('reports.partials._footer')
    @hasSection('sem_marca_dagua')
    @else
        @include('reports.partials._marca-dagua')
    @endif
    <main class="pdf-conteudo">
        @yield('conteudo')
    </main>
</body>
</html>
