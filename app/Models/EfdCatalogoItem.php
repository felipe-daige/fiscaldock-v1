<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EfdCatalogoItem extends Model
{
    protected $table = 'efd_catalogo_itens';

    /** Tabela TIPO_ITEM do registro 0200 (Guia Prático EFD). NCM não é exigido em 07/08/09/10/99. */
    public const TIPO_ITEM_LABELS = [
        '00' => 'Mercadoria p/ Revenda',
        '01' => 'Matéria-Prima',
        '02' => 'Embalagem',
        '03' => 'Produto em Processo',
        '04' => 'Produto Acabado',
        '05' => 'Subproduto',
        '06' => 'Produto Intermediário',
        '07' => 'Uso e Consumo',
        '08' => 'Ativo Imobilizado',
        '09' => 'Serviços',
        '10' => 'Outros Insumos',
        '99' => 'Outras',
    ];

    protected $fillable = [
        'user_id', 'cliente_id', 'importacao_id',
        'cod_item', 'descr_item', 'cod_barra', 'tipo_item',
        'cod_ncm', 'cod_gen', 'aliq_icms', 'unid_inv', 'dados_brutos',
    ];

    protected function casts(): array
    {
        return [
            'aliq_icms' => 'decimal:4',
            'dados_brutos' => 'array',
        ];
    }

    /**
     * Tipos do 0200 que se referem a mercadoria/produto e, portanto, EXIGEM NCM
     * (Guia Prático EFD ICMS/IPI — COD_NCM obrigatório p/ itens de mercadoria/
     * produto). 07–10 e 99 (uso e consumo, ativo, serviços, outros insumos,
     * outras) não exigem; tipo desconhecido/nulo também não dispara alerta.
     */
    public const TIPOS_EXIGEM_NCM = ['00', '01', '02', '03', '04', '05', '06'];

    /** Rótulo do tipo do item (ex.: "Outras"); '—' se desconhecido. */
    public function getTipoLabelAttribute(): string
    {
        return self::TIPO_ITEM_LABELS[$this->tipo_item] ?? '—';
    }

    /** NCM é exigido p/ este item? (mercadoria/produto). NCM em branco em item que
     *  exige é gap real; em item que não exige é legítimo. */
    public function exigeNcm(): bool
    {
        return in_array($this->tipo_item, self::TIPOS_EXIGEM_NCM, true);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function importacao(): BelongsTo
    {
        return $this->belongsTo(EfdImportacao::class, 'importacao_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
