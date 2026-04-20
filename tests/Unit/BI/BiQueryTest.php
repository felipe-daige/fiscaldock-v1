<?php

use App\BI\Queries\BiQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Concrete stub para testar a classe abstrata
function makeBiQuery(array $filtros): BiQuery
{
    return new class($filtros) extends BiQuery {
        public function execute(): array { return []; }
        public function exposeResolverIntervalo(): array { return $this->resolverIntervalo(); }
        public function exposeBaseQuery(string $tabela) { return $this->baseQuery($tabela); }
    };
}

it('resolve intervalo com data_inicio e data_fim explícitos', function () {
    $query = makeBiQuery([
        'user_id'     => 1,
        'data_inicio' => '2026-01-15',
        'data_fim'    => '2026-02-28',
    ]);
    $intervalo = $query->exposeResolverIntervalo();
    expect($intervalo['inicio']->format('Y-m-d'))->toBe('2026-01-15');
    expect($intervalo['fim']->format('Y-m-d'))->toBe('2026-02-28');
});

it('resolve intervalo com ano e mes', function () {
    $query = makeBiQuery(['user_id' => 1, 'ano' => 2026, 'mes' => 3]);
    $intervalo = $query->exposeResolverIntervalo();
    expect($intervalo['inicio']->format('Y-m-d'))->toBe('2026-03-01');
    expect($intervalo['fim']->format('Y-m-d'))->toBe('2026-03-31');
});

it('resolve intervalo com apenas ano', function () {
    $query = makeBiQuery(['user_id' => 1, 'ano' => 2025]);
    $intervalo = $query->exposeResolverIntervalo();
    expect($intervalo['inicio']->format('Y-m-d'))->toBe('2025-01-01');
    expect($intervalo['fim']->format('Y-m-d'))->toBe('2025-12-31');
});

it('resolve intervalo default para mês atual quando filtros vazios', function () {
    $query = makeBiQuery(['user_id' => 1]);
    $intervalo = $query->exposeResolverIntervalo();
    expect($intervalo['inicio']->format('Y-m-d'))->toBe(now()->startOfMonth()->format('Y-m-d'));
    expect($intervalo['fim']->format('Y-m-d'))->toBe(now()->endOfMonth()->format('Y-m-d'));
});
