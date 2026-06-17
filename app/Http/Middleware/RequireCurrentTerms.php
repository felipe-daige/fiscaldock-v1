<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LGPD fase 2.2 — força o re-aceite quando a versão vigente dos documentos
 * (config/legal.php) é mais nova do que a última aceita pelo titular.
 *
 * Só intercepta navegação full-page (GET, não-AJAX) — não quebra o SPA nem POSTs.
 * O gate pega o titular no próximo full-load (login/refresh/nova aba), que é como
 * todo mundo entra no app. `logout` fica fora do grupo `auth`, sempre acessível.
 * O bump de versão é raro; o gap de uma sessão SPA já aberta é aceitável e se cura
 * no próximo full-load. Aplicado por FQCN na rota (bootstrap/app.php não é montado).
 */
class RequireCurrentTerms
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User
            && $this->defasado($user)
            && $request->isMethod('GET')
            && ! $request->ajax()
            && ! $request->expectsJson()
            && ! $request->routeIs('app.reaceite.*')) {
            return redirect()->route('app.reaceite.show');
        }

        return $next($request);
    }

    private function defasado(User $user): bool
    {
        return $user->terms_version !== config('legal.terms_version')
            || $user->privacy_version !== config('legal.privacy_version');
    }
}
