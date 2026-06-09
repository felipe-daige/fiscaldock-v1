<?php

namespace App\Services\Xml;

use SimpleXMLElement;

/**
 * Parser nativo de NF-e modelo 55 (XSD 4.00). Sem dependência externa, sem DB.
 * Retorna ['header' => [...colunas xml_notas], 'itens' => [[...colunas xml_notas_itens]], 'payload' => [...]].
 *
 * Pegadinha do namespace NF-e: o ns padrão não herda em xpath; navegamos com
 * children($ns) re-derivando a cada nível.
 */
class NfeXmlParser
{
    private const NS = 'http://www.portalfiscal.inf.br/nfe';

    public function parse(string $content): array
    {
        $root = $this->loadXml($content);

        $rootName = $root->getName();
        if ($rootName === 'nfeProc') {
            $rc = $root->children(self::NS);
            $nfe = $rc->NFe;
            // infProt está dentro de protNFe; seus filhos também estão no NS.
            $prot = isset($rc->protNFe) ? $rc->protNFe->children(self::NS)->infProt : null;
            $protc = $prot ? $prot->children(self::NS) : null;
        } elseif ($rootName === 'NFe') {
            $nfe = $root;
            $prot = null;
            $protc = null;
        } else {
            throw new NfeParseException("Raiz inesperada para NF-e: {$rootName}");
        }

        $inf = $nfe->children(self::NS)->infNFe;
        $infc = $inf->children(self::NS);
        $ide = $infc->ide->children(self::NS);

        $modelo = (string) $ide->mod;
        if ($modelo !== '55') {
            throw new NfeParseException("Modelo não suportado nesta fase: {$modelo} (esperado 55)");
        }

        $id = (string) $inf->attributes()->Id;       // "NFe" + 44 dígitos
        $chave = preg_replace('/[^0-9]/', '', substr($id, 3));

        $emit = $infc->emit->children(self::NS);
        $dest = isset($infc->dest) ? $infc->dest->children(self::NS) : null;
        $tot = $infc->total->children(self::NS)->ICMSTot->children(self::NS);

        $header = [
            'chave_acesso' => $chave,
            'id_alternativo' => $id,
            'tipo_documento' => 'NFE',
            'modelo' => '55',
            'origem' => 'importacao_xml',
            'ambiente' => (string) $ide->tpAmb,
            'versao_layout' => (string) $inf->attributes()->versao,
            'numero_documento' => (int) (string) $ide->nNF,
            'serie' => (string) $ide->serie,
            'data_emissao' => $this->iso((string) $ide->dhEmi),
            'natureza_operacao' => (string) $ide->natOp,
            'tipo_nota' => (int) (string) $ide->tpNF,
            'finalidade' => (int) (string) $ide->finNFe,
            'municipio_fato_gerador_ibge' => (string) $ide->cMunFG ?: null,
            'chave_referenciada' => $this->primeiraRefNFe($ide),
            'emit_documento' => $this->doc($emit),
            'emit_razao_social' => (string) $emit->xNome,
            'emit_uf' => (string) $emit->enderEmit->children(self::NS)->UF ?: null,
            'emit_municipio_ibge' => (string) $emit->enderEmit->children(self::NS)->cMun ?: null,
            'emit_ie' => (string) $emit->IE ?: null,
            'emit_im' => (string) $emit->IM ?: null,
            'dest_documento' => $dest ? $this->doc($dest) : null,
            'dest_razao_social' => $dest ? (string) $dest->xNome : null,
            'dest_uf' => $dest && isset($dest->enderDest) ? ((string) $dest->enderDest->children(self::NS)->UF ?: null) : null,
            'dest_municipio_ibge' => $dest && isset($dest->enderDest) ? ((string) $dest->enderDest->children(self::NS)->cMun ?: null) : null,
            'dest_ie' => $dest ? ((string) $dest->IE ?: null) : null,
            'dest_im' => $dest ? ((string) $dest->IM ?: null) : null,
            'valor_total' => $this->num($tot->vNF),
            'valor_desconto' => $this->num($tot->vDesc),
            'icms_valor' => $this->num($tot->vICMS),
            'icms_st_valor' => $this->num($tot->vST),
            'pis_valor' => $this->num($tot->vPIS),
            'cofins_valor' => $this->num($tot->vCOFINS),
            'ipi_valor' => $this->num($tot->vIPI),
            'tributos_total' => $this->num($tot->vTotTrib),
            'protocolo_autorizacao' => $protc ? ((string) $protc->nProt ?: null) : null,
            'data_autorizacao' => $protc ? $this->iso((string) $protc->dhRecbto) : null,
            'status_autorizacao' => $protc ? ((string) $protc->cStat ?: null) : null,
            'motivo_autorizacao' => $protc ? ((string) $protc->xMotivo ?: null) : null,
        ];

        $itens = [];
        foreach ($infc->det as $det) {
            $itens[] = $this->item($det);
        }

        // Para nodeToArray/parte, passar o ELEMENTO (não a children-view).
        // $emit/$dest/$tot são children-views (usadas no header para acesso por campo);
        // para serialização recursiva precisamos do elemento em si.
        $payload = [
            'emit' => $this->parte($infc->emit),
            'dest' => isset($infc->dest) ? $this->parte($infc->dest) : null,
            'transp' => isset($infc->transp) ? $this->nodeToArray($infc->transp) : null,
            'pag' => isset($infc->pag) ? $this->nodeToArray($infc->pag) : null,
            'infAdic' => isset($infc->infAdic) ? $this->nodeToArray($infc->infAdic) : null,
            'totais' => $this->nodeToArray($infc->total->children(self::NS)->ICMSTot),
            'ide_extra' => [
                'idDest' => $this->strN($ide->idDest ?? null),
                'indFinal' => $this->strN($ide->indFinal ?? null),
                'indPres' => $this->strN($ide->indPres ?? null),
            ],
        ];

        return ['header' => $header, 'itens' => $itens, 'payload' => $payload];
    }

    private function item(SimpleXMLElement $det): array
    {
        $nItem = (int) $det->attributes()->nItem;
        $detc = $det->children(self::NS);
        $prod = $detc->prod->children(self::NS);
        $imp = $detc->imposto->children(self::NS);

        $icms = $this->primeiroGrupo($imp->ICMS ?? null);
        $pis = $this->primeiroGrupo($imp->PIS ?? null);
        $cof = $this->primeiroGrupo($imp->COFINS ?? null);
        $ipi = $this->primeiroGrupo($imp->IPI ?? null);
        $ipiTrib = $ipi && isset($ipi->children(self::NS)->vIPI) ? $ipi->children(self::NS) : null;

        return [
            'numero_item' => $nItem,
            'codigo_item' => (string) $prod->cProd,
            'descricao' => (string) $prod->xProd,
            'quantidade' => $this->num($prod->qCom),
            'unidade_medida' => (string) $prod->uCom ?: null,
            'valor_unitario' => $this->num($prod->vUnCom),
            'valor_total' => $this->num($prod->vProd),
            'cfop' => (string) $prod->CFOP ?: null,
            'ncm' => (string) $prod->NCM ?: null,
            'cest' => (string) $prod->CEST ?: null,
            'ean' => (string) $prod->cEAN ?: null,
            'cst_icms' => $icms ? ((string) ($icms->children(self::NS)->CST ?? $icms->children(self::NS)->CSOSN) ?: null) : null,
            'aliquota_icms' => $icms ? $this->numN($icms->children(self::NS)->pICMS ?? null) : null,
            'valor_icms' => $icms ? $this->numN($icms->children(self::NS)->vICMS ?? null) : null,
            'origem_mercadoria' => $icms ? $this->strN($icms->children(self::NS)->orig ?? null) : null,
            'cst_pis' => $pis ? ((string) $pis->children(self::NS)->CST ?: null) : null,
            'aliquota_pis' => $pis ? $this->numN($pis->children(self::NS)->pPIS ?? null) : null,
            'valor_pis' => $pis ? $this->numN($pis->children(self::NS)->vPIS ?? null) : null,
            'cst_cofins' => $cof ? ((string) $cof->children(self::NS)->CST ?: null) : null,
            'aliquota_cofins' => $cof ? $this->numN($cof->children(self::NS)->pCOFINS ?? null) : null,
            'valor_cofins' => $cof ? $this->numN($cof->children(self::NS)->vCOFINS ?? null) : null,
            'cst_ipi' => $ipi ? ((string) $ipi->children(self::NS)->CST ?: null) : null,
            'valor_ipi' => $ipiTrib ? $this->numN($ipiTrib->vIPI ?? null) : null,
            'metadados' => [
                'uTrib' => (string) $prod->uTrib ?: null,
                'qTrib' => (string) $prod->qTrib ?: null,
                'vUnTrib' => (string) $prod->vUnTrib ?: null,
                'indTot' => $this->strN($prod->indTot ?? null),
                'cEANTrib' => (string) $prod->cEANTrib ?: null,
                'vDesc' => (string) $prod->vDesc ?: null,
                'vFrete' => (string) $prod->vFrete ?: null,
                'vSeg' => (string) $prod->vSeg ?: null,
            ],
        ];
    }

    /** Primeiro grupo-filho de ICMS/PIS/COFINS/IPI (ex.: ICMS40, PISOutr). */
    private function primeiroGrupo(?SimpleXMLElement $node): ?SimpleXMLElement
    {
        if ($node === null) {
            return null;
        }
        foreach ($node->children(self::NS) as $child) {
            return $child;
        }

        return null;
    }

    private function parte(SimpleXMLElement $p): array
    {
        return array_filter($this->nodeToArray($p), fn ($v) => $v !== null && $v !== '');
    }

    private function primeiraRefNFe(SimpleXMLElement $ide): ?string
    {
        if (! isset($ide->NFref)) {
            return null;
        }
        $ref = $ide->NFref->children(self::NS);

        return isset($ref->refNFe) ? preg_replace('/[^0-9]/', '', (string) $ref->refNFe) : null;
    }

    private function doc(SimpleXMLElement $parte): ?string
    {
        $d = (string) ($parte->CNPJ ?? '') ?: (string) ($parte->CPF ?? '');

        return $d !== '' ? preg_replace('/[^0-9]/', '', $d) : null;
    }

    /** Converte nó SimpleXML em array recursivo (só filhos no NS NF-e; ignora xmldsig e outros). */
    private function nodeToArray(SimpleXMLElement $node): array|string
    {
        $children = $node->children(self::NS);
        if ($children->count() === 0) {
            return (string) $node;
        }
        $out = [];
        foreach ($children as $name => $child) {
            $val = $this->nodeToArray($child);
            if (isset($out[$name])) {
                if (! is_array($out[$name]) || ! array_is_list($out[$name])) {
                    $out[$name] = [$out[$name]];
                }
                $out[$name][] = $val;
            } else {
                $out[$name] = $val;
            }
        }

        return $out;
    }

    private function iso(string $dh): ?string
    {
        if ($dh === '') {
            return null;
        }
        try {
            return (new \DateTimeImmutable($dh))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Carrega o XML de forma robusta a encoding: remove BOM e lixo antes do prolog
     * e, se os bytes forem ISO-8859-1 com declaração UTF-8 (mismatch comum em NF-e
     * real), reinterpreta como latin1 normalizando a declaração para UTF-8.
     */
    private function loadXml(string $content): SimpleXMLElement
    {
        // Remove BOM (UTF-8/UTF-16) e qualquer coisa antes do prolog/raiz.
        $content = preg_replace('/^(\xEF\xBB\xBF|\xFF\xFE|\xFE\xFF)/', '', $content) ?? $content;
        $pos = strpos($content, '<?xml');
        if ($pos === false) {
            $pos = strpos($content, '<');
        }
        if ($pos !== false && $pos > 0) {
            $content = substr($content, $pos);
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        try {
            $xml = simplexml_load_string($content);
            if ($xml === false) {
                $alt = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
                $alt = preg_replace('/(<\?xml[^>]*encoding=")[^"]*(")/i', '${1}UTF-8${2}', $alt, 1) ?? $alt;
                libxml_clear_errors();
                $xml = simplexml_load_string($alt);
            }
        } finally {
            libxml_use_internal_errors($previous);
        }

        if ($xml === false) {
            throw new NfeParseException('XML inválido: não foi possível parsear (estrutura ou encoding).');
        }

        return $xml;
    }

    private function num(SimpleXMLElement $v): float
    {
        return (float) (string) $v;
    }

    private function numN(?SimpleXMLElement $v): ?float
    {
        return $v !== null && (string) $v !== '' ? (float) (string) $v : null;
    }

    /** String nulável que preserva o valor "0" (não usar `?: null`, que descarta "0"). */
    private function strN(SimpleXMLElement|string|null $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = (string) $v;

        return $s === '' ? null : $s;
    }
}
