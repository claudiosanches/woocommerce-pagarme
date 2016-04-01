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

<h2><?php _e( 'Payment', 'woocommerce-pagarme' ); ?></h2>

<p class="order_details"><?php echo sprintf( __( 'Payment successfully made using %s credit card in %s.', 'woocommerce-pagarme' ), '<strong>' . $card_brand . '</strong>', '<strong>' . $installments . 'x</strong>' ); ?></p>
