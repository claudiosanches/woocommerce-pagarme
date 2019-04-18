<?php

/**
 * Update Woocommerce Pagar.me Options
 *
 * @param string $optionName Option name
 * @param array $options Values to update
 *
 * @return string
 */
function setup($optionName, $options)
{
    $command = sprintf(
        "wp option update %s '%s' --format=json --allow-root",
        $optionName,
        json_encode($options)
    );

    return shell_exec($command);
}

/**
 * Creates and retrieve data from company temporary route
 *
 * @return stdClass
 */
function getCompanyTemporary()
{
    $ch = curl_init();
    curl_setopt(
        $ch,
        CURLOPT_URL,
        "https://api.pagar.me/1/companies/temporary"
    );
    date_default_timezone_set('America/Sao_Paulo');
    $params = sprintf(
        'name=acceptance_test_company&email=%s@woocoomercesuite.com&password=password',
        date('YmdHis')
    );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        $params
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $companyData = json_decode($result);
    curl_close($ch);

    return $companyData;
}
$apiKey = getenv('API_KEY');
$encryptionKey = getenv('ENCRYPTION_KEY');

if (!$apiKey && !$encryptionKey) {
    $companyData = getCompanyTemporary();
    $apiKey = $companyData->api_key->test;
    $encryptionKey = $companyData->encryption_key->test;
}

/**
 * Get customs options
 *
 * @param array $arguments Array of arguments passed to script
 * @param string $name Name argument
 *
 * @return array
 */
function getCustomOptions($arguments, $name)
{
    $options = [];
    $index = array_search($name, $arguments);

    if ($index !== false && isset($arguments[$index++])) {
        parse_str($arguments[$index], $options);
    }

    return $options;
}

define('PAGARME_API_KEY', $apiKey);
define('PAGARME_ENCRYPTION_KEY', $encryptionKey);

define(
    'WCP_CREDITCARD_OPTION_NAME',
    'woocommerce_pagarme-credit-card_settings'
);

define(
    'WCP_BOLETO_OPTION_NAME',
    'woocommerce_pagarme-banking-ticket_settings'
);

$baseOptions = [
    'enabled' => 'yes',
    'api_key' => PAGARME_API_KEY,
    'encryption_key' => PAGARME_ENCRYPTION_KEY,
    'testing' => 'yes',
    'debug' => 'yes'
];

$optionsCustoms = getCustomOptions($argv, '--credit');
$woocommerceCreditCardOptions = array_merge(
    $baseOptions,
    [
        'title' => 'Cartão de crédito',
        'description' => 'Cartão de crédito Pagar.me',
        'integration' => '',
        'checkout' => 'no',
        'max_installment' => '12',
        'smallest_installment' => '1',
        'interest_rate' => '5',
        'free_installments' => '2',
    ],
    $optionsCustoms
);

$optionsCustoms = getCustomOptions($argv, '--boleto');
$woocommerceBoletoOptions = array_merge(
    $baseOptions,
    [
        'title' => 'Boleto bancário',
        'description' => 'Pagar com boleto bancário',
        'async' => 'no',
    ],
    $optionsCustoms
);

echo setup(WCP_CREDITCARD_OPTION_NAME, $woocommerceCreditCardOptions);
echo setup(WCP_BOLETO_OPTION_NAME, $woocommerceBoletoOptions);
