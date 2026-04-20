{{-- Monitoramento - Detalhe do Participante --}}
@php
    $situacaoUpper = strtoupper((string) ($participante->situacao_cadastral ?? ''));
    $situacaoBadge = match($situacaoUpper) {
        'ATIVA', '02' => ['label' => 'ATIVA', 'hex' => '#047857'],
        'INAPTA', 'SUSPENSA' => ['label' => $situacaoUpper, 'hex' => '#dc2626'],
        'BAIXADA' => ['label' => 'BAIXADA', 'hex' => '#9ca3af'],
        default => ['label' => $situacaoUpper ?: 'SEM STATUS', 'hex' => '#6b7280'],
    };
    $regimeUpper = strtoupper((string) ($participante->regime_tributario ?? ''));
    $regimeBadge = match($regimeUpper) {
        'SIMPLES NACIONAL', 'SIMPLES' => ['label' => $regimeUpper, 'hex' => '#0f766e'],
        'LUCRO PRESUMIDO' => ['label' => $regimeUpper, 'hex' => '#d97706'],
        'LUCRO REAL' => ['label' => $regimeUpper, 'hex' => '#374151'],
        default => $regimeUpper ? ['label' => $regimeUpper, 'hex' => '#6b7280'] : null,
    };
@endphp
<div class="min-h-screen bg-gray-100" id="monitoramento-participante-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="mb-4 sm:mb-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                    <a
                        href="/app/dashboard"
                        class="text-xs text-gray-600 hover:text-gray-900 hover:underline"
                        data-link
                    >
                        Voltar para o dashboard
                    </a>
                        <span class="text-gray-300 hidden sm:inline">|</span>
                        <span class="text-xs text-gray-500">Detalhe operacional do participante</span>
                    </div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide mt-2">{{ $participante->razao_social ?? 'Participante' }}</h1>
                    <p class="text-xs text-gray-500 mt-1 font-mono whitespace-nowrap tabular-nums">{{ $participante->cnpj_formatado }}</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    @if($participante->situacao_cadastral)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $situacaoBadge['hex'] }}">
                            {{ $situacaoBadge['label'] }}
                        </span>
                    @endif
                    @if($regimeBadge)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $regimeBadge['hex'] }}">
                            {{ $regimeBadge['label'] }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded border border-gray-300 overflow-hidden mb-6 sm:mb-8">
            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Ações Operacionais</span>
                        <p class="text-[11px] text-gray-500 mt-1">Gerencie consulta, assinatura e origem cadastral deste participante.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            href="/app/participante/{{ $participante->id }}/editar"
                            data-link
                            class="px-3 py-2 text-sm font-medium bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded"
                        >
                            Editar cadastro
                        </a>
                        <a
                            href="/app/consulta/nova?participantes={{ $participante->id }}"
                            data-link
                            class="px-3 py-2 text-sm font-medium bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded"
                        >
                            Nova consulta
                        </a>
                        @if(!$assinaturaAtiva)
                            <button
                                type="button"
                                id="btn-criar-assinatura"
                                class="px-3 py-2 text-sm font-medium bg-gray-800 text-white hover:bg-gray-700 rounded"
                            >
                                Criar assinatura
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-200">
                <div class="p-4 sm:p-6">
                    @php
                        $origemLabel = match($participante->origem_tipo) {
                            'SPED_EFD_FISCAL' => 'EFD ICMS/IPI',
                            'SPED_EFD_CONTRIB' => 'EFD PIS/COFINS',
                            'NFE' => 'NF-e',
                            'NFSE' => 'NFS-e',
                            'MANUAL' => 'Manual',
                            default => $participante->origem_tipo ?? 'Manual',
                        };
                        $arquivoOrigem = $participante->origem_ref['arquivo'] ?? null;
                    @endphp
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Origem</p>
                    <p class="text-lg font-bold text-gray-900">{{ $origemLabel }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $arquivoOrigem ?: 'Sem arquivo vinculado' }}</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Última Consulta</p>
                    <p class="text-lg font-bold text-gray-900">{{ $participante->ultima_consulta_em ? $participante->ultima_consulta_em->format('d/m/Y') : 'Nunca' }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $participante->ultima_consulta_em ? $participante->ultima_consulta_em->format('H:i') : 'Sem consulta realizada' }}</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Situação</p>
                    <p class="text-lg font-bold text-gray-900">{{ $situacaoBadge['label'] }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">Base cadastral e fiscal monitorada</p>
                </div>
                <div class="p-4 sm:p-6">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1 sm:mb-2">Documento</p>
                    <p class="text-lg font-bold text-gray-900 font-mono">{{ $participante->cnpj_formatado }}</p>
                    <p class="text-[11px] text-gray-500 mt-1">{{ $participante->municipio ?: 'Município não informado' }}{{ $participante->uf ? ' / '.$participante->uf : '' }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            {{-- Coluna Principal --}}
            <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                {{-- Dados Cadastrais --}}
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Dados Cadastrais</span>
                    </div>
                    <div class="p-4 sm:p-6">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 sm:gap-x-6 gap-y-4">
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">CNPJ</dt>
                                <dd class="mt-1 text-sm font-mono text-gray-900">{{ $participante->cnpj_formatado }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Razão Social</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $participante->razao_social ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Situação Cadastral</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $participante->situacao_cadastral ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Regime Tributário</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $participante->regime_tributario ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">UF</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $participante->uf ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Município</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $participante->municipio ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Porte</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $participante->porte ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500 uppercase tracking-wide">Cadastrado em</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $participante->created_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- Dados da Última Consulta --}}
                @if($ultimaConsulta && $ultimaConsulta->resultado_dados)
                    @php
                        $dados = $ultimaConsulta->resultado_dados;
                        $consultasRealizadas = $dados['consultas_realizadas'] ?? [];
                    @endphp
                    <div class="bg-white rounded border border-gray-300 overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Dados da Última Consulta</span>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Consultado em {{ $ultimaConsulta->consultado_em?->format('d/m/Y H:i') }}
                                        @if($ultimaConsulta->lote)
                                        <span class="mx-1">|</span>
                                        <a href="/app/consulta/historico?lote={{ $ultimaConsulta->lote->id }}"
                                           class="text-gray-600 hover:text-gray-900 hover:underline"
                                           data-link>
                                            Lote #{{ $ultimaConsulta->lote->id }}
                                        </a>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($ultimaConsulta->lote?->plano)
                                    @php
                                        $planoBadgeColors = [
                                            'gratuito' => '#6b7280',
                                            'validacao' => '#4338ca',
                                            'licitacao' => '#7c3aed',
                                            'compliance' => '#d97706',
                                            'due_diligence' => '#be123c',
                                            'enterprise' => '#1f2937',
                                        ];
                                        $badgeColor = $planoBadgeColors[$ultimaConsulta->lote->plano->codigo] ?? '#6b7280';
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $badgeColor }}">
                                        {{ $ultimaConsulta->lote->plano->nome }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="p-4 sm:p-6 space-y-6">
                            {{-- Situação Cadastral e Regime Tributário — DANFE Modernizado --}}
                            @php
                                $situacao = strtoupper(trim((string) ($dados['situacao_cadastral'] ?? '')));
                                if ($situacao === 'ATIVA') {
                                    $sitLabel = 'Ativa';                  $sitHex = '#047857';
                                } elseif (in_array($situacao, ['SUSPENSA', 'INAPTA'], true)) {
                                    $sitLabel = ucfirst(mb_strtolower($situacao)); $sitHex = '#d97706';
                                } elseif (in_array($situacao, ['BAIXADA', 'NULA'], true)) {
                                    $sitLabel = ucfirst(mb_strtolower($situacao)); $sitHex = '#dc2626';
                                } elseif ($situacao !== '' && $situacao !== 'DESCONHECIDA') {
                                    $sitLabel = ucfirst(mb_strtolower($situacao)); $sitHex = '#374151';
                                } else {
                                    $sitLabel = 'Não informada';          $sitHex = '#9ca3af';
                                }

                                $regimeRaw = trim((string) ($dados['regime_tributario'] ?? ''));
                                $regimeUp  = strtoupper($regimeRaw);
                                $isSimples = ($dados['simples_nacional'] ?? false) === true;
                                $isMei     = ($dados['mei'] ?? false) === true;

                                if ($isMei) {
                                    $regLabel = 'MEI';                    $regHex = '#047857';
                                } elseif ($isSimples || str_contains($regimeUp, 'SIMPLES')) {
                                    $regLabel = 'Simples Nacional';       $regHex = '#047857';
                                } elseif (str_contains($regimeUp, 'PRESUMIDO')) {
                                    $regLabel = 'Lucro Presumido';        $regHex = '#0f766e';
                                } elseif (str_contains($regimeUp, 'REAL')) {
                                    $regLabel = 'Lucro Real';             $regHex = '#0f766e';
                                } elseif ($regimeRaw !== '') {
                                    $regLabel = $regimeRaw;               $regHex = '#374151';
                                } else {
                                    $regLabel = 'Não informado';          $regHex = '#9ca3af';
                                }

                                $inicioFmt = null;
                                if (! empty($dados['data_inicio_atividade'])) {
                                    try { $inicioFmt = \Carbon\Carbon::parse($dados['data_inicio_atividade'])->format('d/m/Y'); }
                                    catch (\Exception $e) { $inicioFmt = (string) $dados['data_inicio_atividade']; }
                                }
                                $dtOpcao = null;
                                if (! empty($dados['data_opcao_simples'])) {
                                    try { $dtOpcao = \Carbon\Carbon::parse($dados['data_opcao_simples'])->format('d/m/Y'); }
                                    catch (\Exception $e) { $dtOpcao = (string) $dados['data_opcao_simples']; }
                                }
                                $dtExclusao = null;
                                if (! empty($dados['data_exclusao_simples'])) {
                                    try { $dtExclusao = \Carbon\Carbon::parse($dados['data_exclusao_simples'])->format('d/m/Y'); }
                                    catch (\Exception $e) { $dtExclusao = (string) $dados['data_exclusao_simples']; }
                                }

                                $temSituacao = isset($dados['situacao_cadastral']);
                                $temRegime   = isset($dados['regime_tributario']) || isset($dados['simples_nacional']) || isset($dados['mei']);
                            @endphp

                            @if($temSituacao || $temRegime)
                            @php
                                $crtVal = $participante->crt ?? null;
                                $crtLabel = '—';
                                if ($crtVal === 1) $crtLabel = '1 — Simples Nacional';
                                elseif ($crtVal === 2) $crtLabel = '2 — Simples excesso subl.';
                                elseif ($crtVal === 3) $crtLabel = '3 — Regime Normal';
                                elseif ($crtVal === 4) $crtLabel = '4 — MEI';
                            @endphp
                            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                                <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Situação Fiscal</span>
                                </div>

                                @if($temRegime)
                                <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-gray-200">
                                    <div class="px-3 py-2.5">
                                        <p class="flex items-center gap-1.5 text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">
                                            <span class="inline-block w-2 h-2" style="background-color: {{ $regHex }}"></span>
                                            Regime Tributário
                                        </p>
                                        <p class="mt-1.5 text-sm text-gray-900 font-semibold leading-tight inline-block border-b-2 pb-0.5" style="border-bottom-color: {{ $regHex }}">{{ $regLabel }}</p>
                                        @if(! empty($dados['regime_tributario_ano']))
                                        <p class="text-[11px] text-gray-500 leading-tight mt-0.5">Ano-base {{ $dados['regime_tributario_ano'] }}</p>
                                        @endif
                                    </div>
                                    <div class="px-3 py-2.5">
                                        <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">Simples Nacional</p>
                                        <p class="mt-1.5 text-sm text-gray-900 font-semibold leading-tight">{{ $isSimples ? 'Optante' : 'Não optante' }}</p>
                                        @if($dtOpcao)
                                        <p class="text-[11px] text-gray-500 leading-tight mt-0.5">Opção {{ $dtOpcao }}</p>
                                        @endif
                                        @if($dtExclusao)
                                        <p class="text-[11px] leading-tight mt-0.5" style="color:#dc2626">Exclusão {{ $dtExclusao }}</p>
                                        @endif
                                    </div>
                                    <div class="px-3 py-2.5">
                                        <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">MEI</p>
                                        <p class="mt-1.5 text-sm text-gray-900 font-semibold leading-tight">{{ $isMei ? 'Sim' : 'Não' }}</p>
                                    </div>
                                    <div class="px-3 py-2.5">
                                        <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">CRT</p>
                                        <p class="mt-1.5 text-sm text-gray-900 font-mono leading-tight">{{ $crtLabel }}</p>
                                    </div>
                                </div>
                                @endif

                                @if($temSituacao)
                                <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-gray-200 {{ $temRegime ? 'border-t border-gray-200' : '' }}">
                                    <div class="px-3 py-2.5">
                                        <p class="flex items-center gap-1.5 text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">
                                            <span class="inline-block w-2 h-2" style="background-color: {{ $sitHex }}"></span>
                                            Situação Cadastral
                                        </p>
                                        <p class="mt-1.5 text-sm text-gray-900 font-semibold leading-tight inline-block border-b-2 pb-0.5" style="border-bottom-color: {{ $sitHex }}">{{ $sitLabel }}</p>
                                    </div>
                                    <div class="px-3 py-2.5">
                                        <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">Início Atividade</p>
                                        <p class="mt-1.5 text-sm text-gray-900 font-semibold leading-tight">{{ $inicioFmt ?? '—' }}</p>
                                    </div>
                                    <div class="px-3 py-2.5">
                                        <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">Tipo</p>
                                        <p class="mt-1.5 text-sm text-gray-900 font-semibold leading-tight">{{ $dados['matriz_filial'] ?? '—' }}</p>
                                    </div>
                                    <div class="px-3 py-2.5">
                                        <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">Motivo</p>
                                        <p class="mt-1.5 text-sm text-gray-700 leading-tight">{{ $dados['motivo_situacao_cadastral'] ?? '—' }}</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif

                            {{-- Certidões Negativas — cell grid DANFE / NF-e --}}
                            @php
                                $certidoesConfig = [
                                    ['key' => 'cnd_federal',  'titulo' => 'CND Federal',        'subtitulo' => 'Receita Federal / PGFN'],
                                    ['key' => 'cnd_estadual', 'titulo' => 'CND Estadual',       'subtitulo' => 'Fazenda Estadual'],
                                    ['key' => 'crf_fgts',     'titulo' => 'CRF (FGTS)',         'subtitulo' => 'Caixa Econômica Federal'],
                                    ['key' => 'cndt',         'titulo' => 'CNDT (Trabalhista)', 'subtitulo' => 'Tribunal Superior do Trabalho'],
                                ];

                                $certidoes = [];
                                foreach ($certidoesConfig as $cfg) {
                                    $d = $dados[$cfg['key']] ?? null;
                                    if (! is_array($d) || empty($d)) continue;

                                    $status = strtoupper(trim((string) ($d['status'] ?? $d['situacao'] ?? '')));
                                    $situacaoCnd = strtoupper(trim((string) ($d['situacao'] ?? '')));
                                    $conseguiuEmitir = $d['conseguiu_emitir'] ?? null;

                                    if ($status === 'INDETERMINADO' || $conseguiuEmitir === false) {
                                        $statusLabel = 'Indeterminada'; $statusHex = '#6b7280';
                                    } elseif ($status === '') {
                                        $statusLabel = 'Não consultada'; $statusHex = '#9ca3af';
                                    } elseif (str_contains($status, 'POSITIVA COM EFEITO') || str_contains($status, 'EFEITO DE NEGATIVA') || $situacaoCnd === 'REGULAR_COM_RESSALVAS') {
                                        $statusLabel = 'Regular c/ ressalvas'; $statusHex = '#d97706';
                                    } elseif (in_array($status, ['POSITIVA', 'IRREGULAR', 'IRREGULARIDADE'], true)) {
                                        $statusLabel = 'Irregular'; $statusHex = '#dc2626';
                                    } elseif (in_array($status, ['NEGATIVA', 'REGULAR', 'REGULARIDADE'], true)) {
                                        $statusLabel = 'Regular'; $statusHex = '#047857';
                                    } else {
                                        $statusLabel = ucfirst(mb_strtolower($status)); $statusHex = '#374151';
                                    }

                                    $dv = $d['data_validade'] ?? $d['validade'] ?? null;
                                    $validadeFmt = null; $validadeTexto = null; $validadeHex = '#6b7280';
                                    if ($dv) {
                                        try {
                                            $dataVal = \Carbon\Carbon::parse($dv);
                                            $validadeFmt = $dataVal->format('d/m/Y');
                                            $dias = (int) floor(now()->startOfDay()->diffInDays($dataVal->startOfDay(), false));
                                            if ($dias < 0) { $validadeTexto = 'Vencida há '.abs($dias).' dia'.(abs($dias) === 1 ? '' : 's'); $validadeHex = '#dc2626'; }
                                            elseif ($dias === 0) { $validadeTexto = 'Vence hoje'; $validadeHex = '#d97706'; }
                                            elseif ($dias <= 30) { $validadeTexto = 'Vence em '.$dias.' dia'.($dias === 1 ? '' : 's'); $validadeHex = '#d97706'; }
                                            else { $validadeTexto = 'Vence em '.$dias.' dias'; $validadeHex = '#6b7280'; }
                                        } catch (\Exception $e) { $validadeFmt = (string) $dv; }
                                    }

                                    $emissaoFmt = null;
                                    $emissaoRaw = $d['emissao_data'] ?? $d['data_emissao'] ?? null;
                                    if ($emissaoRaw) {
                                        try { $emissaoFmt = \Carbon\Carbon::parse($emissaoRaw)->format('d/m/Y'); }
                                        catch (\Exception $e) { $emissaoFmt = (string) $emissaoRaw; }
                                    }

                                    $mensagemCnd = $d['mensagem'] ?? null;
                                    if (! $mensagemCnd && ! empty($d['errors']) && is_array($d['errors'])) $mensagemCnd = $d['errors'][0] ?? null;

                                    $certidoes[] = [
                                        'titulo'        => $cfg['titulo'],
                                        'subtitulo'     => $cfg['subtitulo'],
                                        'statusLabel'   => $statusLabel,
                                        'statusHex'     => $statusHex,
                                        'validadeFmt'   => $validadeFmt,
                                        'validadeTexto' => $validadeTexto,
                                        'validadeHex'   => $validadeHex,
                                        'emissaoFmt'    => $emissaoFmt,
                                        'codigo'        => $d['certidao_codigo'] ?? null,
                                        'certidaoTxt'   => $d['certidao'] ?? null,
                                        'debitosRfb'    => array_key_exists('debitos_rfb', $d)  ? (bool) $d['debitos_rfb']  : null,
                                        'debitosPgfn'   => array_key_exists('debitos_pgfn', $d) ? (bool) $d['debitos_pgfn'] : null,
                                        'prorrogada'    => ! empty($d['validade_prorrogada']),
                                        'mensagem'      => $mensagemCnd,
                                        'indeterminada' => ($statusLabel === 'Indeterminada'),
                                    ];
                                }
                            @endphp

                            @if(count($certidoes) > 0)
                            <div class="bg-white rounded border border-gray-300 overflow-hidden">
                                <div class="bg-gray-50 px-4 py-2 border-b border-gray-300 flex items-center justify-between">
                                    <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Certidões e Consultas</span>
                                    <span class="text-[10px] font-semibold text-gray-400">{{ count($certidoes) }} {{ count($certidoes) === 1 ? 'registro' : 'registros' }}</span>
                                </div>
                                <div class="divide-y divide-gray-300">
                                    @foreach($certidoes as $cert)
                                    <div>
                                        <div class="bg-gray-50/60 px-3 py-1.5 border-b border-gray-200 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[11px] font-semibold text-gray-900 uppercase tracking-wide">{{ $cert['titulo'] }}</span>
                                                @if($cert['prorrogada'])
                                                <span class="text-[9px] font-bold uppercase tracking-wider" style="color:#4338ca">· Prorrogada</span>
                                                @endif
                                            </div>
                                            <span class="text-[10px] text-gray-500">{{ $cert['subtitulo'] }}</span>
                                        </div>

                                        @if($cert['indeterminada'])
                                        <div class="px-3 py-3 border-l-[3px]" style="border-left-color: {{ $cert['statusHex'] }}">
                                            <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">Situação</p>
                                            <p class="mt-1.5 text-sm text-gray-900 font-semibold leading-tight">{{ $cert['statusLabel'] }}</p>
                                            <p class="mt-2 text-[12px] text-gray-700 leading-snug">{{ $cert['mensagem'] ?? 'Não foi possível emitir a certidão pela internet.' }}</p>
                                            <p class="mt-1 text-[10px] text-gray-500 leading-snug">Tente novamente mais tarde ou emita manualmente no portal oficial.</p>
                                        </div>
                                        @else
                                        <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-gray-200">
                                            <div class="px-3 py-2.5">
                                                <p class="flex items-center gap-1.5 text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">
                                                    <span class="inline-block w-2 h-2" style="background-color: {{ $cert['statusHex'] }}"></span>
                                                    Situação
                                                </p>
                                                <p class="mt-1.5 text-sm text-gray-900 font-semibold leading-tight inline-block border-b-2 pb-0.5" style="border-bottom-color: {{ $cert['statusHex'] }}">{{ $cert['statusLabel'] }}</p>
                                            </div>
                                            <div class="px-3 py-2.5">
                                                <p class="flex items-center gap-1.5 text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">
                                                    <span class="inline-block w-2 h-2" style="background-color: {{ $cert['validadeHex'] }}"></span>
                                                    Validade
                                                </p>
                                                <p class="mt-1.5 text-sm text-gray-900 font-mono leading-tight inline-block border-b-2 pb-0.5" style="border-bottom-color: {{ $cert['validadeHex'] }}">{{ $cert['validadeFmt'] ?? '—' }}</p>
                                                @if($cert['validadeTexto'])
                                                <p class="text-[11px] leading-tight mt-0.5" style="color: {{ $cert['validadeHex'] }}">{{ $cert['validadeTexto'] }}</p>
                                                @endif
                                            </div>
                                            <div class="px-3 py-2.5">
                                                <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">Emissão</p>
                                                <p class="mt-1.5 text-sm text-gray-700 font-mono leading-tight">{{ $cert['emissaoFmt'] ?? '—' }}</p>
                                            </div>
                                            <div class="px-3 py-2.5">
                                                <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">Código</p>
                                                <p class="mt-1.5 text-xs text-gray-700 font-mono break-all leading-tight">{{ $cert['codigo'] ?? '—' }}</p>
                                            </div>
                                        </div>

                                        @if($cert['debitosRfb'] !== null || $cert['debitosPgfn'] !== null)
                                        <div class="grid grid-cols-1 sm:grid-cols-2 divide-x divide-gray-200 border-t border-gray-200">
                                            @if($cert['debitosRfb'] !== null)
                                            @php
                                                $rfbAlerta = $cert['debitosRfb'] === true;
                                                $rfbLabel  = $rfbAlerta ? 'Com débitos' : 'Sem débitos';
                                                $rfbHex    = '#d97706';
                                            @endphp
                                            <div class="px-3 py-2.5">
                                                <p class="flex items-center gap-1.5 text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">
                                                    @if($rfbAlerta)<span class="inline-block w-2 h-2" style="background-color: {{ $rfbHex }}"></span>@endif
                                                    Receita Federal
                                                </p>
                                                <p class="mt-1.5 text-sm {{ $rfbAlerta ? 'text-gray-900 font-semibold inline-block border-b-2 pb-0.5' : 'text-gray-700' }} leading-tight" @if($rfbAlerta) style="border-bottom-color: {{ $rfbHex }}" @endif>{{ $rfbLabel }}</p>
                                                <p class="text-[11px] text-gray-500 leading-tight mt-0.5">Tributos administrados (DARF, parcelamentos)</p>
                                            </div>
                                            @endif
                                            @if($cert['debitosPgfn'] !== null)
                                            @php
                                                $pgfnAlerta = $cert['debitosPgfn'] === true;
                                                $pgfnLabel  = $pgfnAlerta ? 'Inscrito em dívida ativa' : 'Sem inscrição';
                                                $pgfnHex    = '#dc2626';
                                            @endphp
                                            <div class="px-3 py-2.5">
                                                <p class="flex items-center gap-1.5 text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">
                                                    @if($pgfnAlerta)<span class="inline-block w-2 h-2" style="background-color: {{ $pgfnHex }}"></span>@endif
                                                    PGFN — Dívida Ativa
                                                </p>
                                                <p class="mt-1.5 text-sm {{ $pgfnAlerta ? 'text-gray-900 font-semibold inline-block border-b-2 pb-0.5' : 'text-gray-700' }} leading-tight" @if($pgfnAlerta) style="border-bottom-color: {{ $pgfnHex }}" @endif>{{ $pgfnLabel }}</p>
                                                <p class="text-[11px] text-gray-500 leading-tight mt-0.5">Procuradoria-Geral da Fazenda — DAU</p>
                                            </div>
                                            @endif
                                        </div>
                                        @endif

                                        @if($cert['certidaoTxt'] || ($cert['debitosRfb'] !== null || $cert['debitosPgfn'] !== null))
                                        <div class="px-3 py-2 border-t border-gray-200 bg-gray-50/40">
                                            @if($cert['debitosRfb'] !== null || $cert['debitosPgfn'] !== null)
                                            <p class="text-[10px] text-gray-500 italic leading-snug">Valores não divulgados pela CND. Para detalhamento, consultar Situação Fiscal no e-CAC com certificado digital.</p>
                                            @endif
                                            @if($cert['certidaoTxt'])
                                            <p class="text-[11px] text-gray-600 leading-relaxed {{ ($cert['debitosRfb'] !== null || $cert['debitosPgfn'] !== null) ? 'mt-1.5' : '' }}">{{ $cert['certidaoTxt'] }}</p>
                                            @endif
                                        </div>
                                        @endif
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            {{-- Dados Cadastrais Completos --}}
                            @if(isset($dados['razao_social']) || isset($dados['natureza_juridica']) || isset($dados['capital_social']))
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Dados Cadastrais</h3>
                                <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @if(isset($dados['razao_social']))
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">Razão Social</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $dados['razao_social'] }}</dd>
                                    </div>
                                    @endif
                                    @if(isset($dados['nome_fantasia']) && $dados['nome_fantasia'])
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">Nome Fantasia</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $dados['nome_fantasia'] }}</dd>
                                    </div>
                                    @endif
                                    @if(isset($dados['natureza_juridica']))
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">Natureza Jurídica</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $dados['natureza_juridica'] }}</dd>
                                    </div>
                                    @endif
                                    @if(isset($dados['capital_social']))
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">Capital Social</dt>
                                        <dd class="mt-1 text-sm font-semibold text-gray-900">R$ {{ number_format($dados['capital_social'], 2, ',', '.') }}</dd>
                                    </div>
                                    @endif
                                    @if(isset($dados['data_inicio_atividade']))
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">Início Atividade</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($dados['data_inicio_atividade'])->format('d/m/Y') }}</dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>
                            @endif

                            {{-- Endereço --}}
                            @if(isset($dados['endereco']) && is_array($dados['endereco']))
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Endereço</h3>
                                @php $end = $dados['endereco']; @endphp
                                <div class="bg-gray-50 rounded border border-gray-200 p-4">
                                    <p class="text-sm text-gray-900">
                                        {{ $end['logradouro'] ?? '' }}{{ isset($end['numero']) ? ', ' . $end['numero'] : '' }}
                                        @if(isset($end['complemento']) && $end['complemento'])
                                        - {{ $end['complemento'] }}
                                        @endif
                                    </p>
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ $end['bairro'] ?? '' }} - {{ $end['municipio'] ?? '' }}/{{ $end['uf'] ?? '' }}
                                    </p>
                                    @if(isset($end['cep']))
                                    <p class="text-sm text-gray-500 mt-1 font-mono">CEP: {{ preg_replace('/(\d{5})(\d{3})/', '$1-$2', $end['cep']) }}</p>
                                    @endif
                                </div>
                                {{-- Telefones empilhados com icone --}}
                                @if((isset($dados['telefone_1']) && $dados['telefone_1']) || (isset($dados['telefone_2']) && $dados['telefone_2']))
                                <div class="mt-3">
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500 mb-2">Telefones</dt>
                                        <dd class="space-y-2">
                                            @php
                                                // Funcao para formatar telefone
                                                $formatarTelefone = function($tel) {
                                                    $tel = preg_replace('/\D/', '', $tel);
                                                    if (strlen($tel) === 11) {
                                                        return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
                                                    } elseif (strlen($tel) === 10) {
                                                        return '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
                                                    }
                                                    return $tel;
                                                };
                                            @endphp
                                            @if(isset($dados['telefone_1']) && $dados['telefone_1'])
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                </svg>
                                                <span class="text-sm font-mono text-gray-900">{{ $formatarTelefone($dados['telefone_1']) }}</span>
                                            </div>
                                            @endif
                                            @if(isset($dados['telefone_2']) && $dados['telefone_2'])
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                                </svg>
                                                <span class="text-sm font-mono text-gray-900">{{ $formatarTelefone($dados['telefone_2']) }}</span>
                                            </div>
                                            @endif
                                        </dd>
                                    </div>
                                </div>
                                @endif

                            </div>
                            @endif

                            {{-- Mapa de Localizacao --}}
                            @if($participante->latitude && $participante->longitude)
                            <div class="border-t border-gray-200 pt-4">
                                <div id="participante-mapa-container">
                                    <div id="participante-mapa"
                                         class="h-48 rounded border border-gray-200 bg-gray-100"
                                         data-lat="{{ $participante->latitude }}"
                                         data-lng="{{ $participante->longitude }}">
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- CNAEs --}}
                            @if(isset($dados['cnaes']) && is_array($dados['cnaes']))
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Atividades Econômicas (CNAEs)</h3>
                                @if(isset($dados['cnaes']['principal']))
                                <div class="bg-gray-50 border border-gray-200 rounded p-3 mb-3">
                                    <dt class="text-xs text-gray-500 font-semibold uppercase tracking-wide">CNAE Principal</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <span class="font-mono text-gray-700">{{ $dados['cnaes']['principal']['codigo'] ?? '' }}</span>
                                        - {{ $dados['cnaes']['principal']['descricao'] ?? '' }}
                                    </dd>
                                </div>
                                @endif
                                @php
                                    $cnaesSecundariosValidos = isset($dados['cnaes']['secundarios'])
                                        ? array_filter($dados['cnaes']['secundarios'], fn($c) => !empty($c['codigo']) || !empty($c['descricao']))
                                        : [];
                                @endphp
                                @if(count($cnaesSecundariosValidos) > 0)
                                <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                    <dt class="text-xs text-gray-500 font-semibold mb-2">CNAEs Secundários ({{ count($cnaesSecundariosValidos) }})</dt>
                                    <dd class="space-y-1 max-h-40 overflow-y-auto">
                                        @foreach(array_slice($cnaesSecundariosValidos, 0, 10) as $cnae)
                                        <div class="text-xs text-gray-700">
                                            <span class="font-mono text-gray-500">{{ $cnae['codigo'] ?? '' }}</span>
                                            - {{ Str::limit($cnae['descricao'] ?? '', 60) }}
                                        </div>
                                        @endforeach
                                        @if(count($cnaesSecundariosValidos) > 10)
                                        <p class="text-xs text-gray-400 mt-2">... e mais {{ count($cnaesSecundariosValidos) - 10 }} CNAEs</p>
                                        @endif
                                    </dd>
                                </div>
                                @endif
                            </div>
                            @endif

                            {{-- SINTEGRA --}}
                            @if(in_array('sintegra', $consultasRealizadas) && isset($dados['sintegra']))
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">SINTEGRA</h3>
                                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">Inscrição Estadual</dt>
                                        <dd class="mt-1 text-sm font-mono text-gray-900">{{ $dados['sintegra']['ie'] ?? '-' }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">Situação IE</dt>
                                        <dd class="mt-1 text-sm font-semibold {{ ($dados['sintegra']['situacao'] ?? '') === 'HABILITADO' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $dados['sintegra']['situacao'] ?? '-' }}
                                        </dd>
                                    </div>
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">Regime Apuração</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $dados['sintegra']['regime_apuracao'] ?? '-' }}</dd>
                                    </div>
                                </dl>
                            </div>
                            @endif


                            {{-- Compliance (TCU/CEIS/CNEP) --}}
                            @if(in_array('tcu_consolidada', $consultasRealizadas) && isset($dados['tcu_consolidada']))
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Compliance</h3>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">CEIS</dt>
                                        <dd class="mt-1 text-sm font-semibold {{ !($dados['tcu_consolidada']['ceis'] ?? false) ? 'text-green-600' : 'text-red-600' }}">
                                            {{ ($dados['tcu_consolidada']['ceis'] ?? false) ? 'Consta' : 'Nada consta' }}
                                        </dd>
                                    </div>
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">CNEP</dt>
                                        <dd class="mt-1 text-sm font-semibold {{ !($dados['tcu_consolidada']['cnep'] ?? false) ? 'text-green-600' : 'text-red-600' }}">
                                            {{ ($dados['tcu_consolidada']['cnep'] ?? false) ? 'Consta' : 'Nada consta' }}
                                        </dd>
                                    </div>
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">Acórdão TCU</dt>
                                        <dd class="mt-1 text-sm font-semibold {{ !($dados['tcu_consolidada']['acordao_tcu'] ?? false) ? 'text-green-600' : 'text-red-600' }}">
                                            {{ ($dados['tcu_consolidada']['acordao_tcu'] ?? false) ? 'Consta' : 'Nada consta' }}
                                        </dd>
                                    </div>
                                    <div class="bg-gray-50 rounded border border-gray-200 p-3">
                                        <dt class="text-xs text-gray-500">Licitação Impedida</dt>
                                        <dd class="mt-1 text-sm font-semibold {{ !($dados['tcu_consolidada']['licitacao_impedida'] ?? false) ? 'text-green-600' : 'text-red-600' }}">
                                            {{ ($dados['tcu_consolidada']['licitacao_impedida'] ?? false) ? 'Sim' : 'Não' }}
                                        </dd>
                                    </div>
                                </div>
                            </div>
                            @endif

                            {{-- QSA (Socios) --}}
                            @if(in_array('qsa', $consultasRealizadas) && !empty($dados['qsa']))
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Quadro Societario (QSA)</h3>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">CPF/CNPJ</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qualificação</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($dados['qsa'] as $socio)
                                            <tr>
                                                <td class="px-3 py-2 text-gray-900">{{ $socio['nome'] ?? '-' }}</td>
                                                <td class="px-3 py-2 text-gray-600 font-mono">{{ $socio['cpf_cnpj'] ?? '-' }}</td>
                                                <td class="px-3 py-2 text-gray-600">{{ $socio['qualificacao'] ?? '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif

                            {{-- Protestos --}}
                            @if(in_array('protestos', $consultasRealizadas) && isset($dados['protestos']))
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">Protestos</h3>
                                @if(empty($dados['protestos']))
                                    <p class="text-sm text-green-600 font-medium">Nenhum protesto encontrado</p>
                                @else
                                    <div class="bg-white border border-gray-300 border-l-4 border-l-red-500 rounded p-4">
                                        <p class="text-sm font-semibold text-red-700">{{ count($dados['protestos']) }} protesto(s) encontrado(s)</p>
                                        <p class="text-xs text-red-600 mt-1">Consulte o relatório completo para detalhes</p>
                                    </div>
                                @endif
                            </div>
                            @endif

                            {{-- ESG --}}
                            @if((in_array('trabalho_escravo', $consultasRealizadas) || in_array('ibama_autuacoes', $consultasRealizadas)) && (isset($dados['trabalho_escravo']) || isset($dados['ibama_autuacoes'])))
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">ESG</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    @if(isset($dados['trabalho_escravo']))
                                    <div class="bg-gray-50 border border-gray-200 rounded p-3">
                                        <dt class="text-xs text-gray-500">Lista Trabalho Escravo</dt>
                                        <dd class="mt-1 text-sm font-semibold {{ !$dados['trabalho_escravo'] ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $dados['trabalho_escravo'] ? 'Consta' : 'Nada consta' }}
                                        </dd>
                                    </div>
                                    @endif
                                    @if(isset($dados['ibama_autuacoes']))
                                    <div class="bg-gray-50 border border-gray-200 rounded p-3">
                                        <dt class="text-xs text-gray-500">Autuações IBAMA</dt>
                                        <dd class="mt-1 text-sm font-semibold {{ empty($dados['ibama_autuacoes']) ? 'text-green-600' : 'text-red-600' }}">
                                            {{ empty($dados['ibama_autuacoes']) ? 'Nenhuma' : count($dados['ibama_autuacoes']) . ' autuacao(oes)' }}
                                        </dd>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @else
                    {{-- Estado vazio - nenhuma consulta realizada --}}
                    <div class="bg-white rounded border border-gray-300 overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Dados da Última Consulta</span>
                        </div>
                        <div class="p-4 sm:p-6 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Nenhuma consulta realizada para este participante</p>
                            <p class="mt-1 text-xs text-gray-400">Clique em "Consulta Avulsa" para obter dados atualizados</p>
                        </div>
                    </div>
                @endif

                {{-- Notas Fiscais (EFD + XML unificadas) --}}
                <div id="notas-fiscais">
                    @include('autenticado.partials.notas-fiscais-card', [
                        'notas' => $notasFiscais,
                        'totalNotas' => $totalNotasFiscais,
                        'ajaxUrl' => $notasAjaxUrl,
                        'contexto' => $notasContexto,
                        'entityId' => $notasEntityId,
                    ])
                </div>

                {{-- Histórico de Consultas --}}
                @if(isset($lotesDoParticipante))
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Histórico de Consultas</span>
                            <span class="text-[10px] font-semibold text-gray-400 bg-gray-200 px-2 py-0.5 rounded">{{ $lotesDoParticipante->count() }}</span>
                        </div>
                    </div>
                    @if($lotesDoParticipante->isEmpty())
                    <div class="px-6 py-10 text-center text-gray-400">
                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm">Nenhuma consulta realizada para este participante.</p>
                    </div>
                    @else
                    <div class="divide-y divide-gray-200">
                        @php
                            $lotePlanoBadgeColors = [
                                'gratuito' => '#6b7280',
                                'validacao' => '#4338ca',
                                'licitacao' => '#7c3aed',
                                'compliance' => '#d97706',
                                'due_diligence' => '#be123c',
                                'enterprise' => '#1f2937',
                            ];
                        @endphp
                        @foreach($lotesDoParticipante as $lote)
                        @php
                            $resultado = $lote->resultados->first();
                            $statusResultado = $resultado?->status ?? ($lote->status === 'concluido' ? 'pendente' : $lote->status);
                            $situacao = $resultado?->getDado('situacao_cadastral');
                            $simples = $resultado?->getDado('simples_nacional');
                            $cndFederal = $resultado?->getDado('cnd_federal');
                            $cndt = $resultado?->getDado('cndt');
                            $dataConsulta = $resultado?->consultado_em ?? $lote->created_at;
                            $resultadoStatusColors = [
                                'sucesso'     => '#047857',
                                'erro'        => '#dc2626',
                                'timeout'     => '#dc2626',
                                'pendente'    => $lote->status === 'processando' ? '#4338ca' : '#6b7280',
                                'processando' => '#4338ca',
                                'concluido'   => '#047857',
                            ];
                            $resultadoStatusLabel = [
                                'sucesso'     => 'Sucesso',
                                'erro'        => 'Erro',
                                'timeout'     => 'Timeout',
                                'pendente'    => $lote->status === 'processando' ? 'Em andamento' : 'Pendente',
                                'processando' => 'Em andamento',
                                'concluido'   => 'Concluído',
                            ];
                        @endphp
                        <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded border border-gray-300 bg-gray-50 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Lote #{{ $lote->id }}</p>
                                        <p class="text-xs text-gray-500">{{ $dataConsulta?->format('d/m/Y H:i') ?? '-' }}</p>
                                        @if($statusResultado === 'sucesso' && ($situacao || $simples !== null))
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            @if($situacao)
                                                <span>{{ $situacao }}</span>
                                            @endif
                                            @if($simples !== null)
                                                <span class="mx-1">·</span>
                                                <span>SN: {{ $simples ? 'Optante' : 'Não optante' }}</span>
                                            @endif
                                            @if($cndFederal !== null)
                                                <span class="mx-1">·</span>
                                                <span>CND: {{ $cndFederal ? 'Regular' : 'Irregular' }}</span>
                                            @endif
                                            @if($cndt !== null)
                                                <span class="mx-1">·</span>
                                                <span>CNDT: {{ $cndt ? 'Regular' : 'Irregular' }}</span>
                                            @endif
                                        </p>
                                        @endif
                                        @if($statusResultado === 'erro' && $resultado?->error_message)
                                        <p class="text-xs text-red-500 mt-0.5 truncate max-w-xs">{{ $resultado->error_message }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($lote->plano)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $lotePlanoBadgeColors[$lote->plano->codigo] ?? '#6b7280' }}">
                                        {{ $lote->plano->nome }}
                                    </span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: {{ $resultadoStatusColors[$statusResultado] ?? '#6b7280' }}">
                                        {{ $resultadoStatusLabel[$statusResultado] ?? ucfirst($statusResultado) }}
                                    </span>
                                    <a href="/app/consulta/historico?lote={{ $lote->id }}"
                                       class="inline-flex items-center p-2 rounded text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors"
                                       data-link
                                       title="Ver detalhes do lote">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Coluna Lateral --}}
            <div class="space-y-4 sm:space-y-6">
                {{-- Assinatura Ativa --}}
                @if($assinaturaAtiva)
                    <div class="bg-white rounded border border-gray-300 overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Assinatura Ativa</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide text-white" style="background-color: #047857">
                                    ATIVA
                                </span>
                            </div>
                        </div>
                        <div class="p-4 sm:p-6 space-y-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Plano</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">{{ $assinaturaAtiva->plano->nome ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Frequência</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    @php
                                        $frequencias = [
                                            'diario' => 'Diaria',
                                            'semanal' => 'Semanal',
                                            'quinzenal' => 'Quinzenal',
                                            'mensal' => 'Mensal',
                                        ];
                                    @endphp
                                    {{ $frequencias[$assinaturaAtiva->frequencia] ?? ucfirst($assinaturaAtiva->frequencia) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Próxima Execução</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $assinaturaAtiva->proxima_execucao_em ? $assinaturaAtiva->proxima_execucao_em->format('d/m/Y H:i') : '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Última Execução</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $assinaturaAtiva->ultima_execucao_em ? $assinaturaAtiva->ultima_execucao_em->format('d/m/Y H:i') : 'Nunca' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide">Créditos/Execução</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $assinaturaAtiva->plano->custo_creditos ?? 0 }} creditos</p>
                            </div>
                            <div class="pt-4 border-t border-gray-200 flex gap-2">
                                @if($assinaturaAtiva->status === 'ativo')
                                    <button
                                        type="button"
                                        class="btn-pausar-assinatura flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 rounded border border-gray-300 bg-white text-gray-600 text-sm font-semibold transition hover:bg-gray-50"
                                        data-assinatura-id="{{ $assinaturaAtiva->id }}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Pausar
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        class="btn-reativar-assinatura flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 rounded border border-gray-300 bg-white text-gray-600 text-sm font-semibold transition hover:bg-gray-50"
                                        data-assinatura-id="{{ $assinaturaAtiva->id }}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Reativar
                                    </button>
                                @endif
                                <button
                                    type="button"
                                    class="btn-cancelar-assinatura inline-flex items-center justify-center gap-2 px-3 py-2 rounded border border-gray-300 bg-white text-gray-600 text-sm font-semibold transition hover:bg-gray-50"
                                    data-assinatura-id="{{ $assinaturaAtiva->id }}"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Estatisticas --}}
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Estatísticas</span>
                    </div>
                    <div class="p-4 sm:p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Total de consultas</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $estatisticas['total_consultas'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Consultas com sucesso</span>
                            <span class="text-sm font-semibold text-green-600">{{ $estatisticas['consultas_sucesso'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Consultas com erro</span>
                            <span class="text-sm font-semibold text-red-600">{{ $estatisticas['consultas_erro'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Creditos utilizados</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $estatisticas['creditos_utilizados'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Notas fiscais</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $totalNotasFiscais ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                {{-- Saldo de Creditos --}}
                <div class="bg-white rounded border border-gray-300 overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                        <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Saldo de Créditos</span>
                    </div>
                    <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded border border-gray-300 bg-gray-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Saldo de Créditos</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($credits ?? 0, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    <a
                        href="/app/creditos"
                        class="block w-full text-center px-4 py-2 rounded bg-gray-800 text-white text-sm font-semibold transition hover:bg-gray-700"
                        data-link
                    >
                        Comprar Créditos
                    </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Criar Assinatura --}}
<div id="modal-criar-assinatura" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded border border-gray-300 max-w-md w-full overflow-hidden">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">Criar Assinatura</span>
                <button type="button" class="modal-close text-gray-400 hover:text-gray-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <form id="form-criar-assinatura">
            <div class="p-4 sm:p-6 space-y-4">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Selecione o Plano</label>
                    <select name="plano_id" id="select-plano-assinatura" class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400" required>
                        <option value="">Selecione...</option>
                        @foreach($planos as $plano)
                            <option value="{{ $plano->id }}" data-creditos="{{ $plano->custo_creditos }}">
                                {{ $plano->nome }} ({{ $plano->custo_creditos }} creditos)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Frequência</label>
                    <select name="frequencia" id="select-frequencia" class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400" required>
                        <option value="diario">Diaria</option>
                        <option value="semanal">Semanal</option>
                        <option value="quinzenal" selected>Quinzenal</option>
                        <option value="mensal">Mensal</option>
                    </select>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded p-4">
                    <p class="text-sm text-gray-600 mb-2">Participante:</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $participante->razao_social ?? $participante->cnpj_formatado }}</p>
                    <p class="text-xs text-gray-500 font-mono">{{ $participante->cnpj_formatado }}</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 bg-white flex justify-end gap-3">
                <button type="button" class="modal-close px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 text-sm font-semibold transition hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 rounded bg-gray-800 text-white text-sm font-semibold transition hover:bg-gray-700">
                    Criar Assinatura
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    'use strict';

    function initMonitoramentoParticipante() {
        const container = document.getElementById('monitoramento-participante-container');
        if (!container) return;

        if (container.dataset.initialized === '1') return;
        container.dataset.initialized = '1';

        console.log('[Monitoramento Participante] Inicializando...');

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const participanteId = {{ $participante->id }};

        // Elementos
        const btnCriarAssinatura = document.getElementById('btn-criar-assinatura');
        const modalCriarAssinatura = document.getElementById('modal-criar-assinatura');
const formCriarAssinatura = document.getElementById('form-criar-assinatura');

        // Abrir modal criar assinatura
        if (btnCriarAssinatura) {
            btnCriarAssinatura.addEventListener('click', function() {
                if (modalCriarAssinatura) {
                    modalCriarAssinatura.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                }
            });
        }

        // Submit criar assinatura
        if (formCriarAssinatura) {
            formCriarAssinatura.addEventListener('submit', async function(e) {
                e.preventDefault();

                const planoId = document.getElementById('select-plano-assinatura').value;
                const frequencia = document.getElementById('select-frequencia').value;

                if (!planoId) {
                    window.showToast && window.showToast('Selecione um plano', 'error');
                    return;
                }

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Criando...';

                try {
                    const response = await fetch('/app/monitoramento/assinatura', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            participante_id: participanteId,
                            plano_id: planoId,
                            frequencia: frequencia,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || 'Erro ao criar assinatura');
                    }

                    window.showToast && window.showToast('Assinatura criada com sucesso!', 'success');
                    modalCriarAssinatura.classList.add('hidden');
                    document.body.style.overflow = '';

                    // Recarregar pagina para atualizar
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);

                } catch (err) {
                    console.error('[Monitoramento Participante] Erro:', err);
                    window.showToast && window.showToast(err.message || 'Erro ao criar assinatura', 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            });
        }

        // Pausar assinatura
        document.querySelectorAll('.btn-pausar-assinatura').forEach(function(btn) {
            btn.addEventListener('click', async function() {
                if (!confirm('Deseja pausar esta assinatura?')) return;

                const assinaturaId = this.dataset.assinaturaId;
                try {
                    const response = await fetch('/app/monitoramento/assinatura/' + assinaturaId + '/pausar', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || 'Erro ao pausar assinatura');
                    }

                    window.showToast && window.showToast('Assinatura pausada com sucesso!', 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } catch (err) {
                    window.showToast && window.showToast(err.message, 'error');
                }
            });
        });

        // Reativar assinatura
        document.querySelectorAll('.btn-reativar-assinatura').forEach(function(btn) {
            btn.addEventListener('click', async function() {
                const assinaturaId = this.dataset.assinaturaId;
                try {
                    const response = await fetch('/app/monitoramento/assinatura/' + assinaturaId + '/reativar', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || 'Erro ao reativar assinatura');
                    }

                    window.showToast && window.showToast('Assinatura reativada com sucesso!', 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } catch (err) {
                    window.showToast && window.showToast(err.message, 'error');
                }
            });
        });

        // Cancelar assinatura
        document.querySelectorAll('.btn-cancelar-assinatura').forEach(function(btn) {
            btn.addEventListener('click', async function() {
                if (!confirm('Tem certeza que deseja cancelar esta assinatura? Esta ação não pode ser desfeita.')) return;

                const assinaturaId = this.dataset.assinaturaId;
                try {
                    const response = await fetch('/app/monitoramento/assinatura/' + assinaturaId, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || 'Erro ao cancelar assinatura');
                    }

                    window.showToast && window.showToast('Assinatura cancelada com sucesso!', 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } catch (err) {
                    window.showToast && window.showToast(err.message, 'error');
                }
            });
        });

        // Copiar chave de acesso
        document.querySelectorAll('.btn-copiar-chave').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const chave = this.dataset.chave;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(chave).then(function() {
                        window.showToast && window.showToast('Chave copiada para a área de transferência!', 'success');
                    }).catch(function() {
                        fallbackCopyTextToClipboard(chave);
                    });
                } else {
                    fallbackCopyTextToClipboard(chave);
                }
            });
        });

        // Fallback para copiar texto
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                document.execCommand('copy');
                window.showToast && window.showToast('Chave copiada para a área de transferência!', 'success');
            } catch (err) {
                window.showToast && window.showToast('Erro ao copiar chave', 'error');
            }

            document.body.removeChild(textArea);
        }

        // Fechar modais
        document.querySelectorAll('.modal-close').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const modal = btn.closest('[id^="modal-"]');
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        });

        // Fechar modal clicando fora
        [modalCriarAssinatura].forEach(function(modal) {
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.classList.add('hidden');
                        document.body.style.overflow = '';
                    }
                });
            }
        });

        // Inicializar mapa de localizacao via Leaflet
        var mapContainer = document.getElementById('participante-mapa');
        if (mapContainer) {
            var lat = parseFloat(mapContainer.dataset.lat);
            var lng = parseFloat(mapContainer.dataset.lng);
            if (lat && lng) {
                var map = L.map('participante-mapa').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                L.marker([lat, lng]).addTo(map);
            } else {
                var cont = mapContainer.closest('#participante-mapa-container');
                if (cont) cont.classList.add('hidden');
            }
        }

        console.log('[Monitoramento Participante] Inicialização concluída');
    }

    // Expor globalmente para SPA
    window.initMonitoramentoParticipante = initMonitoramentoParticipante;

    // Auto-inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMonitoramentoParticipante, { once: true });
    } else {
        initMonitoramentoParticipante();
    }
})();
</script>
