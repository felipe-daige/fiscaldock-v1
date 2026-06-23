@if(session()->has('impersonator_id'))
<div style="background-color:#fef3c7;border-bottom:1px solid #f59e0b" class="px-4 py-2 text-[13px] text-amber-900 flex items-center justify-between">
    <span>Você está vendo como <strong>{{ auth()->user()->name }}</strong> (modo leitura).</span>
    <form method="POST" action="{{ route('app.admin.impersonar.sair') }}">
        @csrf
        <button class="underline font-semibold">Voltar ao admin</button>
    </form>
</div>
@endif
