<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

uses(TestCase::class);

test('e-mail de redefinição de senha usa cópia em pt-BR e link correto', function () {
    $user = User::factory()->make(['email' => 'maria@example.com', 'name' => 'Maria']);

    $notification = new ResetPassword('token-abc-123');
    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Redefinir sua senha — FiscalDock');
    expect($mail->greeting)->toBe('Olá, Maria!');
    expect(collect($mail->introLines)->implode(' '))->toContain('redefinir a senha');
    expect($mail->actionUrl)->toContain('/redefinir-senha/token-abc-123');
    expect($mail->actionUrl)->toContain('email=maria%40example.com');
});
