<div class="min-h-screen bg-gray-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        @include('autenticado.admin.partials.nav', ['tab' => 'auditoria'])
        <h1 class="text-lg font-bold text-gray-900 mb-4">Trilha administrativa</h1>
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-gray-200 text-[11px] text-gray-500 uppercase">
                    <th class="px-3 py-2 text-left">Data</th>
                    <th class="px-3 py-2 text-left">Operador</th>
                    <th class="px-3 py-2 text-left">Alvo</th>
                    <th class="px-3 py-2 text-left">Ação</th>
                    <th class="px-3 py-2 text-left">Motivo</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                    <tr>
                        <td class="px-3 py-2 text-[12px] text-gray-500">{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-2">{{ $log->admin->name ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $log->alvo->name ?? '—' }}</td>
                        <td class="px-3 py-2"><span class="text-[11px] font-semibold text-gray-700">{{ $log->acao }}</span></td>
                        <td class="px-3 py-2 text-[12px] text-gray-600">{{ $log->motivo }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">Sem ações registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
