<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\MonitoramentoPlano;
use App\Models\User;

class PricingCatalogService
{
    public const CREDIT_UNIT_PRICE = 0.20;
    public const MINIMUM_DEPOSIT = 50.00;
    public const FIRST_PURCHASE_LOCKED_PRODUCTS = ['compliance', 'due_diligence'];

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
                'slug' => 'enterprise',
                'nome' => 'Enterprise',
                'creditos' => 5000,
                'preco' => 1000.00,
                'badge' => 'Escala',
                'usage_hint' => 'Para operação intensiva',
                'featured' => false,
                'descricao' => 'Atalho promocional para operações intensivas que precisam acelerar saldo e faixa.',
            ],
        ];
    }

    public function getPackages(): array
    {
        return $this->getFeaturedOffers();
    }

    public function getMinimumDeposit(): float
    {
        return self::MINIMUM_DEPOSIT;
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
            'creditos' => (int) round($normalizedAmount / self::CREDIT_UNIT_PRICE),
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
     * Faixas comerciais por histórico líquido de créditos pagos.
     */
    public function getTiers(): array
    {
        return [
            [
                'slug' => 'base',
                'nome' => 'Base',
                'min_paid_credits' => 0,
                'max_paid_credits' => 999,
            ],
            [
                'slug' => 'x',
                'nome' => 'Faixa X',
                'min_paid_credits' => 1000,
                'max_paid_credits' => 4999,
            ],
            [
                'slug' => 'y',
                'nome' => 'Faixa Y',
                'min_paid_credits' => 5000,
                'max_paid_credits' => 19999,
            ],
            [
                'slug' => 'z',
                'nome' => 'Faixa Z',
                'min_paid_credits' => 20000,
                'max_paid_credits' => null,
            ],
        ];
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
     * Catálogo comercial público.
     */
    public function getProductCatalog(): array
    {
        return [
            [
                'slug' => 'validacao',
                'nome' => 'Validação',
                'descricao' => 'Consulta fiscal básica de CNPJ com Simples Nacional e SINTEGRA para qualificação inicial.',
                'credits_by_tier' => [
                    'base' => 5,
                    'x' => 5,
                    'y' => 5,
                    'z' => 5,
                ],
            ],
            [
                'slug' => 'licitacao',
                'nome' => 'Licitação',
                'descricao' => 'Consulta para editais e contratação pública com CND Federal, CNDT e FGTS.',
                'credits_by_tier' => [
                    'base' => 10,
                    'x' => 10,
                    'y' => 10,
                    'z' => 10,
                ],
            ],
            [
                'slug' => 'compliance',
                'nome' => 'Compliance',
                'descricao' => 'Consulta de regularidade fiscal e trabalhista completa por CNPJ.',
                'credits_by_tier' => [
                    'base' => 18,
                    'x' => 18,
                    'y' => 18,
                    'z' => 18,
                ],
            ],
            [
                'slug' => 'due_diligence',
                'nome' => 'Due Diligence',
                'descricao' => 'Consulta ampliada de risco com compliance, sanções, CNJ, protestos e processos.',
                'credits_by_tier' => [
                    'base' => 35,
                    'x' => 35,
                    'y' => 35,
                    'z' => 35,
                ],
            ],
            [
                'slug' => 'clearance',
                'nome' => 'Clearance',
                'descricao' => 'Validação premium de notas fiscais com custo mais alto por consulta, preservando o posicionamento premium do produto.',
                'credits_by_tier' => [
                    'base' => 14,
                    'x' => 12,
                    'y' => 10,
                    'z' => 8,
                ],
            ],
        ];
    }

    public function getLandingPricingData(): array
    {
        $tiers = $this->getTiers();
        $featuredOffers = array_map(fn (array $package) => $this->decorateOffer($package), $this->getFeaturedOffers());

        $products = array_map(function (array $product) use ($tiers) {
            $rows = [];
            foreach ($tiers as $tier) {
                $credits = $product['credits_by_tier'][$tier['slug']];
                $rows[] = [
                    'tier_slug' => $tier['slug'],
                    'tier_name' => $tier['nome'],
                    'credits' => $credits,
                    'price' => $this->creditsToCurrency($credits),
                    'price_for_100' => $this->creditsToCurrency($credits * 100),
                ];
            }

            $entryRow = $rows[0];
            $bestRow = $rows[array_key_last($rows)];

            return array_merge($product, [
                'rows' => $rows,
                'entry_price_label' => 'A partir de R$ '.number_format($entryRow['price'], 2, ',', '.').'/consulta',
                'best_price_label' => 'Melhor faixa: R$ '.number_format($bestRow['price'], 2, ',', '.').'/consulta',
            ]);
        }, $this->getProductCatalog());

        return [
            'credit_unit_price' => self::CREDIT_UNIT_PRICE,
            'minimum_deposit' => $this->getMinimumDeposit(),
            'featured_offers' => $featuredOffers,
            'packages' => $featuredOffers,
            'tiers' => $tiers,
            'products' => $products,
            'compliance_sources' => $this->getComplianceSources(),
        ];
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

    public function getTierForUser(User $user): array
    {
        return $this->getTierForPaidCredits($this->getPaidCreditsForUser($user));
    }

    public function getTierForPaidCredits(int $paidCredits): array
    {
        foreach (array_reverse($this->getTiers()) as $tier) {
            if ($paidCredits >= $tier['min_paid_credits']) {
                return $tier;
            }
        }

        return $this->getTiers()[0];
    }

    public function getNextTierForUser(User $user): ?array
    {
        return $this->getNextTierForPaidCredits($this->getPaidCreditsForUser($user));
    }

    public function getNextTierForPaidCredits(int $paidCredits): ?array
    {
        foreach ($this->getTiers() as $tier) {
            if ($tier['min_paid_credits'] > $paidCredits) {
                return $tier;
            }
        }

        return null;
    }

    public function getTierProgressForUser(User $user): array
    {
        $paidCredits = $this->getPaidCreditsForUser($user);
        $currentTier = $this->getTierForPaidCredits($paidCredits);
        $nextTier = $this->getNextTierForPaidCredits($paidCredits);

        if ($nextTier === null) {
            return [
                'paid_credits' => $paidCredits,
                'current_tier' => $currentTier,
                'next_tier' => null,
                'credits_remaining' => 0,
                'progress_percent' => 100,
            ];
        }

        $rangeStart = $currentTier['min_paid_credits'];
        $rangeEnd = $nextTier['min_paid_credits'];
        $creditsRemaining = max(0, $rangeEnd - $paidCredits);
        $progressPercent = (int) min(
            100,
            max(0, (($paidCredits - $rangeStart) / max(1, ($rangeEnd - $rangeStart))) * 100)
        );

        return [
            'paid_credits' => $paidCredits,
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
            'credits_remaining' => $creditsRemaining,
            'progress_percent' => $progressPercent,
        ];
    }

    public function getProductCreditsForUser(string $productSlug, User $user, ?MonitoramentoPlano $legacyPlan = null): int
    {
        $currentTier = $this->getTierForUser($user);

        foreach ($this->getProductCatalog() as $product) {
            if ($product['slug'] === $productSlug) {
                return (int) $product['credits_by_tier'][$currentTier['slug']];
            }
        }

        return (int) ($legacyPlan?->custo_creditos ?? 0);
    }

    public function getProductCreditsByPlan(MonitoramentoPlano $plan, User $user): int
    {
        $mappedProduct = match ($plan->codigo) {
            'validacao' => 'validacao',
            'licitacao' => 'licitacao',
            'compliance' => 'compliance',
            'due_diligence' => 'due_diligence',
            'clearance' => 'clearance',
            default => null,
        };

        if ($mappedProduct === null) {
            return (int) $plan->custo_creditos;
        }

        return $this->getProductCreditsForUser($mappedProduct, $user, $plan);
    }

    public function getCommercialSummaryForUser(User $user): array
    {
        $progress = $this->getTierProgressForUser($user);
        $tier = $progress['current_tier'];
        $featuredOffers = array_map(fn (array $package) => $this->decorateOffer($package), $this->getFeaturedOffers());

        $products = array_map(function (array $product) use ($tier) {
            $credits = $product['credits_by_tier'][$tier['slug']];

            return [
                'slug' => $product['slug'],
                'nome' => $product['nome'],
                'descricao' => $product['descricao'],
                'credits' => $credits,
                'price' => $this->creditsToCurrency($credits),
            ];
        }, $this->getProductCatalog());

        return array_merge($progress, [
            'credit_unit_price' => self::CREDIT_UNIT_PRICE,
            'minimum_deposit' => $this->getMinimumDeposit(),
            'featured_offers' => $featuredOffers,
            'products' => $products,
            'packages' => $featuredOffers,
        ]);
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
        return round($credits * self::CREDIT_UNIT_PRICE, 2);
    }
}
