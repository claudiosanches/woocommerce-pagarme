<?php
/**
 * Credit Card - Plain email instructions.
 *
 * @author  Pagar.me
 * @package WooCommerce_Pagarme/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

_e( 'Payment', 'woocommerce-pagarme' );

echo "\n\n";

echo sprintf( __( 'Payment successfully made using %s credit card in %s.', 'woocommerce-pagarme' ), $card_brand, $installments . 'x' );

echo "\n\n****************************************************\n\n";
