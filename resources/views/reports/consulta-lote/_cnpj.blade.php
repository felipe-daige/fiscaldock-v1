{{-- Uma página de CNPJ no dossiê. Espera $d (item de getDetalhes). --}}
@php($fiscal = $d['fiscal_resumo'] ?? null)
<div @if(!$loop->first) style="page-break-before: always;" @endif>
    {{-- Identificação --}}
    <div class="secao">
        <div class="secao-header">Identificação</div>
        <div class="secao-body">
            <table class="lote-kv">
                <tr><td class="k">CNPJ</td><td class="v">{{ $d['documento'] ?: '—' }}</td><td class="k">UF</td><td class="v">{{ $d['uf'] ?: '—' }}</td></tr>
                <tr><td class="k">Razão social</td><td class="v" colspan="3">{{ $d['razao_social'] ?: '—' }}</td></tr>
                <tr><td class="k">Situação</td><td class="v">{{ $d['situacao_cadastral'] ?? '—' }}</td><td class="k">Regime</td><td class="v">{{ $d['regime_tributario'] ?? '—' }}</td></tr>
            </table>
        </div>
    </div>

    @if($d['status_consulta'] !== 'sucesso')
        <div class="secao"><div class="secao-body"><div class="msg">Consulta não concluída: {{ $d['error_message'] ?: 'sem detalhe disponível' }}.</div></div></div>
    @else
        {{-- Certidões & comprovantes --}}
        @if(!empty($d['blocos']))
            <div class="secao">
                <div class="secao-header">Certidões &amp; comprovantes</div>
                <div class="secao-body">
                    <table class="cards">
                        @foreach(array_chunk($d['blocos'], 2) as $par)
                            <tr>
                                @foreach($par as $bloco)
                                    <td>
                                        <div class="card">
                                            <div class="card-head">
                                                <table style="width: 100%;">
                                                    <tr>
                                                        <td class="card-title">{{ $bloco['titulo'] }}</td>
                                                        <td style="text-align: right;">
                                                            @if(!empty($bloco['badge']))
                                                                <span class="badge" style="background-color: {{ $bloco['badge']['hex'] }}">{{ $bloco['badge']['label'] }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="card-body">
                                                @if(!empty($bloco['itens']))
                                                    <table class="kv">
                                                        @foreach($bloco['itens'] as $item)
                                                            <tr>
                                                                <td class="k">{{ $item['label'] }}</td>
                                                                <td class="v">{{ $item['valor'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </table>
                                                @endif

                                                @foreach(($bloco['listas'] ?? []) as $lista)
                                                    <div class="list-title">{{ $lista['titulo'] }}</div>
                                                    @foreach($lista['linhas'] as $linha)
                                                        <div class="list-item">• {{ $linha }}</div>
                                                    @endforeach
                                                @endforeach

                                                @if(!empty($bloco['mensagem']))
                                                    <div class="msg">{{ $bloco['mensagem'] }}</div>
                                                @endif

                                                @if(!empty($bloco['comprovante_url']))
                                                    <div class="comprovante">
                                                        <a href="{{ $bloco['comprovante_url'] }}">Baixar certidão / comprovante (PDF)</a>
                                                        <div class="url">{{ $bloco['comprovante_url'] }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                @endforeach
                                @if(count($par) === 1)
                                    <td></td>
                                @endif
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endif

        {{-- Parecer --}}
        @if(!empty($d['resumo']))
            <div class="secao"><div class="secao-header">Parecer fiscal</div><div class="secao-body"><div class="list-item">{{ $d['resumo'] }}</div></div></div>
        @endif

        {{-- Panorama fiscal --}}
        @if(empty($fiscal))
            <div class="secao"><div class="secao-body"><div class="msg">Sem movimentação no acervo fiscal (EFD) deste CNPJ.</div></div></div>
        @else
            @include('reports.consulta-lote._movimentacao', ['fiscal' => $fiscal])

            @if(!empty($fiscal['credito_reforma']))
                @include('reports.consulta-lote._credito-reforma', ['cr' => $fiscal['credito_reforma'], 'base' => (float) ($fiscal['total_comprado'] ?? 0)])
            @endif

            @include('reports.consulta-lote._lista', [
                'titulo' => 'Top produtos',
                'cabecalho' => ['Descrição', 'Valor', 'Qtd'], 'aligns' => ['left', 'right', 'right'],
                'linhas' => collect($fiscal['top_produtos'] ?? [])->take(10)->map(fn ($p) => [
                    $p['descricao'] ?? $p['cod_item'] ?? '—',
                    'R$ '.number_format((float) ($p['valor'] ?? 0), 2, ',', '.'),
                    (string) (int) ($p['qtd'] ?? 0),
                ])->all(),
                'vazio' => 'Sem produtos no acervo.',
            ])

            @include('reports.consulta-lote._lista', [
                'titulo' => 'Top CFOPs',
                'cabecalho' => ['CFOP', 'Descrição', 'Valor', 'Qtd'], 'aligns' => ['left', 'left', 'right', 'right'],
                'linhas' => collect($fiscal['top_cfops'] ?? [])->take(10)->map(fn ($c) => [
                    (string) ($c['cfop'] ?? '—'),
                    $c['descricao'] ?? '',
                    'R$ '.number_format((float) ($c['valor'] ?? 0), 2, ',', '.'),
                    (string) (int) ($c['qtd'] ?? 0),
                ])->all(),
                'vazio' => 'Sem CFOPs no acervo.',
            ])

            @include('reports.consulta-lote._lista', [
                'titulo' => 'Top notas — entradas',
                'cabecalho' => ['Nota', 'CFOP', 'Data', 'Valor'], 'aligns' => ['left', 'left', 'left', 'right'],
                'linhas' => collect($fiscal['top_notas_entrada'] ?? [])->take(10)->map(fn ($n) => [
                    ($n['numero'] ?? '—').(!empty($n['serie']) ? '/'.$n['serie'] : ''),
                    (string) ($n['cfop'] ?? '—'),
                    $n['data'] ? \Carbon\Carbon::parse($n['data'])->format('d/m/Y') : '—',
                    'R$ '.number_format((float) ($n['valor'] ?? 0), 2, ',', '.'),
                ])->all(),
                'vazio' => 'Sem entradas.',
            ])

            @include('reports.consulta-lote._lista', [
                'titulo' => 'Top notas — saídas',
                'cabecalho' => ['Nota', 'CFOP', 'Data', 'Valor'], 'aligns' => ['left', 'left', 'left', 'right'],
                'linhas' => collect($fiscal['top_notas_saida'] ?? [])->take(10)->map(fn ($n) => [
                    ($n['numero'] ?? '—').(!empty($n['serie']) ? '/'.$n['serie'] : ''),
                    (string) ($n['cfop'] ?? '—'),
                    $n['data'] ? \Carbon\Carbon::parse($n['data'])->format('d/m/Y') : '—',
                    'R$ '.number_format((float) ($n['valor'] ?? 0), 2, ',', '.'),
                ])->all(),
                'vazio' => 'Sem saídas.',
            ])

            @include('reports.consulta-lote._lista', [
                'titulo' => $fiscal['relacionamentos_titulo'] ?? 'Por empresa',
                'cabecalho' => ['Empresa', 'Papel', 'Valor'], 'aligns' => ['left', 'left', 'right'],
                'linhas' => collect($fiscal['relacionamentos'] ?? [])->take(10)->map(fn ($r) => [
                    $r['nome'] ?? $r['empresa_nome'] ?? '—',
                    ucfirst((string) ($r['papel'] ?? '—')),
                    'R$ '.number_format((float) ($r['valor_entrada'] ?? 0) + (float) ($r['valor_saida'] ?? 0), 2, ',', '.'),
                ])->all(),
                'vazio' => 'Sem contrapartes.',
            ])
        @endif
    @endif
</div>
