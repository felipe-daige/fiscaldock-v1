<?php

use App\Http\Controllers\Concerns\RespondeAjax;
use Illuminate\Http\Request;

function fakeControllerComTrait(): object
{
    return new class
    {
        use RespondeAjax;

        public function checa(Request $r): bool
        {
            return $this->isAjaxRequest($r);
        }
    };
}

it('detecta X-Requested-With: XMLHttpRequest', function () {
    $req = Request::create('/x', 'GET', server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
    expect(fakeControllerComTrait()->checa($req))->toBeTrue();
});

it('detecta Accept: application/json (sem X-Requested-With)', function () {
    $req = Request::create('/x', 'GET', server: ['HTTP_ACCEPT' => 'application/json']);
    expect(fakeControllerComTrait()->checa($req))->toBeTrue();
});

it('detecta os dois sinais juntos', function () {
    $req = Request::create('/x', 'GET', server: [
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    expect(fakeControllerComTrait()->checa($req))->toBeTrue();
});

it('é falso numa navegação de página normal (HTML)', function () {
    $req = Request::create('/x', 'GET', server: ['HTTP_ACCEPT' => 'text/html']);
    expect(fakeControllerComTrait()->checa($req))->toBeFalse();
});
