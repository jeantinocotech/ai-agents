<?php

/**
 * Orientação operacional sobre pagamentos (PCI / Asaas).
 *
 * Pagamentos continuam criados pela API atual; recomendação de segurança:
 * migrar entrada de dados de cartão para fluxo tokenizado/hosted do Asaas
 * quando possível, para não trafegar PAN/CVV pela aplicação.
 */
return [
    'asaas_prefers_hosted_card_entry' => env('PAYMENTS_ASAAS_PREFERS_HOSTED_CARD', true),
];
