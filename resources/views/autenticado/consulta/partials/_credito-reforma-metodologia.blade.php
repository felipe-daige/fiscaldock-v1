{{-- Metodologia do crédito IBS/CBS — estático, sem fetch. Reusável (consulta + score fiscal). --}}
<div class="mt-2 rounded border border-gray-100 bg-gray-50 px-2.5 py-2 text-[11px] text-gray-600 space-y-2">
    <div class="font-mono text-[10px] text-gray-500 leading-relaxed">
        Crédito potencial = volume de entradas × alíquota de referência (pleno 2033)<br>
        Crédito em risco = potencial × (1 − fator de crédito do regime do fornecedor)
    </div>
    <table class="w-full text-[10px]">
        <tbody>
            <tr><td class="pr-2 text-gray-500">Alíquota referência (pleno 2033)</td><td class="font-mono">~28,5%</td><td class="text-gray-400">est. Fazenda; trava 26,5% Senado; TCU</td></tr>
            <tr><td class="pr-2 text-gray-500">Regime regular (Real/Presumido c/ opção)</td><td class="font-mono">crédito integral</td><td class="text-gray-400">LC 214/2025</td></tr>
            <tr><td class="pr-2 text-gray-500">Simples sem opção</td><td class="font-mono">~30% (estimativa)</td><td class="text-gray-400">art. 41 §3 LC 214/2025</td></tr>
            <tr><td class="pr-2 text-gray-500">Regime não identificado</td><td class="font-mono">—</td><td class="text-gray-400">rodar consulta CNPJ</td></tr>
        </tbody>
    </table>
    <p class="text-[10px] text-gray-500">
        <strong>Art. 47, LC 214/2025:</strong> o crédito de IBS/CBS do comprador só se concretiza quando o tributo é extinto (recolhido) na etapa anterior. Fornecedor irregular ou Simples → seu crédito não nasce integral.
    </p>
    <p class="text-[10px] text-gray-400">Projeção de planejamento no regime pleno (2033). Em 2026 a cobrança efetiva é teste (~1%). Valores estimados, não cobrança atual. Legado destacado = ICMS+PIS+COFINS+IPI nas entradas; destacado ≠ aproveitado (depende de CST/CFOP e do seu regime).</p>
    <div class="flex flex-wrap gap-x-3 gap-y-1 text-[10px]">
        <a href="https://www.planalto.gov.br/ccivil_03/leis/lcp/lcp214.htm" target="_blank" rel="noopener" class="text-blue-600 underline">LC 214/2025 (Planalto)</a>
        <a href="https://www.gov.br/fazenda/pt-br/acesso-a-informacao/acoes-e-programas/reforma-tributaria" target="_blank" rel="noopener" class="text-blue-600 underline">Min. Fazenda</a>
        <a href="https://sites.tcu.gov.br/reforma-tributaria/" target="_blank" rel="noopener" class="text-blue-600 underline">TCU</a>
        <a href="https://www.camara.leg.br/noticias/1237089-reforma-tributaria-comeca-fase-de-transicao-com-testes-de-novos-impostos-em-2026/" target="_blank" rel="noopener" class="text-blue-600 underline">Câmara</a>
    </div>
</div>
