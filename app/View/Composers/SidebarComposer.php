<?php

namespace App\View\Composers;

use App\Models\Alerta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $userId = Auth::id();
        $count = 0;

        if ($userId) {
            $count = Cache::remember(
                "sidebar:alertas_ativos:{$userId}",
                60,
                fn () => Alerta::where('user_id', $userId)->where('status', 'ativo')->count()
            );
        }

        $view->with('alertasAtivosCount', $count);
    }
}
