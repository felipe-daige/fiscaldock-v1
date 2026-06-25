{{-- Crédito IBS/CBS passo a passo. Espera $cr (credito_reforma) e $base (total_comprado). --}}
<div class="secao">
    <div class="secao-header">Crédito tributário (IBS/CBS) — passo a passo</div>
    <div class="secao-body">
        @if(!empty($cr['fornecedor']))
            @php($f = $cr['fornecedor'])
            <table class="kv" style="width:100%;">
                <tr><td class="k" style="width:62%;">1. Base (compras deste fornecedor)</td><td class="v right">R$ {{ number_format((float) $base, 2, ',', '.') }}</td></tr>
                <tr><td class="k">2. Alíquota IBS/CBS (ano vigente)</td><td class="v right">{{ number_format(($f['aliquota'] ?? 0) * 100, 1, ',', '.') }}%</td></tr>
                <tr><td class="k">3. Crédito potencial (pleno) = base × alíquota</td><td class="v right">R$ {{ number_format((float) ($f['credito_potencial'] ?? 0), 2, ',', '.') }}</td></tr>
                @if(($f['credito_em_risco'] ?? null) !== null)
                    <tr><td class="k">4. Regime do fornecedor → aproveitamento</td><td class="v right">{{ $f['gera_credito'] ?? '—' }}</td></tr>
                    <tr><td class="k">5. Crédito aproveitável = potencial × fator</td><td class="v right">R$ {{ number_format((float) ($f['credito_potencial'] ?? 0) - (float) ($f['credito_em_risco'] ?? 0), 2, ',', '.') }}</td></tr>
                    <tr><td class="k">6. Crédito em risco = potencial × (1 − fator)</td><td class="v right">R$ {{ number_format((float) ($f['credito_em_risco'] ?? 0), 2, ',', '.') }}</td></tr>
                @else
                    <tr><td class="k" colspan="2"><span class="msg">Regime do fornecedor não identificado — rode a consulta para estimar o crédito aproveitável.</span></td></tr>
                @endif
            </table>
        @endif

        @if(!empty($cr['cliente_b2b']))
            <div class="list-item" style="margin-top:5px;">Você transfere <strong>R$ {{ number_format((float) ($cr['cliente_b2b']['credito_transferido'] ?? 0), 2, ',', '.') }}</strong> de crédito a este comprador (B2B).</div>
        @endif

        @if(!empty($cr['legado']))
            <div class="list-item" style="margin-top:3px;">Regime atual: <strong>R$ {{ number_format((float) ($cr['legado']['destacado'] ?? 0), 2, ',', '.') }}</strong> destacado nas entradas (ICMS+PIS+COFINS+IPI).</div>
        @endif

        <div class="msg">Estimativa. Crédito pleno conforme art. 47 da LC 214/2025 (não cumulatividade IBS/CBS); o aproveitamento real depende do regime do fornecedor e da apuração. Valores aproximados.</div>
    </div>
</div>
