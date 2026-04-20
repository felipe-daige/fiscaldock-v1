<div id="resumo-fiscal-container" class="min-h-screen bg-gray-100">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

    <style>
        .rf-skeleton { background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%); background-size: 200% 100%; animation: rf-shimmer 1.5s infinite; }
        @keyframes rf-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .rf-section-content { transition: max-height 0.3s ease, opacity 0.2s ease; }
        .rf-section-content.collapsed { max-height: 0; opacity: 0; overflow: hidden; }
        .rf-chevron { transition: transform 0.2s ease; }
        .rf-chevron.rotated { transform: rotate(180deg); }
        .rf-nav { scrollbar-width: none; -ms-overflow-style: none; }
        .rf-nav::-webkit-scrollbar { display: none; }
        .rf-nav-link { color: #6b7280; background-color: transparent; }
        .rf-nav-link:hover { background-color: #f3f4f6; color: #111827; }
        .rf-nav-link.active { background-color: #1f2937 !important; color: #ffffff !important; }
    </style>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide">Painel Fiscal por Competência</h1>
        <p class="text-xs text-gray-500 mt-1">Apuração consolidada por período</p>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Filtros</span>
        </div>
        <div class="p-4 flex flex-col sm:flex-row items-start sm:items-end gap-3">
            <div class="flex-1 min-w-0 w-full sm:w-auto">
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Cliente</label>
                <select id="rf-cliente" class="w-full border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}" {{ $c->id == ($defaultClienteId ?? '') ? 'selected' : '' }}>
                            {{ $c->razao_social ?? $c->nome }}
                            @if($c->is_empresa_propria) (Própria) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-44">
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Competência</label>
                <select id="rf-competencia" class="w-full border border-gray-300 rounded text-sm focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                    @foreach($competencias as $comp)
                        <option value="{{ $comp }}" {{ $comp == ($defaultCompetencia ?? '') ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($comp . '-01')->translatedFormat('M/Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button id="rf-btn-filtrar" class="w-full sm:w-auto px-4 py-2 bg-gray-800 text-white rounded text-sm font-semibold hover:bg-gray-700 transition-colors">
                Carregar
            </button>
        </div>
    </div>

    {{-- Navegacao sticky --}}
    <nav class="rf-nav sticky top-0 z-20 bg-gray-100/95 backdrop-blur-sm py-2 mb-4 flex items-center gap-1 overflow-x-auto border-b border-gray-300" id="rf-nav">
        <a href="#secao-resumo" class="rf-nav-link active whitespace-nowrap px-3 py-1.5 rounded text-[11px] font-semibold uppercase tracking-wide transition-colors">Resumo</a>
        <a href="#secao-icms" class="rf-nav-link whitespace-nowrap px-3 py-1.5 rounded text-[11px] font-semibold uppercase tracking-wide transition-colors">ICMS/IPI</a>
        <a href="#secao-pis-cofins" class="rf-nav-link whitespace-nowrap px-3 py-1.5 rounded text-[11px] font-semibold uppercase tracking-wide transition-colors">PIS/COFINS</a>
        <a href="#secao-retencoes" class="rf-nav-link whitespace-nowrap px-3 py-1.5 rounded text-[11px] font-semibold uppercase tracking-wide transition-colors">Retenções</a>
        <a href="#secao-cruzamentos" class="rf-nav-link whitespace-nowrap px-3 py-1.5 rounded text-[11px] font-semibold uppercase tracking-wide transition-colors">Cruzamentos</a>
        <a href="#secao-alertas" class="rf-nav-link whitespace-nowrap px-3 py-1.5 rounded text-[11px] font-semibold uppercase tracking-wide transition-colors">Alertas</a>
    </nav>

    {{-- Estado vazio global --}}
    <div id="rf-empty-state" class="hidden text-center py-16">
        <svg class="mx-auto w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Sem dados para este período</h3>
        <p class="text-xs text-gray-400">Importe um arquivo EFD para ver o resumo fiscal desta competência.</p>
    </div>

    @php
        $secoes = [
            ['id' => 'resumo', 'label' => 'Resumo Executivo', 'hex' => '#4338ca', 'content_id' => 'rf-resumo-content'],
            ['id' => 'icms', 'label' => 'Apuração ICMS/IPI', 'hex' => '#047857', 'content_id' => 'rf-icms-content'],
            ['id' => 'pis-cofins', 'label' => 'Apuração PIS/COFINS', 'hex' => '#7c3aed', 'content_id' => 'rf-piscofins-content'],
            ['id' => 'retencoes', 'label' => 'Retenções na Fonte', 'hex' => '#d97706', 'content_id' => 'rf-retencoes-content'],
            ['id' => 'cruzamentos', 'label' => 'Cruzamentos e Divergências', 'hex' => '#b91c1c', 'content_id' => 'rf-cruzamentos-content'],
            ['id' => 'alertas', 'label' => 'Alertas Fiscais', 'hex' => '#b91c1c', 'content_id' => 'rf-alertas-content', 'badge' => true],
        ];
    @endphp

    @foreach($secoes as $sec)
    <section id="secao-{{ $sec['id'] }}" class="rf-section mb-6">
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between cursor-pointer select-none" data-toggle="{{ $sec['id'] }}">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 rounded" style="background-color: {{ $sec['hex'] }}"></span>
                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">{{ $sec['label'] }}</span>
                    @if($sec['badge'] ?? false)
                        <span id="rf-alertas-badge" class="hidden ml-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #b91c1c">0</span>
                    @endif
                </div>
                <svg class="rf-chevron w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
            <div class="rf-section-content p-4" data-section="{{ $sec['id'] }}">
                <div id="{{ $sec['content_id'] }}">
                    @if($sec['id'] === 'resumo')
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                            @for($i = 0; $i < 5; $i++)
                            <div class="bg-white rounded border border-gray-300 p-4">
                                <div class="rf-skeleton h-3 w-20 rounded mb-3"></div>
                                <div class="rf-skeleton h-7 w-28 rounded mb-2"></div>
                                <div class="rf-skeleton h-3 w-16 rounded"></div>
                            </div>
                            @endfor
                        </div>
                    @else
                        <div class="rf-skeleton h-4 w-40 rounded mb-4"></div>
                        <div class="rf-skeleton h-32 w-full rounded"></div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    @endforeach

</div>
</div>

<script>
(function() {
    'use strict';

    var container = document.getElementById('resumo-fiscal-container');
    if (!container) return;

    var loadedSections = {};

    // ── Helpers ──

    function fBrl(v) {
        if (v === null || v === undefined) return 'R$ 0,00';
        return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fPct(v) {
        if (v === null || v === undefined) return '-';
        return (v >= 0 ? '+' : '') + v.toFixed(1) + '%';
    }

    function getParams() {
        var p = new URLSearchParams();
        p.set('cliente_id', document.getElementById('rf-cliente')?.value || '');
        p.set('competencia', document.getElementById('rf-competencia')?.value || '');
        return p.toString();
    }

    function semDados(el, msg) {
        el.innerHTML = '<div class="text-center py-8"><p class="text-xs text-gray-400 uppercase tracking-wide">' + (msg || 'Sem dados para este período.') + '</p></div>';
    }

    function deltaHtml(delta) {
        if (!delta || (delta.valor === 0 && delta.percentual === 0)) return '<span class="text-[11px] text-gray-400">—</span>';
        var up = delta.valor > 0;
        var hex = up ? '#b91c1c' : '#047857';
        var arrow = up ? '&#9650;' : '&#9660;';
        return '<span class="text-[11px] font-semibold" style="color: ' + hex + '">' + arrow + ' ' + fPct(delta.percentual) + '</span>';
    }

    function semaforoHtml(status, label) {
        var hex = { verde: '#047857', amarelo: '#d97706', vermelho: '#b91c1c', sem_dados: '#9ca3af' };
        var h = hex[status] || hex.sem_dados;
        return '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: ' + h + '">' + (label || status) + '</span>';
    }

    // ── Sub-bloco (header cinza DANFE) ──
    function bloco(titulo, inner) {
        return '<div class="bg-white rounded border border-gray-300 overflow-hidden mb-4">' +
            '<div class="bg-gray-50 px-4 py-2 border-b border-gray-200">' +
                '<span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">' + titulo + '</span>' +
            '</div>' +
            '<div class="p-4">' + inner + '</div>' +
        '</div>';
    }

    // ── Loaders ──

    async function loadSection(id, url, renderFn) {
        if (loadedSections[id]) return;
        try {
            var resp = await fetch(url + '?' + getParams(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            var data = await resp.json();
            renderFn(data);
            loadedSections[id] = true;
        } catch (err) {
            console.error('[RF] Erro ' + id + ':', err);
            var el = document.getElementById('rf-' + id.replace('secao-', '') + '-content') || document.getElementById('rf-' + id + '-content');
            if (el) el.innerHTML = '<div class="bg-white rounded border border-gray-300 border-l-4 p-4 text-sm text-gray-700" style="border-left-color: #b91c1c">Erro ao carregar dados.</div>';
        }
    }

    // ── Renders ──

    function renderResumo(data) {
        var el = document.getElementById('rf-resumo-content');
        if (!data.tem_dados) { semDados(el); document.getElementById('rf-empty-state')?.classList.remove('hidden'); return; }
        document.getElementById('rf-empty-state')?.classList.add('hidden');

        var kpis = data.kpis;
        var cards = [
            { label: 'ICMS a Recolher', key: 'icms_a_recolher' },
            { label: 'PIS a Recolher', key: 'pis_a_recolher' },
            { label: 'COFINS a Recolher', key: 'cofins_a_recolher' },
            { label: 'Retenções Compensáveis', key: 'retencoes_compensaveis' },
            { label: 'Saldo Líquido', key: 'saldo_liquido' }
        ];

        var html = '<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">';
        cards.forEach(function(c) {
            var k = kpis[c.key];
            var val = k ? k.valor : 0;

            html += '<div class="bg-white rounded border border-gray-300 p-4">';
            html += '<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">' + c.label + '</p>';
            html += '<p class="text-lg font-bold text-gray-900 font-mono truncate">' + fBrl(val) + '</p>';
            html += '<div class="mt-1">' + deltaHtml(k ? k.delta : null) + '</div>';
            html += '</div>';
        });
        html += '</div>';
        el.innerHTML = html;
    }

    function renderIcms(data) {
        var el = document.getElementById('rf-icms-content');
        if (!data.tem_dados) { semDados(el, 'Sem apuração ICMS para este período. Importe um EFD ICMS/IPI.'); return; }

        var p = data.icms_proprio;
        var html = '';

        if (data.periodo_inicio) {
            html += '<div class="mb-4 text-[11px] text-gray-500 uppercase tracking-wide">Período: ' + data.periodo_inicio + ' a ' + data.periodo_fim + '</div>';
        }

        // ICMS Proprio
        var inner = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        inner += '<div class="space-y-2">';
        inner += '<h4 class="text-[10px] font-bold uppercase tracking-widest" style="color: #b91c1c">Débitos</h4>';
        inner += fluxoRow('Total Débitos', p.tot_debitos, 'red');
        inner += fluxoRow('(+) Ajustes Débitos', p.aj_debitos);
        inner += fluxoRow('(+) Estornos Crédito', p.estornos_credito);
        inner += fluxoRow('(=) Total Aj. Débitos', p.tot_aj_debitos, 'red', true);
        inner += '</div>';

        inner += '<div class="space-y-2">';
        inner += '<h4 class="text-[10px] font-bold uppercase tracking-widest" style="color: #047857">Créditos</h4>';
        inner += fluxoRow('Total Créditos', p.tot_creditos, 'green');
        inner += fluxoRow('(+) Ajustes Créditos', p.aj_creditos);
        inner += fluxoRow('(+) Estornos Débito', p.estornos_debito);
        inner += fluxoRow('(=) Total Aj. Créditos', p.tot_aj_creditos, 'green', true);
        inner += '</div>';
        inner += '</div>';

        inner += '<div class="mt-4 pt-4 border-t border-gray-200 space-y-2">';
        inner += fluxoRow('Saldo Credor Anterior', p.sld_credor_ant, 'green');
        inner += fluxoRow('Saldo Apurado', p.sld_apurado, p.sld_apurado >= 0 ? 'red' : 'green', true);
        inner += fluxoRow('(-) Deduções', p.tot_deducoes);
        inner += '<div class="pt-2 border-t border-gray-200">';
        if (p.a_recolher > 0) {
            inner += fluxoRow('ICMS a Recolher', p.a_recolher, 'red', true, true);
        } else {
            inner += fluxoRow('Saldo Credor a Transportar', p.sld_credor_transportar, 'green', true, true);
        }
        if (p.deb_especiais > 0) {
            inner += fluxoRow('(+) Débitos Especiais', p.deb_especiais, 'red');
        }
        inner += '</div></div>';

        html += bloco('ICMS Próprio (E110)', inner);

        // ICMS-ST
        if (data.tem_st && data.icms_st) {
            var st = data.icms_st;
            var stInner = '<div class="space-y-2">';
            if (st.uf) stInner = '<div class="mb-3"><span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #ea580c">UF ' + st.uf + '</span></div>' + stInner;
            stInner += fluxoRow('Saldo Credor Anterior', st.sld_credor_ant, 'green');
            stInner += fluxoRow('(+) Devoluções', st.devolucoes);
            stInner += fluxoRow('(+) Ressarcimentos', st.ressarcimentos);
            stInner += fluxoRow('(+) Outros Créditos', st.outros_creditos);
            stInner += fluxoRow('(-) Retenção', st.retencao, 'red');
            stInner += fluxoRow('(-) Outros Débitos', st.outros_debitos);
            stInner += '<div class="pt-2 border-t border-gray-200">';
            stInner += fluxoRow('ICMS-ST a Recolher', st.icms_recolher, 'red', true, true);
            stInner += '</div></div>';
            html += bloco('ICMS-ST (E210)', stInner);
        }

        // DIFAL/FCP
        if (data.tem_difal && data.difal_fcp) {
            var dfInner = '';
            var df = data.difal_fcp;
            var dfItems = Array.isArray(df) ? df : (df.items && Array.isArray(df.items) ? df.items : null);
            if (dfItems && dfItems.length > 0) {
                dfInner += '<div class="overflow-x-auto"><table class="w-full text-sm">';
                dfInner += '<thead><tr class="border-b border-gray-300">';
                dfInner += '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">UF</th>';
                dfInner += '<th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">DIFAL Origem</th>';
                dfInner += '<th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">DIFAL Destino</th>';
                dfInner += '<th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">FCP a Recolher</th>';
                dfInner += '</tr></thead><tbody class="divide-y divide-gray-100">';
                var totDifalOri = 0, totDifalDst = 0, totFcp = 0;
                dfItems.forEach(function(d) {
                    var vlOri = parseFloat(d.VL_SLD_DEV_ANT_DIFAL ?? d.difal_origem ?? 0);
                    var vlDst = parseFloat(d.VL_ICMS_RECOLHER_DIFAL ?? d.difal_destino ?? d.icms_recolher ?? 0);
                    var vlFcp = parseFloat(d.VL_FCP_RECOLHER ?? d.fcp ?? 0);
                    totDifalOri += vlOri; totDifalDst += vlDst; totFcp += vlFcp;
                    dfInner += '<tr class="hover:bg-gray-50/50 transition-colors">';
                    dfInner += '<td class="px-3 py-2 text-sm text-gray-700 font-mono">' + (d.UF ?? d.uf ?? '—') + '</td>';
                    dfInner += '<td class="px-3 py-2 text-right font-mono text-sm text-gray-700">' + fBrl(vlOri) + '</td>';
                    dfInner += '<td class="px-3 py-2 text-right font-mono text-sm text-gray-700">' + fBrl(vlDst) + '</td>';
                    dfInner += '<td class="px-3 py-2 text-right font-mono text-sm text-gray-700">' + fBrl(vlFcp) + '</td>';
                    dfInner += '</tr>';
                });
                if (dfItems.length > 1) {
                    dfInner += '<tr class="border-t-2 border-gray-300 bg-gray-50 font-bold text-sm">';
                    dfInner += '<td class="px-3 py-2 text-gray-900 uppercase tracking-wide text-[10px]">Total</td>';
                    dfInner += '<td class="px-3 py-2 text-right font-mono text-gray-900">' + fBrl(totDifalOri) + '</td>';
                    dfInner += '<td class="px-3 py-2 text-right font-mono text-gray-900">' + fBrl(totDifalDst) + '</td>';
                    dfInner += '<td class="px-3 py-2 text-right font-mono text-gray-900">' + fBrl(totFcp) + '</td>';
                    dfInner += '</tr>';
                }
                dfInner += '</tbody></table></div>';
                dfInner += '<div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">';
                dfInner += '<span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">DIFAL + FCP a Recolher</span>';
                dfInner += '<span class="text-base font-bold font-mono" style="color: #b91c1c">' + fBrl(totDifalDst + totFcp) + '</span>';
                dfInner += '</div>';
            } else {
                var vlDifalOri = parseFloat(df.VL_SLD_DEV_ANT_DIFAL ?? df.difal_origem ?? 0);
                var vlDifalDst = parseFloat(df.VL_ICMS_RECOLHER_DIFAL ?? df.difal_destino ?? df.icms_recolher ?? 0);
                var vlFcp2     = parseFloat(df.VL_FCP_RECOLHER ?? df.fcp ?? 0);
                dfInner += '<div class="space-y-2">';
                if (df.UF || df.uf) {
                    dfInner += '<div class="flex justify-between items-center py-1 text-sm"><span class="text-gray-600">UF Destino</span><span class="text-gray-700 font-medium">' + (df.UF ?? df.uf) + '</span></div>';
                }
                dfInner += fluxoRow('DIFAL Origem (Saldo Dev. Ant.)', vlDifalOri);
                dfInner += fluxoRow('DIFAL Destino (ICMS a Recolher)', vlDifalDst, 'red', true);
                dfInner += fluxoRow('FCP a Recolher', vlFcp2, 'red');
                dfInner += '</div>';
                dfInner += '<div class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">';
                dfInner += '<span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">DIFAL + FCP a Recolher</span>';
                dfInner += '<span class="text-base font-bold font-mono" style="color: #b91c1c">' + fBrl(vlDifalDst + vlFcp2) + '</span>';
                dfInner += '</div>';
            }
            html += bloco('DIFAL/FCP (E310)', dfInner);
        }

        // Obrigacoes
        var obs = (data.icms_obrigacoes || []).concat(data.st_obrigacoes || []);
        if (obs.length > 0) {
            var obInner = '<div class="overflow-x-auto"><table class="w-full">';
            obInner += '<thead><tr class="border-b border-gray-300">';
            obInner += '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Código</th>';
            obInner += '<th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th>';
            obInner += '<th class="px-3 py-2 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Vencimento</th>';
            obInner += '</tr></thead><tbody class="divide-y divide-gray-100">';
            obs.forEach(function(ob) {
                var dtVcto = ob.dt_vcto || ob.data_vencimento || '';
                var valor = ob.vl_or || ob.valor_obrigacao || 0;
                var vencido = dtVcto && new Date(dtVcto) < new Date();
                obInner += '<tr class="hover:bg-gray-50/50 transition-colors">';
                obInner += '<td class="px-3 py-2 font-mono text-xs text-gray-700">' + (ob.cod_or || ob.codigo_credito || '-') + '</td>';
                obInner += '<td class="px-3 py-2 text-right text-sm font-semibold text-gray-900 font-mono">' + fBrl(valor) + '</td>';
                obInner += '<td class="px-3 py-2 text-center text-sm text-gray-700">';
                if (vencido) obInner += '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white mr-2" style="background-color: #b91c1c">Vencido</span>';
                obInner += (dtVcto ? new Date(dtVcto).toLocaleDateString('pt-BR') : '—');
                obInner += '</td></tr>';
            });
            obInner += '</tbody></table></div>';
            html += bloco('Obrigações a Recolher (E116/E250)', obInner);
        }

        el.innerHTML = html;
    }

    function fluxoRow(label, valor, color, bold, big) {
        var valHex = '#374151';
        if (color === 'red') valHex = '#b91c1c';
        if (color === 'green') valHex = '#047857';
        var valClass = 'font-mono';
        if (bold) valClass += ' font-bold';
        valClass += big ? ' text-base' : ' text-sm';
        var labelClass = bold ? 'font-semibold text-gray-800' : 'text-gray-600';
        return '<div class="flex justify-between items-center py-1 ' + (big ? 'text-base' : 'text-sm') + '">' +
            '<span class="' + labelClass + '">' + label + '</span>' +
            '<span class="' + valClass + '" style="color: ' + valHex + '">' + fBrl(valor) + '</span></div>';
    }

    function renderPisCofins(data) {
        var el = document.getElementById('rf-piscofins-content');
        if (!data.tem_dados) { semDados(el, 'Sem apuração PIS/COFINS para este período. Importe um EFD Contribuições.'); return; }

        var html = '';

        // Regime badge
        var regimeLabel = { nao_cumulativo: 'Não-Cumulativo', cumulativo: 'Cumulativo', misto: 'Misto' };
        var regimeHex = { nao_cumulativo: '#4338ca', cumulativo: '#374151', misto: '#7c3aed' };
        html += '<div class="mb-4"><span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: ' + (regimeHex[data.regime] || '#9ca3af') + '">Regime: ' + (regimeLabel[data.regime] || data.regime) + '</span></div>';

        html += renderContribuicao('PIS', data.pis);
        html += renderContribuicao('COFINS', data.cofins);

        if (data.tem_creditos_nc) {
            var credPis = data.pis_creditos_nc || [];
            var credCofins = data.cofins_creditos_nc || [];
            if (credPis.length > 0 || credCofins.length > 0) {
                var credInner = '';
                if (credPis.length > 0) {
                    credInner += '<h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">PIS</h4>';
                    credInner += renderCreditosTable(credPis);
                }
                if (credCofins.length > 0) {
                    credInner += '<h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2 mt-4">COFINS</h4>';
                    credInner += renderCreditosTable(credCofins);
                }
                html += bloco('Créditos Não-Cumulativos (M100/M500)', credInner);
            }
        }

        var naoTrib = data.pis_nao_tributado || [];
        if (naoTrib.length > 0) {
            var ntInner = '<div class="overflow-x-auto"><table class="w-full"><thead><tr class="border-b border-gray-300"><th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CST</th><th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Valor</th></tr></thead><tbody class="divide-y divide-gray-100">';
            naoTrib.forEach(function(r) {
                ntInner += '<tr class="hover:bg-gray-50/50 transition-colors"><td class="px-3 py-2 font-mono text-sm text-gray-700">' + (r.cst || r.cod_cst || '-') + '</td><td class="px-3 py-2 text-right text-sm font-semibold text-gray-900 font-mono">' + fBrl(r.vl_rec || r.valor || 0) + '</td></tr>';
            });
            ntInner += '</tbody></table></div>';
            html += bloco('Receitas Não Tributadas (M400/M410)', ntInner);
        }

        el.innerHTML = html;
    }

    function renderContribuicao(nome, dados) {
        var inner = '';
        var temNc = dados.nao_cumulativo > 0 || dados.nc_recolher > 0;
        var temCum = dados.cumulativo > 0 || dados.cum_recolher > 0;

        if (temNc) {
            inner += '<h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Não-Cumulativo</h4>';
            inner += '<div class="space-y-1 mb-4">';
            inner += fluxoRow('Contribuição Apurada', dados.nao_cumulativo, 'red');
            inner += fluxoRow('(-) Crédito Descontado', dados.credito_descontado, 'green');
            inner += fluxoRow('(-) Crédito Desc. Anterior', dados.credito_desc_ant);
            inner += fluxoRow('(=) Contribuição Devida NC', dados.nc_devida, 'red', true);
            inner += fluxoRow('(-) Retenção NC', dados.retencao_nc);
            inner += fluxoRow('(-) Outras Deduções NC', dados.outras_deducoes_nc);
            inner += fluxoRow(nome + ' NC a Recolher', dados.nc_recolher, 'red', true);
            inner += '</div>';
        }

        if (temCum) {
            inner += '<h4 class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest mb-2">Cumulativo</h4>';
            inner += '<div class="space-y-1 mb-4">';
            inner += fluxoRow('Contribuição Apurada', dados.cumulativo, 'red');
            inner += fluxoRow('(-) Retenção Cum.', dados.retencao_cum);
            inner += fluxoRow('(-) Outras Deduções Cum.', dados.outras_deducoes_cum);
            inner += fluxoRow(nome + ' Cum. a Recolher', dados.cum_recolher, 'red', true);
            inner += '</div>';
        }

        inner += '<div class="pt-3 border-t border-gray-200">';
        inner += fluxoRow('Total ' + nome + ' a Recolher', dados.total_recolher, 'red', true, true);
        inner += '</div>';

        return bloco('Apuração ' + nome, inner);
    }

    function renderCreditosTable(creditos) {
        var html = '<div class="overflow-x-auto"><table class="w-full mb-2"><thead><tr class="border-b border-gray-300"><th class="px-2 py-1.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Tipo</th><th class="px-2 py-1.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Apropriado</th><th class="px-2 py-1.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Desc. Anterior</th><th class="px-2 py-1.5 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Desc. Período</th></tr></thead><tbody class="divide-y divide-gray-100">';
        creditos.forEach(function(c) {
            html += '<tr class="hover:bg-gray-50/50 transition-colors"><td class="px-2 py-1.5 text-xs text-gray-700">' + (c.tipo_credito || c.cod_cred || '-') + '</td>';
            html += '<td class="px-2 py-1.5 text-right font-mono text-xs text-gray-700">' + fBrl(c.vl_cred_apur || c.valor_credito_apropriado || 0) + '</td>';
            html += '<td class="px-2 py-1.5 text-right font-mono text-xs text-gray-700">' + fBrl(c.vl_cred_desc_ant || c.valor_credito_desc_ant || 0) + '</td>';
            html += '<td class="px-2 py-1.5 text-right font-mono text-xs text-gray-700">' + fBrl(c.vl_cred_desc || c.valor_credito_desc_per || 0) + '</td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        return html;
    }

    function renderRetencoes(data) {
        var el = document.getElementById('rf-retencoes-content');
        if (!data.tem_dados) { semDados(el, 'Sem retenções na fonte (F600) para este período.'); return; }

        var k = data.kpis;
        var html = '';

        html += '<div class="grid grid-cols-3 gap-3 mb-4">';
        html += '<div class="bg-white rounded border border-gray-300 p-4 text-center">';
        html += '<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Total Retido</p>';
        html += '<p class="text-lg font-bold text-gray-900 font-mono">' + fBrl(k.total_retido) + '</p></div>';
        html += '<div class="bg-white rounded border border-gray-300 p-4 text-center">';
        html += '<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Retenções</p>';
        html += '<p class="text-lg font-bold text-gray-900 font-mono">' + k.qtd_retencoes + '</p></div>';
        html += '<div class="bg-white rounded border border-gray-300 p-4 text-center">';
        html += '<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">CNPJs Retentores</p>';
        html += '<p class="text-lg font-bold text-gray-900 font-mono">' + k.cnpjs_unicos + '</p></div>';
        html += '</div>';

        var tblInner = '<div class="overflow-x-auto"><table class="w-full">';
        tblInner += '<thead><tr class="border-b border-gray-300">';
        tblInner += '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Data</th>';
        tblInner += '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">CNPJ</th>';
        tblInner += '<th class="px-3 py-2 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Natureza</th>';
        tblInner += '<th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Base Cálculo</th>';
        tblInner += '<th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">PIS</th>';
        tblInner += '<th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">COFINS</th>';
        tblInner += '<th class="px-3 py-2 text-right text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Total</th>';
        tblInner += '<th class="px-3 py-2 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide bg-gray-50">Cód. Receita</th>';
        tblInner += '</tr></thead><tbody class="divide-y divide-gray-100">';

        (data.retencoes || []).forEach(function(r) {
            var natHex = { '01': '#4338ca', '02': '#ea580c', '03': '#7c3aed' };
            var h = natHex[r.natureza_raw] || '#9ca3af';
            tblInner += '<tr class="hover:bg-gray-50/50 transition-colors">';
            tblInner += '<td class="px-3 py-2 whitespace-nowrap text-sm text-gray-700 font-mono">' + r.data + '</td>';
            tblInner += '<td class="px-3 py-2 font-mono text-xs text-gray-700">' + r.cnpj + '</td>';
            tblInner += '<td class="px-3 py-2"><span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: ' + h + '">' + r.natureza + '</span></td>';
            tblInner += '<td class="px-3 py-2 text-right font-mono text-sm text-gray-700">' + fBrl(r.base_calculo) + '</td>';
            tblInner += '<td class="px-3 py-2 text-right font-mono text-sm text-gray-700">' + fBrl(r.valor_pis) + '</td>';
            tblInner += '<td class="px-3 py-2 text-right font-mono text-sm text-gray-700">' + fBrl(r.valor_cofins) + '</td>';
            tblInner += '<td class="px-3 py-2 text-right font-mono text-sm font-bold text-gray-900">' + fBrl(r.total) + '</td>';
            tblInner += '<td class="px-3 py-2 text-center font-mono text-xs text-gray-700">' + (r.cod_receita || '-') + '</td>';
            tblInner += '</tr>';
        });

        tblInner += '<tr class="border-t-2 border-gray-300 bg-gray-50 font-bold">';
        tblInner += '<td colspan="6" class="px-3 py-2 text-right text-[10px] uppercase tracking-wide text-gray-900">Total</td>';
        tblInner += '<td class="px-3 py-2 text-right font-mono text-gray-900">' + fBrl(k.total_retido) + '</td>';
        tblInner += '<td></td></tr>';
        tblInner += '</tbody></table></div>';

        html += bloco('Detalhamento de Retenções (F600)', tblInner);

        el.innerHTML = html;
    }

    function renderCruzamentos(data) {
        var el = document.getElementById('rf-cruzamentos-content');
        var html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';

        html += cruzamentoCard('ICMS Débitos', 'Apuração (E110) vs Soma das Notas de Saída', data.icms.tem_dados, data.icms.declarado_debito, data.icms.notas_debito, data.icms.divergencia_debito_pct, data.icms.status_debito);
        html += cruzamentoCard('ICMS Créditos', 'Apuração (E110) vs Soma das Notas de Entrada', data.icms.tem_dados, data.icms.declarado_credito, data.icms.notas_credito, data.icms.divergencia_credito_pct, data.icms.status_credito);
        html += cruzamentoCard('PIS a Recolher', 'Apuração (M200) vs Soma dos Itens', data.pis_cofins.tem_dados, data.pis_cofins.pis_declarado, data.pis_cofins.pis_notas, data.pis_cofins.pis_divergencia_pct, data.pis_cofins.pis_status);
        html += cruzamentoCard('COFINS a Recolher', 'Apuração (M600) vs Soma dos Itens', data.pis_cofins.tem_dados, data.pis_cofins.cofins_declarado, data.pis_cofins.cofins_notas, data.pis_cofins.cofins_divergencia_pct, data.pis_cofins.cofins_status);

        var ret = data.retencoes;
        html += '<div class="bg-white rounded border border-gray-300 overflow-hidden md:col-span-2 lg:col-span-2">';
        html += '<div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">';
        html += '<span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Retenções vs Apuração</span>';
        html += semaforoHtml(ret.status, ret.status === 'verde' ? 'OK' : 'Atenção');
        html += '</div>';
        html += '<div class="p-4">';
        if (ret.tem_dados) {
            html += '<div class="grid grid-cols-3 gap-4 text-center">';
            html += '<div><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Retido (F600)</p><p class="text-sm font-bold text-gray-900 font-mono">' + fBrl(ret.total_retido) + '</p></div>';
            html += '<div><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Deduzido na Apuração</p><p class="text-sm font-bold text-gray-900 font-mono">' + fBrl(ret.deduzido_apuracao) + '</p></div>';
            var ncHex = ret.nao_compensado > 0 ? '#d97706' : '#047857';
            html += '<div><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Não Compensado</p><p class="text-sm font-bold font-mono" style="color: ' + ncHex + '">' + fBrl(ret.nao_compensado) + '</p></div>';
            html += '</div>';
        } else {
            html += '<p class="text-sm text-gray-400">Sem dados de retenção para cruzamento.</p>';
        }
        html += '</div></div>';

        html += '</div>';
        el.innerHTML = html;
    }

    function cruzamentoCard(titulo, subtitulo, temDados, declarado, notas, divPct, status) {
        var html = '<div class="bg-white rounded border border-gray-300 overflow-hidden">';
        html += '<div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between gap-2">';
        html += '<div><span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">' + titulo + '</span>';
        html += '<p class="text-[10px] text-gray-400 normal-case">' + subtitulo + '</p></div>';
        html += semaforoHtml(status, divPct !== null ? divPct.toFixed(1) + '%' : 'N/A');
        html += '</div>';
        html += '<div class="p-4">';
        if (temDados) {
            html += '<div class="grid grid-cols-2 gap-3 text-center">';
            html += '<div class="bg-gray-50 rounded border border-gray-200 p-3"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Declarado</p><p class="text-sm font-bold text-gray-900 font-mono">' + fBrl(declarado) + '</p></div>';
            html += '<div class="bg-gray-50 rounded border border-gray-200 p-3"><p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Notas</p><p class="text-sm font-bold text-gray-900 font-mono">' + fBrl(notas) + '</p></div>';
            html += '</div>';
        } else {
            html += '<p class="text-sm text-gray-400">Sem dados de apuração.</p>';
        }
        html += '</div></div>';
        return html;
    }

    function renderAlertas(data) {
        var el = document.getElementById('rf-alertas-content');
        var badge = document.getElementById('rf-alertas-badge');

        if (!data.alertas || data.alertas.length === 0) {
            if (badge) badge.classList.add('hidden');
            el.innerHTML = '<div class="bg-white rounded border border-gray-300 border-l-4 p-6 text-center" style="border-left-color: #047857">' +
                '<svg class="mx-auto w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #047857"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' +
                '<p class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Nenhum alerta para este período</p>' +
                '<p class="text-xs text-gray-500 mt-1">Todos os cruzamentos estão dentro dos limites esperados.</p></div>';
            return;
        }

        if (badge) {
            badge.textContent = data.resumo.total;
            badge.classList.remove('hidden');
        }

        var html = '';

        html += '<div class="grid grid-cols-3 gap-3 mb-4">';
        var mkKpi = function(label, valor, hex) {
            return '<div class="bg-white rounded border border-gray-300 p-3 text-center">' +
                '<p class="text-xl font-bold font-mono" style="color: ' + hex + '">' + valor + '</p>' +
                '<p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">' + label + '</p>' +
            '</div>';
        };
        html += mkKpi('Críticos', data.resumo.alta || 0, data.resumo.alta > 0 ? '#b91c1c' : '#d1d5db');
        html += mkKpi('Atenção', data.resumo.media || 0, data.resumo.media > 0 ? '#d97706' : '#d1d5db');
        html += mkKpi('Info', data.resumo.info || 0, '#9ca3af');
        html += '</div>';

        html += '<div class="space-y-3">';
        data.alertas.forEach(function(a) {
            var sevBorder = { alta: '#b91c1c', media: '#d97706', info: '#3b82f6' };
            var sevBadge = { alta: '#b91c1c', media: '#d97706', info: '#3b82f6' };
            var borderHex = sevBorder[a.severidade] || '#9ca3af';
            var badgeHex = sevBadge[a.severidade] || '#9ca3af';
            html += '<div class="bg-white rounded border border-gray-300 border-l-4 p-4" style="border-left-color: ' + borderHex + '">';
            html += '<div class="flex items-start justify-between gap-2">';
            html += '<div>';
            html += '<div class="flex items-center gap-2 mb-1">';
            html += '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: ' + badgeHex + '">' + a.categoria + '</span>';
            html += '<h4 class="text-sm font-semibold text-gray-900">' + a.titulo + '</h4>';
            html += '</div>';
            html += '<p class="text-sm text-gray-600">' + a.descricao + '</p>';
            html += '</div>';
            if (a.valor) html += '<span class="text-sm font-bold text-gray-900 font-mono whitespace-nowrap">' + fBrl(a.valor) + '</span>';
            html += '</div></div>';
        });
        html += '</div>';

        el.innerHTML = html;
    }

    // ── Section map ──

    var sectionMap = {
        'resumo': { url: '/app/resumo-fiscal/resumo-executivo', render: renderResumo },
        'icms': { url: '/app/resumo-fiscal/apuracao-icms', render: renderIcms },
        'pis-cofins': { url: '/app/resumo-fiscal/apuracao-pis-cofins', render: renderPisCofins },
        'retencoes': { url: '/app/resumo-fiscal/retencoes', render: renderRetencoes },
        'cruzamentos': { url: '/app/resumo-fiscal/cruzamentos', render: renderCruzamentos },
        'alertas': { url: '/app/resumo-fiscal/alertas', render: renderAlertas }
    };

    // ── IntersectionObserver lazy loading ──

    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var id = entry.target.id.replace('secao-', '');
                var cfg = sectionMap[id];
                if (cfg) loadSection(id, cfg.url, cfg.render);
            }
        });
    }, { rootMargin: '200px' });

    Object.keys(sectionMap).forEach(function(id) {
        var el = document.getElementById('secao-' + id);
        if (el) observer.observe(el);
    });

    // ── Sticky nav scroll + highlight ──

    document.querySelectorAll('.rf-nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = link.getAttribute('href').slice(1);
            var target = document.getElementById(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.querySelectorAll('.rf-nav-link').forEach(function(l) {
                    l.classList.remove('active');
                });
                link.classList.add('active');
            }
        });
    });

    // ── Collapsible sections ──

    document.querySelectorAll('[data-toggle]').forEach(function(header) {
        header.addEventListener('click', function() {
            var sectionId = header.getAttribute('data-toggle');
            var content = document.querySelector('.rf-section-content[data-section="' + sectionId + '"]');
            var chevron = header.querySelector('.rf-chevron');
            if (content) content.classList.toggle('collapsed');
            if (chevron) chevron.classList.toggle('rotated');
        });
    });

    // ── Filter button ──

    document.getElementById('rf-btn-filtrar')?.addEventListener('click', function() {
        loadedSections = {};
        document.getElementById('rf-empty-state')?.classList.add('hidden');
        document.getElementById('rf-alertas-badge')?.classList.add('hidden');

        Object.keys(sectionMap).forEach(function(id) {
            var el = document.getElementById('secao-' + id);
            if (el) {
                observer.unobserve(el);
                observer.observe(el);
            }
        });

        loadSection('resumo', sectionMap.resumo.url, sectionMap.resumo.render);
    });

    // ── Cleanup for SPA ──

    function cleanup() {
        observer.disconnect();
        loadedSections = {};
    }

    window._cleanupFunctions = window._cleanupFunctions || {};
    window._cleanupFunctions.resumoFiscal = cleanup;

    // ── Initial load ──

    loadSection('resumo', sectionMap.resumo.url, sectionMap.resumo.render);

})();
</script>
