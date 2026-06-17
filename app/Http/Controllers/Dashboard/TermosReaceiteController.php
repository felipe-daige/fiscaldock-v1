<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ConsentLog;
use App\Services\Lgpd\ConsentLogService;
use Illuminate\Http\Request;

/**
 * LGPD fase 2.2 — interstitial de re-aceite dos documentos legais.
 *
 * Página standalone (sem sidebar/SPA, de propósito): o titular fica "preso" aqui
 * até re-aceitar a versão vigente. O aceite re-carimba a versão e grava na trilha
 * `consent_logs` (reaproveita a fase 2.1).
 */
class TermosReaceiteController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        // Já está em dia? Não há o que re-aceitar — volta pro app.
        if ($user->terms_version === config('legal.terms_version')
            && $user->privacy_version === config('legal.privacy_version')) {
            return redirect('/app/dashboard');
        }

        return view('autenticado.privacidade.reaceite', ['user' => $user]);
    }

    public function aceitar(Request $request)
    {
        $request->validate([
            'aceito' => 'accepted',
        ], [
            'aceito.accepted' => 'Você precisa aceitar os Termos de Uso e a Política de Privacidade atualizados.',
        ]);

        $user = $request->user();

        $user->forceFill([
            'terms_accepted_at' => now(),
            'terms_version' => config('legal.terms_version'),
            'privacy_version' => config('legal.privacy_version'),
        ])->save();

        $consent = new ConsentLogService;
        $consent->registrar($user->id, ConsentLog::TIPO_TERMOS, ConsentLog::ACAO_ACEITE,
            versao: config('legal.terms_version'), ip: $request->ip(), userAgent: $request->userAgent());
        $consent->registrar($user->id, ConsentLog::TIPO_PRIVACIDADE, ConsentLog::ACAO_ACEITE,
            versao: config('legal.privacy_version'), ip: $request->ip(), userAgent: $request->userAgent());

        return redirect('/app/dashboard')->with('success', 'Obrigado! Seu aceite foi registrado.');
    }
}
