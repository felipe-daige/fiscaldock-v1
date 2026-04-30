<?php

namespace App\Services\Clearance\Comparacao\Adapters;

use App\Models\XmlNota;
use App\Services\Clearance\Comparacao\ItemNormalizado;
use App\Services\Clearance\Comparacao\NotaNormalizada;
use App\Services\Clearance\Comparacao\SefazSource;

final class XmlNotaSefazNfeAdapter implements SefazSource
{
    public function __construct(private readonly XmlNota $nota) {}

    public function carregar(): NotaNormalizada
    {
        $payload = is_array($this->nota->payload) ? $this->nota->payload : [];
        $clearance = $payload['nfe_clearance'] ?? [];
        $totais = $clearance['totais'] ?? [];
        $emit = $clearance['emitente'] ?? [];
        $dest = $clearance['destinatario'] ?? [];
        $eventos = $clearance['eventos'] ?? [];
        $autorizacao = $eventos[0] ?? [];

        $modelo = isset($clearance['modelo']) ? (string) $clearance['modelo']
            : (strlen((string) $this->nota->nfe_id) === 44 ? substr((string) $this->nota->nfe_id, 20, 2) : null);

        return new NotaNormalizada(
            chave: (string) $this->nota->nfe_id,
            tipoDocumento: 'NFE',
            header: [
                'numero' => isset($clearance['numero']) ? (string) $clearance['numero'] : ($this->nota->numero_nota !== null ? (string) $this->nota->numero_nota : null),
                'serie' => isset($clearance['serie']) ? (string) $clearance['serie'] : ($this->nota->serie !== null ? (string) $this->nota->serie : null),
                'data_emissao' => $this->extrairDataEmissao($clearance),
                'modelo' => $modelo,
                'natureza_operacao' => $clearance['natureza_operacao'] ?? null,
            ],
            metaSefaz: [
                'situacao' => $this->nota->situacao_sefaz ?? ($clearance['status'] ?? null),
                'protocolo' => $autorizacao['protocolo'] ?? null,
                'data_autorizacao' => $autorizacao['data_autorizacao'] ?? null,
                'verificado_em' => $this->nota->verificado_sefaz_em?->format('Y-m-d H:i:s'),
                'consulta_lote_id' => $this->nota->consulta_lote_id,
            ],
            partes: [
                'emit' => [
                    'cnpj' => $emit['cnpj'] ?? $this->nota->emit_cnpj,
                    'razao_social' => $emit['nome'] ?? $this->nota->emit_razao_social,
                    'ie' => $emit['ie'] ?? null,
                    'uf' => $emit['uf'] ?? $this->nota->emit_uf,
                ],
                'dest' => [
                    'cnpj' => $dest['cnpj'] ?? $this->nota->dest_cnpj,
                    'razao_social' => $dest['nome'] ?? $this->nota->dest_razao_social,
                    'ie' => $dest['ie'] ?? null,
                    'uf' => $dest['uf'] ?? $this->nota->dest_uf,
                ],
            ],
            totais: [
                'valor_total' => isset($clearance['valor_total']) ? (float) $clearance['valor_total'] : (float) $this->nota->valor_total,
                'base_icms' => $this->valor($totais, 'base_calculo_icms'),
                'valor_icms' => $this->valor($totais, 'valor_icms'),
                'valor_ipi' => $this->valor($totais, 'valor_ipi'),
                'valor_pis' => $this->valor($totais, 'valor_pis'),
                'valor_cofins' => $this->valor($totais, 'valor_cofins'),
                'valor_frete' => $this->valor($totais, 'valor_frete'),
                'valor_seguro' => $this->valor($totais, 'valor_seguro'),
                'valor_desconto' => $this->valor($totais, 'valor_descontos'),
            ],
            itens: $this->mapearItens($clearance['produtos'] ?? []),
            origemLabel: $this->origemLabel(),
            camposNaoRetornados: [
                'itens' => ['cProd', 'ncm', 'cfop', 'uCom'],
            ],
        );
    }

    public function origemLabel(): string
    {
        $data = $this->nota->verificado_sefaz_em?->format('d/m/Y H:i')
            ?? $this->nota->created_at?->format('d/m/Y H:i')
            ?? '—';

        return "SEFAZ NF-e ({$data})";
    }

    /**
     * @param  array<string, mixed>  $totais
     */
    private function valor(array $totais, string $chave): ?float
    {
        $normalizado = $totais['normalizado_'.$chave] ?? null;
        if ($normalizado !== null && $normalizado !== '' && $normalizado !== 0 && $normalizado !== 0.0) {
            return (float) $normalizado;
        }
        $bruto = $totais[$chave] ?? null;
        if ($bruto === null || $bruto === '') {
            return $normalizado === null ? null : (float) $normalizado;
        }

        return (float) str_replace(',', '.', (string) $bruto);
    }

    private function extrairDataEmissao(array $clearance): ?string
    {
        $data = $clearance['data_emissao'] ?? null;
        if ($data) {
            try {
                return (new \DateTimeImmutable((string) $data))->format('Y-m-d');
            } catch (\Throwable) {
                return (string) $data;
            }
        }

        return $this->nota->data_emissao?->format('Y-m-d');
    }

    /**
     * @param  array<int, array<string, mixed>>  $produtos
     * @return array<int, ItemNormalizado>
     */
    private function mapearItens(array $produtos): array
    {
        return array_values(array_map(function (array $p, int $idx): ItemNormalizado {
            return new ItemNormalizado(
                cProd: null,
                nItem: (int) ($p['num'] ?? ($idx + 1)),
                xProd: isset($p['descricao']) ? (string) $p['descricao'] : null,
                ncm: null,
                cfop: null,
                qCom: $this->parseDecimal($p['quantidade'] ?? null),
                uCom: isset($p['unidade_comercial']) ? (string) $p['unidade_comercial'] : null,
                vUnCom: $this->parseDecimal($p['valor_unidade'] ?? null),
                vProd: $this->parseDecimal($p['valor_produto'] ?? null),
            );
        }, $produtos, array_keys($produtos)));
    }

    private function parseDecimal(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (float) str_replace(',', '.', (string) $valor);
    }
}
