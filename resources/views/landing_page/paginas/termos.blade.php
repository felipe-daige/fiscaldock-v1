@push('structured-data')
    @include('landing_page.partials.breadcrumb-schema', [
        'trail' => [
            ['name' => 'Início', 'url' => url('/')],
            ['name' => 'Termos de Uso', 'url' => url('/termos')],
        ],
    ])
@endpush

<section class="bg-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        <div class="bg-white rounded border border-gray-300 overflow-hidden">
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-widest">FiscalDock</p>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 uppercase tracking-wide mt-1">Termos de Uso</h1>
                <p class="text-xs text-gray-500 mt-1">Condições gerais para uso das páginas públicas, canais de contato e plataforma.</p>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-[11px] uppercase tracking-wide text-gray-500">
                    <a href="{{ route('inicio') }}" class="hover:underline" style="color: #1e4fa0">Início</a>
                    <span>/</span>
                    <span>Termos de Uso</span>
                </div>
            </div>

            <div class="p-4 sm:p-6 space-y-6 text-sm text-gray-700 leading-relaxed">
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">1. Aceite</p>
                    <p>Ao acessar o site da FiscalDock, seus conteúdos públicos e canais de contato, você concorda com estes termos. Caso não concorde, interrompa a navegação e o uso dos formulários e canais disponibilizados.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">2. Finalidade do site</p>
                    <p>As páginas públicas apresentam informações institucionais e comerciais sobre a FiscalDock, seus módulos, pacotes de créditos, faixas comerciais e conteúdos educativos. O envio de dados por formulários ou canais de contato não garante contratação, agenda automática, aprovação de cadastro nem ativação imediata de serviços.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">3. Conta e acesso</p>
                    <p>Quando houver criação de conta ou acesso à plataforma, o usuário deve fornecer informações verdadeiras, atualizadas e completas, além de manter o sigilo de suas credenciais. O uso indevido, compartilhamento não autorizado ou tentativa de acesso irregular poderá resultar em bloqueio do acesso.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">4. Conteúdo e disponibilidade</p>
                    <p>A FiscalDock busca manter as informações atualizadas, mas pode ajustar funcionalidades, preços, disponibilidade, textos e fluxos a qualquer momento. Conteúdos de blog, landing pages e materiais educativos têm caráter informativo e não substituem avaliação jurídica, fiscal ou contábil específica.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">5. Uso permitido</p>
                    <p>Você concorda em não utilizar o site ou a plataforma para fraude, engenharia reversa, scraping abusivo, envio de conteúdo malicioso, violação de direitos de terceiros ou qualquer atividade que comprometa a segurança, disponibilidade ou integridade dos serviços.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">6. Propriedade intelectual</p>
                    <p>Marcas, textos, layouts, bases visuais, software e materiais da FiscalDock permanecem protegidos por legislação aplicável. Nenhum conteúdo pode ser reproduzido ou explorado comercialmente sem autorização prévia por escrito.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">7. Contato</p>
                    <p>Para dúvidas comerciais, operacionais ou jurídicas, utilize os canais oficiais: <a href="mailto:contato@fiscaldock.com.br" class="hover:underline" style="color: #1e4fa0">contato@fiscaldock.com.br</a> e <a href="https://wa.me/5567999844366" target="_blank" rel="noopener" class="hover:underline" style="color: #1e4fa0">(67) 99984-4366</a>.</p>
                </div>

                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">8. Atualizações</p>
                    <p>Estes termos podem ser revisados periodicamente. A versão publicada nesta página prevalece a partir da data de atualização.</p>
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
