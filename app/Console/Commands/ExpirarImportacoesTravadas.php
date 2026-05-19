<?php

namespace App\Console\Commands;

use App\Models\EfdImportacao;
use App\Models\XmlImportacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpirarImportacoesTravadas extends Command
{
    protected $signature = 'importacao:expirar-travadas';

    protected $description = 'Marca como erro importações EFD/XML presas em processando sem atualização além da janela configurada';

    public function handle(): int
    {
        $efd = EfdImportacao::travadas()->get();
        foreach ($efd as $importacao) {
            $importacao->marcarComoTravada();
        }

        $xml = XmlImportacao::travadas()->get();
        foreach ($xml as $importacao) {
            $importacao->marcarComoTravada();
        }

        if ($efd->count() + $xml->count() > 0) {
            Log::warning('Importações travadas expiradas', [
                'efd' => $efd->count(),
                'xml' => $xml->count(),
                'janela_minutos' => (int) config('importacao.stale_minutos'),
            ]);
        }

        $this->info("Importações expiradas: EFD={$efd->count()}, XML={$xml->count()}");

        return self::SUCCESS;
    }
}
