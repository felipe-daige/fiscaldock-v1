<?php

namespace App\Services\Clearance\Comparacao;

final class NotaNormalizada
{
    /**
     * @param  array<string, mixed>  $header  ['numero', 'serie', 'data_emissao', 'modelo', 'natureza_operacao']
     * @param  array<string, mixed>  $metaSefaz  SEFAZ-only: ['situacao', 'protocolo', 'data_autorizacao']. Vazio no declarado.
     * @param  array<string, array<string, mixed>>  $partes  ['emit' => [...], 'dest' => [...], 'tomador' => [...] (CT-e), 'remetente' => [...] (CT-e)]
     * @param  array<string, ?float>  $totais  ['valor_total', 'base_icms', 'valor_icms', 'valor_ipi', 'valor_pis', 'valor_cofins', 'valor_frete', 'valor_seguro', 'valor_desconto']
     * @param  array<int, ItemNormalizado|ComponenteCte>  $itens
     * @param  array<string, array<int, string>>  $camposNaoRetornados  Por seção (header/totais/itens), lista de chaves que a fonte não retorna — usadas para neutralizar comparação.
     */
    public function __construct(
        public readonly string $chave,
        public readonly string $tipoDocumento,
        public readonly array $header,
        public readonly array $metaSefaz,
        public readonly array $partes,
        public readonly array $totais,
        public readonly array $itens,
        public readonly string $origemLabel,
        public readonly array $camposNaoRetornados = [],
    ) {}
}
