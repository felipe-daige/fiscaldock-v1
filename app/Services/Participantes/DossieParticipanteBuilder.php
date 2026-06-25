<?php

namespace App\Services\Participantes;

use App\Models\ConsultaResultado;
use App\Models\Participante;
use App\Services\Consultas\Fiscal\TopMovimentacaoQuery;
use App\Services\Consultas\ResultadoDetalhePresenter;

/**
 * Monta o payload único do dossiê do participante: consulta (certidões) + score
 * + movimentações EFD. Sem efeitos colaterais — tudo derivado.
 */
final class DossieParticipanteBuilder
{
    public function __construct(
        private ParticipanteMovimentacaoService $movimentacao,
        private ResultadoDetalhePresenter $presenter,
        private TopMovimentacaoQuery $top,
    ) {}

    public function montar(Participante $p): array
    {
        $ultima = ConsultaResultado::where('participante_id', $p->id)
            ->where('status', ConsultaResultado::STATUS_SUCESSO)
            ->orderByDesc('consultado_em')
            ->first();

        $consulta = [
            'tem' => (bool) $ultima,
            'resumo' => $ultima ? $this->presenter->resumoTextual($ultima) : null,
            'blocos' => $ultima ? $this->presenter->blocos($ultima) : [],
            'consultado_em' => $ultima?->consultado_em?->format('d/m/Y H:i'),
        ];

        $score = $ultima
            ? $ultima->calcularScore()
            : ['scores' => [], 'score_total' => 0, 'classificacao' => 'medio'];

        return [
            'participante' => $p,
            'gerado_em' => now()->format('d/m/Y H:i'),
            'consulta' => $consulta,
            'score' => $score,
            'movimentacao' => [
                'kpis' => $this->movimentacao->kpis($p),
                'por_competencia' => $this->movimentacao->porCompetencia($p),
                'por_cfop' => $this->movimentacao->porCfop($p),
                'por_cst' => $this->movimentacao->porCst($p),
                'impostos' => $this->movimentacao->impostos($p),
            ],
            'top_produtos' => $this->top->produtos($p->user_id, 'participante_id', [$p->id], 10)[$p->id] ?? [],
            'top_cfops' => $this->top->cfops($p->user_id, 'participante_id', [$p->id], 10)[$p->id] ?? [],
        ];
    }
}
