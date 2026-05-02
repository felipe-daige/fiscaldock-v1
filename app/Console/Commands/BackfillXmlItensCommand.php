<?php

namespace App\Console\Commands;

use App\Models\XmlNota;
use App\Models\XmlNotaItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BackfillXmlItensCommand extends Command
{
    protected $signature = 'xml:backfill-itens
                            {--user= : ID do usuario para limitar o backfill}
                            {--force : Reprocessa notas que ja tem itens (apaga e recria)}';

    protected $description = 'Popula xml_notas_itens a partir de xml_notas.payload->det (achata <det> do XML pra colunas tipadas)';

    public function handle(): int
    {
        $query = XmlNota::query()->whereNotNull('payload');

        if ($userId = $this->option('user')) {
            $query->where('user_id', (int) $userId);
        }

        if (! $this->option('force')) {
            $query->whereNotExists(function ($q): void {
                $q->select(DB::raw(1))
                    ->from('xml_notas_itens')
                    ->whereColumn('xml_notas_itens.xml_nota_id', 'xml_notas.id');
            });
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Nada a processar.');

            return 0;
        }

        $this->info("Processando {$total} nota(s)...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $totais = ['itens_inseridos' => 0, 'notas_sem_det' => 0, 'erros' => 0];

        $query->orderBy('id')->chunkById(200, function ($notas) use ($bar, &$totais): void {
            foreach ($notas as $nota) {
                try {
                    $itens = $this->achatarItens($nota);

                    if ($itens === []) {
                        $totais['notas_sem_det']++;
                        $bar->advance();

                        continue;
                    }

                    DB::transaction(function () use ($nota, $itens, &$totais): void {
                        if ($this->option('force')) {
                            XmlNotaItem::where('xml_nota_id', $nota->id)->delete();
                        }

                        // Idempotência: ON CONFLICT por (xml_nota_id, numero_item) atualiza
                        $now = now();
                        $rows = array_map(
                            fn ($row) => $row + ['created_at' => $now, 'updated_at' => $now],
                            $itens
                        );

                        DB::table('xml_notas_itens')->upsert(
                            $rows,
                            ['xml_nota_id', 'numero_item'],
                            array_keys($rows[0])
                        );

                        $totais['itens_inseridos'] += count($rows);
                    });
                } catch (Throwable $e) {
                    $totais['erros']++;
                    $this->warn("Erro id={$nota->id}: {$e->getMessage()}");
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info(
            "Itens inseridos/atualizados: {$totais['itens_inseridos']} | "
            ."Notas sem det: {$totais['notas_sem_det']} | "
            ."Erros: {$totais['erros']}"
        );

        return 0;
    }

    /**
     * Achata payload->det numa lista de arrays prontos pra insert em xml_notas_itens.
     *
     * @return array<int, array<string, mixed>>
     */
    private function achatarItens(XmlNota $nota): array
    {
        $payload = $nota->payload ?? [];
        $det = $payload['det'] ?? null;

        if (! is_array($det) || $det === []) {
            return [];
        }

        // det pode vir como objeto único quando NF-e tem só 1 produto
        if (isset($det['prod'])) {
            $det = [$det];
        }

        $itens = [];

        foreach (array_values($det) as $idx => $itemRaw) {
            if (! is_array($itemRaw)) {
                continue;
            }

            $itens[] = $this->converterItem($itemRaw, $idx + 1, $nota);
        }

        return $itens;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function converterItem(array $item, int $idxFallback, XmlNota $nota): array
    {
        $prod = is_array($item['prod'] ?? null) ? $item['prod'] : [];
        $imposto = is_array($item['imposto'] ?? null) ? $item['imposto'] : [];

        $icms = $this->extrairIcms($imposto['ICMS'] ?? null);
        $pis = $this->extrairPis($imposto['PIS'] ?? null);
        $cofins = $this->extrairCofins($imposto['COFINS'] ?? null);
        $ipi = $this->extrairIpi($imposto['IPI'] ?? null);

        $numeroItem = isset($item['nItem']) ? (int) $item['nItem'] : $idxFallback;
        $ean = $prod['cEAN'] ?? null;
        if (is_string($ean) && strtoupper(trim($ean)) === 'SEM') {
            $ean = null;
        }

        $metadados = $this->extrairMetadados($item, $imposto);

        return [
            'xml_nota_id' => $nota->id,
            'user_id' => $nota->user_id,
            'numero_item' => $numeroItem,
            'codigo_item' => (string) ($prod['cProd'] ?? ''),
            'ean' => $ean,
            'descricao' => (string) ($prod['xProd'] ?? ''),
            'ncm' => $prod['NCM'] ?? null,
            'cest' => $prod['CEST'] ?? null,
            'cfop' => (string) ($prod['CFOP'] ?? ''),
            'unidade_medida' => $prod['uCom'] ?? null,
            'quantidade' => $this->toDecimal($prod['qCom'] ?? 0),
            'valor_unitario' => isset($prod['vUnCom']) ? $this->toDecimal($prod['vUnCom']) : null,
            'valor_total' => $this->toDecimal($prod['vProd'] ?? 0),
            'cst_icms' => $icms['cst'],
            'aliquota_icms' => $icms['aliquota'],
            'valor_icms' => $icms['valor'],
            'aliquota_icms_st' => $icms['aliquota_st'],
            'valor_icms_st' => $icms['valor_st'],
            'cst_pis' => $pis['cst'],
            'aliquota_pis' => $pis['aliquota'],
            'valor_pis' => $pis['valor'],
            'cst_cofins' => $cofins['cst'],
            'aliquota_cofins' => $cofins['aliquota'],
            'valor_cofins' => $cofins['valor'],
            'cst_ipi' => $ipi['cst'],
            'aliquota_ipi' => $ipi['aliquota'],
            'valor_ipi' => $ipi['valor'],
            'metadados' => $metadados !== [] ? json_encode($metadados) : null,
        ];
    }

    /**
     * ICMS pode vir aninhado em ICMS00, ICMS10, ..., ICMSSN101 (Simples Nacional).
     * Pega a primeira variante populada.
     *
     * @return array{cst: ?string, aliquota: ?float, valor: ?float, aliquota_st: ?float, valor_st: ?float}
     */
    private function extrairIcms(mixed $icmsBloco): array
    {
        $vazio = ['cst' => null, 'aliquota' => null, 'valor' => null, 'aliquota_st' => null, 'valor_st' => null];

        if (! is_array($icmsBloco)) {
            return $vazio;
        }

        foreach ($icmsBloco as $dados) {
            if (! is_array($dados)) {
                continue;
            }

            // Regime normal usa CST; Simples Nacional usa CSOSN. Mesmo campo no schema.
            $cst = $dados['CST'] ?? $dados['CSOSN'] ?? null;

            if ($cst === null && ! isset($dados['pICMS'], $dados['vICMS'])) {
                continue;
            }

            return [
                'cst' => $cst !== null ? (string) $cst : null,
                'aliquota' => isset($dados['pICMS']) ? $this->toDecimal($dados['pICMS']) : null,
                'valor' => isset($dados['vICMS']) ? $this->toDecimal($dados['vICMS']) : null,
                'aliquota_st' => isset($dados['pICMSST']) ? $this->toDecimal($dados['pICMSST']) : null,
                'valor_st' => isset($dados['vICMSST']) ? $this->toDecimal($dados['vICMSST']) : null,
            ];
        }

        return $vazio;
    }

    /**
     * @return array{cst: ?string, aliquota: ?float, valor: ?float}
     */
    private function extrairPis(mixed $pisBloco): array
    {
        return $this->extrairTributoSimples($pisBloco, 'pPIS', 'vPIS');
    }

    /**
     * @return array{cst: ?string, aliquota: ?float, valor: ?float}
     */
    private function extrairCofins(mixed $cofinsBloco): array
    {
        return $this->extrairTributoSimples($cofinsBloco, 'pCOFINS', 'vCOFINS');
    }

    /**
     * IPI vem em IPITrib/IPINT — só IPITrib tem alíquota/valor.
     *
     * @return array{cst: ?string, aliquota: ?float, valor: ?float}
     */
    private function extrairIpi(mixed $ipiBloco): array
    {
        $vazio = ['cst' => null, 'aliquota' => null, 'valor' => null];

        if (! is_array($ipiBloco)) {
            return $vazio;
        }

        foreach ($ipiBloco as $dados) {
            if (! is_array($dados)) {
                continue;
            }

            $cst = $dados['CST'] ?? null;
            if ($cst === null && ! isset($dados['pIPI'])) {
                continue;
            }

            return [
                'cst' => $cst !== null ? (string) $cst : null,
                'aliquota' => isset($dados['pIPI']) ? $this->toDecimal($dados['pIPI']) : null,
                'valor' => isset($dados['vIPI']) ? $this->toDecimal($dados['vIPI']) : null,
            ];
        }

        return $vazio;
    }

    /**
     * @return array{cst: ?string, aliquota: ?float, valor: ?float}
     */
    private function extrairTributoSimples(mixed $bloco, string $aliquotaKey, string $valorKey): array
    {
        $vazio = ['cst' => null, 'aliquota' => null, 'valor' => null];

        if (! is_array($bloco)) {
            return $vazio;
        }

        foreach ($bloco as $dados) {
            if (! is_array($dados)) {
                continue;
            }

            $cst = $dados['CST'] ?? null;
            if ($cst === null && ! isset($dados[$aliquotaKey], $dados[$valorKey])) {
                continue;
            }

            return [
                'cst' => $cst !== null ? (string) $cst : null,
                'aliquota' => isset($dados[$aliquotaKey]) ? $this->toDecimal($dados[$aliquotaKey]) : null,
                'valor' => isset($dados[$valorKey]) ? $this->toDecimal($dados[$valorKey]) : null,
            ];
        }

        return $vazio;
    }

    /**
     * Coleta nós exóticos do <det> que não cabem nas colunas tipadas.
     *
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $imposto
     * @return array<string, mixed>
     */
    private function extrairMetadados(array $item, array $imposto): array
    {
        $metadados = [];

        foreach (['comb', 'med', 'arma', 'rastro', 'DI', 'DIs', 'detExport', 'veicProd', 'infAdProd'] as $key) {
            if (isset($item[$key]) || isset($item['prod'][$key])) {
                $metadados[$key] = $item[$key] ?? $item['prod'][$key];
            }
        }

        // ICMS específico por UF (ICMSST/ICMSUFDest) entra todo aqui pra não inflar colunas
        if (isset($imposto['ICMSUFDest'])) {
            $metadados['ICMSUFDest'] = $imposto['ICMSUFDest'];
        }

        return $metadados;
    }

    /**
     * NF-e usa ponto decimal (padrão internacional). Garante float.
     */
    private function toDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
