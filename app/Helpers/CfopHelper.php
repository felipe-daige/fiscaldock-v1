<?php

namespace App\Helpers;

class CfopHelper
{
    private static array $descricoes = [
        // ═══════════════════════════════════════════════════════════
        // ENTRADAS — mesmo estado (1xxx)
        // ═══════════════════════════════════════════════════════════

        // 1.1xx — Compras p/ industrialização, comercialização ou prestação de serviços
        1101 => 'Compra p/ industrialização',
        1102 => 'Compra p/ comercialização',
        1111 => 'Compra p/ industrialização de mercadoria recebida anteriormente em consignação industrial',
        1113 => 'Compra p/ comercialização de mercadoria recebida anteriormente em consignação mercantil',
        1116 => 'Compra p/ industrialização originada de encomenda p/ recebimento futuro',
        1117 => 'Compra p/ comercialização originada de encomenda p/ recebimento futuro',
        1118 => 'Compra de mercadoria p/ comercialização pelo adquirente originário, entregue pelo vendedor remetente ao destinatário, em venda à ordem',
        1120 => 'Compra p/ industrialização em que a mercadoria foi remetida pelo fornecedor ao industrializador',
        1121 => 'Compra p/ comercialização em que a mercadoria foi remetida pelo fornecedor ao industrializador',
        1122 => 'Compra p/ industrialização de mercadoria não equivalente recebida em troca',
        1124 => 'Industrialização efetuada por outra empresa',
        1125 => 'Industrialização efetuada por outra empresa quando a mercadoria remetida p/ utilização no processo',
        1126 => 'Compra p/ utilização na prestação de serviço',
        1128 => 'Compra p/ utilização na prestação de serviço sujeita ao ICMS',

        // 1.15x — Transferências p/ industrialização, comercialização ou prestação de serviços
        1151 => 'Transferência p/ industrialização',
        1152 => 'Transferência p/ comercialização',
        1153 => 'Transferência de energia elétrica p/ distribuição',
        1154 => 'Transferência p/ utilização na prestação de serviço',

        // 1.2xx — Devoluções de vendas
        1201 => 'Devolução de venda de produção',
        1202 => 'Devolução de venda de mercadoria',
        1203 => 'Devolução de venda de prestação de serviço',
        1204 => 'Devolução de venda de produção efetuada fora do estabelecimento',
        1205 => 'Anulação de valor relativo a prestação de serviço de comunicação',
        1206 => 'Anulação de valor relativo a prestação de serviço de transporte',
        1207 => 'Anulação de valor relativo a venda de energia elétrica',
        1208 => 'Devolução de produção remetida em transferência',
        1209 => 'Devolução de mercadoria remetida em transferência',

        // 1.25x — Compras de energia elétrica
        1251 => 'Compra de energia elétrica p/ distribuição ou comercialização',
        1252 => 'Compra de energia elétrica por estabelecimento industrial',
        1253 => 'Compra de energia elétrica por estabelecimento comercial',
        1254 => 'Compra de energia elétrica por estabelecimento prestador de serviço de transporte',
        1255 => 'Compra de energia elétrica por estabelecimento prestador de serviço de comunicação',
        1256 => 'Compra de energia elétrica por estabelecimento de produtor rural',
        1257 => 'Compra de energia elétrica p/ consumo por demanda contratada',

        // 1.3xx — Aquisições de serviços de comunicação
        1301 => 'Aquisição de serviço de comunicação p/ execução de serviço da mesma natureza',
        1302 => 'Aquisição de serviço de comunicação por estabelecimento comercial',
        1303 => 'Aquisição de serviço de comunicação por estabelecimento industrial',
        1304 => 'Aquisição de serviço de comunicação por estabelecimento prestador de serviço de transporte',
        1305 => 'Aquisição de serviço de comunicação por estabelecimento gerador ou distribuidor de energia elétrica',
        1306 => 'Aquisição de serviço de comunicação por estabelecimento de produtor rural',

        // 1.35x — Aquisições de serviços de transporte
        1351 => 'Aquisição de serviço de transporte p/ execução de serviço da mesma natureza',
        1352 => 'Aquisição de serviço de transporte por estabelecimento comercial',
        1353 => 'Aquisição de serviço de transporte por estabelecimento industrial',
        1354 => 'Aquisição de serviço de transporte por estabelecimento prestador de serviço de comunicação',
        1355 => 'Aquisição de serviço de transporte por estabelecimento gerador ou distribuidor de energia elétrica',
        1356 => 'Aquisição de serviço de transporte por estabelecimento de produtor rural',
        1360 => 'Aquisição de serviço de transporte por contribuinte substituto em relação ao serviço de transporte',

        // 1.4xx — Entradas de mercadorias sujeitas a ST
        1401 => 'Compra p/ industrialização em operação c/ mercadoria sujeita a ST',
        1403 => 'Compra p/ comercialização em operação c/ mercadoria sujeita a ST',
        1406 => 'Compra de bem p/ ativo imobilizado cuja mercadoria está sujeita a ST',
        1407 => 'Compra de mercadoria p/ uso ou consumo cuja mercadoria está sujeita a ST',
        1408 => 'Transferência p/ industrialização em operação c/ mercadoria sujeita a ST',
        1409 => 'Transferência p/ comercialização em operação c/ mercadoria sujeita a ST',
        1410 => 'Devolução de venda de produção em operação c/ mercadoria sujeita a ST',
        1411 => 'Devolução de venda de mercadoria sujeita a ST',
        1414 => 'Retorno de mercadoria remetida p/ estabelecimento do adquirente c/ ST',

        // 1.5xx — Entradas de mercadorias remetidas p/ formação de lote ou c/ fim específico de exportação e eventuais devoluções
        1501 => 'Entrada de mercadoria recebida com fim específico de exportação',
        1503 => 'Entrada decorrente de devolução de produto remetido c/ fim específico de exportação',
        1504 => 'Entrada decorrente de devolução de mercadoria remetida c/ fim específico de exportação',
        1505 => 'Entrada decorrente de devolução simbólica de mercadoria c/ fim específico de exportação',
        1506 => 'Entrada decorrente de devolução de produção p/ formação de lote de exportação',
        1551 => 'Compra de bem p/ ativo imobilizado',
        1552 => 'Transferência de bem do ativo imobilizado',
        1553 => 'Devolução de venda de bem do ativo imobilizado',
        1554 => 'Retorno de bem do ativo imobilizado remetido p/ uso fora do estabelecimento',
        1555 => 'Entrada de bem do ativo imobilizado de terceiro remetido p/ uso no estabelecimento',
        1556 => 'Compra de material p/ uso ou consumo',
        1557 => 'Transferência de material p/ uso ou consumo',

        // 1.6xx — Créditos e ressarcimentos de ICMS
        1601 => 'Recebimento por transferência de crédito de ICMS',
        1602 => 'Recebimento de crédito de ICMS por transferência',
        1603 => 'Ressarcimento de ICMS retido por substituição tributária',
        1604 => 'Lançamento do crédito relativo à compra de bem p/ ativo imobilizado',
        1605 => 'Recebimento por transferência de saldo devedor de ICMS de outro estabelecimento',

        // 1.65x — Entradas de combustíveis e lubrificantes
        1651 => 'Compra de combustível ou lubrificante p/ industrialização',
        1652 => 'Compra de combustível ou lubrificante p/ comercialização',
        1653 => 'Compra de combustível/lubrificante por consumidor final',
        1658 => 'Transferência de combustível ou lubrificante p/ industrialização',
        1659 => 'Transferência de combustível ou lubrificante p/ comercialização',
        1660 => 'Devolução de venda de combustível ou lubrificante destinado à industrialização',
        1661 => 'Devolução de venda de combustível ou lubrificante destinado à comercialização',
        1662 => 'Devolução de venda de combustível ou lubrificante destinado a consumidor final',
        1663 => 'Entrada de combustível ou lubrificante p/ armazenagem',
        1664 => 'Retorno de combustível ou lubrificante remetido p/ armazenagem',

        // 1.9xx — Outras entradas de mercadorias ou aquisições de serviços
        1901 => 'Entrada p/ industrialização por encomenda',
        1902 => 'Retorno de mercadoria remetida p/ industrialização por encomenda',
        1903 => 'Retorno de mercadoria remetida p/ venda fora do estabelecimento',
        1904 => 'Retorno de remessa p/ depósito fechado ou armazém geral',
        1905 => 'Entrada de mercadoria recebida p/ depósito em depósito fechado ou armazém geral',
        1906 => 'Retorno de mercadoria remetida p/ depósito fechado ou armazém geral',
        1907 => 'Retorno simbólico de mercadoria depositada em depósito fechado ou armazém geral',
        1908 => 'Entrada de bem por conta de contrato de comodato',
        1909 => 'Retorno de bem remetido por conta de contrato de comodato',
        1910 => 'Entrada de bonificação, doação ou brinde',
        1911 => 'Entrada de amostra grátis',
        1912 => 'Entrada de mercadoria ou bem recebido para demonstração',
        1913 => 'Retorno de mercadoria ou bem remetido para demonstração',
        1914 => 'Retorno de mercadoria ou bem remetido para exposição ou feira',
        1915 => 'Entrada de mercadoria ou bem recebido para conserto ou reparo',
        1916 => 'Retorno de mercadoria ou bem remetido para conserto ou reparo',
        1917 => 'Entrada de mercadoria recebida em consignação mercantil ou industrial',
        1918 => 'Devolução de mercadoria remetida em consignação mercantil ou industrial',
        1919 => 'Devolução simbólica em consignação mercantil ou industrial',
        1920 => 'Entrada de vasilhame ou sacaria',
        1921 => 'Retorno de vasilhame ou sacaria',
        1922 => 'Lançamento efetuado a título de simples faturamento',
        1923 => 'Entrada de mercadoria recebida do vendedor remetente em venda à ordem',
        1924 => 'Entrada p/ industrialização por conta e ordem do adquirente',
        1925 => 'Retorno de mercadoria remetida p/ industrialização por conta e ordem do adquirente',
        1926 => 'Reclassificação de mercadoria decorrente de formação de kit',
        1931 => 'Lançamento efetuado pelo tomador do serviço de transporte quando a responsabilidade de retenção do imposto for atribuída ao remetente ou alienante',
        1932 => 'Aquisição de serviço de transporte iniciado em UF diversa daquela onde inscrito o prestador',
        1933 => 'Aquisição de serviço tributado pelo ISSQN',
        1934 => 'Entrada simbólica de mercadoria recebida p/ depósito fechado ou armazém geral',
        1949 => 'Outra entrada de mercadoria não especificada',

        // ═══════════════════════════════════════════════════════════
        // ENTRADAS — interestadual (2xxx)
        // ═══════════════════════════════════════════════════════════

        // 2.1xx — Compras
        2101 => 'Compra p/ industrialização',
        2102 => 'Compra p/ comercialização',
        2111 => 'Compra p/ industrialização de mercadoria recebida anteriormente em consignação industrial',
        2113 => 'Compra p/ comercialização de mercadoria recebida anteriormente em consignação mercantil',
        2116 => 'Compra p/ industrialização originada de encomenda p/ recebimento futuro',
        2117 => 'Compra p/ comercialização originada de encomenda p/ recebimento futuro',
        2118 => 'Compra de mercadoria p/ comercialização pelo adquirente originário, entregue pelo vendedor remetente ao destinatário, em venda à ordem',
        2120 => 'Compra p/ industrialização em que a mercadoria foi remetida pelo fornecedor ao industrializador',
        2121 => 'Compra p/ comercialização em que a mercadoria foi remetida pelo fornecedor ao industrializador',
        2122 => 'Compra p/ industrialização de mercadoria não equivalente recebida em troca',
        2124 => 'Industrialização efetuada por outra empresa',
        2125 => 'Industrialização efetuada por outra empresa quando a mercadoria remetida p/ utilização no processo',
        2126 => 'Compra p/ utilização na prestação de serviço',
        2128 => 'Compra p/ utilização na prestação de serviço sujeita ao ICMS',

        // 2.15x — Transferências
        2151 => 'Transferência p/ industrialização',
        2152 => 'Transferência p/ comercialização',
        2153 => 'Transferência de energia elétrica p/ distribuição',
        2154 => 'Transferência p/ utilização na prestação de serviço',

        // 2.2xx — Devoluções de vendas
        2201 => 'Devolução de venda de produção',
        2202 => 'Devolução de venda de mercadoria',
        2203 => 'Devolução de venda de prestação de serviço',
        2204 => 'Devolução de venda de produção efetuada fora do estabelecimento',
        2205 => 'Anulação de valor relativo a prestação de serviço de comunicação',
        2206 => 'Anulação de valor relativo a prestação de serviço de transporte',
        2207 => 'Anulação de valor relativo a venda de energia elétrica',
        2208 => 'Devolução de produção remetida em transferência',
        2209 => 'Devolução de mercadoria remetida em transferência',

        // 2.25x — Compras de energia elétrica
        2251 => 'Compra de energia elétrica p/ distribuição ou comercialização',
        2252 => 'Compra de energia elétrica por estabelecimento industrial',
        2253 => 'Compra de energia elétrica por estabelecimento comercial',
        2254 => 'Compra de energia elétrica por estabelecimento prestador de serviço de transporte',
        2255 => 'Compra de energia elétrica por estabelecimento prestador de serviço de comunicação',
        2256 => 'Compra de energia elétrica por estabelecimento de produtor rural',

        // 2.3xx — Aquisições de serviços de comunicação
        2301 => 'Aquisição de serviço de comunicação p/ execução de serviço da mesma natureza',
        2302 => 'Aquisição de serviço de comunicação por estabelecimento comercial',
        2303 => 'Aquisição de serviço de comunicação por estabelecimento industrial',
        2304 => 'Aquisição de serviço de comunicação por estabelecimento prestador de serviço de transporte',
        2305 => 'Aquisição de serviço de comunicação por estabelecimento gerador ou distribuidor de energia elétrica',
        2306 => 'Aquisição de serviço de comunicação por estabelecimento de produtor rural',

        // 2.35x — Aquisições de serviços de transporte
        2351 => 'Aquisição de serviço de transporte p/ execução de serviço da mesma natureza',
        2352 => 'Aquisição de serviço de transporte por estabelecimento comercial',
        2353 => 'Aquisição de serviço de transporte por estabelecimento industrial',
        2354 => 'Aquisição de serviço de transporte por estabelecimento prestador de serviço de comunicação',
        2355 => 'Aquisição de serviço de transporte por estabelecimento gerador ou distribuidor de energia elétrica',
        2356 => 'Aquisição de serviço de transporte por estabelecimento de produtor rural',

        // 2.4xx — Entradas de mercadorias sujeitas a ST
        2401 => 'Compra p/ industrialização em operação c/ mercadoria sujeita a ST',
        2403 => 'Compra p/ comercialização em operação c/ mercadoria sujeita a ST',
        2406 => 'Compra de bem p/ ativo imobilizado cuja mercadoria está sujeita a ST',
        2407 => 'Compra de mercadoria p/ uso ou consumo cuja mercadoria está sujeita a ST',
        2408 => 'Transferência p/ industrialização em operação c/ mercadoria sujeita a ST',
        2409 => 'Transferência p/ comercialização em operação c/ mercadoria sujeita a ST',
        2410 => 'Devolução de venda de produção em operação c/ mercadoria sujeita a ST',
        2411 => 'Devolução de venda de mercadoria sujeita a ST',
        2414 => 'Retorno de mercadoria remetida p/ venda fora do estabelecimento c/ ST',

        // 2.5xx — Ativo imobilizado e material de uso/consumo
        2501 => 'Entrada de mercadoria recebida com fim específico de exportação',
        2503 => 'Entrada decorrente de devolução de produto remetido c/ fim específico de exportação',
        2504 => 'Entrada decorrente de devolução de mercadoria remetida c/ fim específico de exportação',
        2505 => 'Entrada decorrente de devolução simbólica de mercadoria c/ fim específico de exportação',
        2506 => 'Entrada decorrente de devolução de produção p/ formação de lote de exportação',
        2551 => 'Compra de bem p/ ativo imobilizado',
        2552 => 'Transferência de bem do ativo imobilizado',
        2553 => 'Devolução de venda de bem do ativo imobilizado',
        2554 => 'Retorno de bem do ativo imobilizado remetido p/ uso fora do estabelecimento',
        2555 => 'Entrada de bem do ativo imobilizado de terceiro remetido p/ uso no estabelecimento',
        2556 => 'Compra de material p/ uso ou consumo',
        2557 => 'Transferência de material p/ uso ou consumo',

        // 2.6xx — Créditos e ressarcimentos de ICMS
        2603 => 'Ressarcimento de ICMS retido por substituição tributária',

        // 2.65x — Entradas de combustíveis e lubrificantes
        2651 => 'Compra de combustível ou lubrificante p/ industrialização',
        2652 => 'Compra de combustível ou lubrificante p/ comercialização',
        2653 => 'Compra de combustível/lubrificante por consumidor final',
        2658 => 'Transferência de combustível ou lubrificante p/ industrialização',
        2659 => 'Transferência de combustível ou lubrificante p/ comercialização',
        2660 => 'Devolução de venda de combustível ou lubrificante destinado à industrialização',
        2661 => 'Devolução de venda de combustível ou lubrificante destinado à comercialização',
        2662 => 'Devolução de venda de combustível ou lubrificante destinado a consumidor final',

        // 2.9xx — Outras entradas
        2901 => 'Entrada p/ industrialização por encomenda',
        2902 => 'Retorno de mercadoria remetida p/ industrialização por encomenda',
        2903 => 'Retorno de mercadoria remetida p/ venda fora do estabelecimento',
        2904 => 'Retorno de remessa p/ depósito fechado ou armazém geral',
        2905 => 'Entrada de mercadoria recebida p/ depósito em depósito fechado ou armazém geral',
        2906 => 'Retorno de mercadoria remetida p/ depósito fechado ou armazém geral',
        2907 => 'Retorno simbólico de mercadoria depositada em depósito fechado ou armazém geral',
        2908 => 'Entrada de bem por conta de contrato de comodato',
        2909 => 'Retorno de bem remetido por conta de contrato de comodato',
        2910 => 'Entrada de bonificação, doação ou brinde',
        2911 => 'Entrada de amostra grátis',
        2912 => 'Entrada de mercadoria ou bem recebido para demonstração',
        2913 => 'Retorno de mercadoria ou bem remetido para demonstração',
        2914 => 'Retorno de mercadoria ou bem remetido para exposição ou feira',
        2915 => 'Entrada de mercadoria ou bem recebido para conserto ou reparo',
        2916 => 'Retorno de mercadoria ou bem remetido para conserto ou reparo',
        2917 => 'Entrada de mercadoria recebida em consignação mercantil ou industrial',
        2918 => 'Devolução de mercadoria remetida em consignação mercantil ou industrial',
        2919 => 'Devolução simbólica em consignação mercantil ou industrial',
        2920 => 'Entrada de vasilhame ou sacaria',
        2921 => 'Retorno de vasilhame ou sacaria',
        2922 => 'Lançamento efetuado a título de simples faturamento',
        2923 => 'Entrada de mercadoria recebida do vendedor remetente em venda à ordem',
        2924 => 'Entrada p/ industrialização por conta e ordem do adquirente',
        2925 => 'Retorno de mercadoria remetida p/ industrialização por conta e ordem do adquirente',
        2931 => 'Lançamento efetuado pelo tomador do serviço de transporte quando a responsabilidade de retenção do imposto for atribuída ao remetente ou alienante',
        2932 => 'Aquisição de serviço de transporte iniciado em UF diversa daquela onde inscrito o prestador',
        2933 => 'Aquisição de serviço tributado pelo ISSQN',
        2934 => 'Entrada simbólica de mercadoria recebida p/ depósito fechado ou armazém geral',
        2949 => 'Outra entrada de mercadoria não especificada',

        // ═══════════════════════════════════════════════════════════
        // ENTRADAS — exterior (3xxx)
        // ═══════════════════════════════════════════════════════════

        3101 => 'Compra p/ industrialização',
        3102 => 'Compra p/ comercialização',
        3126 => 'Compra p/ utilização na prestação de serviço',
        3127 => 'Compra p/ industrialização sob o regime de drawback',
        3128 => 'Compra p/ utilização na prestação de serviço sujeita ao ICMS',
        3201 => 'Devolução de venda de produção',
        3202 => 'Devolução de venda de mercadoria',
        3205 => 'Anulação de valor relativo a prestação de serviço de comunicação',
        3206 => 'Anulação de valor relativo a prestação de serviço de transporte',
        3207 => 'Anulação de valor relativo a venda de energia elétrica',
        3211 => 'Devolução de venda de produção sob o regime de drawback',
        3251 => 'Compra de energia elétrica p/ distribuição ou comercialização',
        3301 => 'Aquisição de serviço de comunicação p/ execução de serviço da mesma natureza',
        3351 => 'Aquisição de serviço de transporte p/ execução de serviço da mesma natureza',
        3352 => 'Aquisição de serviço de transporte por estabelecimento comercial',
        3353 => 'Aquisição de serviço de transporte por estabelecimento industrial',
        3354 => 'Aquisição de serviço de transporte por estabelecimento prestador de serviço de comunicação',
        3355 => 'Aquisição de serviço de transporte por estabelecimento gerador ou distribuidor de energia elétrica',
        3356 => 'Aquisição de serviço de transporte por estabelecimento de produtor rural',
        3503 => 'Devolução de mercadoria exportada que tenha sido recebida c/ fim específico de exportação',
        3551 => 'Compra de bem p/ ativo imobilizado',
        3553 => 'Devolução de venda de bem do ativo imobilizado',
        3556 => 'Compra de material p/ uso ou consumo',
        3651 => 'Compra de combustível ou lubrificante p/ industrialização',
        3652 => 'Compra de combustível ou lubrificante p/ comercialização',
        3653 => 'Compra de combustível/lubrificante por consumidor final',
        3930 => 'Lançamento efetuado a título de entrada de bem sob amparo de regime especial aduaneiro de admissão temporária',
        3949 => 'Outra entrada de mercadoria não especificada',

        // ═══════════════════════════════════════════════════════════
        // SAÍDAS — mesmo estado (5xxx)
        // ═══════════════════════════════════════════════════════════

        // 5.1xx — Vendas de produção própria ou de terceiros
        5101 => 'Venda de produção',
        5102 => 'Venda de mercadoria adquirida',
        5103 => 'Venda de produção efetuada fora do estabelecimento',
        5104 => 'Venda de mercadoria adquirida efetuada fora do estabelecimento',
        5105 => 'Venda de produção efetuada fora do estabelecimento, destinada a não contribuinte',
        5106 => 'Venda de mercadoria adquirida efetuada fora do estabelecimento, destinada a não contribuinte',
        5109 => 'Venda de produção destinada à Zona Franca de Manaus ou Áreas de Livre Comércio',
        5110 => 'Venda de mercadoria adquirida destinada à Zona Franca de Manaus ou Áreas de Livre Comércio',
        5111 => 'Venda de produção de industrialização p/ ordem',
        5112 => 'Venda de mercadoria adquirida de industrialização p/ ordem',
        5113 => 'Venda de produção efetuada fora do estabelecimento em operação c/ produto sujeito a ST',
        5114 => 'Venda de mercadoria adquirida efetuada fora do estabelecimento em operação c/ produto sujeito a ST',
        5115 => 'Venda de mercadoria adquirida recebida anteriormente em consignação mercantil',
        5116 => 'Venda de produção originada de encomenda p/ entrega futura',
        5117 => 'Venda de mercadoria adquirida originada de encomenda p/ entrega futura',
        5118 => 'Venda de produção entregue ao destinatário por conta e ordem do adquirente originário, em venda à ordem',
        5119 => 'Venda de mercadoria adquirida entregue ao destinatário por conta e ordem do adquirente originário, em venda à ordem',
        5120 => 'Venda de mercadoria adquirida do Simples Nacional',
        5122 => 'Venda de produção remetida p/ industrialização por conta e ordem do adquirente sem transitar pelo estabelecimento',
        5123 => 'Venda de mercadoria adquirida remetida p/ industrialização por conta e ordem do adquirente sem transitar pelo estabelecimento',
        5124 => 'Industrialização efetuada p/ outra empresa',
        5125 => 'Industrialização efetuada p/ outra empresa quando a mercadoria remetida p/ utilização no processo',

        // 5.15x — Transferências de produção própria ou de terceiros
        5151 => 'Transferência de produção',
        5152 => 'Transferência de mercadoria adquirida',
        5153 => 'Transferência de energia elétrica',
        5155 => 'Transferência de produção c/ fim específico de exportação',
        5156 => 'Transferência de mercadoria adquirida c/ fim específico de exportação',

        // 5.2xx — Devoluções de compras
        5201 => 'Devolução de compra p/ industrialização',
        5202 => 'Devolução de compra p/ comercialização',
        5205 => 'Anulação de valor relativo a aquisição de serviço de comunicação',
        5206 => 'Anulação de valor relativo a aquisição de serviço de transporte',
        5207 => 'Anulação de valor relativo a compra de energia elétrica',
        5208 => 'Devolução de mercadoria recebida em transferência p/ industrialização',
        5209 => 'Devolução de mercadoria recebida em transferência p/ comercialização',
        5210 => 'Devolução de compra p/ utilização na prestação de serviço',
        5251 => 'Venda de energia elétrica p/ distribuição ou comercialização',
        5252 => 'Venda de energia elétrica p/ estabelecimento industrial',
        5253 => 'Venda de energia elétrica p/ estabelecimento comercial',
        5254 => 'Venda de energia elétrica p/ estabelecimento prestador de serviço de transporte',
        5255 => 'Venda de energia elétrica p/ estabelecimento prestador de serviço de comunicação',
        5256 => 'Venda de energia elétrica p/ estabelecimento de produtor rural',
        5257 => 'Venda de energia elétrica p/ consumo por demanda contratada',
        5258 => 'Venda de energia elétrica a não contribuinte',

        // 5.3xx — Prestações de serviços de comunicação
        5301 => 'Prestação de serviço de comunicação p/ execução de serviço da mesma natureza',
        5302 => 'Prestação de serviço de comunicação a estabelecimento comercial',
        5303 => 'Prestação de serviço de comunicação a estabelecimento industrial',
        5304 => 'Prestação de serviço de comunicação a estabelecimento de prestador de serviço de transporte',
        5305 => 'Prestação de serviço de comunicação a estabelecimento gerador ou distribuidor de energia elétrica',
        5306 => 'Prestação de serviço de comunicação a estabelecimento de produtor rural',
        5307 => 'Prestação de serviço de comunicação a não contribuinte',

        // 5.35x — Prestações de serviços de transporte
        5351 => 'Prestação de serviço de transporte p/ execução de serviço da mesma natureza',
        5352 => 'Prestação de serviço de transporte a estabelecimento comercial',
        5353 => 'Prestação de serviço de transporte a estabelecimento industrial',
        5354 => 'Prestação de serviço de transporte a estabelecimento de prestador de serviço de comunicação',
        5355 => 'Prestação de serviço de transporte a estabelecimento gerador ou distribuidor de energia elétrica',
        5356 => 'Prestação de serviço de transporte a estabelecimento de produtor rural',
        5357 => 'Prestação de serviço de transporte a não contribuinte',
        5359 => 'Prestação de serviço de transporte a contribuinte ou a não contribuinte quando a mercadoria transportada está dispensada de emissão de nota fiscal',
        5360 => 'Prestação de serviço de transporte a contribuinte substituto em relação ao serviço de transporte',

        // 5.4xx — Saídas de mercadorias sujeitas a ST
        5401 => 'Venda de produção em operação c/ mercadoria sujeita a ST',
        5402 => 'Venda de produção em operação c/ produto sujeito a ST, destinada a contribuinte substituto',
        5403 => 'Venda de mercadoria sujeita a ST',
        5405 => 'Venda de mercadoria adquirida c/ ST',
        5408 => 'Transferência de produção em operação c/ mercadoria sujeita a ST',
        5409 => 'Transferência de mercadoria adquirida em operação c/ mercadoria sujeita a ST',
        5410 => 'Devolução de compra p/ industrialização em operação c/ mercadoria sujeita a ST',
        5411 => 'Devolução de compra p/ comercialização c/ ST',
        5412 => 'Devolução de bem do ativo imobilizado em operação c/ mercadoria sujeita a ST',
        5413 => 'Devolução de mercadoria destinada ao uso ou consumo em operação c/ mercadoria sujeita a ST',
        5414 => 'Devolução de compra p/ industrialização c/ ST',
        5415 => 'Remessa de mercadoria adquirida c/ ST p/ venda fora do estabelecimento',

        // 5.5xx — Ativo imobilizado e material de uso/consumo
        5501 => 'Remessa de produção p/ exportação',
        5502 => 'Remessa de mercadoria adquirida p/ exportação',
        5503 => 'Devolução de mercadoria recebida c/ fim específico de exportação',
        5504 => 'Remessa de mercadoria p/ formação de lote de exportação',
        5505 => 'Remessa de mercadoria p/ formação de lote p/ posterior exportação',
        5551 => 'Venda de bem do ativo imobilizado',
        5552 => 'Transferência de bem do ativo imobilizado',
        5553 => 'Devolução de compra de bem p/ ativo imobilizado',
        5554 => 'Remessa de bem do ativo imobilizado p/ uso fora do estabelecimento',
        5555 => 'Devolução de bem do ativo imobilizado de terceiro recebido p/ uso no estabelecimento',
        5556 => 'Devolução de compra de material de uso ou consumo',
        5557 => 'Transferência de material p/ uso ou consumo',

        // 5.6xx — Transferências de créditos de ICMS
        5601 => 'Transferência de crédito de ICMS acumulado',
        5602 => 'Transferência de saldo devedor de ICMS de outro estabelecimento',
        5603 => 'Ressarcimento de ICMS retido por substituição tributária',
        5605 => 'Transferência de saldo devedor do ICMS de outro estabelecimento',

        // 5.65x — Saídas de combustíveis e lubrificantes
        5651 => 'Venda de combustível ou lubrificante de produção p/ industrialização',
        5652 => 'Venda de combustível ou lubrificante de produção p/ comercialização',
        5653 => 'Venda de combustível ou lubrificante de produção p/ consumidor final',
        5654 => 'Venda de combustível ou lubrificante adquirido p/ industrialização',
        5655 => 'Venda de combustível ou lubrificante adquirido p/ comercialização',
        5656 => 'Venda de combustível ou lubrificante adquirido p/ consumidor final',
        5657 => 'Remessa de combustível ou lubrificante adquirido p/ venda fora do estabelecimento',
        5658 => 'Transferência de combustível ou lubrificante de produção',
        5659 => 'Transferência de combustível ou lubrificante adquirido',
        5660 => 'Devolução de compra de combustível ou lubrificante adquirido p/ industrialização',
        5661 => 'Devolução de compra de combustível ou lubrificante adquirido p/ comercialização',
        5662 => 'Devolução de compra de combustível ou lubrificante adquirido p/ consumidor final',
        5663 => 'Remessa p/ armazenagem de combustível',
        5664 => 'Retorno de combustível recebido p/ armazenagem',
        5665 => 'Retorno simbólico de combustível recebido p/ armazenagem',
        5666 => 'Remessa p/ armazenagem de combustível por conta e ordem de terceiros',
        5667 => 'Venda de combustível a consumidor final, entregue por transportador revendedor retalhista (TRR)',

        // 5.9xx — Outras saídas
        5901 => 'Remessa p/ industrialização por encomenda',
        5902 => 'Retorno de mercadoria utilizada na industrialização por encomenda',
        5903 => 'Remessa p/ venda fora do estabelecimento',
        5904 => 'Remessa p/ depósito fechado ou armazém geral',
        5905 => 'Remessa p/ depósito de produto sujeito a ST',
        5906 => 'Retorno de mercadoria depositada em depósito fechado ou armazém geral',
        5907 => 'Retorno simbólico de mercadoria depositada em depósito fechado ou armazém geral',
        5908 => 'Remessa de bem por conta de contrato de comodato',
        5909 => 'Retorno de bem recebido por conta de contrato de comodato',
        5910 => 'Remessa de bonificação, doação ou brinde',
        5911 => 'Remessa de amostra grátis',
        5912 => 'Remessa de mercadoria ou bem para demonstração',
        5913 => 'Retorno de mercadoria ou bem recebido para demonstração',
        5914 => 'Remessa de mercadoria ou bem para exposição ou feira',
        5915 => 'Remessa de mercadoria ou bem para conserto ou reparo',
        5916 => 'Retorno de mercadoria ou bem recebido para conserto ou reparo',
        5917 => 'Remessa de mercadoria em consignação mercantil ou industrial',
        5918 => 'Devolução de mercadoria recebida em consignação mercantil ou industrial',
        5919 => 'Devolução simbólica em consignação mercantil ou industrial',
        5920 => 'Remessa de vasilhame ou sacaria',
        5921 => 'Devolução de vasilhame ou sacaria',
        5922 => 'Lançamento efetuado a título de simples faturamento',
        5923 => 'Remessa de mercadoria por conta e ordem de terceiros em venda à ordem',
        5924 => 'Remessa p/ industrialização por conta e ordem do adquirente',
        5925 => 'Retorno de mercadoria recebida p/ industrialização por conta e ordem do adquirente',
        5926 => 'Reclassificação de mercadoria decorrente de formação de kit',
        5927 => 'Lançamento efetuado a título de baixa de estoque decorrente de perda, roubo ou deterioração',
        5928 => 'Lançamento efetuado a título de baixa de estoque decorrente do encerramento da atividade da empresa',
        5929 => 'Lançamento efetuado em decorrência de emissão de documento fiscal também registrada em ECF',
        5931 => 'Lançamento efetuado em decorrência de responsabilidade de retenção do imposto por substituição tributária, atribuída ao remetente ou alienante',
        5932 => 'Prestação de serviço de transporte iniciada em UF diversa daquela onde inscrito o prestador',
        5933 => 'Prestação de serviço tributado pelo ISSQN',
        5934 => 'Remessa simbólica de mercadoria depositada em armazém geral ou depósito fechado',
        5949 => 'Outra saída de mercadoria não especificada',

        // ═══════════════════════════════════════════════════════════
        // SAÍDAS — interestadual (6xxx)
        // ═══════════════════════════════════════════════════════════

        // 6.1xx — Vendas
        6101 => 'Venda de produção',
        6102 => 'Venda de mercadoria adquirida',
        6103 => 'Venda de produção efetuada fora do estabelecimento',
        6104 => 'Venda de mercadoria adquirida efetuada fora do estabelecimento',
        6105 => 'Venda de produção destinada a não contribuinte',
        6106 => 'Venda de mercadoria adquirida destinada a não contribuinte',
        6107 => 'Venda de produção destinada a não contribuinte',
        6108 => 'Venda de mercadoria adquirida de contribuinte substituto',
        6109 => 'Venda de produção destinada à Zona Franca de Manaus ou Áreas de Livre Comércio',
        6110 => 'Venda de mercadoria adquirida destinada à Zona Franca de Manaus ou Áreas de Livre Comércio',
        6111 => 'Venda de produção remetida anteriormente em consignação industrial',
        6112 => 'Venda de mercadoria adquirida remetida anteriormente em consignação mercantil',
        6113 => 'Venda de produção efetuada fora do estabelecimento em operação c/ produto sujeito a ST',
        6114 => 'Venda de mercadoria adquirida efetuada fora do estabelecimento em operação c/ produto sujeito a ST',
        6115 => 'Venda de mercadoria adquirida recebida anteriormente em consignação mercantil',
        6116 => 'Venda de produção originada de encomenda p/ entrega futura',
        6117 => 'Venda de mercadoria adquirida originada de encomenda p/ entrega futura',
        6118 => 'Venda de produção entregue ao destinatário por conta e ordem do adquirente originário, em venda à ordem',
        6119 => 'Venda de mercadoria adquirida entregue ao destinatário por conta e ordem do adquirente originário, em venda à ordem',
        6120 => 'Venda de mercadoria adquirida do Simples Nacional',
        6122 => 'Venda de produção remetida p/ industrialização por conta e ordem do adquirente sem transitar pelo estabelecimento',
        6123 => 'Venda de mercadoria adquirida remetida p/ industrialização por conta e ordem do adquirente sem transitar pelo estabelecimento',
        6124 => 'Industrialização efetuada p/ outra empresa',
        6125 => 'Industrialização efetuada p/ outra empresa quando a mercadoria remetida p/ utilização no processo',

        // 6.15x — Transferências
        6151 => 'Transferência de produção',
        6152 => 'Transferência de mercadoria adquirida',
        6153 => 'Transferência de energia elétrica',
        6155 => 'Transferência de produção c/ fim específico de exportação',
        6156 => 'Transferência de mercadoria adquirida c/ fim específico de exportação',

        // 6.2xx — Devoluções de compras
        6201 => 'Devolução de compra p/ industrialização',
        6202 => 'Devolução de compra p/ comercialização',
        6205 => 'Anulação de valor relativo a aquisição de serviço de comunicação',
        6206 => 'Anulação de valor relativo a aquisição de serviço de transporte',
        6207 => 'Anulação de valor relativo a compra de energia elétrica',
        6208 => 'Devolução de mercadoria recebida em transferência p/ industrialização',
        6209 => 'Devolução de mercadoria recebida em transferência p/ comercialização',
        6210 => 'Devolução de compra p/ utilização na prestação de serviço',
        6251 => 'Venda de energia elétrica p/ distribuição ou comercialização',
        6252 => 'Venda de energia elétrica p/ estabelecimento industrial',
        6253 => 'Venda de energia elétrica p/ estabelecimento comercial',
        6254 => 'Venda de energia elétrica p/ estabelecimento prestador de serviço de transporte',
        6255 => 'Venda de energia elétrica p/ estabelecimento prestador de serviço de comunicação',
        6256 => 'Venda de energia elétrica p/ estabelecimento de produtor rural',
        6257 => 'Venda de energia elétrica p/ consumo por demanda contratada',
        6258 => 'Venda de energia elétrica a não contribuinte',

        // 6.3xx — Prestações de serviços de comunicação
        6301 => 'Prestação de serviço de comunicação p/ execução de serviço da mesma natureza',
        6302 => 'Prestação de serviço de comunicação a estabelecimento comercial',
        6303 => 'Prestação de serviço de comunicação a estabelecimento industrial',
        6304 => 'Prestação de serviço de comunicação a estabelecimento de prestador de serviço de transporte',
        6305 => 'Prestação de serviço de comunicação a estabelecimento gerador ou distribuidor de energia elétrica',
        6306 => 'Prestação de serviço de comunicação a estabelecimento de produtor rural',
        6307 => 'Prestação de serviço de comunicação a não contribuinte',

        // 6.35x — Prestações de serviços de transporte
        6351 => 'Prestação de serviço de transporte p/ execução de serviço da mesma natureza',
        6352 => 'Prestação de serviço de transporte a estabelecimento comercial',
        6353 => 'Prestação de serviço de transporte a estabelecimento industrial',
        6354 => 'Prestação de serviço de transporte a estabelecimento de prestador de serviço de comunicação',
        6355 => 'Prestação de serviço de transporte a estabelecimento gerador ou distribuidor de energia elétrica',
        6356 => 'Prestação de serviço de transporte a estabelecimento de produtor rural',
        6357 => 'Prestação de serviço de transporte a não contribuinte',
        6359 => 'Prestação de serviço de transporte a contribuinte ou a não contribuinte quando a mercadoria transportada está dispensada de emissão de nota fiscal',
        6360 => 'Prestação de serviço de transporte a contribuinte substituto em relação ao serviço de transporte',

        // 6.4xx — Saídas de mercadorias sujeitas a ST
        6401 => 'Venda de produção c/ ST em operação interestadual',
        6402 => 'Venda de produção c/ ST em operação interestadual, destinada a contribuinte substituto',
        6403 => 'Venda de mercadoria sujeita a ST em operação interestadual',
        6404 => 'Venda de mercadoria sujeita a ST, cujo imposto já foi retido anteriormente, em operação interestadual',
        6408 => 'Transferência de produção em operação c/ mercadoria sujeita a ST',
        6409 => 'Transferência de mercadoria adquirida em operação c/ mercadoria sujeita a ST',
        6410 => 'Devolução de compra p/ industrialização em operação c/ mercadoria sujeita a ST',
        6411 => 'Devolução de compra p/ comercialização em operação c/ mercadoria sujeita a ST',
        6412 => 'Devolução de bem do ativo imobilizado em operação c/ mercadoria sujeita a ST',
        6413 => 'Devolução de mercadoria destinada ao uso ou consumo em operação c/ mercadoria sujeita a ST',
        6414 => 'Devolução de compra p/ industrialização c/ ST',
        6415 => 'Remessa de mercadoria adquirida c/ ST p/ venda fora do estabelecimento',

        // 6.5xx — Ativo imobilizado e material de uso/consumo
        6501 => 'Remessa de produção c/ fim específico de exportação',
        6502 => 'Remessa de mercadoria adquirida c/ fim específico de exportação',
        6503 => 'Devolução de mercadoria recebida c/ fim específico de exportação',
        6504 => 'Remessa de mercadoria p/ formação de lote de exportação',
        6505 => 'Remessa de mercadoria p/ formação de lote p/ posterior exportação',
        6551 => 'Venda de bem do ativo imobilizado',
        6552 => 'Transferência de bem do ativo imobilizado',
        6553 => 'Devolução de compra de bem p/ ativo imobilizado',
        6554 => 'Remessa de bem do ativo imobilizado p/ uso fora do estabelecimento',
        6555 => 'Devolução de bem do ativo imobilizado de terceiro recebido p/ uso no estabelecimento',
        6556 => 'Devolução de compra de material de uso ou consumo',
        6557 => 'Transferência de material p/ uso ou consumo',

        // 6.6xx — Transferências de créditos de ICMS
        6603 => 'Ressarcimento de ICMS retido por substituição tributária',

        // 6.65x — Saídas de combustíveis e lubrificantes
        6651 => 'Venda de combustível ou lubrificante de produção p/ industrialização',
        6652 => 'Venda de combustível ou lubrificante de produção p/ comercialização',
        6653 => 'Venda de combustível ou lubrificante de produção p/ consumidor final',
        6654 => 'Venda de combustível ou lubrificante adquirido p/ industrialização',
        6655 => 'Venda de combustível ou lubrificante adquirido p/ comercialização',
        6656 => 'Venda de combustível ou lubrificante adquirido p/ consumidor final',
        6657 => 'Remessa de combustível ou lubrificante adquirido p/ venda fora do estabelecimento',
        6658 => 'Transferência de combustível ou lubrificante de produção',
        6659 => 'Transferência de combustível ou lubrificante adquirido',
        6660 => 'Devolução de compra de combustível ou lubrificante adquirido p/ industrialização',
        6661 => 'Devolução de compra de combustível ou lubrificante adquirido p/ comercialização',
        6662 => 'Devolução de compra de combustível ou lubrificante adquirido p/ consumidor final',
        6663 => 'Remessa p/ armazenagem de combustível',
        6664 => 'Retorno de combustível recebido p/ armazenagem',
        6665 => 'Retorno simbólico de combustível recebido p/ armazenagem',
        6666 => 'Remessa p/ armazenagem de combustível por conta e ordem de terceiros',
        6667 => 'Venda de combustível a consumidor final, entregue por transportador revendedor retalhista (TRR)',

        // 6.9xx — Outras saídas
        6901 => 'Remessa p/ industrialização por encomenda',
        6902 => 'Retorno de mercadoria utilizada na industrialização por encomenda',
        6903 => 'Remessa p/ venda fora do estabelecimento',
        6904 => 'Remessa p/ depósito fechado ou armazém geral',
        6905 => 'Remessa p/ depósito de produto sujeito a ST',
        6906 => 'Retorno de mercadoria depositada em depósito fechado ou armazém geral',
        6907 => 'Retorno simbólico de mercadoria depositada em depósito fechado ou armazém geral',
        6908 => 'Remessa de bem por conta de contrato de comodato',
        6909 => 'Retorno de bem recebido por conta de contrato de comodato',
        6910 => 'Remessa de bonificação, doação ou brinde',
        6911 => 'Remessa de amostra grátis',
        6912 => 'Remessa de mercadoria ou bem para demonstração',
        6913 => 'Retorno de mercadoria ou bem recebido para demonstração',
        6914 => 'Remessa de mercadoria ou bem para exposição ou feira',
        6915 => 'Remessa de mercadoria ou bem para conserto ou reparo',
        6916 => 'Retorno de mercadoria ou bem recebido para conserto ou reparo',
        6917 => 'Remessa de mercadoria em consignação mercantil ou industrial',
        6918 => 'Devolução de mercadoria recebida em consignação mercantil ou industrial',
        6919 => 'Devolução simbólica em consignação mercantil ou industrial',
        6920 => 'Remessa de vasilhame ou sacaria',
        6921 => 'Devolução de vasilhame ou sacaria',
        6922 => 'Lançamento efetuado a título de simples faturamento',
        6923 => 'Remessa de mercadoria por conta e ordem de terceiros em venda à ordem',
        6924 => 'Remessa p/ industrialização por conta e ordem do adquirente',
        6925 => 'Retorno de mercadoria recebida p/ industrialização por conta e ordem do adquirente',
        6929 => 'Lançamento efetuado em decorrência de emissão de documento fiscal também registrada em ECF',
        6931 => 'Lançamento efetuado em decorrência de responsabilidade de retenção do imposto por substituição tributária, atribuída ao remetente ou alienante',
        6932 => 'Prestação de serviço de transporte iniciada em UF diversa daquela onde inscrito o prestador',
        6933 => 'Prestação de serviço tributado pelo ISSQN',
        6934 => 'Remessa simbólica de mercadoria depositada em armazém geral ou depósito fechado',
        6949 => 'Outra saída de mercadoria não especificada',

        // ═══════════════════════════════════════════════════════════
        // SAÍDAS — exterior (7xxx)
        // ═══════════════════════════════════════════════════════════

        7101 => 'Venda de produção',
        7102 => 'Venda de mercadoria adquirida',
        7105 => 'Venda de produção destinada à Zona Franca de Manaus ou Áreas de Livre Comércio',
        7106 => 'Venda de mercadoria adquirida destinada à Zona Franca de Manaus ou Áreas de Livre Comércio',
        7127 => 'Venda de produção sob o regime de drawback',
        7201 => 'Devolução de compra p/ industrialização',
        7202 => 'Devolução de compra p/ comercialização',
        7205 => 'Anulação de valor relativo a aquisição de serviço de comunicação',
        7206 => 'Anulação de valor relativo a aquisição de serviço de transporte',
        7207 => 'Anulação de valor relativo a compra de energia elétrica',
        7210 => 'Devolução de compra p/ utilização na prestação de serviço',
        7211 => 'Devolução de compra p/ industrialização sob o regime de drawback',
        7251 => 'Venda de energia elétrica p/ o exterior',
        7301 => 'Prestação de serviço de comunicação p/ o exterior',
        7358 => 'Prestação de serviço de transporte p/ o exterior',
        7501 => 'Exportação de mercadoria recebida c/ fim específico de exportação',
        7504 => 'Exportação de mercadoria que foi objeto de formação de lote de exportação',
        7551 => 'Venda de bem do ativo imobilizado',
        7553 => 'Devolução de compra de bem p/ ativo imobilizado',
        7556 => 'Devolução de compra de material de uso ou consumo',
        7651 => 'Venda de combustível ou lubrificante de produção p/ o exterior',
        7654 => 'Venda de combustível ou lubrificante adquirido p/ o exterior',
        7667 => 'Venda de combustível a consumidor final no exterior',
        7930 => 'Lançamento efetuado a título de devolução de bem sob amparo de regime especial aduaneiro de admissão temporária',
        7949 => 'Outra saída de mercadoria não especificada',
    ];

    private static array $devolucaoRanges = [
        [1200, 1209], [1410, 1414],
        [2200, 2209], [2410, 2414],
        [3200, 3211],
        [5200, 5210], [5410, 5415],
        [6200, 6210], [6410, 6415],
        [7200, 7211],
    ];

    private static array $transferenciaRanges = [
        [1151, 1154], [1552, 1557],
        [2151, 2154], [2552, 2557],
        [5151, 5156], [5552, 5557],
        [6151, 6156], [6552, 6557],
    ];

    public static function descricao(string|int $cfop): string
    {
        return self::$descricoes[(int) $cfop] ?? 'CFOP não catalogado';
    }

    public static function tipo(string|int $cfop): string
    {
        $primeiro = (int) substr((string) (int) $cfop, 0, 1);

        return in_array($primeiro, [1, 2, 3]) ? 'entrada' : 'saida';
    }

    public static function isDevolucao(string|int $cfop): bool
    {
        return self::inRanges((int) $cfop, self::$devolucaoRanges);
    }

    public static function isTransferencia(string|int $cfop): bool
    {
        return self::inRanges((int) $cfop, self::$transferenciaRanges);
    }

    public static function natureza(string|int $cfop): string
    {
        if (self::isDevolucao($cfop)) {
            return 'devolucao';
        }
        if (self::isTransferencia($cfop)) {
            return 'transferencia';
        }

        return self::tipo($cfop);
    }

    private static function inRanges(int $cfop, array $ranges): bool
    {
        foreach ($ranges as [$min, $max]) {
            if ($cfop >= $min && $cfop <= $max) {
                return true;
            }
        }

        return false;
    }
}
