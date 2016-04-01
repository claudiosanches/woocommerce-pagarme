<?php
/**
 * Bank Slip - Payment instructions.
 *
 * @author  Pagar.me
 * @package WooCommerce_Pagarme/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="woocommerce-message">
	<span><a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank"><?php _e( 'Pay the banking ticket', 'woocommerce-pagarme' ); ?></a><?php _e( 'Please click in the following button to view your banking ticket.', 'woocommerce-pagarme' ); ?><br /><?php _e( 'You can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-pagarme' ); ?><br /><?php _e( 'After we receive the banking ticket payment confirmation, your order will be processed.', 'woocommerce-pagarme' ); ?></span>
</div>
