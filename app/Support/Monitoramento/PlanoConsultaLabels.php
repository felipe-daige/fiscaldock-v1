<?php

namespace App\Support\Monitoramento;

final class PlanoConsultaLabels
{
    /**
     * Mapa canônico chave de consulta → rótulo legível exibido nos cards.
     *
     * Chaves omitidas são intencionalmente "dobradas" em outro rótulo
     * (ex.: `endereco` já está em "Dados cadastrais e endereço";
     * `cnaes_secundarios` já está em "CNAEs (principal e secundários)").
     *
     * @return array<string, string>
     */
    public static function mapa(): array
    {
        return [
            'situacao_cadastral' => 'Situação cadastral (ativa, inapta, baixada)',
            'dados_cadastrais' => 'Dados cadastrais e endereço',
            'cnaes' => 'CNAEs (principal e secundários)',
            'qsa' => 'Quadro societário (QSA)',
            'simples_nacional' => 'Simples Nacional e MEI',
            'regime_tributario' => 'Regime tributário',
            'historico_simples' => 'Histórico no Simples Nacional',
            'parecer_fiscal' => 'Parecer fiscal automático',
            'cnd_federal' => 'CND Federal (PGFN/RFB)',
            'crf_fgts' => 'Regularidade do FGTS (CRF)',
            'cndt' => 'CNDT (débitos trabalhistas)',
            'cnd_estadual' => 'CND Estadual',
            'cnd_municipal' => 'CND Municipal',
            'sintegra' => 'SINTEGRA (inscrição estadual)',
            'cgu_cnc' => 'Sanções e idoneidade (CGU)',
            'cnj_improbidade' => 'Improbidade administrativa (CNJ)',
        ];
    }

    /**
     * Converte as `consultas_incluidas` de um plano nos rótulos exibidos no card.
     * Preserva a ordem do catálogo, ignora chaves desconhecidas/dobradas e
     * não repete rótulos (sub-chaves redundantes colapsam num único rótulo).
     *
     * @param  array<int, string>  $chaves
     * @return array<int, string>
     */
    public static function paraConsultas(array $chaves): array
    {
        $mapa = self::mapa();
        $rotulos = [];

        foreach ($chaves as $chave) {
            if (! isset($mapa[$chave])) {
                continue;
            }

            $rotulo = $mapa[$chave];

            if (! in_array($rotulo, $rotulos, true)) {
                $rotulos[] = $rotulo;
            }
        }

        return $rotulos;
    }
}
