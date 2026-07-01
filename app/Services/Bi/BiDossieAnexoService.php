<?php

namespace App\Services\Bi;

use App\Models\Cliente;
use App\Services\BiService;
use App\Services\Clientes\DossieClienteBuilder;
use App\Services\Participantes\DossieParticipanteBuilder;

/**
 * Monta a seção "Dossiês" anexada ao PDF do BI: dossiês de clientes + participantes
 * do escopo, ordenados por volume EFD. Sem efeitos colaterais (tudo derivado dos
 * builders existentes, que são read-only).
 */
final class BiDossieAnexoService
{
    /** Teto duro de participantes na opção "todos" — evita PDF/timeout runaway. */
    public const TETO_TODOS = 300;

    public function __construct(
        private BiService $bi,
        private DossieClienteBuilder $dossieCliente,
        private DossieParticipanteBuilder $dossieParticipante,
    ) {}

    /**
     * @return array{clientes: list<array>, participantes: list<array>}|null
     *   null = sem dossiês (opção vazia/inválida ou nada a exibir).
     */
    public function montar(int $userId, ?int $clienteId, string $opcao): ?array
    {
        $limite = match ($opcao) {
            '20' => 20,
            '50' => 50,
            'todos' => self::TETO_TODOS,
            default => null,
        };
        if ($limite === null) {
            return null;
        }

        $clientes = $clienteId !== null
            ? Cliente::where('user_id', $userId)->whereKey($clienteId)->where('ativo', true)->get()
            : $this->bi->clientesPorVolume($userId);

        $participantes = $this->bi->participantesPorVolume($userId, $clienteId, $limite);

        $dossiesClientes = $clientes
            ->map(fn (Cliente $c) => $this->dossieCliente->montar($c))
            ->filter(fn (array $d) => $this->temConteudo($d))
            ->values()->all();

        // Participantes já vêm da query de volume (têm movimentação); o guard fica
        // por consistência com os clientes.
        $dossiesParticipantes = $participantes
            ->map(fn ($p) => $this->dossieParticipante->montar($p))
            ->filter(fn (array $d) => $this->temConteudo($d))
            ->values()->all();

        if ($dossiesClientes === [] && $dossiesParticipantes === []) {
            return null;
        }

        return ['clientes' => $dossiesClientes, 'participantes' => $dossiesParticipantes];
    }

    /** Dossiê vale a pena se tem consulta OU movimentação EFD. */
    private function temConteudo(array $d): bool
    {
        return ! empty($d['consulta']['tem'])
            || (int) ($d['movimentacao']['kpis']['total_notas'] ?? 0) > 0;
    }
}
