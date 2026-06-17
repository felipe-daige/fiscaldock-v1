<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\RespondeAjax;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ConsentLog;
use App\Models\ConsultaLote;
use App\Models\CreditTransaction;
use App\Models\EfdImportacao;
use App\Models\Participante;
use App\Models\User;
use App\Models\XmlImportacao;
use App\Services\Lgpd\ConsentLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * LGPD fase 2 — Centro de Privacidade do titular (/app/privacidade).
 *
 * Direitos do titular implementados: ver consentimentos, revogar marketing, exportar os
 * próprios dados (DSAR/JSON) e SOLICITAR exclusão de conta.
 *
 * A exclusão é um PEDIDO (flag `deletion_requested_at`), nunca um hard-delete no clique —
 * o processamento/anonimização respeita a retenção fiscal de SPED/XML (feito off-line).
 * Trilha de auditoria de consentimento (consent_logs) = fase 2.1.
 */
class PrivacidadeController extends Controller
{
    use RespondeAjax;

    private const AUTH_LAYOUT_VIEW = 'autenticado.layouts.app';

    public function index(Request $request)
    {
        $view = 'autenticado.privacidade.index';

        if (! Auth::check()) {
            if ($this->isAjaxRequest($request)) {
                return response('Não autenticado', 401);
            }

            return redirect()->route('login');
        }

        /** @var User $user */
        $user = Auth::user();

        $data = [
            'user' => $user,
            'titularidade' => $this->titularidade($user->id),
            'historico' => ConsentLog::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
        ];

        if ($this->isAjaxRequest($request)) {
            return response(view($view, $data)->render())->header('Content-Type', 'text/html');
        }

        return view(self::AUTH_LAYOUT_VIEW, array_merge(['initialView' => $view], $data));
    }

    public function revogarMarketing(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $user->forceFill([
            'marketing_opt_in' => false,
            'marketing_opt_in_at' => now(),
        ])->save();

        (new ConsentLogService)->registrar($user->id, ConsentLog::TIPO_MARKETING, ConsentLog::ACAO_REVOGACAO,
            valor: false, ip: $request->ip(), userAgent: $request->userAgent());

        return back()->with('status', 'Consentimento de marketing revogado.');
    }

    public function exportarDados(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $payload = [
            'gerado_em' => now()->toIso8601String(),
            'aviso' => 'Exportação de dados pessoais do titular (LGPD, art. 18). Dados de clientes/participantes que você administra não são dados pessoais SEUS e aparecem apenas como contagem.',
            'perfil' => [
                'nome' => $user->name,
                'email' => $user->email,
                'empresa' => $user->empresa,
                'cargo' => $user->cargo,
                'cnpj' => $user->cnpj,
                'criado_em' => optional($user->created_at)->toIso8601String(),
            ],
            'consentimentos' => [
                'termos_aceitos_em' => optional($user->terms_accepted_at)->toIso8601String(),
                'marketing_opt_in' => (bool) $user->marketing_opt_in,
                'marketing_opt_in_em' => optional($user->marketing_opt_in_at)->toIso8601String(),
            ],
            'exclusao' => [
                'solicitada_em' => optional($user->deletion_requested_at)->toIso8601String(),
            ],
            'trilha_consentimento' => ConsentLog::where('user_id', $user->id)
                ->orderBy('created_at')
                ->get(['tipo', 'acao', 'valor', 'versao', 'ip', 'created_at'])
                ->map(fn ($l) => [
                    'tipo' => $l->tipo,
                    'acao' => $l->acao,
                    'valor' => $l->valor,
                    'versao' => $l->versao,
                    'ip' => $l->ip,
                    'em' => optional($l->created_at)->toIso8601String(),
                ]),
            'titularidade' => $this->titularidade($user->id),
            'creditos' => [
                'saldo' => (float) $user->credits,
                'transacoes' => CreditTransaction::where('user_id', $user->id)
                    ->orderBy('created_at')
                    ->get(['type', 'amount', 'description', 'created_at'])
                    ->map(fn ($t) => [
                        'tipo' => $t->type,
                        'valor' => (float) $t->amount,
                        'descricao' => $t->description,
                        'em' => optional($t->created_at)->toIso8601String(),
                    ]),
            ],
        ];

        $filename = 'fiscaldock-meus-dados-'.now()->format('Ymd-His').'.json';

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function exportarCsv(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $logs = ConsentLog::where('user_id', $user->id)->orderBy('created_at')->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['data', 'tipo', 'acao', 'valor', 'versao', 'ip']);
        foreach ($logs as $l) {
            fputcsv($handle, [
                optional($l->created_at)->toIso8601String(),
                $l->tipo,
                $l->acao,
                $l->valor === null ? '' : ($l->valor ? 'true' : 'false'),
                $l->versao,
                $l->ip,
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $filename = 'fiscaldock-consentimentos-'.now()->format('Ymd-His').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function solicitarExclusao(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Idempotente: preserva o timestamp do primeiro pedido.
        if ($user->deletion_requested_at === null) {
            $user->forceFill(['deletion_requested_at' => now()])->save();

            (new ConsentLogService)->registrar($user->id, ConsentLog::TIPO_EXCLUSAO, ConsentLog::ACAO_SOLICITACAO,
                ip: $request->ip(), userAgent: $request->userAgent());

            Log::info('lgpd.exclusao.solicitada', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);
        }

        return back()->with('status', 'Pedido de exclusão registrado. Nossa equipe processará respeitando a retenção fiscal obrigatória.');
    }

    public function cancelarExclusao(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $user->forceFill(['deletion_requested_at' => null])->save();

        (new ConsentLogService)->registrar($user->id, ConsentLog::TIPO_EXCLUSAO, ConsentLog::ACAO_CANCELAMENTO,
            ip: $request->ip(), userAgent: $request->userAgent());

        return back()->with('status', 'Pedido de exclusão cancelado.');
    }

    /**
     * Contagem dos dados que o titular administra (não são dados pessoais dele; só o volume).
     *
     * @return array<string, int>
     */
    private function titularidade(int $userId): array
    {
        return [
            'clientes' => Cliente::where('user_id', $userId)->count(),
            'participantes' => Participante::where('user_id', $userId)->count(),
            'importacoes_efd' => EfdImportacao::where('user_id', $userId)->count(),
            'importacoes_xml' => XmlImportacao::where('user_id', $userId)->count(),
            'consultas_lotes' => ConsultaLote::where('user_id', $userId)->count(),
        ];
    }
}
