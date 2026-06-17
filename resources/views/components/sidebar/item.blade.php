@props(['href', 'icon' => null, 'badge' => null, 'badgeLabel' => null, 'pill' => null])

<a href="{{ $href }}" data-link data-sidebar-link {{ $attributes->merge(['class' => 'sidebar__item']) }}>
    @if($icon)
        {{ $icon }}
    @endif
    <span class="sidebar__item-label">{{ $slot }}</span>

    @if($badge)
        <span class="sidebar__item-badge-count" aria-label="{{ $badgeLabel ?? $badge }}" style="background-color: #d97706;">{{ $badge }}</span>
    @elseif($pill)
        <span style="margin-left:auto; font-size:9px; font-weight:700; line-height:1; padding:2px 6px; border-radius:9999px; background-color:#fef3c7; color:#92400e; text-transform:uppercase; letter-spacing:0.04em;">{{ $pill }}</span>
    @endif
</a>
