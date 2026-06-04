<?php

namespace App\Console\Commands;

use App\Models\EfdNota;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill de `efd_notas.participante_id` para a escrituração FISCAL (EFD ICMS/IPI).
 *
 * Contexto: o workflow n8n fiscal não casa o COD_PART do C100 com o 0150 (Merge
 * "Keep Everything") — ~95% das saídas e ~64% das entradas ficam sem participante_id,
 * apesar do dado existir no SPED (COD_PART no C100 + 0150 COD_PART→CNPJ). O COD_PART
 * não é persistido em metadados, então a recuperação re-parseia o SPED bruto e casa por
 * chave_acesso. Corrige o acervo existente; a raiz (n8n) é tratada à parte.
 */
class BackfillParticipantesFiscal extends Command
{
    protected $signature = 'efd:backfill-participantes-fiscal
        {--dir= : Diretório dos SPED fiscais (default: storage/app/private/SPED/EFD Fiscal)}
        {--user= : Restringe a um user_id}
        {--dry-run : Não grava, só relata}';

    protected $description = 'Preenche participante_id das notas fiscais (EFD ICMS/IPI) re-parseando o SPED bruto (COD_PART do C100 × 0150).';

    public function handle(): int
    {
        $dir = $this->option('dir') ?: storage_path('app/private/SPED/EFD Fiscal');
        $dryRun = (bool) $this->option('dry-run');
        $userOpt = $this->option('user') !== null ? (int) $this->option('user') : null;

        if (! is_dir($dir)) {
            $this->error("Diretório não encontrado: {$dir}");

            return self::FAILURE;
        }

        // 1. chave_acesso → documento (CNPJ/CPF), resolvido POR ARQUIVO (COD_PART é per-arquivo).
        $chaveToDoc = [];
        $arquivos = array_filter(glob($dir.'/*') ?: [], 'is_file');
        foreach ($arquivos as $file) {
            $this->parseArquivo($file, $chaveToDoc);
        }
        $this->info(count($arquivos).' arquivo(s) SPED · '.count($chaveToDoc).' chave(s) mapeada(s) a documento.');

        // 2. participantes por (user_id, documento limpo) → id.
        $docMapCache = [];
        $docMapFor = function (int $userId) use (&$docMapCache): array {
            if (! isset($docMapCache[$userId])) {
                $m = [];
                foreach (DB::table('participantes')->where('user_id', $userId)->whereNotNull('documento')->select('id', 'documento')->get() as $p) {
                    $d = preg_replace('/\D/', '', (string) $p->documento);
                    if ($d !== '') {
                        $m[$d] = (int) $p->id;
                    }
                }
                $docMapCache[$userId] = $m;
            }

            return $docMapCache[$userId];
        };

        // 3. Percorre notas fiscais sem participante_id e preenche por chave.
        $total = 0;
        $atualizadas = 0;
        $semDoc = 0;
        $semParticipante = 0;

        EfdNota::query()
            ->where('origem_arquivo', 'fiscal')
            ->whereNull('participante_id')
            ->whereNotNull('chave_acesso')
            ->when($userOpt, fn ($q) => $q->where('user_id', $userOpt))
            ->select('id', 'user_id', 'chave_acesso')
            ->chunkById(1000, function ($notas) use (&$total, &$atualizadas, &$semDoc, &$semParticipante, $chaveToDoc, $docMapFor, $dryRun) {
                foreach ($notas as $n) {
                    $total++;
                    $doc = $chaveToDoc[$n->chave_acesso] ?? null;
                    if (! $doc) {
                        $semDoc++;

                        continue; // sem COD_PART no SPED (consumidor final) ou nota fora dos arquivos
                    }
                    $pid = $docMapFor((int) $n->user_id)[$doc] ?? null;
                    if (! $pid) {
                        $semParticipante++;

                        continue; // documento não tem participante cadastrado
                    }
                    if (! $dryRun) {
                        EfdNota::whereKey($n->id)->update(['participante_id' => $pid]);
                    }
                    $atualizadas++;
                }
            });

        $this->table(
            ['Notas fiscais sem pid', 'Atualizadas'.($dryRun ? ' (dry-run)' : ''), 'Sem doc no SPED', 'Doc sem participante'],
            [[$total, $atualizadas, $semDoc, $semParticipante]]
        );

        return self::SUCCESS;
    }

    /**
     * Resolve chave_acesso → documento dentro de UM arquivo SPED (COD_PART é escopado ao arquivo).
     * 0150: |0150|COD_PART|NOME|COD_PAIS|CNPJ|CPF|... · C100: |C100|IND_OPER|IND_EMIT|COD_PART|...|CHV_NFE(9)|
     */
    private function parseArquivo(string $file, array &$chaveToDoc): void
    {
        $handle = fopen($file, 'r');
        if (! $handle) {
            return;
        }

        $codpartToDoc = [];
        $c100 = [];
        while (($line = fgets($handle)) !== false) {
            if ($line === '' || $line[0] !== '|') {
                continue;
            }
            $p = explode('|', $line);
            $reg = $p[1] ?? '';
            if ($reg === '0150') {
                $codpart = $p[2] ?? '';
                if ($codpart === '') {
                    continue;
                }
                $cnpj = preg_replace('/\D/', '', $p[5] ?? '');
                $cpf = preg_replace('/\D/', '', $p[6] ?? '');
                $doc = $cnpj !== '' ? $cnpj : $cpf;
                if ($doc !== '') {
                    $codpartToDoc[$codpart] = $doc;
                }
            } elseif ($reg === 'C100') {
                $codpart = $p[4] ?? '';
                $chave = $p[9] ?? '';
                if ($chave !== '' && $codpart !== '') {
                    $c100[] = [$chave, $codpart];
                }
            }
        }
        fclose($handle);

        foreach ($c100 as [$chave, $codpart]) {
            if (isset($codpartToDoc[$codpart])) {
                $chaveToDoc[$chave] = $codpartToDoc[$codpart];
            }
        }
    }
}
