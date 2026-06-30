@php($_tab = $tab ?? '')
@php($_vencidas = \App\Models\AdminPendencia::vencidas()->count())
<div class="mb-5 border-b border-gray-300 flex gap-1 text-[13px]">
    @foreach([
        'visao' => ['Visão Geral', '/app/admin'],
        'usuarios' => ['Usuários', '/app/admin/usuarios'],
        'comercial' => ['Comercial', '/app/admin/comercial'],
        'auditoria' => ['Auditoria', '/app/admin/auditoria'],
        'pendencias' => ['Pendências', '/app/admin/pendencias'],
    ] as $key => [$label, $href])
        <a href="{{ $href }}" data-link
           class="px-3 py-2 -mb-px border-b-2 {{ $_tab === $key ? 'border-gray-800 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-800' }}">{{ $label }}@if($key === 'pendencias' && $_vencidas > 0)<span class="ml-1 inline-flex items-center justify-center bg-red-600 text-white text-[10px] font-bold rounded-full px-1.5 py-0.5 align-middle">{{ $_vencidas }}</span>@endif</a>
    @endforeach
</div>
