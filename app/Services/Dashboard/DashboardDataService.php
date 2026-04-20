<?php

namespace App\Services\Dashboard;

use App\Models\ConsultaLote;
use App\Models\EfdImportacao;
use App\Models\EfdNota;
use App\Models\Participante;
use App\Models\User;
use App\Services\AlertaCentralService;
use App\Services\CreditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardDataService
{
    public function __construct(
        protected CreditService $creditService,
        protected AlertaCentralService $alertaCentralService
    ) {}

    /**
     * Obtém todos os KPIs do dashboard para um usuário.
     */
    public function getKpis(int $userId, User $user): array
    {
        $mesInicio = now()->startOfMonth();
        $mesFim = now()->endOfMonth();

        // Volume processado
        $volumeNotas = EfdNota::where('user_id', $userId)->count();
        $volumeValor = (float) EfdNota::where('user_id', $userId)->sum('valor_total');

        // Participantes
        $participantesTotal = $this->getTotalParticipantes($userId);
        $participantesRisco = DB::table('participante_scores')
            ->join('participantes', 'participantes.id', '=', 'participante_scores.participante_id')
            ->where('participantes.user_id', $userId)
            ->where(function ($q) {
                $q->where('participantes.origem_tipo', '!=', 'PROPRIO')
                    ->orWhereNull('participantes.origem_tipo');
            })
            ->whereIn('participante_scores.classificacao', ['alto', 'critico'])
            ->count();

        // Créditos
        $creditos = $this->creditService->getBalance($user);
        $creditosConsultas = (int) ConsultaLote::where('user_id', $userId)
            ->where('status', 'concluido')
            ->whereBetween('created_at', [$mesInicio, $mesFim])
            ->sum('creditos_cobrados');
        $creditosImportacoes = (int) EfdImportacao::where('user_id', $userId)
            ->whereBetween('created_at', [$mesInicio, $mesFim])
            ->sum('creditos_cobrados');
        $creditosUsadosMes = $creditosConsultas + $creditosImportacoes;

        // Alertas (mesma fonte canônica da Central de Alertas)
        $resumoAlertas = $this->alertaCentralService->obterResumo($userId);
        $alertasTotal = $resumoAlertas['total_ativos'] ?? 0;
        $alertasAlta = $resumoAlertas['por_severidade']['alta'] ?? 0;

        return [
            'volume_total_notas' => $volumeNotas,
            'volume_valor_total' => $volumeValor,
            'participantes_total' => $participantesTotal,
            'participantes_risco' => $participantesRisco,
            'creditos' => $creditos,
            'creditos_usados_mes' => $creditosUsadosMes,
            'alertas_total' => $alertasTotal,
            'alertas_alta' => $alertasAlta,
        ];
    }

    /**
     * Retorna atividade recente mesclando importações e consultas.
     */
    public function getAtividadeRecente(int $userId): Collection
    {
        $importacoes = EfdImportacao::where('user_id', $userId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($i) => [
                'tipo' => 'importacao',
                'descricao' => $i->filename ?? 'Importação EFD',
                'tipo_efd' => $i->tipo_efd,
                'status' => $i->status,
                'data' => $i->created_at,
            ]);

        $consultas = ConsultaLote::where('user_id', $userId)
            ->with('plano:id,nome,codigo')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($c) => [
                'tipo' => 'consulta',
                'descricao' => $c->plano?->nome ?? 'Consulta',
                'status' => $c->status,
                'data' => $c->created_at,
            ]);

        return $importacoes->concat($consultas)
            ->sortByDesc('data')
            ->take(5)
            ->values();
    }

    /**
     * Verifica se o usuário é novo (sem participantes e sem importações).
     */
    public function isUsuarioNovo(int $userId): bool
    {
        $participantesReais = Participante::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('origem_tipo', '!=', 'PROPRIO')
                    ->orWhereNull('origem_tipo');
            })
            ->count();

        return $participantesReais === 0
            && EfdImportacao::where('user_id', $userId)->count() === 0;
    }

    /**
     * Retorna a última importação EFD do usuário.
     */
    public function getUltimaImportacao(int $userId): ?EfdImportacao
    {
        return EfdImportacao::where('user_id', $userId)
            ->latest()
            ->first();
    }

    /**
     * Retorna o total de participantes do usuário.
     */
    public function getTotalParticipantes(int $userId): int
    {
        return Participante::where('user_id', $userId)
            ->where(function ($q) {
                $q->where('origem_tipo', '!=', 'PROPRIO')
                    ->orWhereNull('origem_tipo');
            })
            ->count();
    }

    /**
     * Retorna participantes paginados com score, ordenados por risco (mais arriscados primeiro).
     */
    public function getParticipantesPaginados(int $userId, ?string $busca = null, int $perPage = 20): LengthAwarePaginator
    {
        return Participante::where('participantes.user_id', $userId)
            ->with('score')
            ->leftJoin('participante_scores', 'participantes.id', '=', 'participante_scores.participante_id')
            ->select('participantes.*')
            ->when($busca, function ($q) use ($busca) {
                $busca = trim($busca);
                $cnpjLimpo = preg_replace('/[^0-9]/', '', $busca);

                $q->where(function ($q) use ($busca, $cnpjLimpo) {
                    $q->where('participantes.documento', 'like', "%{$cnpjLimpo}%")
                        ->orWhere('participantes.razao_social', 'ilike', "%{$busca}%")
                        ->orWhere('participantes.nome_fantasia', 'ilike', "%{$busca}%");
                });
            })
            ->orderByDesc('participante_scores.score_total')
            ->orderBy('participantes.razao_social')
            ->paginate($perPage)
            ->withQueryString();
    }
}
