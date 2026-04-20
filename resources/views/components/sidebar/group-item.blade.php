@props(['href', 'suffix' => null, 'suffixTitle' => null, 'pill' => null])

<a
    href="{{ $href }}"
    data-link
    data-sidebar-group-item
    @if($suffixTitle) aria-label="{{ $suffixTitle }}" @endif
    {{ $attributes->merge(['class' => 'sidebar__group-menu-item']) }}
>
    <span class="sidebar__group-menu-item-label">{{ $slot }}</span>

    @if($pill)
        <span class="sidebar__group-menu-item-pill" style="background-color: #fef3c7; color: #92400e;">{{ $pill }}</span>
    @elseif($suffix)
        <span class="sidebar__group-menu-item-suffix" aria-hidden="true">
            @if($suffix === 'development')
                <svg class="sidebar__group-menu-item-suffix-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.7 6.3a1 1 0 010 1.4l-1.8 1.8 3.4 3.4 1.8-1.8a1 1 0 011.4 0l1.1 1.1a1 1 0 010 1.4l-5.9 5.9a2 2 0 01-1 .55l-3.1.78.78-3.1a2 2 0 01.55-1l5.9-5.9a1 1 0 011.4 0m-8.5-8.5a3 3 0 00-4.24 4.24l2.12 2.12-4.95 4.95a1.5 1.5 0 000 2.12l.71.71a1.5 1.5 0 002.12 0l4.95-4.95 2.12 2.12a3 3 0 004.24-4.24L11.7 4.3a3 3 0 00-4.24 0z"></path>
                </svg>
            @else
                {{ $suffix }}
            @endif
        </span>
    @endif

    @if($suffixTitle && ! $pill)
        <span class="sidebar__group-menu-item-tooltip" role="tooltip">{{ $suffixTitle }}</span>
    @endif
</a>
