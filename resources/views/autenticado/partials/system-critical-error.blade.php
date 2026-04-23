@php
    $errorUi = $errorUi ?? [];
    $title = trim((string) ($errorUi['title'] ?? 'Falha no processamento'));
    $message = trim((string) ($errorUi['message'] ?? 'Ocorreu uma instabilidade interna ao processar sua solicitação.'));
    $actionLabel = trim((string) ($errorUi['action_label'] ?? config('support.contact_label', 'Falar com o suporte')));
    $actionUrl = trim((string) ($errorUi['action_url'] ?? config('support.whatsapp_url', 'https://wa.me/5567999844366')));
    $actionTarget = trim((string) ($errorUi['action_target'] ?? '_blank'));
    $actionRel = trim((string) ($errorUi['action_rel'] ?? 'noopener noreferrer'));
@endphp

<div class="bg-white rounded border border-gray-300 p-4 mb-4 border-l-4 border-l-red-500">
    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">{{ $title }}</p>
    <p class="mt-2 text-sm text-gray-700">{{ $message }}</p>
    <div class="mt-4 flex flex-wrap gap-2">
        <a href="{{ $actionUrl }}"
           target="{{ $actionTarget }}"
           rel="{{ $actionRel }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded text-white text-sm font-medium"
           style="background-color: #1f2937;">
            {{ $actionLabel }}
        </a>
    </div>
</div>
