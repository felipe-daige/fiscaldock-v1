{{-- Principais produtos + CFOPs detalhados do participante (reusa o _lista genérico do lote).
     Espera $top_produtos, $top_cfops. --}}
@include('reports.consulta-lote._lista', [
    'titulo' => 'Principais produtos',
    'cabecalho' => ['Descrição', 'Valor', 'Qtd'], 'aligns' => ['left', 'right', 'right'],
    'linhas' => collect($top_produtos ?? [])->take(10)->map(fn ($p) => [
        $p['descricao'] ?? $p['cod_item'] ?? '—',
        'R$ '.number_format((float) ($p['valor'] ?? 0), 2, ',', '.'),
        (string) (int) ($p['qtd'] ?? 0),
    ])->all(),
    'vazio' => 'Sem produtos no acervo.',
])

@include('reports.consulta-lote._lista', [
    'titulo' => 'CFOPs (detalhado)',
    'cabecalho' => ['CFOP', 'Descrição', 'Valor', 'Qtd'], 'aligns' => ['left', 'left', 'right', 'right'],
    'linhas' => collect($top_cfops ?? [])->take(10)->map(fn ($c) => [
        (string) ($c['cfop'] ?? '—'),
        $c['descricao'] ?? '',
        'R$ '.number_format((float) ($c['valor'] ?? 0), 2, ',', '.'),
        (string) (int) ($c['qtd'] ?? 0),
    ])->all(),
    'vazio' => 'Sem CFOPs no acervo.',
])
