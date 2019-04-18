<?php
/**
 * Pagar.me API Auxiliary file
 *
 * @package bin/bash
 */

/**
 * Update Woocommerce Pagar.me Options
 *
 * @param string $option_name Option name.
 * @param array  $options     Values to update.
 *
 * @return string
 */
function setup( $option_name, $options ) {
	$command = sprintf(
		"wp option update %s '%s' --format=json --allow-root",
		$option_name,
		json_encode( $options )
	);

	return shell_exec( $command );
}

/**
 * Creates and retrieve data from company temporary route
 *
 * @return stdClass
 * @throws Exception Emits Exception in case of an error in Datetime.
 */
function get_company_temporary() {
	$ch = curl_init();
	curl_setopt(
		$ch,
		CURLOPT_URL,
		'https://api.pagar.me/1/companies/temporary'
	);
	$date   = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) );
	$params = sprintf(
		'name=acceptance_test_company&email=%s@woocoomercesuite.com&password=password',
		$date->format( 'YmdHis' )
	);
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt(
		$ch,
		CURLOPT_POSTFIELDS,
		$params
	);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	$result       = curl_exec( $ch );
	$company_data = json_decode( $result );
	curl_close( $ch );

	return $company_data;
}
$api_key        = getenv( 'API_KEY' );
$encryption_key = getenv( 'ENCRYPTION_KEY' );

if ( ! $api_key && ! $encryption_key ) {
	$company_data   = get_company_temporary();
	$api_key        = $company_data->api_key->test;
	$encryption_key = $company_data->encryption_key->test;
}

/**
 * Get customs options
 *
 * @param array  $arguments Array of arguments passed to script.
 * @param string $name      Name argument.
 *
 * @return array
 */
function get_custom_options( $arguments, $name ) {
	$options = [];
	$index   = array_search( $name, $arguments );

	if ( false !== $index && isset( $arguments[ $index++ ] ) ) {
		parse_str( $arguments[ $index ], $options );
	}

	return $options;
}

define( 'PAGARME_API_KEY', $api_key );
define( 'PAGARME_ENCRYPTION_KEY', $encryption_key );

define(
	'WCP_CREDITCARD_OPTION_NAME',
	'woocommerce_pagarme-credit-card_settings'
);

define(
	'WCP_BOLETO_OPTION_NAME',
	'woocommerce_pagarme-banking-ticket_settings'
);

$base_options = [
	'enabled'        => 'yes',
	'api_key'        => PAGARME_API_KEY,
	'encryption_key' => PAGARME_ENCRYPTION_KEY,
	'testing'        => 'yes',
	'debug'          => 'yes',
];

$options_customs                 = get_custom_options( $argv, '--credit' );
$woocommerce_credit_card_options = array_merge(
	$base_options,
	[
		'title'                => 'Cartão de crédito',
		'description'          => 'Cartão de crédito Pagar.me',
		'integration'          => '',
		'checkout'             => 'no',
		'max_installment'      => '12',
		'smallest_installment' => '1',
		'interest_rate'        => '5',
		'free_installments'    => '2',
	],
	$options_customs
);

$options_customs            = get_custom_options( $argv, '--boleto' );
$woocommerce_boleto_options = array_merge(
	$base_options,
	[
		'title'       => 'Boleto bancário',
		'description' => 'Pagar com boleto bancário',
		'async'       => 'no',
	],
	$options_customs
);

echo setup( WCP_CREDITCARD_OPTION_NAME, $woocommerce_credit_card_options );
echo setup( WCP_BOLETO_OPTION_NAME, $woocommerce_boleto_options );
