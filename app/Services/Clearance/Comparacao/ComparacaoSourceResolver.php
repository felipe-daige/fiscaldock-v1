<?php

namespace App\Services\Clearance\Comparacao;

use App\Models\EfdNota;
use App\Models\XmlNota;
use App\Services\Clearance\Comparacao\Adapters\EfdNotaDeclaradoAdapter;
use App\Services\Clearance\Comparacao\Adapters\XmlNotaDeclaradoAdapter;
use App\Services\Clearance\Comparacao\Adapters\XmlNotaSefazCteAdapter;
use App\Services\Clearance\Comparacao\Adapters\XmlNotaSefazNfeAdapter;
use InvalidArgumentException;

final class ComparacaoSourceResolver
{
    public function resolver(int $userId, string $chave): ResolverResult
    {
        $tipoDocumento = $this->detectarTipo($chave);

        return new ResolverResult(
            tipoDocumento: $tipoDocumento,
            declarado: $this->resolverDeclarado($userId, $chave),
            sefaz: $this->resolverSefaz($userId, $chave, $tipoDocumento),
        );
    }

    public function temEfdAlternativo(int $userId, string $chave): bool
    {
        $temXml = XmlNota::query()
            ->where('user_id', $userId)
            ->where('nfe_id', $chave)
            ->where('origem', 'xml_upload')
            ->exists();
        $temEfd = EfdNota::query()
            ->where('user_id', $userId)
            ->where('chave_acesso', $chave)
            ->exists();

        return $temXml && $temEfd;
    }

    private function detectarTipo(string $chave): string
    {
        if (strlen($chave) !== 44) {
            throw new InvalidArgumentException('Chave deve ter 44 dígitos, recebeu '.strlen($chave));
        }

        $modelo = substr($chave, 20, 2);

        return match ($modelo) {
            '55', '65' => 'NFE',
            '57' => 'CTE',
            default => throw new InvalidArgumentException("Modelo {$modelo} não suportado pela comparação."),
        };
    }

    private function resolverDeclarado(int $userId, string $chave): ?DeclaradoSource
    {
        $xmlUpload = XmlNota::query()
            ->where('user_id', $userId)
            ->where('nfe_id', $chave)
            ->where('origem', 'xml_upload')
            ->first();
        if ($xmlUpload !== null) {
            return new XmlNotaDeclaradoAdapter($xmlUpload);
        }

        $efd = EfdNota::query()
            ->where('user_id', $userId)
            ->where('chave_acesso', $chave)
            ->first();
        if ($efd !== null) {
            return new EfdNotaDeclaradoAdapter($efd);
        }

        return null;
    }

    private function resolverSefaz(int $userId, string $chave, string $tipoDocumento): ?SefazSource
    {
        $row = XmlNota::query()
            ->where('user_id', $userId)
            ->where('nfe_id', $chave)
            ->whereNotNull('situacao_sefaz')
            ->first();
        if ($row === null) {
            return null;
        }

        return $tipoDocumento === 'CTE'
            ? new XmlNotaSefazCteAdapter($row)
            : new XmlNotaSefazNfeAdapter($row);
    }
}
