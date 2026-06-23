<?php

namespace App\Services\Efd;

use App\Models\EfdImportacao;
use App\Support\CsvExport;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Exporta tudo que foi extraído de uma importação EFD como um ZIP de CSVs
 * (um arquivo por dataset). Colunas dirigidas pelo schema da tabela —
 * fidelidade total, sem hardcode. Datasets vazios são pulados.
 */
class EfdPlanilhaExportService
{
    /** Datasets oferecidos no modal (key => label). Fonte única view + backend. */
    public const DATASETS = [
        'notas' => 'Notas (cabeçalho)',
        'notas_itens' => 'Itens das notas',
        'participantes' => 'Participantes',
        'apuracao_pis_cofins' => 'Apuração PIS/COFINS',
        'apuracao_icms' => 'Apuração ICMS/IPI',
        'retencoes_fonte' => 'Retenções na fonte',
        'catalogo_itens' => 'Catálogo de itens',
    ];

    /** key do dataset => tabela física. */
    private const TABELAS = [
        'notas' => 'efd_notas',
        'notas_itens' => 'efd_notas_itens',
        'participantes' => 'participantes',
        'apuracao_pis_cofins' => 'efd_apuracoes_contribuicoes',
        'apuracao_icms' => 'efd_apuracoes_icms',
        'retencoes_fonte' => 'efd_retencoes_fonte',
        'catalogo_itens' => 'efd_catalogo_itens',
    ];

    /**
     * Gera o ZIP em arquivo temporário e devolve o caminho.
     * Quem chama é responsável por enviar/remover o arquivo.
     */
    public function zipPath(EfdImportacao $imp, int $userId): string
    {
        $arquivos = $this->arquivos($imp, $userId);

        $path = tempnam(sys_get_temp_dir(), 'efdexport').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if (empty($arquivos)) {
            $zip->addFromString('LEIA-ME.txt', 'Nenhum dado extraído para esta importação.');
        }
        foreach ($arquivos as $nome => $conteudo) {
            $zip->addFromString($nome, $conteudo);
        }

        $zip->close();

        return $path;
    }

    public function nomeZip(EfdImportacao $imp): string
    {
        return $this->prefixo($imp).'.zip';
    }

    public function nomeCsv(EfdImportacao $imp, string $key): string
    {
        return $this->prefixo($imp).'_'.$key.'.csv';
    }

    private function prefixo(EfdImportacao $imp): string
    {
        $tipo = Str::slug($imp->tipo_efd ?: 'efd');
        $periodo = $imp->periodo_inicio ? $imp->periodo_inicio->format('Y-m') : 'sem-periodo';

        return "efd_{$tipo}_{$periodo}_{$imp->id}";
    }

    /**
     * @return array<string,string> nome do arquivo => conteúdo CSV (sem datasets vazios)
     */
    public function arquivos(EfdImportacao $imp, int $userId): array
    {
        $escopos = $this->escopos($imp);
        $out = [];

        foreach (self::TABELAS as $key => $tabela) {
            $csv = $this->dump($tabela, $escopos[$key]);
            if ($csv !== null) {
                $out[$key.'.csv'] = $csv;
            }
        }

        return $out;
    }

    /** CSV de um único dataset. Sempre com cabeçalho (header-only se vazio). */
    public function csvDataset(EfdImportacao $imp, int $userId, string $key): string
    {
        $tabela = self::TABELAS[$key];
        $colunas = Schema::getColumnListing($tabela);
        $rows = $this->escopos($imp)[$key](DB::table($tabela))->get();
        $linhas = $rows->map(fn ($r) => array_map(fn ($c) => $r->{$c}, $colunas))->all();

        return $this->csv($colunas, $linhas);
    }

    /** @return array<string,Closure> key do dataset => escopo da query. */
    private function escopos(EfdImportacao $imp): array
    {
        $notaIds = fn ($q) => $q->select('id')->from('efd_notas')->where('importacao_id', $imp->id);

        return [
            'notas' => fn ($q) => $q->where('importacao_id', $imp->id),
            'notas_itens' => fn ($q) => $q->whereIn('efd_nota_id', $notaIds),
            'participantes' => $this->escopoParticipantes($imp),
            'apuracao_pis_cofins' => fn ($q) => $q->where('importacao_id', $imp->id),
            'apuracao_icms' => fn ($q) => $q->where('importacao_id', $imp->id),
            'retencoes_fonte' => fn ($q) => $q->where('importacao_id', $imp->id),
            'catalogo_itens' => fn ($q) => $q->where('importacao_id', $imp->id),
        ];
    }

    /** Dual-path: participante_ids (n8n v2) ou importacao_efd_id (legado). */
    private function escopoParticipantes(EfdImportacao $imp): Closure
    {
        if (! empty($imp->participante_ids)) {
            return fn ($q) => $q->whereIn('id', $imp->participante_ids);
        }

        return fn ($q) => $q->where('importacao_efd_id', $imp->id);
    }

    /** Dump tabular de uma tabela filtrada; null se não houver linhas. */
    private function dump(string $tabela, Closure $escopo): ?string
    {
        $colunas = Schema::getColumnListing($tabela);
        $rows = $escopo(DB::table($tabela))->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $linhas = $rows->map(fn ($r) => array_map(fn ($c) => $r->{$c}, $colunas))->all();

        return $this->csv($colunas, $linhas);
    }

    /** CSV compatível com Excel/Sheets (BOM UTF-8 + delimitador ";"). */
    private function csv(array $colunas, array $linhas): string
    {
        return CsvExport::build($colunas, $linhas);
    }
}
