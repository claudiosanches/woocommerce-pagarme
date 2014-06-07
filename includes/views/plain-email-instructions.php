<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( 'boleto' == $data['payment_method'] ) {
	_e( 'Payment', 'woocommerce-pagarme' );

	echo "\n\n";

	_e( 'Please use the following link to view your Banking Ticket, you can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-pagarme' );

	echo "\n";

	echo esc_url( $data['boleto_url'] );

	echo "\n\n****************************************************\n\n";
}
