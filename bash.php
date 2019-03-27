<?php

$woocommerceOptions = [
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

$command = 'wp option update woocommerce_pagarme-credit-card_settings \''. json_encode($woocommerceOptions) . '\' --format=json --allow-root';
echo shell_exec($command);