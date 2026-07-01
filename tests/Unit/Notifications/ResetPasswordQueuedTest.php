<?php

use App\Models\User;
use App\Notifications\ResetPasswordQueued;
use Illuminate\Contracts\Queue\ShouldQueue;

uses(Tests\TestCase::class);

test('ResetPasswordQueued implementa ShouldQueue', function () {
    expect(new ResetPasswordQueued('token-abc'))->toBeInstanceOf(ShouldQueue::class);
});

test('ResetPasswordQueued herda a customização pt-BR/marca do toMailUsing', function () {
    $user = User::factory()->make(['email' => 'maria@example.com', 'name' => 'Maria']);

    $mail = (new ResetPasswordQueued('token-abc-123'))->toMail($user);

    expect($mail->subject)->toBe('Redefinir sua senha — FiscalDock');
    expect($mail->greeting)->toBe('Olá, Maria!');
    expect($mail->actionUrl)->toContain('/redefinir-senha/token-abc-123');
});
