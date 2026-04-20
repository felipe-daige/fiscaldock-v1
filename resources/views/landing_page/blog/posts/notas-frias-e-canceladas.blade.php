<p>Nota fria é aquela que não existe de verdade para o fisco — nunca foi autorizada, foi denegada ou foi cancelada depois da emissão. Quando ela entra na escrituração, o escritório está gerando crédito ou custo sobre algo que, para a Receita, nunca aconteceu.</p>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">Os sinais mais comuns</h2>

<div class="blog-table-wrap">
    <table class="blog-table">
        <thead>
            <tr><th>Sinal</th><th>O que investigar</th></tr>
        </thead>
        <tbody>
            <tr><td><strong>Status SEFAZ: cancelada</strong></td><td>Escrituração precisa ser revertida ou nunca ocorrer</td></tr>
            <tr><td><strong>Status SEFAZ: denegada</strong></td><td>Emitente com irregularidade — operação não pode ser aceita</td></tr>
            <tr><td><strong>Chave sem consulta</strong></td><td>Documento pode ser frio ou adulterado — confirmar antes de escriturar</td></tr>
            <tr><td><strong>Divergência de valor</strong></td><td>XML mostra X, lançamento mostra Y — erro ou manipulação</td></tr>
            <tr><td><strong>Emitente baixado</strong></td><td>CNPJ não estava apto a emitir na data — crédito indevido</td></tr>
        </tbody>
    </table>
</div>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">Como detectar em lote</h2>

<ol class="list-decimal pl-6 space-y-3 my-4">
    <li>extrair a lista de chaves escrituradas no período</li>
    <li>consultar status em bloco na SEFAZ</li>
    <li>marcar divergências em uma fila de revisão</li>
    <li>validar cadastralmente o emitente de cada nota crítica</li>
</ol>

<div class="blog-data-card">
    <div class="blog-data-card__title">Impacto típico por tipo de ocorrência</div>
    <div class="blog-bar-chart">
        <div class="blog-bar-chart__row">
            <div class="blog-bar-chart__label">Cancelada escriturada</div>
            <div class="blog-bar-chart__track"><div class="blog-bar-chart__fill blog-bar-chart__fill--amber" style="--bar-value: 88"></div></div>
            <div class="blog-bar-chart__value">alto</div>
        </div>
        <div class="blog-bar-chart__row">
            <div class="blog-bar-chart__label">Denegada escriturada</div>
            <div class="blog-bar-chart__track"><div class="blog-bar-chart__fill blog-bar-chart__fill--amber" style="--bar-value: 96"></div></div>
            <div class="blog-bar-chart__value">crítico</div>
        </div>
        <div class="blog-bar-chart__row">
            <div class="blog-bar-chart__label">Emitente baixado</div>
            <div class="blog-bar-chart__track"><div class="blog-bar-chart__fill" style="--bar-value: 82"></div></div>
            <div class="blog-bar-chart__value">alto</div>
        </div>
    </div>
</div>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">Onde isso costuma escapar</h2>

<ul class="list-disc pl-6 space-y-2 my-4">
    <li>escrituração feita com XML antigo, sem reconsultar SEFAZ</li>
    <li>cancelamentos comunicados por e-mail e não registrados no sistema</li>
    <li>pacotes de XML vindos do cliente sem checagem</li>
    <li>divergência só detectada quando o fisco envia malha</li>
</ul>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">Conclusão</h2>

<p>Detectar nota fria e cancelada não é projeto — é rotina. Com verificação em lote contra a SEFAZ antes do fechamento, o escritório trata a exceção quando ela ainda é trabalho de revisão, não autuação.</p>
