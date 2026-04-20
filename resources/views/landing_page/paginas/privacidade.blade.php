@push('structured-data')
    @include('landing_page.partials.breadcrumb-schema', [
        'trail' => [
            ['name' => 'Início', 'url' => url('/')],
            ['name' => 'Política de Privacidade', 'url' => url('/privacidade')],
        ],
    ])
@endpush

<section class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">FiscalDock</p>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide mt-1">Política de Privacidade</h1>
                <p class="text-xs text-gray-500 mt-1">Como tratamos dados pessoais nos canais públicos e interações comerciais da FiscalDock.</p>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-[11px] uppercase tracking-wide text-gray-500">
                    <a href="{{ route('inicio') }}" class="hover:underline" style="color: #1e4fa0">Início</a>
                    <span>/</span>
                    <span>Política de Privacidade</span>
                </div>
            </div>

            <div class="p-4 sm:p-6 space-y-6 text-sm text-gray-700 leading-relaxed">
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">1. Dados coletados</p>
                    <p>Podemos coletar dados informados diretamente por você, como nome, e-mail, telefone e informações enviadas por formulários, além de dados técnicos básicos como endereço IP, navegador, páginas acessadas e data/hora da interação.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">2. Finalidades</p>
                    <p>Utilizamos esses dados para responder contatos comerciais, identificar interesse em nossos serviços, melhorar a navegação, proteger o ambiente contra abuso e dar continuidade a conversas iniciadas por você com a FiscalDock.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">3. Base legal e retenção</p>
                    <p>O tratamento pode ocorrer com base em consentimento, execução de procedimentos preliminares à contratação, legítimo interesse e cumprimento de obrigações legais ou regulatórias. Os dados são mantidos pelo período necessário para cumprir essas finalidades e exigências aplicáveis.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">4. Compartilhamento</p>
                    <p>A FiscalDock não comercializa dados pessoais. O compartilhamento pode ocorrer com fornecedores de infraestrutura, atendimento, segurança, analytics ou parceiros operacionais, sempre dentro do necessário para prestação do serviço ou cumprimento de obrigação legal.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">5. Segurança</p>
                    <p>Adotamos medidas administrativas e técnicas razoáveis para reduzir risco de acesso não autorizado, perda, alteração ou divulgação indevida de dados. Ainda assim, nenhum ambiente é completamente imune a incidentes.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">6. Direitos do titular</p>
                    <p>Você pode solicitar confirmação de tratamento, acesso, correção, anonimização quando cabível, revogação de consentimento e demais direitos previstos na LGPD, observadas as hipóteses legais aplicáveis.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">7. Contato para privacidade</p>
                    <p>Solicitações relacionadas à privacidade podem ser enviadas para <a href="mailto:contato@fiscaldock.com.br" class="hover:underline" style="color: #1e4fa0">contato@fiscaldock.com.br</a> ou pelo WhatsApp <a href="https://wa.me/5567999844366" target="_blank" rel="noopener" class="hover:underline" style="color: #1e4fa0">(67) 99984-4366</a>.</p>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-3">Continuar navegação</p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('agendar') }}" class="bg-gray-800 text-white hover:bg-gray-700 rounded text-sm font-medium px-4 py-3 text-center">
                            Falar com especialista
                        </a>
                        <a href="{{ route('inicio') }}" class="bg-white border border-gray-300 text-gray-600 hover:bg-gray-50 rounded text-sm font-medium px-4 py-3 text-center">
                            Voltar ao início
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
