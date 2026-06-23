@props([
    'href' => null,
    'variant' => 'default',
    'icon' => null,
])

@php
    // Design system DANFE: cor por style inline (Tailwind v4 cor → oklch quebra em alguns browsers).
    $cor = $variant === 'danger' ? '#b91c1c' : '#374151';
    $hover = $variant === 'danger' ? '#fef2f2' : '#f9fafb';
    $base = 'flex w-full items-center gap-2 whitespace-nowrap px-3 py-2 text-left text-[13px] transition-colors';
@endphp

@if ($href)
    <a href="{{ $href }}" role="menuitem" data-acoes-item
        class="{{ $base }}" style="color: {{ $cor }};"
        onmouseover="this.style.backgroundColor='{{ $hover }}'" onmouseout="this.style.backgroundColor=''"
        {{ $attributes }}>
        @if ($icon)
            <span class="shrink-0 text-gray-400">{!! $icon !!}</span>
        @endif
        <span>{{ $slot }}</span>
    </a>
@else
    <button type="button" role="menuitem" data-acoes-item
        class="{{ $base }}" style="color: {{ $cor }};"
        onmouseover="this.style.backgroundColor='{{ $hover }}'" onmouseout="this.style.backgroundColor=''"
        {{ $attributes }}>
        @if ($icon)
            <span class="shrink-0 text-gray-400">{!! $icon !!}</span>
        @endif
        <span>{{ $slot }}</span>
    </button>
@endif
