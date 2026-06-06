<?php

namespace App\Providers;

use App\View\Composers\SidebarComposer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Consultas\FonteRegistry::class, fn () => new \App\Services\Consultas\FonteRegistry([
            new \App\Services\Consultas\Fontes\CadastroFonte,
            new \App\Services\Consultas\Fontes\CndFederalFonte,
            new \App\Services\Consultas\Fontes\CndtFonte,
            new \App\Services\Consultas\Fontes\CrfFgtsFonte,
            new \App\Services\Consultas\Fontes\CndEstadualFonte,
            new \App\Services\Consultas\Fontes\SintegraFonte,
            new \App\Services\Consultas\Fontes\CguCncFonte,
            new \App\Services\Consultas\Fontes\CnjImprobidadeFonte,
            new \App\Services\Consultas\Fontes\CndMunicipalFonte,
            new \App\Services\Consultas\Fontes\ProtestosFonte,
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Força https na geração de URLs/Assets em produção (evita Mixed Content
        // quando o TLS termina no Traefik e a app recebe HTTP internamente).
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        View::composer('autenticado.partials.sidebar', SidebarComposer::class);
    }
}
