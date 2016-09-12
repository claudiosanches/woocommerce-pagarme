<?php
/**
 * Bank Slip - Plain email instructions.
 *
 * @author  Pagar.me
 * @package WooCommerce_Pagarme/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

esc_html_e( 'Payment', 'woocommerce-pagarme' );

echo "\n\n";

esc_html_e( 'Please use the link below to view your banking ticket, you can print and pay in your internet banking or in a lottery retailer:', 'woocommerce-pagarme' );

echo "\n";

echo esc_url( $url );

echo "\n";

esc_html_e( 'After we receive the banking ticket payment confirmation, your order will be processed.', 'woocommerce-pagarme' );

echo "\n\n****************************************************\n\n";
