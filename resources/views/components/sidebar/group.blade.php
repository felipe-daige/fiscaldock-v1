@props(['title', 'icon' => null])

<details class="sidebar__group group/details" data-sidebar-group {{ $attributes }}>
    <summary class="sidebar__group-trigger marker:content-none [&::-webkit-details-marker]:hidden" data-sidebar-group-trigger>
        <div class="flex items-center gap-3 min-w-0">
            @if($icon)
                {{ $icon }}
            @endif
            <span class="sidebar__item-label truncate">{{ $title }}</span>
        </div>
        <svg class="sidebar__group-arrow transition-transform duration-200 group-open/details:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </summary>

    <div class="sidebar__group-menu">
        {{ $slot }}
    </div>
</details>
