<?php

namespace App\Jobs;

use App\Models\XmlImportacao;
use App\Models\XmlNota;
use App\Services\Xml\NfeParseException;
use App\Services\Xml\NfeXmlParser;
use App\Services\Xml\XmlNotaImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessarXmlImportacaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public int $importacaoId,
        public int $userId,
        public string $tabId,
        public string $ownerDoc,
        public string $storageDir,
        public string $ownerLado = '',
    ) {}

    public function handle(NfeXmlParser $parser, XmlNotaImporter $importer): void
    {
        $imp = XmlImportacao::find($this->importacaoId);
        if (! $imp) {
            return;
        }
        $cacheKey = "progresso:{$this->userId}:{$this->tabId}";

        $arquivos = collect(Storage::disk('local')->files($this->storageDir))
            ->filter(fn ($f) => str_ends_with(strtolower($f), '.xml'))
            ->values();
        $total = $arquivos->count();

        $novos = $dups = $erros = $semDono = 0;
        $errosDetalhados = [];
        $i = 0;

        foreach ($arquivos as $path) {
            $i++;
            try {
                $parsed = $parser->parse(Storage::disk('local')->get($path));
                $status = $importer->importar($parsed, $this->ownerDoc, $imp, $this->ownerLado ?: null);
                if (in_array($status, ['novo', 'sem_dono'], true)) {
                    $novos++;
                } elseif (in_array($status, ['duplicado', 'duplicado_atualizado'], true)) {
                    $dups++;
                }
                if ($status === 'sem_dono') {
                    $semDono++;
                }
            } catch (NfeParseException|\Throwable $e) {
                $erros++;
                $errosDetalhados[] = ['arquivo' => basename($path), 'erro' => $e->getMessage()];
                Log::warning('XML import: erro em arquivo', ['arquivo' => $path, 'erro' => $e->getMessage()]);
            }

            $this->progresso($cacheKey, 'processando', (int) round($i / max($total, 1) * 100),
                "Processando {$i} de {$total}...");
        }

        $participanteIds = XmlNota::where('importacao_xml_id', $imp->id)
            ->get(['emit_participante_id', 'dest_participante_id'])
            ->flatMap(fn ($n) => [$n->emit_participante_id, $n->dest_participante_id])
            ->filter()->unique()->values()->all();

        $valorTotal = (float) XmlNota::where('importacao_xml_id', $imp->id)->sum('valor_total');

        // Modo "criar cliente pelo lado": define o cliente da importação como o dono mais
        // comum (o lado escolhido), pro histórico exibir. No modo forçado já vem do create.
        $clienteImportacao = $imp->cliente_id;
        if ($this->ownerLado !== '' && ! $clienteImportacao) {
            $col = $this->ownerLado === 'emit' ? 'emit_cliente_id' : 'dest_cliente_id';
            $clienteImportacao = XmlNota::where('importacao_xml_id', $imp->id)
                ->whereNotNull($col)
                ->groupBy($col)
                ->orderByRaw('COUNT(*) DESC')
                ->value($col);
        }

        // Lote misto (decidir_depois / auto): o dono de cada nota está em xml_notas.cliente_id.
        // Header só recebe FK quando o lote está TODO resolvido para 1 único dono; se há mais
        // de um dono OU ainda resta nota sem dono, fica null (= "Vários (N)" / pendente de grupo).
        if (! $clienteImportacao) {
            $temSemDono = XmlNota::where('importacao_xml_id', $imp->id)->whereNull('cliente_id')->exists();
            $donosDistintos = XmlNota::where('importacao_xml_id', $imp->id)
                ->whereNotNull('cliente_id')
                ->distinct()
                ->pluck('cliente_id');
            $clienteImportacao = (! $temSemDono && $donosDistintos->count() === 1)
                ? (int) $donosDistintos->first()
                : null;
        }

        $imp->update([
            'status' => 'concluido',
            'cliente_id' => $clienteImportacao,
            'total_xmls' => $total,
            'xmls_processados' => $total,
            'xmls_novos' => $novos,
            'xmls_duplicados_processados' => $dups,
            'xmls_com_erro' => $erros,
            'valor_total' => $valorTotal,
            'participante_ids' => $participanteIds,
            'erros_detalhados' => $errosDetalhados ?: null,
            'concluido_em' => now(),
            'tempo_processamento_segundos' => $imp->iniciado_em ? (int) now()->diffInSeconds($imp->iniciado_em) : null,
        ]);

        $this->progresso($cacheKey, 'concluido', 100,
            "Concluído: {$novos} novas, {$dups} duplicadas, {$erros} com erro.", [
                'importacao_id' => $imp->id,
                'sem_dono' => $semDono,
            ]);

        Storage::disk('local')->deleteDirectory($this->storageDir);
    }

    public function failed(\Throwable $e): void
    {
        XmlImportacao::where('id', $this->importacaoId)
            ->update(['status' => 'erro', 'erro_mensagem' => $e->getMessage()]);
        $this->progresso("progresso:{$this->userId}:{$this->tabId}", 'erro', 0, 'Falha no processamento.');
    }

    private function progresso(string $key, string $status, int $progresso, string $mensagem, array $extra = []): void
    {
        Cache::put($key, array_merge([
            'status' => $status,
            'progresso' => $progresso,
            'mensagem' => $mensagem,
        ], $extra), 600);
    }
}
