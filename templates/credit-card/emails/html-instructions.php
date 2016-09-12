<?php
/**
 * Credit Card - HTML email instructions.
 *
 * @author  Pagar.me
 * @package WooCommerce_Pagarme/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2><?php esc_html_e( 'Payment', 'woocommerce-pagarme' ); ?></h2>

<p class="order_details"><?php printf( wp_kses( __( 'Payment successfully made using %1$s credit card in %2$s.', 'woocommerce-pagarme' ), array( 'strong' => array() ) ), '<strong>' . esc_html( $card_brand ) . '</strong>', '<strong>' . intval( $installments ) . 'x</strong>' ); ?></p>
