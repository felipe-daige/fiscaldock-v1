<p>O cruzamento entre XMLs e EFD é uma das revisões mais úteis do fechamento — e também uma das mais negligenciadas. Quando o escritório faz essa conferência apenas por amostra, a chance de uma nota faltante passar é alta.</p>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">O que o confronto detecta</h2>

<div class="blog-table-wrap">
    <table class="blog-table">
        <thead>
            <tr><th>Ocorrência</th><th>O que indica</th></tr>
        </thead>
        <tbody>
            <tr><td><strong>XML existe, EFD não</strong></td><td>Nota não escriturada — omissão de receita ou de compra</td></tr>
            <tr><td><strong>EFD existe, XML não</strong></td><td>Lançamento sem documento de origem</td></tr>
            <tr><td><strong>Valores divergentes</strong></td><td>Erro de digitação, rateio incorreto, manipulação</td></tr>
            <tr><td><strong>CFOP divergente</strong></td><td>Classificação incoerente com a natureza da operação</td></tr>
            <tr><td><strong>CST divergente</strong></td><td>Tributação registrada diferente do documento</td></tr>
        </tbody>
    </table>
</div>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">Passo a passo operacional</h2>

<ol class="list-decimal pl-6 space-y-3 my-4">
    <li>reunir todos os XMLs recebidos e emitidos do período</li>
    <li>extrair o conjunto de chaves escrituradas da EFD</li>
    <li>rodar diff: chaves apenas em XML, chaves apenas em EFD, chaves em ambos</li>
    <li>nas chaves comuns, comparar valor, CFOP, CST e natureza da operação</li>
    <li>documentar as exceções em fila de revisão, não em planilha paralela</li>
</ol>

<div class="blog-data-card">
    <div class="blog-data-card__title">Onde o cruzamento mais revela problema</div>
    <div class="blog-bar-chart">
        <div class="blog-bar-chart__row">
            <div class="blog-bar-chart__label">Notas faltantes</div>
            <div class="blog-bar-chart__track"><div class="blog-bar-chart__fill blog-bar-chart__fill--amber" style="--bar-value: 74"></div></div>
            <div class="blog-bar-chart__value">comum</div>
        </div>
        <div class="blog-bar-chart__row">
            <div class="blog-bar-chart__label">Valores divergentes</div>
            <div class="blog-bar-chart__track"><div class="blog-bar-chart__fill" style="--bar-value: 68"></div></div>
            <div class="blog-bar-chart__value">frequente</div>
        </div>
        <div class="blog-bar-chart__row">
            <div class="blog-bar-chart__label">CFOP incoerente</div>
            <div class="blog-bar-chart__track"><div class="blog-bar-chart__fill blog-bar-chart__fill--emerald" style="--bar-value: 58"></div></div>
            <div class="blog-bar-chart__value">relevante</div>
        </div>
    </div>
</div>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">Erros que travam o processo</h2>

<ul class="list-disc pl-6 space-y-2 my-4">
    <li>comparar por número de nota em vez de chave — colide entre emitentes</li>
    <li>confrontar só por totalizador — esconde compensações entre itens</li>
    <li>tratar XML recebido como verdade absoluta sem validar status SEFAZ</li>
    <li>documentar divergências em planilha que ninguém atualiza</li>
</ul>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">Conclusão</h2>

<p>O confronto XML × EFD não é glamuroso, mas é o que separa um fechamento revisado de um fechamento transmitido. Quando o processo entra na rotina mensal, as divergências aparecem cedo — e deixam de virar autuação depois.</p>
