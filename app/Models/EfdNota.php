<?php

namespace App\Models;

use App\Services\CatalogoHistoricoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EfdNota extends Model
{
    protected $table = 'efd_notas';

    protected $fillable = [
        'user_id', 'cliente_id', 'participante_id', 'importacao_id', 'chave_acesso', 'modelo', 'numero',
        'serie', 'data_emissao', 'tipo_operacao', 'valor_total',
        'valor_desconto', 'origem_arquivo', 'metadados', 'validacao', 'cancelada',
    ];

    protected function casts(): array
    {
        return [
            'data_emissao' => 'date',
            'valor_total' => 'decimal:2',
            'valor_desconto' => 'decimal:2',
            'metadados' => 'array',
            'validacao' => 'array',
            'cancelada' => 'boolean',
        ];
    }

    public function scopeAtivas($query)
    {
        return $query->where('cancelada', false);
    }

    /**
     * Dedup de origem (P1) — a MESMA NF-e é escriturada em 'fiscal' e 'contribuicoes'.
     * Mantém a fiscal e só inclui quem não tem gêmea fiscal por chave (NFS-e, NF-e órfãs).
     * Regra canônica única, espelha EfdAgregadorService::notasDedup. Aplicar em métricas
     * de VALOR/CONTAGEM de nota — NUNCA na leitura de tributo (precisa das 2 origens).
     */
    public function scopeDedupOrigem($query)
    {
        return $query->where(function ($q) {
            $q->where('origem_arquivo', 'fiscal')
                ->orWhereRaw("NOT EXISTS (SELECT 1 FROM efd_notas f WHERE f.user_id = efd_notas.user_id AND f.origem_arquivo = 'fiscal' AND f.chave_acesso IS NOT NULL AND f.chave_acesso = efd_notas.chave_acesso)");
        });
    }

    public function scopeCanceladas($query)
    {
        return $query->where('cancelada', true);
    }

    public function importacao(): BelongsTo
    {
        return $this->belongsTo(EfdImportacao::class, 'importacao_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function participante(): BelongsTo
    {
        return $this->belongsTo(Participante::class, 'participante_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(EfdNotaItem::class, 'efd_nota_id');
    }

    /**
     * Consolidado C190 (efd_notas_consolidados) — fonte autoritativa do ICMS/ST/IPI
     * no perfil comercial (B), onde o C170 não carrega ICMS (P2). Só existe na
     * escrituração fiscal (origem 'fiscal').
     */
    public function consolidados(): HasMany
    {
        return $this->hasMany(EfdNotaConsolidado::class, 'efd_nota_id');
    }

    /** @var array<string, EfdCatalogoItem>|null cache de catalogoPorItem() */
    private ?array $catalogoMapCache = null;

    private ?Collection $itensDetalheCache = null;

    private bool $itensViaTwin = false;

    /**
     * Itens a exibir no detalhe da nota. Usa os C170 próprios; quando não há (saída
     * fiscal escriturada só por C190, P2), faz fallback pros itens da gêmea de
     * contribuicoes (mesma chave) — onde o produto da saída realmente está detalhado.
     * Memoizado.
     */
    public function itensDetalhe(): Collection
    {
        if ($this->itensDetalheCache !== null) {
            return $this->itensDetalheCache;
        }

        if ($this->itens->isNotEmpty()) {
            $this->itensViaTwin = false;

            return $this->itensDetalheCache = $this->itens;
        }

        if (! $this->chave_acesso) {
            return $this->itensDetalheCache = collect();
        }

        $itens = EfdNotaItem::whereIn('efd_nota_id', function ($q) {
            $q->select('id')->from('efd_notas')
                ->where('user_id', $this->user_id)
                ->where('chave_acesso', $this->chave_acesso)
                ->where('origem_arquivo', 'contribuicoes')
                ->where('id', '!=', $this->id);
        })->orderBy('numero_item')->get();

        $this->itensViaTwin = $itens->isNotEmpty();

        return $this->itensDetalheCache = $itens;
    }

    /** Os itens exibidos vieram da gêmea de contribuicoes (não da própria nota)? */
    public function itensViaTwin(): bool
    {
        $this->itensDetalhe();

        return $this->itensViaTwin;
    }

    /**
     * Catálogo (0200) associado aos itens da nota, indexado por `codigo_item`.
     * Casa por (user_id, cod_item) preferindo a MESMA importação (versão do período),
     * com fallback à mais recente. Memoizado — seguro chamar em loop na view.
     *
     * @return array<string, EfdCatalogoItem>
     */
    public function catalogoPorItem(): array
    {
        if ($this->catalogoMapCache !== null) {
            return $this->catalogoMapCache;
        }

        $codigos = $this->itensDetalhe()->pluck('codigo_item')->filter()->unique()->values();
        if ($codigos->isEmpty()) {
            return $this->catalogoMapCache = [];
        }

        // Catálogo é único por (cliente_id, cod_item) → escopar pelo cliente da nota dá 1 linha por código.
        $map = [];
        EfdCatalogoItem::where('user_id', $this->user_id)
            ->when($this->cliente_id, fn ($q) => $q->where('cliente_id', $this->cliente_id))
            ->whereIn('cod_item', $codigos)
            ->orderByDesc('id')
            ->get()
            ->each(function ($c) use (&$map) {
                $map[$c->cod_item] ??= $c;
            });

        $this->aplicarCatalogoDoPeriodo($map, $codigos->all());

        return $this->catalogoMapCache = $map;
    }

    /**
     * Sobrescreve nos itens do catálogo os campos logados (NCM, alíquota, unidade,
     * descrição) pela versão que valia na importação DESTA nota — para que uma nota
     * de período anterior cruze com o cadastro como ele era na época, e não com a
     * versão sobrescrita por uma importação posterior (DO UPDATE). Sem histórico
     * para o item, o mapa permanece com a versão atual (caso comum, zero custo).
     *
     * @param  array<string,EfdCatalogoItem>  $map  (mutado por referência)
     * @param  array<int,string>  $codigos
     */
    private function aplicarCatalogoDoPeriodo(array &$map, array $codigos): void
    {
        if (empty($map) || ! $this->importacao_id) {
            return;
        }

        $ref = DB::table('efd_importacoes')
            ->where('id', $this->importacao_id)
            ->first(['concluido_em', 'created_at']);

        $refTime = $ref->concluido_em ?? $ref->created_at ?? null;
        if (! $refTime) {
            return;
        }

        $overrides = app(CatalogoHistoricoService::class)->valoresNaData(
            (int) $this->user_id,
            $this->cliente_id ? (int) $this->cliente_id : null,
            $codigos,
            (string) $refTime,
        );

        foreach ($overrides as $cod => $campos) {
            if (! isset($map[$cod])) {
                continue;
            }
            foreach ($campos as $campo => $valor) {
                $map[$cod]->setAttribute($campo, $valor);
            }
        }
    }

    // Acessores

    public function getIsEntradaAttribute(): bool
    {
        return $this->tipo_operacao === 'entrada';
    }

    public function getIsSaidaAttribute(): bool
    {
        return $this->tipo_operacao === 'saida';
    }

    public function getTipoNotaFormatadoAttribute(): string
    {
        return $this->tipo_operacao === 'entrada' ? 'Entrada' : 'Saída';
    }

    public function getModeloDocFormatadoAttribute(): string
    {
        return match ((string) $this->modelo) {
            '00' => 'NFS-e',
            '01' => 'Nota Fiscal',
            '1B' => 'Nota Fiscal Avulsa',
            '04' => 'Nota Fiscal de Produtor',
            '55' => 'NF-e',
            '57' => 'CT-e',
            '65' => 'NFC-e',
            '67' => 'CT-e OS',
            default => $this->modelo ?? 'N/A',
        };
    }

    // Scopes

    public function scopeDoUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeEntradas($query)
    {
        return $query->where('tipo_operacao', 'entrada');
    }

    public function scopeSaidas($query)
    {
        return $query->where('tipo_operacao', 'saida');
    }

    public function scopeEfdFiscal($query)
    {
        return $query->where('origem_arquivo', 'fiscal');
    }

    public function scopeEfdContrib($query)
    {
        return $query->where('origem_arquivo', 'contribuicoes');
    }

    public function scopePeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_emissao', [$dataInicio, $dataFim]);
    }
}
