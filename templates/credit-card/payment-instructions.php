<?php
/**
 * Credit Card - Payment instructions.
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
	<span><?php echo sprintf( __( 'Payment successfully made using %s credit card in %s.', 'woocommerce-pagarme' ), '<strong>' . $card_brand . '</strong>', '<strong>' . $installments . 'x</strong>' ); ?></span>
</div>
