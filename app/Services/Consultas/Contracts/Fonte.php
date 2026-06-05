<?php

namespace App\Services\Consultas\Contracts;

interface Fonte
{
    /** Chave canônica usada em resultado_dados / consultas_realizadas (ex: 'cadastro'). */
    public function chave(): string;

    /**
     * Sub-atributos de `monitoramento_planos.consultas_incluidas` que esta fonte
     * fornece (ex: cadastro fornece 'situacao_cadastral', 'endereco', 'qsa'...).
     * Usado pelo Registry para mapear plano → fontes necessárias.
     *
     * @return string[]
     */
    public function fornece(): array;

    /** Provider responsável: 'minhareceita' | 'infosimples'. */
    public function provider(): string;

    /** Slug do endpoint no provider (vazio quando o provider monta a URL pelo CNPJ). */
    public function slug(): string;

    /** Monta os params da chamada a partir do alvo (participante normalizado). */
    public function params(array $alvo): array;

    /**
     * Converte o raw do provider no shape canônico mergeado em resultado_dados.
     * Recebe o $status canônico (sucesso/nao_encontrado/indeterminado/erro_participante/
     * retry/fatal) para a fonte interpretar não-sucessos (ex: 611→INDETERMINADO).
     * Deve retornar [] quando não há nada a persistir (ex: falha técnica).
     */
    public function normalizar(array $raw, string $status = 'sucesso'): array;

    /** Custo em créditos desta fonte (0 = grátis, ex: cadastro/minhareceita). */
    public function custoCreditos(): int;

    /**
     * A fonte está operacional para rotear pro Laravel? Gate de cutover seguro:
     * fontes InfoSimples só ficam prontas quando ativadas (token + flag), evitando
     * migrar um plano pago pro Laravel antes do provedor estar pago/validado.
     */
    public function pronta(): bool;

    /**
     * A fonte se aplica a ESTE alvo? Trata a limitação de cobertura do provedor por
     * UF/cidade (ex: CND Estadual/Municipal só existem em algumas UFs/municípios no
     * InfoSimples). Quando false, o job pula a fonte sem chamar o provedor nem cobrar,
     * marcando o resultado como INDISPONÍVEL.
     */
    public function aplicavelPara(array $alvo): bool;
}
