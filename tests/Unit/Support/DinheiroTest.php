<?php

use App\Support\Dinheiro;

it('formata reais BR', function () {
    expect(Dinheiro::brl(3))->toBe('R$ 3,00');
    expect(Dinheiro::brl(1234.5))->toBe('R$ 1.234,50');
    expect(Dinheiro::brl(0))->toBe('R$ 0,00');
});
