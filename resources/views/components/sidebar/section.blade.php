@props(['title'])

<div class="sidebar__section">
    <div class="sidebar__section-title">{{ $title }}</div>
    {{ $slot }}
</div>
