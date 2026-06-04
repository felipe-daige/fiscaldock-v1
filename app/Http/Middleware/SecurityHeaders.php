<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança aplicados a todas as respostas.
 *
 * Conservador de propósito: NÃO define Content-Security-Policy, que quebraria
 * scripts inline e as CDNs usadas (leaflet/unpkg/jsdelivr/swiper). Cobre os
 * vetores de maior risco com baixo risco de regressão:
 *  - X-Frame-Options: anti-clickjacking (protege a tela de login de UI-redress)
 *  - X-Content-Type-Options: impede MIME-sniffing
 *  - Referrer-Policy: não vaza URL completa (com tokens em query) para terceiros
 *  - Permissions-Policy: desliga APIs poderosas que a app não usa
 *  - Strict-Transport-Security: força HTTPS (só quando a requisição é segura)
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), payment=()',
        ];

        // HSTS apenas sob HTTPS (TLS termina no Traefik; TrustProxies marca secure).
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
