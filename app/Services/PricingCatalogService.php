<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\MonitoramentoPlano;
use App\Models\User;

class PricingCatalogService
{
    public const CREDIT_UNIT_PRICE = 0.20;

    // Piso de depósito no sistema. Alinhado ao mínimo do provedor (InfoSimples ~R$100).
    public const MINIMUM_DEPOSIT = 100.00;

    public const FIRST_PURCHASE_LOCKED_PRODUCTS = ['compliance'];

    public function __construct(
        private \App\Services\Entitlements\EntitlementService $entitlements = new \App\Services\Entitlements\EntitlementService,
        private \App\Services\Admin\ComercialParametroService $comercial = new \App\Services\Admin\ComercialParametroService
    ) {}

    /**
     * Preço por crédito efetivo (override admin §6.1 ou o default CREDIT_UNIT_PRICE).
     * As constantes permanecem como fallback e back-compat de consumidores estáticos.
     */
    public function creditUnitPrice(): float
    {
        return (float) $this->comercial->valor('credit_unit_price', self::CREDIT_UNIT_PRICE);
    }

    /**
     * Ofertas promocionais destacadas.
     */
    public function getFeaturedOffers(): array
    {
        return [
            [
                'slug' => 'business',
                'nome' => 'Business',
                'creditos' => 1000,
                'preco' => 200.00,
                'badge' => 'Promocional',
                'usage_hint' => 'Para volume mensal',
                'featured' => true,
                'descricao' => 'Atalho promocional para rotina mensal com saldo forte de entrada.',
            ],
            [
                // slug 'enterprise' mantido por compatibilidade de rota; nome de exibição
                // é "Volume" para não colidir com o tier de assinatura Enterprise.
                'slug' => 'enterprise',
                'nome' => 'Volume',
                'creditos' => 5000,
                'preco' => 1000.00,
                'badge' => 'Escala',
                'usage_hint' => 'Para operação intensiva',
                'featured' => false,
                'descricao' => 'Atalho promocional para operações intensivas que precisam acelerar saldo.',
            ],
        ];
    }

    public function getPackages(): array
    {
        return $this->getFeaturedOffers();
    }

    public function getMinimumDeposit(): float
    {
        return (float) $this->comercial->valor('minimum_deposit', self::MINIMUM_DEPOSIT);
    }

    public function getFirstPurchaseLockedProducts(): array
    {
        return self::FIRST_PURCHASE_LOCKED_PRODUCTS;
    }

    public function userHasFirstPurchase(User $user): bool
    {
        return CreditTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'purchase')
            ->where('amount', '>', 0)
            ->exists();
    }

    public function productRequiresFirstPurchase(string $productCode): bool
    {
        return in_array($productCode, self::FIRST_PURCHASE_LOCKED_PRODUCTS, true);
    }

    public function userCanUseProduct(User $user, string $productCode): bool
    {
        if (! $this->productRequiresFirstPurchase($productCode)) {
            return true;
        }

        return $this->userHasFirstPurchase($user);
    }

    /**
     * O plano está sujeito ao teto de consultas do período de teste?
     * (Gratuito fica de fora; os demais planos pagos compartilham um pool único.)
     */
    public function planoTemTetoTrial(string $codigo): bool
    {
        return in_array($codigo, (array) config('trial.planos_com_teto', []), true);
    }

    /**
     * Status do teto de teste — pool GLOBAL de consultas (CNPJs) somado entre
     * TODOS os planos pagos, liberado apenas até a 1ª compra confirmada.
     *
     * @return array{aplicavel: bool, limite: int, usados: int, restantes: int, bloqueado: bool}
     */
    public function trialCapStatus(User $user, MonitoramentoPlano $plano, int $novosCnpjs = 0): array
    {
        $limite = (int) config('trial.limite_consultas_sem_compra', 5);

        $aplicavel = $this->planoTemTetoTrial($plano->codigo) && ! $this->userHasFirstPurchase($user);

        if (! $aplicavel) {
            return [
                'aplicavel' => false,
                'limite' => $limite,
                'usados' => 0,
                'restantes' => $limite,
                'bloqueado' => false,
            ];
        }

        // Pool único: soma os CNPJs consumidos em QUALQUER plano sujeito ao teto.
        $planosComTetoIds = MonitoramentoPlano::query()
            ->whereIn('codigo', (array) config('trial.planos_com_teto', []))
            ->pluck('id');

        $usados = (int) \App\Models\ConsultaLote::query()
            ->where('user_id', $user->id)
            ->whereIn('plano_id', $planosComTetoIds)
            ->where('status', '!=', \App\Models\ConsultaLote::STATUS_ERRO)
            ->sum('total_participantes');

        return [
            'aplicavel' => true,
            'limite' => $limite,
            'usados' => $usados,
            'restantes' => max(0, $limite - $usados),
            'bloqueado' => ($usados + $novosCnpjs) > $limite,
        ];
    }

    public function getPackageBySlug(string $slug): ?array
    {
        foreach ($this->getFeaturedOffers() as $package) {
            if ($package['slug'] === $slug) {
                return $this->decorateOffer($package);
            }
        }

        return null;
    }

    public function buildCustomDeposit(float $amount): ?array
    {
        $normalizedAmount = round($amount, 2);

        if ($normalizedAmount < $this->getMinimumDeposit()) {
            return null;
        }

        return [
            'slug' => 'custom',
            'nome' => 'Recarga personalizada',
            'creditos' => (int) round($normalizedAmount / $this->creditUnitPrice()),
            'preco' => $normalizedAmount,
            'badge' => 'Valor livre',
            'usage_hint' => 'Você escolhe quanto pagar',
            'featured' => false,
            'descricao' => 'Depósito customizado acima do mínimo operacional para comprar apenas o saldo que fizer sentido agora.',
            'is_custom' => true,
            'kind' => 'custom',
        ];
    }

    public function resolveCheckoutSelection(string $slug, mixed $amount = null): ?array
    {
        if ($slug === 'custom') {
            $normalizedAmount = $this->normalizeAmount($amount);

            if ($normalizedAmount === null) {
                return null;
            }

            return $this->buildCustomDeposit($normalizedAmount);
        }

        return $this->getPackageBySlug($slug);
    }

    /**
     * Fontes de dados que compõem o produto Compliance.
     * Status: ativo (já operacional), em_implementacao (em rollout), em_breve (roadmap).
     */
    public function getComplianceSources(): array
    {
        return [
            [
                'slug' => 'minha_receita',
                'nome' => 'Cadastro RFB (minhareceita.org)',
                'categoria' => 'Cadastral',
                'status' => 'ativo',
                'descricao_curta' => 'Situação cadastral, CNAEs, QSA, regime tributário.',
            ],
            [
                'slug' => 'cnd_federal',
                'nome' => 'CND Federal (PGFN/RFB)',
                'categoria' => 'Fiscal obrigatória',
                'status' => 'em_implementacao',
                'descricao_curta' => 'Certidão Negativa de Débitos Federais e Dívida Ativa da União.',
            ],
            [
                'slug' => 'cnd_estadual',
                'nome' => 'CND Estadual (SEFAZ)',
                'categoria' => 'Fiscal obrigatória',
                'status' => 'em_breve',
                'descricao_curta' => 'Certidão estadual nas 27 UFs via SEFAZ.',
            ],
            [
                'slug' => 'cnd_municipal',
                'nome' => 'CND Municipal (Prefeituras)',
                'categoria' => 'Fiscal obrigatória',
                'status' => 'em_breve',
                'descricao_curta' => 'Certidão municipal por cidade do participante.',
            ],
            [
                'slug' => 'cndt',
                'nome' => 'CNDT (TST)',
                'categoria' => 'Trabalhista obrigatória',
                'status' => 'em_breve',
                'descricao_curta' => 'Certidão Negativa de Débitos Trabalhistas — exigida em licitação.',
            ],
            [
                'slug' => 'crf_fgts',
                'nome' => 'CRF FGTS (Caixa)',
                'categoria' => 'FGTS obrigatória',
                'status' => 'em_breve',
                'descricao_curta' => 'Certificado de Regularidade do FGTS.',
            ],
            [
                'slug' => 'cgu_cnc',
                'nome' => 'CGU CNC (CEIS+CNEP+CEPIM+ePAD)',
                'categoria' => 'Sanções',
                'status' => 'em_breve',
                'descricao_curta' => 'Cadastro unificado de sanções a entes privados.',
            ],
            [
                'slug' => 'cnj_improbidade',
                'nome' => 'CNJ Improbidade',
                'categoria' => 'Reputacional',
                'status' => 'em_breve',
                'descricao_curta' => 'Improbidade e inelegibilidade de sócios e administradores.',
            ],
            [
                'slug' => 'sintegra',
                'nome' => 'SINTEGRA unificada',
                'categoria' => 'Cadastral estadual',
                'status' => 'em_breve',
                'descricao_curta' => 'Inscrição estadual ativa — protege crédito de ICMS.',
            ],
        ];
    }

    /**
     * Catálogo comercial público — preço único por plano (sem faixas de volume).
     */
    public function getProductCatalog(): array
    {
        return [
            [
                'slug' => 'validacao',
                'nome' => 'Validação',
                'descricao' => 'Consulta fiscal básica de CNPJ com Simples Nacional e SINTEGRA para qualificação inicial.',
            ],
            [
                'slug' => 'licitacao',
                'nome' => 'Licitação',
                'descricao' => 'Consulta para editais e contratação pública com CND Federal, CNDT e FGTS.',
            ],
            [
                'slug' => 'compliance',
                'nome' => 'Compliance',
                'descricao' => 'Consulta de regularidade fiscal e trabalhista completa por CNPJ.',
            ],
            [
                'slug' => 'due_diligence',
                'nome' => 'Due Diligence',
                'descricao' => 'Consulta ampliada de risco com compliance, sanções, CNJ, protestos e processos.',
            ],
            [
                'slug' => 'clearance',
                'nome' => 'Clearance',
                'descricao' => 'Validação premium de notas fiscais com custo mais alto por consulta, preservando o posicionamento premium do produto.',
            ],
        ];
    }

    /**
     * Custo em créditos para executar um produto de consulta para o dado plano.
     * Override admin opcional via comercial_parametros; default = custo_creditos do plano.
     */
    public function getProductCreditsByPlan(MonitoramentoPlano $plan, User $user): int
    {
        return (int) ($this->comercial->valor('preco_'.$plan->codigo, $plan->custo_creditos));
    }

    public function getPaidCreditsForUser(User $user): int
    {
        $purchased = (int) CreditTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'purchase')
            ->sum('amount');

        $refunded = (int) abs(CreditTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'purchase_refund')
            ->sum('amount'));

        return max(0, $purchased - $refunded);
    }

    /**
     * Retorna os dados de pricing para a landing page.
     * Cada produto tem {slug, nome, descricao, credits, price} — sem matriz de faixas.
     *
     * Shim backward-compat: 'tiers' e 'rows' por produto retornam [] para que
     * views que ainda iterem essas chaves (precos.blade, plano/index) não fatalizem.
     * Relabel das views é task posterior — aqui só paramos o 500.
     */
    public function getLandingPricingData(): array
    {
        $featuredOffers = array_map(fn (array $package) => $this->decorateOffer($package), $this->getFeaturedOffers());

        $products = array_map(function (array $product) {
            $plano = MonitoramentoPlano::where('codigo', $product['slug'])->first();
            $credits = $plano ? (int) $plano->custo_creditos : 0;

            return [
                'slug' => $product['slug'],
                'nome' => $product['nome'],
                'descricao' => $product['descricao'],
                'credits' => $credits,
                'price' => $this->creditsToCurrency($credits),
                // shim: views legadas iteram $product['rows'] — empty loop é safe
                'rows' => [],
                'by_tier' => [],
                'entry_price_label' => 'A partir de R$ '.number_format($this->creditsToCurrency($credits), 2, ',', '.').'/consulta',
                'best_price_label' => 'A partir de R$ '.number_format($this->creditsToCurrency($credits), 2, ',', '.').'/consulta',
            ];
        }, $this->getProductCatalog());

        return [
            'credit_unit_price' => $this->creditUnitPrice(),
            'minimum_deposit' => $this->getMinimumDeposit(),
            'featured_offers' => $featuredOffers,
            'packages' => $featuredOffers,
            'products' => $products,
            'compliance_sources' => $this->getComplianceSources(),
            // shim: views legadas iteram $tiers — empty loop é safe
            'tiers' => [],
        ];
    }

    /**
     * Retorna o resumo comercial do usuário para as views autenticadas.
     * Cada produto tem {slug, nome, descricao, credits, price} — sem matriz de faixas.
     *
     * Shim backward-compat: chaves de faixa retornam valores seguros para que
     * views que ainda as referenciem (plano/index, creditos/index) não fatalizem.
     * Relabel das views é task posterior — aqui só paramos o 500.
     */
    public function getCommercialSummaryForUser(User $user): array
    {
        $featuredOffers = array_map(fn (array $package) => $this->decorateOffer($package), $this->getFeaturedOffers());

        $products = array_map(function (array $product) use ($user) {
            $plano = MonitoramentoPlano::where('codigo', $product['slug'])->first();
            $credits = $plano ? $this->getProductCreditsByPlan($plano, $user) : 0;

            return [
                'slug' => $product['slug'],
                'nome' => $product['nome'],
                'descricao' => $product['descricao'],
                'credits' => $credits,
                'price' => $this->creditsToCurrency($credits),
                // shim: views legadas iteram $product['by_tier'] dentro do loop $tiers
                // como $tiers=[] o loop externo nunca executa, mas por segurança:
                'by_tier' => [],
            ];
        }, $this->getProductCatalog());

        return [
            'credit_unit_price' => $this->creditUnitPrice(),
            'minimum_deposit' => $this->getMinimumDeposit(),
            'featured_offers' => $featuredOffers,
            'packages' => $featuredOffers,
            'products' => $products,
            // shim backward-compat: chaves de faixa que views autenticadas ainda lêem
            'tiers' => [],
            'current_tier' => null,
            'next_tier' => null,
            'paid_credits' => 0,
            'credits_remaining' => 0,
            'progress_percent' => 100,
        ];
    }

    private function decorateOffer(array $package): array
    {
        $package['price_per_credit'] = $package['creditos'] > 0
            ? round($package['preco'] / $package['creditos'], 2)
            : 0.0;
        $package['is_custom'] = false;
        $package['kind'] = 'featured';

        return $package;
    }

    private function normalizeAmount(mixed $amount): ?float
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        if (is_string($amount)) {
            $amount = str_replace(',', '.', trim($amount));
        }

        if (! is_numeric($amount)) {
            return null;
        }

        return round((float) $amount, 2);
    }

    public function creditsToCurrency(int $credits): float
    {
        return round($credits * $this->creditUnitPrice(), 2);
    }
}
