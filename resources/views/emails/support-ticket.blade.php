<h1>Nova solicitação de suporte</h1>

<p><strong>Usuário:</strong> {{ $user->name }} {{ $user->sobrenome }}</p>
<p><strong>Email:</strong> {{ $user->email }}</p>
<p><strong>Categoria:</strong> {{ $payload['categoria'] }}</p>
<p><strong>Assunto:</strong> {{ $payload['assunto'] }}</p>
<p><strong>Enviado em:</strong> {{ $payload['enviado_em']->format('d/m/Y H:i:s') }}</p>

@if(!empty($payload['contexto']))
<p><strong>Contexto:</strong> {{ $payload['contexto'] }}</p>
@endif

@if(!empty($payload['url_origem']))
<p><strong>URL de origem:</strong> {{ $payload['url_origem'] }}</p>
@endif

@if(!empty($payload['mensagem_erro']))
<p><strong>Mensagem de erro:</strong> {{ $payload['mensagem_erro'] }}</p>
@endif

@if(!empty($payload['ip']))
<p><strong>IP:</strong> {{ $payload['ip'] }}</p>
@endif

@if(!empty($payload['user_agent']))
<p><strong>User-Agent:</strong> {{ $payload['user_agent'] }}</p>
@endif

<hr>

<p><strong>Mensagem:</strong></p>
<pre style="white-space: pre-wrap; font-family: Arial, sans-serif;">{{ $payload['mensagem'] }}</pre>
