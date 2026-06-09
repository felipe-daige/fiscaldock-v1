<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Avisa o usuário que o auto top-up por saldo foi pausado (cartão recusado, expirado
 * ou teto diário atingido) e que precisa atualizar o cartão/reativar.
 *
 * Futuro: quando houver integração de WhatsApp, notificar também por WhatsApp + e-mail.
 */
class RecargaAutomaticaPausada extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public User $usuario, public string $motivo) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Sua recarga automática foi pausada');
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Olá '.e($this->usuario->name).',</p>'
                .'<p>Sua recarga automática por saldo baixo foi pausada: '.e($this->motivo).'.</p>'
                .'<p>Atualize o cartão em <strong>Créditos</strong> para reativar.</p>',
        );
    }
}
