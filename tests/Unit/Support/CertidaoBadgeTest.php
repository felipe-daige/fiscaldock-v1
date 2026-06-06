<?php

use App\Support\CertidaoBadge;

it('retorna vazio para valor nulo ou string vazia', function () {
    expect(CertidaoBadge::classificar(null)['label'])->toBe('—');
    expect(CertidaoBadge::classificar('')['label'])->toBe('—');
    expect(CertidaoBadge::classificar([])['label'])->toBe('—');
});

it('classifica REGULAR/regular como Regular (verde)', function () {
    expect(CertidaoBadge::classificar('regular'))->toMatchArray(['label' => 'Regular', 'hex' => '#047857']);
    expect(CertidaoBadge::classificar(['status' => 'REGULAR']))->toMatchArray(['label' => 'Regular', 'hex' => '#047857']);
});

it('classifica certidão Negativa como Regular (verde) — corrige o bug str_contains negativa', function () {
    expect(CertidaoBadge::classificar(['status' => 'Negativa']))->toMatchArray(['label' => 'Regular', 'hex' => '#047857']);
});

it('classifica Positiva com efeitos de negativa como Regular (verde)', function () {
    expect(CertidaoBadge::classificar(['status' => 'Positiva com efeitos de negativa']))
        ->toMatchArray(['label' => 'Regular', 'hex' => '#047857']);
});

it('classifica Positiva pura (com débitos) como Irregular (vermelho)', function () {
    expect(CertidaoBadge::classificar(['status' => 'Positiva']))->toMatchArray(['label' => 'Irregular', 'hex' => '#dc2626']);
    expect(CertidaoBadge::classificar(['status' => 'Irregular']))->toMatchArray(['label' => 'Irregular', 'hex' => '#dc2626']);
});

it('classifica situação HABILITADO (sintegra) como Regular', function () {
    expect(CertidaoBadge::classificar(['situacao' => 'HABILITADO']))->toMatchArray(['label' => 'Regular', 'hex' => '#047857']);
});

it('classifica INDETERMINADO como Indeterminada (âmbar)', function () {
    expect(CertidaoBadge::classificar(['status' => 'INDETERMINADO']))
        ->toMatchArray(['label' => 'Indeterminada', 'hex' => '#d97706']);
});

it('classifica INDISPONIVEL e NAO_ENCONTRADA como neutros', function () {
    expect(CertidaoBadge::classificar(['status' => 'INDISPONIVEL'])['hex'])->toBe('#9ca3af');
    expect(CertidaoBadge::classificar(['status' => 'NAO_ENCONTRADA'])['hex'])->toBe('#6b7280');
});

it('com aplicarIndeterminado, conseguiu_emitir=false vira Indeterminada com motivo', function () {
    $r = CertidaoBadge::classificar(
        ['status' => 'Positiva', 'conseguiu_emitir' => false, 'mensagem' => 'Receita sem dados.'],
        true
    );
    expect($r['label'])->toBe('Indeterminada');
    expect($r['hex'])->toBe('#d97706');
    expect($r['indeterminado'])->toBeTrue();
    expect($r['motivo'])->toBe('Receita sem dados.');
});

it('sem aplicarIndeterminado, Positiva continua Irregular', function () {
    expect(CertidaoBadge::classificar(['status' => 'Positiva', 'conseguiu_emitir' => false]))
        ->toMatchArray(['label' => 'Irregular', 'hex' => '#dc2626']);
});
