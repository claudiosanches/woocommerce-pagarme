<?php
/**
 * Notice: Currency not supported.
 *
 * @package WooCommerce_Pagarme/Admin/Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="error inline">
	<p><strong><?php _e( 'Pagar.me Disabled', 'woocommerce-pagarme' ); ?></strong>: <?php printf( __( 'Currency %s is not supported. Works only with Brazilian Real.', 'woocommerce-pagarme' ), '<code>' . get_woocommerce_currency() . '</code>' ); ?>
	</p>
</div>

<?php
