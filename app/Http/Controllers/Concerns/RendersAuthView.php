<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait RendersAuthView
{
    private const AUTH_VIEW_PREFIX_DEFAULT = 'autenticado.';
    private const AUTH_LAYOUT_VIEW_DEFAULT = 'autenticado.layout';

    protected function renderAuthView(Request $request, string $viewName, array $data = [])
    {
        $prefix = defined(static::class.'::AUTH_VIEW_PREFIX')
            ? static::AUTH_VIEW_PREFIX
            : self::AUTH_VIEW_PREFIX_DEFAULT;
        $layout = defined(static::class.'::AUTH_LAYOUT_VIEW')
            ? static::AUTH_LAYOUT_VIEW
            : self::AUTH_LAYOUT_VIEW_DEFAULT;

        $view = $prefix.$viewName;

        if (! view()->exists($view)) {
            abort(404);
        }

        if ($this->isAjaxRequestForRender($request)) {
            return view($view, $data);
        }

        return view($layout, array_merge([
            'initialView' => $view,
        ], $data));
    }

    private function isAjaxRequestForRender(Request $request): bool
    {
        return $request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }
}
