<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * Smoke: nenhuma página autenticada GET (sem parâmetro) pode lançar 5xx.
 *
 * Complementa os detectores estáticos — pega 500 de runtime (a classe do bug LandingLead:
 * referência que só explode quando o código executa). Não valida conteúdo, só que renderiza.
 */
it('nenhuma rota GET autenticada sem parâmetro retorna 5xx', function () {
    $user = User::factory()->trialAtivo()->create(['is_admin' => true]);

    // Endpoints de streaming/SSE penduram a request — fora do smoke.
    $excluir = ['stream', 'progresso', 'sse'];

    $rotas = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($r) => in_array('GET', $r->methods(), true))
        ->map(fn ($r) => $r->uri())
        ->filter(fn ($uri) => str_starts_with($uri, 'app/') || $uri === 'app/dashboard')
        ->reject(fn ($uri) => str_contains($uri, '{'))
        ->reject(fn ($uri) => collect($excluir)->contains(fn ($x) => str_contains($uri, $x)))
        ->unique()
        ->values();

    expect($rotas)->not->toBeEmpty();

    $falhas = [];
    foreach ($rotas as $uri) {
        try {
            $status = actingAs($user)->get('/'.$uri)->baseResponse->getStatusCode();
        } catch (\Throwable $e) {
            $falhas[] = $uri.' => EXCEPTION: '.$e->getMessage();

            continue;
        }
        if ($status >= 500) {
            $falhas[] = $uri.' => '.$status;
        }
    }

    expect($falhas)->toBe([], 'Páginas autenticadas com 5xx: '.PHP_EOL.implode(PHP_EOL, $falhas));
});

it('nenhuma rota GET pública sem parâmetro retorna 5xx', function () {
    $excluir = ['stream', 'progresso', 'sse'];

    $rotas = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($r) => in_array('GET', $r->methods(), true))
        ->map(fn ($r) => $r->uri())
        ->reject(fn ($uri) => str_starts_with($uri, 'app/') || str_starts_with($uri, 'api/') || str_starts_with($uri, '_'))
        ->reject(fn ($uri) => str_contains($uri, '{'))
        ->reject(fn ($uri) => collect($excluir)->contains(fn ($x) => str_contains($uri, $x)))
        ->unique()
        ->values();

    expect($rotas)->not->toBeEmpty();

    $falhas = [];
    foreach ($rotas as $uri) {
        try {
            $status = $this->get('/'.$uri)->baseResponse->getStatusCode();
        } catch (\Throwable $e) {
            $falhas[] = $uri.' => EXCEPTION: '.$e->getMessage();

            continue;
        }
        if ($status >= 500) {
            $falhas[] = $uri.' => '.$status;
        }
    }

    expect($falhas)->toBe([], 'Páginas públicas com 5xx: '.PHP_EOL.implode(PHP_EOL, $falhas));
});

it('captura de lead do banner não quebra (caminho LandingLead::create)', function () {
    $response = $this->post('/lead/banner-contato', ['email' => 'lead-smoke@example.com']);

    expect($response->baseResponse->getStatusCode())->toBeLessThan(500);
    $this->assertDatabaseHas('landing_leads', ['email' => 'lead-smoke@example.com']);
});
