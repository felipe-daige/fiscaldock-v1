@component('mail::message')
# Sua recarga automática foi pausada

Olá {{ $usuario->name }},

Sua recarga automática por saldo baixo foi pausada: {{ $motivo }}.

@component('mail::button', ['url' => url('/app/creditos')])
Atualizar cartão
@endcomponent

Se você não reconhece essa recarga, entre em contato com o suporte.
@endcomponent
