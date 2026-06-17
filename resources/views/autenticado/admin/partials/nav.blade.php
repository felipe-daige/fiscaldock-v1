@php($_tab = $tab ?? '')
<div class="mb-5 border-b border-gray-300 flex gap-1 text-[13px]">
    @foreach([
        'visao' => ['Visão Geral', '/app/admin'],
        'usuarios' => ['Usuários', '/app/admin/usuarios'],
        'comercial' => ['Comercial', '/app/admin/comercial'],
    ] as $key => [$label, $href])
        <a href="{{ $href }}" data-link
           class="px-3 py-2 -mb-px border-b-2 {{ $_tab === $key ? 'border-gray-800 text-gray-900 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-800' }}">{{ $label }}</a>
    @endforeach
</div>
