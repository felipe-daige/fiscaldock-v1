<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Detecção canônica de requisição AJAX/JSON nos controllers.
 *
 * O frontend sinaliza "quero dados, não a página inteira" de dois jeitos:
 * `X-Requested-With: XMLHttpRequest` (→ Request::ajax()) e/ou
 * `Accept: application/json` (→ Request::wantsJson()). Checar só um dos dois
 * deixa escapar metade das chamadas — por isso o ponto único é o superset.
 */
trait RespondeAjax
{
    protected function isAjaxRequest(Request $request): bool
    {
        return $request->ajax() || $request->wantsJson();
    }
}
