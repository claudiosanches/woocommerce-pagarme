<?php
/**
 * Bank Slip - HTML email instructions.
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

<p class="order_details"><?php _e( 'Please use the link below to view your banking ticket, you can print and pay in your internet banking or in a lottery retailer:', 'woocommerce-pagarme' ); ?><br /><a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank"><?php _e( 'Pay the banking ticket', 'woocommerce-pagarme' ); ?></a><br /><?php _e( 'After we receive the banking ticket payment confirmation, your order will be processed.', 'woocommerce-pagarme' ); ?></p>
