{{-- Panorama fiscal do CNPJ (cliente OU participante) no acervo EFD, em estética de
     documento fiscal (DANFE/NFe). Shape único de ParticipanteFiscalResumoService /
     ClienteFiscalResumoService, ou null. Param opcional $cabecalho (razao/documento/uf). --}}
@php($fiscal = $fiscal ?? null)
@php($cabecalho = $cabecalho ?? [])
@php($papelHex = ['fornecedor' => '#2563eb', 'cliente' => '#0f766e', 'ambos' => '#7c3aed'])
@php($papelLabel = ['fornecedor' => 'Fornecedor', 'cliente' => 'Cliente', 'ambos' => 'Fornecedor e cliente'])

<div class="mt-3 rounded-lg border border-slate-300 bg-white overflow-hidden shadow-sm">
    {{-- Cabeçalho estilo documento fiscal --}}
    <div class="flex items-start justify-between gap-3 px-3.5 py-2.5 border-b border-slate-200 bg-slate-50">
        <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Relacionamento &amp; Movimentação Fiscal</p>
            @if(!empty($cabecalho['razao']))
                <p class="text-sm font-bold text-slate-800 truncate" title="{{ $cabecalho['razao'] }}">{{ $cabecalho['razao'] }}</p>
            @endif
            @if(!empty($cabecalho['documento']) || !empty($cabecalho['uf']))
                <p class="text-xs font-mono text-slate-500">
                    {{ $cabecalho['documento'] ?? '' }}@if(!empty($cabecalho['uf'])) · {{ $cabecalho['uf'] }}@endif
                </p>
            @endif
        </div>
        <div class="flex flex-col items-end gap-1 shrink-0">
            @if(!empty($fiscal) && !empty($fiscal['papel']))
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                      style="background-color: {{ $papelHex[$fiscal['papel']] ?? '#374151' }}">{{ $papelLabel[$fiscal['papel']] ?? '—' }}</span>
            @elseif(!empty($fiscal))
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white"
                      style="background-color: #475569">Acervo próprio</span>
            @endif
            @if(!empty($fiscal['primeira_nota']) && !empty($fiscal['ultima_nota']))
                <span class="text-[11px] text-slate-500 font-mono">
                    {{ \Carbon\Carbon::parse($fiscal['primeira_nota'])->format('m/Y') }} – {{ \Carbon\Carbon::parse($fiscal['ultima_nota'])->format('m/Y') }}
                </span>
            @endif
        </div>
    </div>

    @if(empty($fiscal))
        <div class="px-3.5 py-3">
            <p class="text-xs text-slate-500">Sem movimentação no acervo fiscal (EFD) deste CNPJ.</p>
        </div>
    @else
        @php($pfMax = max((float) $fiscal['total_comprado'], (float) $fiscal['total_vendido'], 0.01))
        {{-- Quadro de totais estilo formulário fiscal --}}
        <div class="grid grid-cols-2 divide-x divide-slate-200 border-b border-slate-200">
            <div class="px-3.5 py-2.5">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Entradas (comprado)</p>
                <p class="text-base font-bold text-slate-900 font-mono leading-tight">R$ {{ number_format($fiscal['total_comprado'], 2, ',', '.') }}</p>
                <div class="mt-1 h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full" style="width: {{ round(100 * (float) $fiscal['total_comprado'] / $pfMax) }}%; background-color: #2563eb"></div>
                </div>
                <p class="mt-1 text-[10px] text-slate-400">{{ $fiscal['qtd_entrada'] }} nota(s)</p>
            </div>
            <div class="px-3.5 py-2.5">
                <p class="text-[10px] uppercase tracking-wide text-slate-400">Saídas (vendido)</p>
                <p class="text-base font-bold text-slate-900 font-mono leading-tight">R$ {{ number_format($fiscal['total_vendido'], 2, ',', '.') }}</p>
                <div class="mt-1 h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full" style="width: {{ round(100 * (float) $fiscal['total_vendido'] / $pfMax) }}%; background-color: #0f766e"></div>
                </div>
                <p class="mt-1 text-[10px] text-slate-400">{{ $fiscal['qtd_saida'] }} nota(s)</p>
            </div>
        </div>

            @if(!empty($fiscal['credito_reforma']))
                @php($cr = $fiscal['credito_reforma'])
                @php($crHex = ['verde' => '#047857', 'amarelo' => '#b45309', 'vermelho' => '#b91c1c', 'cinza' => '#6b7280'])
                <div class="rounded border border-gray-100 bg-white px-2.5 py-2" data-credito-reforma>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide mb-1.5">Crédito tributário (IBS/CBS · pleno)</p>

                    @if(!empty($cr['fornecedor']))
                        @php($f = $cr['fornecedor'])
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-block w-2 h-2 rounded-full" style="background-color: {{ $crHex[$f['flag']] ?? '#6b7280' }}"></span>
                            <span class="text-xs text-gray-700">
                                Fornecedor: {{ $f['gera_credito'] }}.
                                @if($f['credito_em_risco'] !== null)
                                    <strong class="font-mono">R$ {{ number_format($f['credito_em_risco'], 2, ',', '.') }}</strong> em risco
                                @else
                                    <span class="text-gray-500">Regime do fornecedor não consultado — rode a consulta pra estimar o crédito.</span>
                                @endif
                            </span>
                        </div>
                    @endif

                    @if(!empty($cr['cliente_b2b']))
                        @php($b = $cr['cliente_b2b'])
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-block w-2 h-2 rounded-full" style="background-color: {{ $crHex[$b['flag']] ?? '#6b7280' }}"></span>
                            <span class="text-xs text-gray-700">
                                Você transfere <strong class="font-mono">R$ {{ number_format($b['credito_transferido'], 2, ',', '.') }}</strong> de crédito a este comprador (B2B).
                            </span>
                        </div>
                    @endif

                    @php($crTemRisco = !empty($cr['fornecedor']) && $cr['fornecedor']['credito_em_risco'] !== null)
                    @php($crTemLegado = !empty($cr['legado']))
                    @if($crTemRisco || $crTemLegado)
                    <details class="mt-1">
                        <summary class="text-[11px] text-blue-600 cursor-pointer select-none">Como calculamos ▸</summary>
                        @if($crTemRisco)
                            <p class="text-[10px] text-gray-500 mt-1 font-mono">Potencial pleno: R$ {{ number_format($cr['fornecedor']['credito_potencial'], 2, ',', '.') }} · alíquota {{ number_format($cr['fornecedor']['aliquota'] * 100, 1, ',', '.') }}%</p>
                        @endif
                        @if($crTemLegado)
                            <p class="text-[10px] text-gray-500 mt-1">Regime atual: <strong class="font-mono">R$ {{ number_format($cr['legado']['destacado'], 2, ',', '.') }}</strong> destacado nas entradas (ICMS+PIS+COFINS+IPI).</p>
                        @endif
                        @include('autenticado.consulta.partials._credito-reforma-metodologia')
                    </details>
                    @endif
                </div>
            @endif

        <div class="px-3.5 py-3 space-y-3.5">
            @php($pfVisivel = (int) config('consultas.panorama_fiscal.visivel', 10))

            @if(!empty($fiscal['top_produtos']))
                @php($pfProds = collect($fiscal['top_produtos']))
                <div data-pf-list>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-[10px] text-slate-400 uppercase tracking-wide">Principais produtos negociados</p>
                        @include('autenticado.consulta.partials._panorama-seletor', ['count' => $pfProds->count(), 'default' => $pfVisivel])
                    </div>
                    <table class="w-full text-[11px] border-collapse">
                        <thead>
                            <tr class="text-slate-400 uppercase tracking-wide border-b border-slate-200">
                                <th class="text-left font-medium py-0.5 pr-2">Descrição</th>
                                <th class="text-right font-medium py-0.5 whitespace-nowrap">Valor</th>
                                <th class="text-right font-medium py-0.5 whitespace-nowrap pl-2">Qtd</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pfProds as $i => $p)
                                <tr data-pf-row @class(['hidden' => $i >= $pfVisivel, 'odd:bg-slate-50/60 hover:bg-slate-100/70'])>
                                    @include('autenticado.consulta.partials._panorama-produto-linha', ['p' => $p])
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(!empty($fiscal['relacionamentos']))
                @php($pfRels = collect($fiscal['relacionamentos']))
                <div data-pf-list>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-[10px] text-slate-400 uppercase tracking-wide">{{ $fiscal['relacionamentos_titulo'] ?? 'Por empresa' }}</p>
                        @include('autenticado.consulta.partials._panorama-seletor', ['count' => $pfRels->count(), 'default' => $pfVisivel])
                    </div>
                    <table class="w-full text-[11px] border-collapse">
                        <thead>
                            <tr class="text-slate-400 uppercase tracking-wide border-b border-slate-200">
                                <th class="text-left font-medium py-0.5 pr-2">Empresa</th>
                                <th class="text-left font-medium py-0.5 pr-2 whitespace-nowrap">Papel</th>
                                <th class="text-right font-medium py-0.5 whitespace-nowrap">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pfRels as $i => $rel)
                                <tr data-pf-row @class(['hidden' => $i >= $pfVisivel, 'odd:bg-slate-50/60 hover:bg-slate-100/70'])>
                                    @include('autenticado.consulta.partials._panorama-contraparte-linha', ['rel' => $rel, 'papelHex' => $papelHex, 'papelLabel' => $papelLabel])
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if(!empty($fiscal['top_cfops']))
                @php($pfCfops = collect($fiscal['top_cfops']))
                <div data-pf-list>
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-[10px] text-slate-400 uppercase tracking-wide">Principais CFOPs</p>
                        @include('autenticado.consulta.partials._panorama-seletor', ['count' => $pfCfops->count(), 'default' => $pfVisivel])
                    </div>
                    <table class="w-full text-[11px] border-collapse">
                        <thead>
                            <tr class="text-slate-400 uppercase tracking-wide border-b border-slate-200">
                                <th class="text-left font-medium py-0.5 pr-2 whitespace-nowrap">CFOP</th>
                                <th class="text-left font-medium py-0.5 pr-2">Descrição</th>
                                <th class="text-right font-medium py-0.5 whitespace-nowrap">Valor</th>
                                <th class="text-right font-medium py-0.5 whitespace-nowrap pl-2">Qtd</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pfCfops as $i => $c)
                                <tr data-pf-row @class(['hidden' => $i >= $pfVisivel, 'odd:bg-slate-50/60 hover:bg-slate-100/70'])>
                                    @include('autenticado.consulta.partials._panorama-cfop-linha', ['c' => $c])
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
