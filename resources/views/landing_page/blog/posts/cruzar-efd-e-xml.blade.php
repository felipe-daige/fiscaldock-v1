<p>Cruzar EFD e XML é uma das práticas mais valiosas para o contador que quer sair da revisão superficial. O XML mostra o documento de origem. A EFD mostra o que foi efetivamente escriturado. Quando os dois lados não conversam, o risco aparece.</p>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">Por que esse cruzamento importa</h2>

<p>Muita divergência passa despercebida porque o time revisa apenas o arquivo final da obrigação. O problema é que a EFD pode estar tecnicamente montada, mas ainda assim carregar omissões, classificações incorretas ou valores inconsistentes em relação ao documento fiscal.</p>

<p>O cruzamento ajuda a localizar:</p>

<ul class="list-disc pl-6 space-y-2 my-4">
    <li>notas que existem no XML e não entraram na escrituração</li>
    <li>documentos escriturados com valor diferente do documento de origem</li>
    <li>CFOP ou CST divergente da operação efetiva</li>
    <li>itens com NCM sensível ou padrão tributário fora do histórico</li>
</ul>

<div class="blog-table-wrap">
    <table class="blog-table">
        <thead>
            <tr>
                <th>Campo</th>
                <th>O que comparar</th>
                <th>Sinal de risco</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Chave/Número</strong></td>
                <td>XML recebido versus documento escriturado</td>
                <td>Nota não escriturada ou período incorreto</td>
            </tr>
            <tr>
                <td><strong>Participante</strong></td>
                <td>Emitente/destinatário versus cadastro</td>
                <td>Fornecedor ou cliente inconsistentes</td>
            </tr>
            <tr>
                <td><strong>Valor</strong></td>
                <td>Total do XML versus valor contábil</td>
                <td>Base ou documento divergente</td>
            </tr>
            <tr>
                <td><strong>CFOP/CST</strong></td>
                <td>Natureza da operação versus classificação</td>
                <td>Tributação incoerente</td>
            </tr>
        </tbody>
    </table>
</div>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">O que comparar primeiro</h2>

<p>Nem sempre é necessário começar pelo item. O melhor retorno costuma vir de uma sequência simples:</p>

<ol class="list-decimal pl-6 space-y-3 my-4">
    <li><strong>Chave e número do documento:</strong> confirma se a nota foi capturada e escriturada.</li>
    <li><strong>Participante:</strong> verifica se o emitente ou destinatário bate com o cadastro usado na EFD.</li>
    <li><strong>Valor total e base de imposto:</strong> localiza rapidamente documentos com risco financeiro.</li>
    <li><strong>CFOP e CST:</strong> identifica classificação incoerente.</li>
    <li><strong>Itens sensíveis:</strong> foca em mercadorias com maior impacto de tributação ou recorrência de erro.</li>
</ol>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">Onde costumam aparecer as divergências</h2>

<p>Os cenários mais comuns no escritório contábil são:</p>

<ul class="list-disc pl-6 space-y-2 my-4">
    <li>XML recebido, mas não escriturado no período correto</li>
    <li>nota de devolução com CFOP inadequado</li>
    <li>documento de fornecedor com cadastro inconsistente</li>
    <li>valor contábil ou base tributável diferente do XML</li>
    <li>itens com NCM reaproveitado de cadastro antigo sem revisão</li>
</ul>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">Como operacionalizar sem criar mais retrabalho</h2>

<p>O erro de muitos escritórios é tentar fazer esse cruzamento de forma artesanal, documento por documento. Isso não escala. O caminho mais eficiente é transformar o processo em filtro de exceções:</p>

<ul class="list-disc pl-6 space-y-2 my-4">
    <li>consolidar XML e EFD em uma base única de conferência</li>
    <li>comparar automaticamente os campos-chave</li>
    <li>priorizar apenas divergências materiais ou recorrentes</li>
    <li>registrar padrões de erro por cliente para a revisão do mês seguinte</li>
</ul>

<div class="blog-data-card">
    <div class="blog-data-card__title">Fluxo ideal de conferência</div>
    <div class="blog-step-flow md:grid-cols-4">
        <div class="blog-step-flow__item">
            <div class="blog-step-flow__step">1</div>
            <div class="blog-step-flow__title">Captura</div>
            <div class="blog-step-flow__text">Consolidar XML e EFD em uma base única.</div>
        </div>
        <div class="blog-step-flow__item">
            <div class="blog-step-flow__step">2</div>
            <div class="blog-step-flow__title">Comparação</div>
            <div class="blog-step-flow__text">Confrontar chave, participante, valores e classificação.</div>
        </div>
        <div class="blog-step-flow__item">
            <div class="blog-step-flow__step">3</div>
            <div class="blog-step-flow__title">Exceções</div>
            <div class="blog-step-flow__text">Filtrar apenas divergências materiais ou recorrentes.</div>
        </div>
        <div class="blog-step-flow__item">
            <div class="blog-step-flow__step">4</div>
            <div class="blog-step-flow__title">Revisão</div>
            <div class="blog-step-flow__text">Atuar tecnicamente só onde existe risco real.</div>
        </div>
    </div>
</div>

<h2 class="text-2xl font-bold text-gray-900 mt-10 mb-4">Conclusão</h2>

<p>Cruzar EFD e XML não é excesso de zelo. É o atalho mais consistente para detectar problemas antes da transmissão ou antes que o fisco faça esse mesmo confronto. O contador continua sendo quem interpreta a regra, mas a comparação dos dados precisa acontecer com método.</p>
