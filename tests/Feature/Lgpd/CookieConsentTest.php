<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renderiza o banner de consentimento de cookies na landing pública', function () {
    $resp = $this->get('/')->assertOk();

    $resp->assertSee('cookie-consent-banner', false)
        ->assertSee('cookie-consent-accept', false)
        ->assertSee('cookie-consent-reject', false)
        ->assertSee('Política de Privacidade');
});
