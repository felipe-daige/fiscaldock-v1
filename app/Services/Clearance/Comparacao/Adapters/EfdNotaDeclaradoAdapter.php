<?php

namespace App\Services\Clearance\Comparacao\Adapters;

use App\Models\EfdNota;
use App\Services\Clearance\Comparacao\DeclaradoSource;
use App\Services\Clearance\Comparacao\ItemNormalizado;
use App\Services\Clearance\Comparacao\NotaNormalizada;
use Illuminate\Support\Facades\DB;

final class EfdNotaDeclaradoAdapter implements DeclaradoSource
{
    public function __construct(private readonly EfdNota $nota) {}

    public function carregar(): NotaNormalizada
    {
        $modelo = $this->resolverModelo();
        $tipoDocumento = match ($modelo) {
            '57' => 'CTE',
            '65' => 'NFCE',
            default => 'NFE',
        };

        [$emit, $dest] = $this->resolverPartes();

        return new NotaNormalizada(
            chave: (string) $this->nota->chave_acesso,
            tipoDocumento: $tipoDocumento,
            header: [
                'numero' => $this->nota->numero !== null ? (string) $this->nota->numero : null,
                'serie' => $this->nota->serie !== null ? (string) $this->nota->serie : null,
                'data_emissao' => $this->nota->data_emissao?->format('Y-m-d') ?? (string) $this->nota->data_emissao,
                'modelo' => $modelo,
                'natureza_operacao' => null,
            ],
            metaSefaz: [],
            partes: [
                'emit' => $emit,
                'dest' => $dest,
            ],
            totais: [
                'valor_total' => (float) $this->nota->valor_total,
                'base_icms' => null,
                'valor_icms' => null,
                'valor_ipi' => null,
                'valor_pis' => null,
                'valor_cofins' => null,
                'valor_frete' => null,
                'valor_seguro' => null,
                'valor_desconto' => $this->nota->valor_desconto !== null ? (float) $this->nota->valor_desconto : null,
            ],
            itens: $this->mapearItens(),
            origemLabel: $this->origemLabel(),
        );
    }

    public function origemLabel(): string
    {
        $tipo = $this->nota->origem_arquivo === 'contribuicoes' ? 'EFD PIS/COFINS' : 'EFD ICMS/IPI';
        $data = $this->nota->data_emissao?->format('m/Y') ?? '—';

        return "{$tipo} ({$data})";
    }

    private function resolverModelo(): ?string
    {
        if ($this->nota->modelo) {
            return (string) $this->nota->modelo;
        }
        if (strlen((string) $this->nota->chave_acesso) === 44) {
            return substr((string) $this->nota->chave_acesso, 20, 2);
        }

        return null;
    }

    /**
     * Para tipo_operacao='saida' (venda): emit=cliente (empresa do user), dest=participante (cliente externo).
     * Para tipo_operacao='entrada' (compra): emit=participante (fornecedor), dest=cliente (empresa do user).
     *
     * @return array{0: array<string, string|null>, 1: array<string, string|null>}
     */
    private function resolverPartes(): array
    {
        $cliente = $this->nota->cliente;
        $participante = $this->nota->participante;

        $clienteParte = [
            'cnpj' => $cliente?->documento,
            'razao_social' => $cliente?->razao_social,
            'ie' => $cliente?->inscricao_estadual,
            'uf' => $cliente?->uf,
        ];
        $participanteParte = [
            'cnpj' => $participante?->documento,
            'razao_social' => $participante?->razao_social,
            'ie' => $participante?->inscricao_estadual,
            'uf' => $participante?->uf,
        ];

        if ($this->nota->tipo_operacao === 'entrada') {
            return [$participanteParte, $clienteParte];
        }

        return [$clienteParte, $participanteParte];
    }

    /**
     * @return array<int, ItemNormalizado>
     */
    private function mapearItens(): array
    {
        $itens = $this->nota->itens()->orderBy('numero_item')->get();
        if ($itens->isEmpty()) {
            return [];
        }

        $codigos = $itens->pluck('codigo_item')->filter()->unique()->values()->all();
        $catalogo = $codigos === []
            ? collect()
            : DB::table('efd_catalogo_itens')
                ->where('user_id', $this->nota->user_id)
                ->whereIn('cod_item', $codigos)
                ->select('cod_item', 'cod_ncm')
                ->get()
                ->keyBy('cod_item');

        return $itens->map(function ($item) use ($catalogo): ItemNormalizado {
            $ncm = $item->codigo_item !== null ? ($catalogo[$item->codigo_item]->cod_ncm ?? null) : null;

            return new ItemNormalizado(
                cProd: $item->codigo_item !== null ? (string) $item->codigo_item : null,
                nItem: (int) $item->numero_item,
                xProd: $item->descricao !== null ? (string) $item->descricao : null,
                ncm: $ncm !== null ? (string) $ncm : null,
                cfop: $item->cfop !== null ? (string) $item->cfop : null,
                qCom: $item->quantidade !== null ? (float) $item->quantidade : null,
                uCom: $item->unidade_medida !== null ? (string) $item->unidade_medida : null,
                vUnCom: $item->valor_unitario !== null ? (float) $item->valor_unitario : null,
                vProd: $item->valor_total !== null ? (float) $item->valor_total : null,
            );
        })->all();
    }
}
