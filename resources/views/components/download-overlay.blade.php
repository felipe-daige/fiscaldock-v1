@props(['id' => 'download-overlay', 'texto' => 'Gerando arquivo…'])
{{--
    Overlay de download do design system. Mostrado por <x-download-button> enquanto
    o fetch do arquivo roda; fechado quando o blob baixa. Cache-robusto: togglado por
    classList.add/remove('hidden') via onclick inline (sem JS-file). Ver
    project_design_system_modal / feedback_bi_js_no_cache_bust.
--}}
<div id="{{ $id }}"
     class="hidden fixed inset-0 z-[60] flex items-center justify-center px-4"
     style="background-color: rgba(0,0,0,0.5);">
    <div class="bg-white rounded border border-gray-300 px-6 py-5 flex items-center gap-3"
         style="box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
        <span class="inline-block w-5 h-5 rounded-full animate-spin"
              style="border: 2px solid #e5e7eb; border-top-color: #374151;"></span>
        <span class="text-sm text-gray-700">{{ $texto }}</span>
    </div>
</div>
