<?php

define(
    'WCP_CREDITCARD_OPTION_NAME',
    'woocommerce_pagarme-credit-card_settings'
);

define(
    'WCP_BOLETO_OPTION_NAME',
    'woocommerce_pagarme-banking-ticket_settings'
);

/**
 * Update Woocommerce Pagar.me Options
 *
 * @param string $optionName
 * @param array $options
 */
function setup($optionName, $options) {
    $command = sprintf(
        "wp option update %s '%s' --format=json --allow-root",
        $optionName,
        json_encode($options)
    );
    return shell_exec($command);
}

$woocommerceCreditCardOptions = [
    'enabled' => 'yes',
    'title' => 'Cartão de crédito',
    'description' => 'Cartão de crédito Pagar.me',
    'integration' => '',
    'api_key' => getenv('API_KEY'),
    'encryption_key' => getenv('ENCRYPTION_KEY'),
    'checkout' => 'yes',
    'max_installment' => '12',
    'smallest_installment' => '1',
    'interest_rate' => '1',
    'free_installments' => '0',
    'testing' => 'yes',
    'debug' => 'yes'
];

$woocommerceBoletoOptions = [
    'enabled' => 'Boleto bancário',
    'description' => 'Pagar com boleto bancário',
    'integration' => '',
    'api_key' => getenv('API_KEY'),
    'encryption_key' => getenv('ENCRYPTION_KEY'),
    'async' => 'no',
    'testing' => '',
    'debug' => 'yes'
];

echo setup(WCP_CREDITCARD_OPTION_NAME, $woocommerceCreditCardOptions);
echo setup(WCP_BOLETO_OPTION_NAME, $woocommerceBoletoOptions);