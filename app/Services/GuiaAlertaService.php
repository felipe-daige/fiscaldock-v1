<?php

namespace App\Services;

use App\Models\Alerta;

/**
 * Fonte única da "Orientação de Tratativa" dos alertas: o que significa, como
 * resolver e qual a ação (CTA). Antes vivia hardcoded no show.blade.php — agora
 * é testável e consistente entre lista e detalhe, com CTA resolvido por alerta
 * (corrige o caso agregado, sem participante_id).
 */
class GuiaAlertaService
{
    /**
     * @return array{titulo_o_que_e:string, texto_o_que_e:string, titulo_acao:string, texto_acao:string, cta_text:string, cta_url:?string}
     */
    public function para(Alerta $alerta): array
    {
        $tipo = (string) $alerta->tipo;
        $guia = $this->base();

        $mapa = $this->textos();
        foreach ($mapa as $def) {
            if (in_array($tipo, $def['tipos'], true)) {
                $guia = array_merge($guia, $def['guia']);
                break;
            }
        }

        $guia['cta_url'] = $this->resolverCta($alerta, $guia['cta_url'] ?? null);

        return $guia;
    }

    /**
     * Versão resumida pra renderizar na LISTA (card) — só o essencial pra agir.
     *
     * @return array{cta_text:string, cta_url:?string}
     */
    public function resumo(Alerta $alerta): array
    {
        $g = $this->para($alerta);

        return ['cta_text' => $g['cta_text'], 'cta_url' => $g['cta_url']];
    }

    private function resolverCta(Alerta $alerta, ?string $ctaUrl): ?string
    {
        // Placeholder dinâmico: rotas que dependem do alerta concreto.
        if ($ctaUrl === ':participante') {
            return $alerta->participante_id
                ? '/app/participante/'.$alerta->participante_id
                : '/app/participantes';
        }

        return $ctaUrl;
    }

    /**
     * @return array{titulo_o_que_e:string, texto_o_que_e:string, titulo_acao:string, texto_acao:string, cta_text:string, cta_url:?string}
     */
    private function base(): array
    {
        return [
            'titulo_o_que_e' => 'O que isso significa?',
            'texto_o_que_e' => 'Encontramos algumas inconsistências em nossos registros ou integrações automáticas.',
            'titulo_acao' => 'Como resolver',
            'texto_acao' => 'Siga os protocolos internos para revisar as informações listadas abaixo e marque o alerta como resolvido ao concluir.',
            'cta_text' => 'Marcar como Resolvido',
            'cta_url' => null,
        ];
    }

    /**
     * @return array<int, array{tipos: array<int,string>, guia: array<string,mixed>}>
     */
    private function textos(): array
    {
        return [
            [
                'tipos' => ['nunca_consultado', 'consulta_vencida'],
                'guia' => [
                    'texto_o_que_e' => 'Participante(s) com notas fiscais que nunca tiveram o CNPJ verificado junto à Receita Federal, ou cuja consulta foi feita há mais de 90 dias. Manter a situação cadastral em dia evita negócios com empresas inaptas.',
                    'texto_acao' => 'Acesse a consulta agora e verifique a situação cadastral na Receita Federal. Ao concluir com sucesso, o alerta some sozinho do painel (ou resolva manualmente).',
                    'cta_text' => 'Ir para Consulta',
                    'cta_url' => ':participante',
                ],
            ],
            [
                'tipos' => ['situacao_irregular', 'cnpj_situacao_irregular', 'participante_inativo', 'participante_sem_ie', 'fornecedor_irregular'],
                'guia' => [
                    'texto_o_que_e' => 'Este participante está com pendências cadastrais na Receita Federal (ex.: Baixada, Inapta, Suspensa). Operar com este CNPJ pode causar rejeições de notas fiscais e pesadas multas.',
                    'texto_acao' => 'Recomende ao responsável financeiro interromper operações comerciais e bloquear o cadastro no ERP até a total regularização. Abra a ficha do participante para conferir os detalhes.',
                    'cta_text' => 'Ver participante',
                    'cta_url' => ':participante',
                ],
            ],
            [
                'tipos' => ['notas_duplicadas'],
                'guia' => [
                    'texto_o_que_e' => 'Há duas ou mais notas registradas com exatamente a mesma numeração, série, modelo e participante. Normalmente indica notas importadas em duplicidade ou duplo input no ERP.',
                    'texto_acao' => 'Confira a listagem abaixo no seu ERP/Contábil, cancele e apague o registro excedente para os livros refletirem a realidade, e gere novo SPED.',
                    'cta_text' => '',
                    'cta_url' => null,
                ],
            ],
            [
                'tipos' => ['notas_valor_zerado', 'notas_sem_itens', 'cfops_inconsistentes', 'participantes_sem_cnpj'],
                'guia' => [
                    'texto_o_que_e' => 'Existem notas importadas com inconsistências (sem valor, sem itens, ou CFOP de entrada/saída cruzado). Isso impede escriturações corretas e pode gerar passivos.',
                    'texto_acao' => 'Acesse os dados da(s) nota(s) afetada(s) no seu ERP. Revise se os itens foram integrados corretamente e se os CFOPs estão de acordo com o padrão SEFAZ.',
                    'cta_text' => '',
                    'cta_url' => null,
                ],
            ],
            [
                'tipos' => ['gap_importacao', 'gap_temporal'],
                'guia' => [
                    'texto_o_que_e' => 'Detectamos meses sem escrituração EFD importada num período onde seria esperado ter arquivos fiscais — possível obrigação acessória não entregue.',
                    'texto_acao' => 'Faça o upload do(s) arquivo(s) SPED (EFD ICMS/IPI ou Contribuições) dos meses indicados abaixo dentro da plataforma.',
                    'cta_text' => 'Ir para Importações SPED',
                    'cta_url' => '/app/importacao/efd',
                ],
            ],
            [
                'tipos' => ['pis_cofins_incompleto'],
                'guia' => [
                    'texto_o_que_e' => 'Um volume alto de itens (PIS/COFINS) veio sem detalhamento de impostos ou sem as alíquotas base no arquivo exportado.',
                    'texto_acao' => 'Provável erro no cadastro de produtos ou no mapeamento Tributário/NCM do ERP fiscal. Revise o cadastro e gere novo SPED.',
                    'cta_text' => '',
                    'cta_url' => null,
                ],
            ],
        ];
    }
}
