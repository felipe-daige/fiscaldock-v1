<?php

namespace App\Services\Clearance\Comparacao\Adapters;

use App\Models\XmlNota;
use App\Services\Clearance\Comparacao\ComponenteCte;
use App\Services\Clearance\Comparacao\NotaNormalizada;
use App\Services\Clearance\Comparacao\SefazSource;

final class XmlNotaSefazCteAdapter implements SefazSource
{
    public function __construct(private readonly XmlNota $nota) {}

    public function carregar(): NotaNormalizada
    {
        $payload = is_array($this->nota->payload) ? $this->nota->payload : [];
        $clearance = $payload['cte_clearance'] ?? [];
        $impostos = $clearance['totais']['impostos'] ?? [];
        $eventos = $clearance['eventos'] ?? [];
        $autorizacao = $eventos[0] ?? [];

        $modelo = isset($clearance['modelo']) ? (string) $clearance['modelo']
            : (strlen((string) $this->nota->nfe_id) === 44 ? substr((string) $this->nota->nfe_id, 20, 2) : null);

        return new NotaNormalizada(
            chave: (string) $this->nota->nfe_id,
            tipoDocumento: 'CTE',
            header: [
                'numero' => isset($clearance['numero']) ? (string) $clearance['numero'] : ($this->nota->numero_nota !== null ? (string) $this->nota->numero_nota : null),
                'serie' => isset($clearance['serie']) ? (string) $clearance['serie'] : ($this->nota->serie !== null ? (string) $this->nota->serie : null),
                'data_emissao' => $this->extrairDataEmissao($clearance),
                'modelo' => $modelo,
                'natureza_operacao' => $clearance['natureza_operacao'] ?? null,
                'cfop' => $clearance['cfop'] ?? null,
                'modal' => $clearance['modal'] ?? null,
                'tipo_servico' => $clearance['tipo_servico'] ?? null,
            ],
            metaSefaz: [
                'situacao' => $this->nota->situacao_sefaz ?? ($clearance['status'] ?? null),
                'protocolo' => $autorizacao['protocolo'] ?? null,
                'data_autorizacao' => $autorizacao['data_autorizacao'] ?? null,
                'verificado_em' => $this->nota->verificado_sefaz_em?->format('Y-m-d H:i:s'),
                'consulta_lote_id' => $this->nota->consulta_lote_id,
            ],
            partes: [
                'emit' => $this->parteCte($clearance['emitente'] ?? null, $this->nota->emit_cnpj, $this->nota->emit_razao_social, $this->nota->emit_uf),
                'dest' => $this->parteCte($clearance['destinatario'] ?? null, $this->nota->dest_cnpj, $this->nota->dest_razao_social, $this->nota->dest_uf),
                'tomador' => $this->parteCte($clearance['tomador'] ?? null),
                'remetente' => $this->parteCte($clearance['remetente'] ?? null),
                'expedidor' => $this->parteCte($clearance['expedidor'] ?? null),
                'recebedor' => $this->parteCte($clearance['recebedor'] ?? null),
            ],
            totais: [
                'valor_total' => isset($clearance['valor_prestacao']) ? (float) $clearance['valor_prestacao'] : (float) $this->nota->valor_total,
                'valor_carga' => isset($clearance['valor_carga']) ? (float) $clearance['valor_carga'] : null,
                'base_icms' => isset($impostos['normalizado_base_calculo_icms']) && $impostos['normalizado_base_calculo_icms'] !== 0
                    ? (float) $impostos['normalizado_base_calculo_icms']
                    : null,
                'valor_icms' => isset($impostos['normalizado_valor_icms']) && $impostos['normalizado_valor_icms'] !== 0
                    ? (float) $impostos['normalizado_valor_icms']
                    : null,
                'valor_ipi' => null,
                'valor_pis' => null,
                'valor_cofins' => null,
                'valor_frete' => null,
                'valor_seguro' => null,
                'valor_desconto' => null,
            ],
            itens: $this->mapearComponentes($clearance['componentes'] ?? []),
            origemLabel: $this->origemLabel(),
        );
    }

    public function origemLabel(): string
    {
        $data = $this->nota->verificado_sefaz_em?->format('d/m/Y H:i')
            ?? $this->nota->created_at?->format('d/m/Y H:i')
            ?? '—';

        return "SEFAZ CT-e ({$data})";
    }

    /**
     * @param  array<string, mixed>|null  $parte
     * @return array<string, string|null>
     */
    private function parteCte(?array $parte, ?string $cnpjFallback = null, ?string $razaoFallback = null, ?string $ufFallback = null): array
    {
        return [
            'cnpj' => $parte['cnpj'] ?? $parte['cpf'] ?? $cnpjFallback,
            'razao_social' => $parte['nome'] ?? $razaoFallback,
            'ie' => $parte['ie'] ?? null,
            'uf' => $parte['uf'] ?? $ufFallback,
            'municipio' => $parte['municipio'] ?? null,
        ];
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
     * @param  array<int, array<string, mixed>>  $componentes
     * @return array<int, ComponenteCte>
     */
    private function mapearComponentes(array $componentes): array
    {
        return array_values(array_map(function (array $c): ComponenteCte {
            return new ComponenteCte(
                nome: isset($c['nome']) ? (string) $c['nome'] : '',
                valor: $this->parseDecimal($c['valor'] ?? null) ?? 0.0,
            );
        }, $componentes));
    }

    private function parseDecimal(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (float) str_replace(',', '.', (string) $valor);
    }
}
