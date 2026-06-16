<?php

namespace App\Http\Middleware;

use App\Services\Entitlements\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresEntitlement
{
    public function __construct(private EntitlementService $entitlements) {}

    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->entitlements->permits($user, $capability)) {
            abort(403, 'Seu plano não inclui este recurso.');
        }

        return $next($request);
    }
}
