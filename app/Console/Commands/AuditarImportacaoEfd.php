<?php

namespace App\Console\Commands;

use App\Models\EfdImportacao;
use App\Services\EfdAuditoriaService;
use Illuminate\Console\Command;

class AuditarImportacaoEfd extends Command
{
    protected $signature = 'importacao:auditar {id : ID da efd_importacoes}';

    protected $description = 'Reconcilia SPED bruto com banco e popula efd_divergencias';

    public function handle(EfdAuditoriaService $svc): int
    {
        $imp = EfdImportacao::find($this->argument('id'));
        if (! $imp) {
            $this->error("Importação {$this->argument('id')} não encontrada.");

            return self::FAILURE;
        }

        $this->info("Auditando importação #{$imp->id} ({$imp->tipo_efd}) — user {$imp->user_id}");

        $resultado = $svc->auditar($imp);

        $this->table(
            ['métrica', 'valor'],
            collect($resultado)->map(fn ($v, $k) => [$k, $v])->values()->all()
        );

        if ($resultado['divergencias_geradas'] === 0) {
            $this->info('Nenhuma divergência detectada.');
        } else {
            $this->warn("Geradas/atualizadas: {$resultado['divergencias_geradas']} divergência(s).");
            $this->line("Consulta: SELECT * FROM efd_divergencias WHERE importacao_id = {$imp->id};");
        }

        return self::SUCCESS;
    }
}
