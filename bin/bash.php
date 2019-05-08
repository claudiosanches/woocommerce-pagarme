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

define( 'PAGARME_API_KEY', getenv( 'API_KEY' ) );
define( 'PAGARME_ENCRYPTION_KEY', getenv( 'ENCRYPTION_KEY' ) );

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
