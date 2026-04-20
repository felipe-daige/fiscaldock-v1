{{-- Análise Fiscal Unificada (alertas integrados por categoria) --}}
@php
 $af = $resumoFinal['analise_fiscal'] ?? [];
 $rfAlertas = $resumoFinal['alertas'] ?? [];

 // Agrupar alertas por categoria
 $alertasPorCategoria = collect($rfAlertas)->groupBy('categoria');

 // Contagem por severidade
 $severidades = collect($rfAlertas)->groupBy('tipo')->map->count();
 $totalDanger = $severidades->get('danger', 0);
 $totalWarning = $severidades->get('warning', 0);
 $totalInfo = $severidades->get('info', 0);
 $totalAlertas = count($rfAlertas);

 // Config de alertas (para banners inline)
 $alertaTipoConfig = [
 'danger' => ['border' => 'border-l-red-500', 'badgeHex' => '#dc2626', 'label' => 'Crítico', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
 'warning' => ['border' => 'border-l-amber-500', 'badgeHex' => '#d97706', 'label' => 'Atenção', 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
 'info' => ['border' => 'border-l-blue-500', 'badgeHex' => '#374151', 'label' => 'Info', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
 ];
@endphp

<div class="bg-white rounded border border-gray-300 mt-6" id="analise-fiscal-section">
 {{-- Header --}}
 <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
 <div class="flex items-center justify-between">
 <div>
 <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Análise Fiscal</span>
 <p class="text-xs text-gray-500 mt-0.5">Cruzamentos, indicadores e alertas gerados automaticamente.</p>
 </div>
 </div>
 @if($totalAlertas > 0)
 <div class="flex items-center gap-2 mt-2">
 @if($totalDanger > 0)
 <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded" style="background-color: #dc2626">{{ $totalDanger }} {{ $totalDanger === 1 ? 'crítico' : 'críticos' }}</span>
 @endif
 @if($totalWarning > 0)
 <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded" style="background-color: #d97706">{{ $totalWarning }} {{ $totalWarning === 1 ? 'atenção' : 'atenção' }}</span>
 @endif
 @if($totalInfo > 0)
 <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded" style="background-color: #374151">{{ $totalInfo }} info</span>
 @endif
 </div>
 @endif
 </div>

 <div class="divide-y divide-gray-200">

 {{-- 1. Indicadores KPI --}}
 @if(!empty($af['indicadores_kpi']))
 @php $kpi = $af['indicadores_kpi']; @endphp
 <details open>
 <summary class="px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition-colors">
 <span class="text-sm font-semibold text-gray-900">Indicadores KPI</span>
 <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
 </summary>
 <div class="px-4 pb-4">
 <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
 @if(isset($kpi['ticket_medio_entradas']))
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">Ticket Médio Entradas</p>
 <p class="text-lg font-bold text-gray-900">R$ {{ number_format($kpi['ticket_medio_entradas'], 2, ',', '.') }}</p>
 </div>
 @endif
 @if(isset($kpi['ticket_medio_saidas']))
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">Ticket Médio Saídas</p>
 <p class="text-lg font-bold text-gray-900">R$ {{ number_format($kpi['ticket_medio_saidas'], 2, ',', '.') }}</p>
 </div>
 @endif
 @if(isset($kpi['total_impostos_periodo']) || isset($kpi['total_impostos_declarados']))
 @php $totalImpostos = $kpi['total_impostos_periodo'] ?? $kpi['total_impostos_declarados'] ?? 0; @endphp
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">Total Impostos</p>
 <p class="text-lg font-bold text-gray-900">R$ {{ number_format($totalImpostos, 2, ',', '.') }}</p>
 </div>
 @endif
 @if(isset($kpi['carga_tributaria_percentual']))
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">Carga Tributária</p>
 <p class="text-lg font-bold text-gray-900">{{ number_format($kpi['carga_tributaria_percentual'], 1, ',', '.') }}%</p>
 </div>
 @endif
 @if(isset($kpi['total_entradas']))
 <div class="bg-gray-50 border border-gray-300 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">Total Entradas</p>
 <p class="text-lg font-bold text-gray-800">R$ {{ number_format(is_array($kpi['total_entradas']) ? ($kpi['total_entradas']['valor'] ?? 0) : $kpi['total_entradas'], 2, ',', '.') }}</p>
 @if(is_array($kpi['total_entradas']) && isset($kpi['total_entradas']['count']))
 <a href="/app/notas-fiscais?tipo_operacao=entrada&importacao_id={{ $importacao->id }}" data-link class="text-[10px] text-gray-500 hover:text-gray-700 hover:underline">{{ $kpi['total_entradas']['count'] }} notas →</a>
 @endif
 </div>
 @endif
 @if(isset($kpi['total_saidas']))
 <div class="bg-gray-50 border border-gray-300 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">Total Saídas</p>
 <p class="text-lg font-bold text-gray-800">R$ {{ number_format(is_array($kpi['total_saidas']) ? ($kpi['total_saidas']['valor'] ?? 0) : $kpi['total_saidas'], 2, ',', '.') }}</p>
 @if(is_array($kpi['total_saidas']) && isset($kpi['total_saidas']['count']))
 <a href="/app/notas-fiscais?tipo_operacao=saida&importacao_id={{ $importacao->id }}" data-link class="text-[10px] text-gray-500 hover:text-gray-700 hover:underline">{{ $kpi['total_saidas']['count'] }} notas →</a>
 @endif
 </div>
 @endif
 </div>
 </div>
 </details>
 @endif

 {{-- 2. ICMS Declarado vs Notas --}}
 @if(!empty($af['icms_declarado_vs_notas']))
 @php
 $icmsVs = $af['icms_declarado_vs_notas'];
 $alertasIcms = $alertasPorCategoria->get('icms', collect());
 @endphp
 <details>
 <summary class="px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition-colors">
 <div class="flex items-center gap-2">
 <span class="text-sm font-semibold text-gray-900">ICMS Declarado vs Notas</span>
 @foreach($alertasIcms->groupBy('tipo') as $tipo => $group)
 @php $cfg = $alertaTipoConfig[$tipo] ?? $alertaTipoConfig['info']; @endphp
 <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded" style="background-color: {{ $cfg['badgeHex'] }}">{{ $group->count() }}</span>
 @endforeach
 </div>
 <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
 </summary>
 <div class="px-4 pb-4">
 {{-- Alertas vinculados --}}
 @foreach($alertasIcms as $alerta)
 @php $cfg = $alertaTipoConfig[$alerta['tipo'] ?? 'info'] ?? $alertaTipoConfig['info']; @endphp
 <div class="border-l-4 {{ $cfg['border'] }} bg-white rounded p-3 mb-2">
 <div class="flex items-center gap-2 mb-1">
 <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: {{ $cfg['badgeHex'] }}">{{ $cfg['label'] }}</span>
 <span class="text-sm font-semibold text-gray-900">{{ $alerta['titulo'] ?? '' }}</span>
 </div>
 <p class="text-sm text-gray-600">{{ $alerta['descricao'] ?? $alerta['mensagem'] ?? '' }}</p>
 @if(!empty($alerta['valor']))
 <p class="text-xs text-gray-400 mt-1">Valor: {{ is_numeric($alerta['valor']) ? (is_float($alerta['valor'] + 0) ? 'R$ ' . number_format($alerta['valor'], 2, ',', '.') : $alerta['valor']) : $alerta['valor'] }}</p>
 @endif
 </div>
 @endforeach
 {{-- Conteúdo analítico --}}
 <div class="overflow-x-auto">
 <table class="min-w-full text-sm">
 <thead><tr class="border-b border-gray-300">
 <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 uppercase">Métrica</th>
 <th class="text-right py-2 px-3 text-xs font-medium text-gray-500 uppercase">Valor</th>
 </tr></thead>
 <tbody class="divide-y divide-gray-100">
 <tr><td class="py-2 px-3 text-gray-700">Débitos Declarados (E110)</td><td class="py-2 px-3 text-right font-mono text-gray-900">R$ {{ number_format($icmsVs['declarado_debitos'] ?? 0, 2, ',', '.') }}</td></tr>
 <tr><td class="py-2 px-3 text-gray-700">Créditos Declarados (E110)</td><td class="py-2 px-3 text-right font-mono text-gray-900">R$ {{ number_format($icmsVs['declarado_creditos'] ?? 0, 2, ',', '.') }}</td></tr>
 <tr><td class="py-2 px-3 text-gray-700">ICMS a Recolher</td><td class="py-2 px-3 text-right font-mono font-bold text-gray-900">R$ {{ number_format($icmsVs['declarado_recolher'] ?? 0, 2, ',', '.') }}</td></tr>
 <tr class="bg-gray-50"><td class="py-2 px-3 text-gray-700">Total Notas Entradas</td><td class="py-2 px-3 text-right font-mono text-gray-900">R$ {{ number_format($icmsVs['notas_entradas_valor'] ?? 0, 2, ',', '.') }}</td></tr>
 <tr class="bg-gray-50"><td class="py-2 px-3 text-gray-700">Total Notas Saídas</td><td class="py-2 px-3 text-right font-mono text-gray-900">R$ {{ number_format($icmsVs['notas_saidas_valor'] ?? 0, 2, ',', '.') }}</td></tr>
 <tr><td class="py-2 px-3 text-gray-700 font-medium">Ratio Débitos/Saídas</td><td class="py-2 px-3 text-right font-mono font-bold text-gray-700">{{ number_format($icmsVs['ratio_debitos_sobre_saidas'] ?? 0, 1, ',', '.') }}%</td></tr>
 </tbody>
 </table>
 @if(!empty($icmsVs['nota']))
 <p class="text-xs text-gray-400 mt-2 px-3 italic">{{ $icmsVs['nota'] }}</p>
 @endif
 </div>
 </div>
 </details>
 @endif

 {{-- 3. Concentração de Fornecedores --}}
 @if(!empty($af['concentracao_fornecedores']) && !empty($af['concentracao_fornecedores']['top_5']))
 @php
 $conc = $af['concentracao_fornecedores'];
 $alertasParticipantes = $alertasPorCategoria->get('participantes', collect());
 @endphp
 <details>
 <summary class="px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition-colors">
 <div class="flex items-center gap-2">
 <span class="text-sm font-semibold text-gray-900">Concentração de Fornecedores</span>
 @if($conc['concentracao_alta'] ?? false)
 <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #d97706">Alta</span>
 @endif
 @foreach($alertasParticipantes->groupBy('tipo') as $tipo => $group)
 @php $cfg = $alertaTipoConfig[$tipo] ?? $alertaTipoConfig['info']; @endphp
 <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded" style="background-color: {{ $cfg['badgeHex'] }}">{{ $group->count() }}</span>
 @endforeach
 </div>
 <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
 </summary>
 <div class="px-4 pb-4">
 @foreach($alertasParticipantes as $alerta)
 @php $cfg = $alertaTipoConfig[$alerta['tipo'] ?? 'info'] ?? $alertaTipoConfig['info']; @endphp
 <div class="border-l-4 {{ $cfg['border'] }} bg-white rounded p-3 mb-2">
 <div class="flex items-center gap-2 mb-1">
 <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: {{ $cfg['badgeHex'] }}">{{ $cfg['label'] }}</span>
 <span class="text-sm font-semibold text-gray-900">{{ $alerta['titulo'] ?? '' }}</span>
 </div>
 <p class="text-sm text-gray-600">{{ $alerta['descricao'] ?? $alerta['mensagem'] ?? '' }}</p>
 </div>
 @endforeach
 <p class="text-xs text-gray-500 mb-2">Total entradas: <span class="font-medium text-gray-700">R$ {{ number_format($conc['total_entradas_valor'] ?? 0, 2, ',', '.') }}</span></p>
 <div class="overflow-x-auto">
 <table class="min-w-full text-sm">
 <thead><tr class="border-b border-gray-300">
 <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 uppercase">#</th>
 <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 uppercase">CNPJ</th>
 <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 uppercase">Razão Social</th>
 <th class="text-right py-2 px-3 text-xs font-medium text-gray-500 uppercase">Notas</th>
 <th class="text-right py-2 px-3 text-xs font-medium text-gray-500 uppercase">Valor</th>
 <th class="text-right py-2 px-3 text-xs font-medium text-gray-500 uppercase">%</th>
 </tr></thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($conc['top_5'] as $i => $forn)
 @php $fornCnpj = $forn['documento'] ?? $forn['cnpj'] ?? null; @endphp
 <tr>
 <td class="py-2 px-3 text-gray-400 text-xs">{{ $i + 1 }}</td>
 <td class="py-2 px-3 font-mono text-xs">
 @if($fornCnpj)
 <a href="/app/notas-fiscais?participante_cnpj={{ $fornCnpj }}" data-link class="text-gray-600 hover:text-gray-900 hover:underline">{{ $fornCnpj }}</a>
 @else — @endif
 </td>
 <td class="py-2 px-3 text-gray-700 truncate max-w-[200px]">{{ $forn['razao_social'] ?? '—' }}</td>
 <td class="py-2 px-3 text-right text-gray-700">{{ $forn['num_notas'] ?? 0 }}</td>
 <td class="py-2 px-3 text-right font-mono text-gray-900">R$ {{ number_format($forn['valor_total'] ?? 0, 2, ',', '.') }}</td>
 <td class="py-2 px-3 text-right font-bold text-gray-700">{{ number_format($forn['percentual'] ?? 0, 1, ',', '.') }}%</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 </div>
 </details>
 @endif

 {{-- 4. Notas Irregulares --}}
 @if(!empty($af['notas_irregulares']) && ($af['notas_irregulares']['count'] ?? 0) > 0)
 @php
 $irreg = $af['notas_irregulares'];
 $alertasNotas = $alertasPorCategoria->get('notas', collect());
 @endphp
 <details>
 <summary class="px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition-colors">
 <div class="flex items-center gap-2">
 <span class="text-sm font-semibold text-gray-900">Notas Irregulares</span>
 <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #dc2626">{{ $irreg['count'] }}</span>
 @foreach($alertasNotas->groupBy('tipo') as $tipo => $group)
 @php $cfg = $alertaTipoConfig[$tipo] ?? $alertaTipoConfig['info']; @endphp
 <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded" style="background-color: {{ $cfg['badgeHex'] }}">{{ $group->count() }} {{ $cfg['label'] }}</span>
 @endforeach
 </div>
 <div class="flex items-center gap-2">
 <span class="text-xs text-gray-500">R$ {{ number_format($irreg['valor_total'] ?? 0, 2, ',', '.') }}</span>
 <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
 </div>
 </summary>
 <div class="px-4 pb-4">
 @foreach($alertasNotas as $alerta)
 @php $cfg = $alertaTipoConfig[$alerta['tipo'] ?? 'info'] ?? $alertaTipoConfig['info']; @endphp
 <div class="border-l-4 {{ $cfg['border'] }} bg-white rounded p-3 mb-2">
 <div class="flex items-center gap-2 mb-1">
 <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: {{ $cfg['badgeHex'] }}">{{ $cfg['label'] }}</span>
 <span class="text-sm font-semibold text-gray-900">{{ $alerta['titulo'] ?? '' }}</span>
 </div>
 <p class="text-sm text-gray-600">{{ $alerta['descricao'] ?? $alerta['mensagem'] ?? '' }}</p>
 @if(!empty($alerta['valor']))
 <p class="text-xs text-gray-400 mt-1">Valor: {{ is_numeric($alerta['valor']) ? (is_float($alerta['valor'] + 0) ? 'R$ ' . number_format($alerta['valor'], 2, ',', '.') : $alerta['valor']) : $alerta['valor'] }}</p>
 @endif
 </div>
 @endforeach
 @if(!empty($irreg['resumo_por_situacao']))
 <div class="flex flex-wrap gap-2 mb-3">
 @foreach($irreg['resumo_por_situacao'] as $cod => $sit)
 <span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-700">
 {{ $sit['label'] ?? 'Cód. '.$cod }}: <span class="font-bold">{{ $sit['count'] ?? 0 }}</span>
 </span>
 @endforeach
 </div>
 @endif
 @if(!empty($irreg['lista']))
 <div class="overflow-x-auto max-h-64 overflow-y-auto">
 <table class="min-w-full text-xs">
 <thead class="sticky top-0 bg-white"><tr class="border-b border-gray-200">
 <th class="text-left py-1.5 px-2 text-gray-500 uppercase">Nº Doc</th>
 <th class="text-left py-1.5 px-2 text-gray-500 uppercase">Situação</th>
 <th class="text-right py-1.5 px-2 text-gray-500 uppercase">Valor</th>
 <th class="text-left py-1.5 px-2 text-gray-500 uppercase">Bloco</th>
 </tr></thead>
 <tbody class="divide-y divide-gray-100">
 @foreach(array_slice($irreg['lista'], 0, 50) as $nota)
 <tr>
 <td class="py-1.5 px-2 font-mono">
 @if(!empty($nota['nota_id']))
 <a href="/app/notas-fiscais/efd/{{ $nota['nota_id'] }}" data-link class="text-gray-600 hover:text-gray-900 hover:underline">{{ $nota['num_doc'] ?? $nota['nota_id'] }}</a>
 @elseif(!empty($nota['num_doc']))
 <span class="text-gray-700">{{ $nota['num_doc'] }}</span>
 @else — @endif
 </td>
 <td class="py-1.5 px-2 text-gray-700">{{ $nota['cod_sit_label'] ?? $nota['cod_sit'] ?? '—' }}</td>
 <td class="py-1.5 px-2 text-right font-mono text-gray-900">R$ {{ number_format($nota['valor'] ?? 0, 2, ',', '.') }}</td>
 <td class="py-1.5 px-2 text-gray-500">{{ $nota['bloco'] ?? '—' }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 @endif
 </div>
 </details>
 @endif

 {{-- 4b. Notas em Situação Especial --}}
 @if(!empty($af['notas_situacao_especial']) && ($af['notas_situacao_especial']['count'] ?? 0) > 0)
 @php $espec = $af['notas_situacao_especial']; @endphp
 <details>
 <summary class="px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition-colors">
 <div class="flex items-center gap-2">
 <span class="text-sm font-semibold text-gray-900">Notas em Situação Especial</span>
 <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #374151">{{ $espec['count'] }}</span>
 </div>
 <div class="flex items-center gap-2">
 <span class="text-xs text-gray-500">R$ {{ number_format($espec['valor_total'] ?? 0, 2, ',', '.') }}</span>
 <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
 </div>
 </summary>
 <div class="px-4 pb-4">
 <p class="text-xs text-gray-600 mb-2">Notas legítimas em regime especial, extemporâneas ou complementares.</p>
 @if(!empty($espec['resumo_por_situacao']))
 <div class="flex flex-wrap gap-2 mb-3">
 @foreach($espec['resumo_por_situacao'] as $cod => $sit)
 <span class="px-2 py-1 bg-gray-100 border border-gray-300 rounded text-xs text-gray-700">
 {{ $sit['label'] ?? 'Cód. '.$cod }}: <span class="font-bold">{{ $sit['count'] ?? 0 }}</span>
 </span>
 @endforeach
 </div>
 @endif
 @if(!empty($espec['lista']))
 <div class="overflow-x-auto max-h-64 overflow-y-auto">
 <table class="min-w-full text-xs">
 <thead class="sticky top-0 bg-white"><tr class="border-b border-gray-200">
 <th class="text-left py-1.5 px-2 text-gray-500 uppercase">Nº Doc</th>
 <th class="text-left py-1.5 px-2 text-gray-500 uppercase">Situação</th>
 <th class="text-right py-1.5 px-2 text-gray-500 uppercase">Valor</th>
 <th class="text-left py-1.5 px-2 text-gray-500 uppercase">Bloco</th>
 </tr></thead>
 <tbody class="divide-y divide-gray-100">
 @foreach(array_slice($espec['lista'], 0, 50) as $nota)
 <tr>
 <td class="py-1.5 px-2 font-mono">
 @if(!empty($nota['nota_id']))
 <a href="/app/notas-fiscais/efd/{{ $nota['nota_id'] }}" data-link class="text-gray-600 hover:text-gray-900 hover:underline">{{ $nota['num_doc'] ?? $nota['nota_id'] }}</a>
 @elseif(!empty($nota['num_doc']))
 <span class="text-gray-700">{{ $nota['num_doc'] }}</span>
 @else — @endif
 </td>
 <td class="py-1.5 px-2 text-gray-700">{{ $nota['cod_sit_label'] ?? $nota['cod_sit'] ?? '—' }}</td>
 <td class="py-1.5 px-2 text-right font-mono text-gray-900">R$ {{ number_format($nota['valor'] ?? 0, 2, ',', '.') }}</td>
 <td class="py-1.5 px-2 text-gray-500">{{ $nota['bloco'] ?? '—' }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 @endif
 </div>
 </details>
 @endif

 {{-- 5. Duplicatas --}}
 @if(!empty($af['duplicatas']) && ($af['duplicatas']['count'] ?? 0) > 0)
 @php $dup = $af['duplicatas']; @endphp
 <details>
 <summary class="px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition-colors">
 <span class="text-sm font-semibold text-gray-900">Duplicatas</span>
 <div class="flex items-center gap-2">
 <span class="text-xs text-gray-500">{{ $dup['count'] }} · R$ {{ number_format($dup['valor_total_potencial'] ?? 0, 2, ',', '.') }}</span>
 <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
 </div>
 </summary>
 <div class="px-4 pb-4">
 <div class="grid grid-cols-3 gap-3">
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">Quantidade</p>
 <p class="text-lg font-bold text-gray-700">{{ $dup['count'] }}</p>
 </div>
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">Valor Potencial</p>
 <p class="text-lg font-bold text-gray-800">R$ {{ number_format($dup['valor_total_potencial'] ?? 0, 2, ',', '.') }}</p>
 </div>
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">% do Total</p>
 <p class="text-lg font-bold text-gray-700">{{ number_format($dup['percentual_do_total'] ?? 0, 1, ',', '.') }}%</p>
 </div>
 </div>
 </div>
 </details>
 @endif

 {{-- 6. Análise de Frete --}}
 @if(!empty($af['analise_frete']))
 @php
 $frete = $af['analise_frete'];
 $alertasLogistica = $alertasPorCategoria->get('logistica', collect());
 @endphp
 <details>
 <summary class="px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition-colors">
 <div class="flex items-center gap-2">
 <span class="text-sm font-semibold text-gray-900">Análise de Frete</span>
 @foreach($alertasLogistica->groupBy('tipo') as $tipo => $group)
 @php $cfg = $alertaTipoConfig[$tipo] ?? $alertaTipoConfig['info']; @endphp
 <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded" style="background-color: {{ $cfg['badgeHex'] }}">{{ $group->count() }}</span>
 @endforeach
 </div>
 <div class="flex items-center gap-2">
 <span class="text-xs text-gray-500">R$ {{ number_format($frete['total_frete'] ?? 0, 2, ',', '.') }}</span>
 <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
 </div>
 </summary>
 <div class="px-4 pb-4">
 @foreach($alertasLogistica as $alerta)
 @php $cfg = $alertaTipoConfig[$alerta['tipo'] ?? 'info'] ?? $alertaTipoConfig['info']; @endphp
 <div class="border-l-4 {{ $cfg['border'] }} bg-white rounded p-3 mb-2">
 <div class="flex items-center gap-2 mb-1">
 <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: {{ $cfg['badgeHex'] }}">{{ $cfg['label'] }}</span>
 <span class="text-sm font-semibold text-gray-900">{{ $alerta['titulo'] ?? '' }}</span>
 </div>
 <p class="text-sm text-gray-600">{{ $alerta['descricao'] ?? $alerta['mensagem'] ?? '' }}</p>
 </div>
 @endforeach
 <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">Total Frete</p>
 <p class="text-sm font-bold text-gray-800">R$ {{ number_format($frete['total_frete'] ?? 0, 2, ',', '.') }}</p>
 </div>
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">Total Mercadorias</p>
 <p class="text-sm font-bold text-gray-800">R$ {{ number_format($frete['total_mercadorias'] ?? 0, 2, ',', '.') }}</p>
 </div>
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">% Frete/Mercad.</p>
 <p class="text-sm font-bold text-gray-700">{{ number_format($frete['percentual_frete_sobre_mercadorias'] ?? 0, 1, ',', '.') }}%</p>
 </div>
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">CT-es</p>
 <p class="text-sm font-bold text-gray-800">{{ $frete['total_ctes'] ?? 0 }}</p>
 </div>
 </div>
 @if(!empty($frete['rotas']))
 <p class="text-xs font-medium text-gray-600 mb-1.5">Top 5 Rotas</p>
 <table class="min-w-full text-xs">
 <thead><tr class="border-b border-gray-300">
 <th class="text-left py-1.5 px-2 text-gray-500 uppercase">Rota (Origem → Destino)</th>
 <th class="text-right py-1.5 px-2 text-gray-500 uppercase">CT-es</th>
 <th class="text-right py-1.5 px-2 text-gray-500 uppercase">Valor</th>
 </tr></thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($frete['rotas'] as $rota)
 <tr>
 <td class="py-1.5 px-2 text-gray-700 font-mono">{{ $rota['rota'] ?? ($rota['origem'] ?? '?') . ' → ' . ($rota['destino'] ?? '?') }}</td>
 <td class="py-1.5 px-2 text-right text-gray-700">{{ $rota['count'] ?? 0 }}</td>
 <td class="py-1.5 px-2 text-right font-mono text-gray-900">R$ {{ number_format($rota['valor_total'] ?? 0, 2, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 @endif
 </div>
 </details>
 @endif

 {{-- 7. Obrigações a Vencer --}}
 @if(!empty($af['obrigacoes_a_vencer']) && !empty($af['obrigacoes_a_vencer']['lista'] ?? $af['obrigacoes_a_vencer']['obrigacoes'] ?? null))
 @php
 $obr = $af['obrigacoes_a_vencer'];
 $alertasObrigacoes = $alertasPorCategoria->get('obrigacoes', collect());
 @endphp
 <details>
 <summary class="px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition-colors">
 <div class="flex items-center gap-2">
 <span class="text-sm font-semibold text-gray-900">Obrigações a Vencer</span>
 @if(($obr['vencidas'] ?? 0) > 0)
 <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #dc2626">{{ $obr['vencidas'] }} vencida{{ ($obr['vencidas'] ?? 0) > 1 ? 's' : '' }}</span>
 @endif
 @if(($obr['proximas_7_dias'] ?? 0) > 0)
 <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: #d97706">{{ $obr['proximas_7_dias'] }} próx.</span>
 @endif
 @foreach($alertasObrigacoes->groupBy('tipo') as $tipo => $group)
 @php $cfg = $alertaTipoConfig[$tipo] ?? $alertaTipoConfig['info']; @endphp
 <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded" style="background-color: {{ $cfg['badgeHex'] }}">{{ $group->count() }} {{ $cfg['label'] }}</span>
 @endforeach
 </div>
 <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
 </summary>
 <div class="px-4 pb-4">
 @foreach($alertasObrigacoes as $alerta)
 @php $cfg = $alertaTipoConfig[$alerta['tipo'] ?? 'info'] ?? $alertaTipoConfig['info']; @endphp
 <div class="border-l-4 {{ $cfg['border'] }} bg-white rounded p-3 mb-2">
 <div class="flex items-center gap-2 mb-1">
 <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: {{ $cfg['badgeHex'] }}">{{ $cfg['label'] }}</span>
 <span class="text-sm font-semibold text-gray-900">{{ $alerta['titulo'] ?? '' }}</span>
 </div>
 <p class="text-sm text-gray-600">{{ $alerta['descricao'] ?? $alerta['mensagem'] ?? '' }}</p>
 </div>
 @endforeach
 <p class="text-xs text-gray-500 mb-2">Total a recolher: <span class="font-bold text-gray-700">R$ {{ number_format($obr['total_a_recolher'] ?? $obr['total_recolher'] ?? 0, 2, ',', '.') }}</span></p>
 <div class="overflow-x-auto">
 <table class="min-w-full text-xs">
 <thead><tr class="border-b border-gray-300">
 <th class="text-left py-1.5 px-2 text-gray-500 uppercase">Tipo</th>
 <th class="text-left py-1.5 px-2 text-gray-500 uppercase">Cód. Receita</th>
 <th class="text-left py-1.5 px-2 text-gray-500 uppercase">Vencimento</th>
 <th class="text-right py-1.5 px-2 text-gray-500 uppercase">Valor</th>
 <th class="text-right py-1.5 px-2 text-gray-500 uppercase">Dias</th>
 </tr></thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($obr['lista'] ?? $obr['obrigacoes'] ?? [] as $ob)
 @php
 $dias = $ob['dias_para_vencimento'] ?? null;
 $diasClass = $dias === null ? 'text-gray-400' : ($dias < 0 ? 'text-gray-700 font-bold' : ($dias <= 7 ? 'text-gray-700 font-bold' : 'text-gray-700'));
 $diasLabel = $dias === null ? '—' : ($dias < 0 ? 'Vencida' : ($dias == 0 ? 'Hoje' : $dias . 'd'));
 @endphp
 <tr>
 <td class="py-1.5 px-2 text-gray-700">{{ $ob['tipo'] ?? '—' }}</td>
 <td class="py-1.5 px-2 text-gray-700 font-mono">{{ $ob['cod_receita'] ?? '—' }}</td>
 <td class="py-1.5 px-2 text-gray-700">{{ $ob['data_vencimento'] ?? '—' }}</td>
 <td class="py-1.5 px-2 text-right font-mono text-gray-900">R$ {{ number_format($ob['valor'] ?? 0, 2, ',', '.') }}</td>
 <td class="py-1.5 px-2 text-right {{ $diasClass }}">{{ $diasLabel }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 </div>
 </details>
 @endif

 {{-- 8. Alertas do Catálogo --}}
 @if(!empty($af['catalogo_alertas']))
 @php
 $cat = $af['catalogo_alertas'];
 $alertasCatalogo = $alertasPorCategoria->get('catalogo', collect());
 @endphp
 <details>
 <summary class="px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition-colors">
 <div class="flex items-center gap-2">
 <span class="text-sm font-semibold text-gray-900">Alertas do Catálogo</span>
 @foreach($alertasCatalogo->groupBy('tipo') as $tipo => $group)
 @php $cfg = $alertaTipoConfig[$tipo] ?? $alertaTipoConfig['info']; @endphp
 <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded" style="background-color: {{ $cfg['badgeHex'] }}">{{ $group->count() }}</span>
 @endforeach
 </div>
 <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
 </summary>
 <div class="px-4 pb-4">
 @foreach($alertasCatalogo as $alerta)
 @php $cfg = $alertaTipoConfig[$alerta['tipo'] ?? 'info'] ?? $alertaTipoConfig['info']; @endphp
 <div class="border-l-4 {{ $cfg['border'] }} bg-white rounded p-3 mb-2">
 <div class="flex items-center gap-2 mb-1">
 <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: {{ $cfg['badgeHex'] }}">{{ $cfg['label'] }}</span>
 <span class="text-sm font-semibold text-gray-900">{{ $alerta['titulo'] ?? '' }}</span>
 </div>
 <p class="text-sm text-gray-600">{{ $alerta['descricao'] ?? $alerta['mensagem'] ?? '' }}</p>
 </div>
 @endforeach
 <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
 @if(isset($cat['itens_sem_ncm']))
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">Sem NCM</p>
 <p class="text-lg font-bold text-gray-700">{{ $cat['itens_sem_ncm'] }}</p>
 </div>
 @endif
 @if(isset($cat['itens_aliq_zero']) || isset($cat['itens_aliquota_zero']))
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">Alíq. Zero</p>
 <p class="text-lg font-bold text-gray-800">{{ $cat['itens_aliq_zero'] ?? $cat['itens_aliquota_zero'] ?? 0 }}</p>
 </div>
 @endif
 @if(isset($cat['total_itens']))
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">Total Itens</p>
 <p class="text-lg font-bold text-gray-800">{{ $cat['total_itens'] }}</p>
 </div>
 @endif
 </div>
 @php $ncmDist = $cat['distribuicao_ncm'] ?? $cat['top_capitulos_ncm'] ?? []; @endphp
 @if(!empty($ncmDist))
 <p class="text-xs font-medium text-gray-600 mt-3 mb-1.5">Top Capítulos NCM</p>
 <div class="space-y-1">
 @foreach($ncmDist as $cap)
 <div class="flex items-center justify-between px-2 py-1.5 bg-gray-50 rounded">
 <span class="text-xs text-gray-700 font-mono">Cap. {{ $cap['cod_gen'] ?? $cap['capitulo'] ?? '—' }}</span>
 <div class="flex items-center gap-2">
 <span class="text-xs font-medium text-gray-700">{{ $cap['count'] ?? 0 }} itens</span>
 @if(isset($cap['percentual']))
 <span class="text-xs text-gray-400">({{ number_format($cap['percentual'], 1, ',', '.') }}%)</span>
 @endif
 </div>
 </div>
 @endforeach
 </div>
 @endif
 </div>
 </details>
 @endif

 {{-- 9. Análise Geográfica --}}
 @if(!empty($af['analise_geografica']) && !empty($af['analise_geografica']['ufs_origem'] ?? $af['analise_geografica']['ufs'] ?? null))
 @php
 $geo = $af['analise_geografica'];
 $alertasGeo = $alertasPorCategoria->get('geografico', collect());
 @endphp
 <details>
 <summary class="px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 transition-colors">
 <div class="flex items-center gap-2">
 <span class="text-sm font-semibold text-gray-900">Análise Geográfica</span>
 @foreach($alertasGeo->groupBy('tipo') as $tipo => $group)
 @php $cfg = $alertaTipoConfig[$tipo] ?? $alertaTipoConfig['info']; @endphp
 <span class="text-[10px] font-bold text-white px-2 py-0.5 rounded" style="background-color: {{ $cfg['badgeHex'] }}">{{ $group->count() }}</span>
 @endforeach
 </div>
 <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
 </summary>
 <div class="px-4 pb-4">
 @foreach($alertasGeo as $alerta)
 @php $cfg = $alertaTipoConfig[$alerta['tipo'] ?? 'info'] ?? $alertaTipoConfig['info']; @endphp
 <div class="border-l-4 {{ $cfg['border'] }} bg-white rounded p-3 mb-2">
 <div class="flex items-center gap-2 mb-1">
 <span class="px-2 py-0.5 rounded text-[10px] font-bold text-white" style="background-color: {{ $cfg['badgeHex'] }}">{{ $cfg['label'] }}</span>
 <span class="text-sm font-semibold text-gray-900">{{ $alerta['titulo'] ?? '' }}</span>
 </div>
 <p class="text-sm text-gray-600">{{ $alerta['descricao'] ?? $alerta['mensagem'] ?? '' }}</p>
 </div>
 @endforeach
 @if(isset($geo['operacoes_interestaduais']))
 @php $interest = $geo['operacoes_interestaduais']; @endphp
 <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">Interestaduais</p>
 <p class="text-sm font-bold text-gray-800">{{ $interest['count'] ?? 0 }} notas</p>
 </div>
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">Valor Interestadual</p>
 <p class="text-sm font-bold text-gray-800">R$ {{ number_format($interest['valor_total'] ?? $interest['valor'] ?? 0, 2, ',', '.') }}</p>
 </div>
 @if(isset($interest['percentual']))
 <div class="bg-gray-50 rounded p-3 text-center">
 <p class="text-[10px] text-gray-500 uppercase tracking-wide">% Interestadual</p>
 <p class="text-sm font-bold text-gray-800">{{ number_format($interest['percentual'], 1, ',', '.') }}%</p>
 </div>
 @endif
 </div>
 @endif
 @php $ufsData = $geo['ufs_origem'] ?? $geo['ufs'] ?? []; @endphp
 @if(!empty($ufsData))
 @if(isset($geo['uf_declarante']))
 <p class="text-xs text-gray-500 mb-1.5">UF declarante: <span class="font-bold text-gray-700">{{ $geo['uf_declarante'] }}</span></p>
 @endif
 <table class="min-w-full text-xs">
 <thead><tr class="border-b border-gray-300">
 <th class="text-left py-1.5 px-2 text-gray-500 uppercase">UF</th>
 <th class="text-right py-1.5 px-2 text-gray-500 uppercase">Notas</th>
 <th class="text-right py-1.5 px-2 text-gray-500 uppercase">Valor</th>
 </tr></thead>
 <tbody class="divide-y divide-gray-100">
 @foreach($ufsData as $ufKey => $uf)
 <tr>
 <td class="py-1.5 px-2 text-gray-700 font-bold">{{ $uf['sigla'] ?? $uf['uf'] ?? $ufKey }}</td>
 <td class="py-1.5 px-2 text-right text-gray-700">{{ $uf['count'] ?? 0 }}</td>
 <td class="py-1.5 px-2 text-right font-mono text-gray-900">R$ {{ number_format($uf['valor_total'] ?? $uf['valor'] ?? 0, 2, ',', '.') }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 @endif
 </div>
 </details>
 @endif

 </div>
</div>
