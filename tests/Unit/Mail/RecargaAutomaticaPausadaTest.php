<?php

use App\Mail\RecargaAutomaticaPausada;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

uses(TestCase::class);

test('RecargaAutomaticaPausada implementa ShouldQueue', function () {
    $user = User::factory()->make(['name' => 'Maria']);

    $mail = new RecargaAutomaticaPausada($user, 'cartão recusado');

    expect($mail)->toBeInstanceOf(ShouldQueue::class);
});

test('RecargaAutomaticaPausada usa o tema Markdown com o motivo certo', function () {
    $user = User::factory()->make(['name' => 'Maria']);

    $mail = new RecargaAutomaticaPausada($user, 'cartão recusado');
    $content = $mail->content();

    expect($content->markdown)->toBe('emails.recarga-automatica-pausada');
});
