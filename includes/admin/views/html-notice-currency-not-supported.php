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
	<p><strong><?php esc_html_e( 'Pagar.me Disabled', 'woocommerce-pagarme' ); ?></strong>: <?php printf( wp_kses( __( 'Currency %s is not supported. Works only with Brazilian Real.', 'woocommerce-pagarme' ), array( 'code' => array() ) ), '<code>' . esc_html( get_woocommerce_currency() ) . '</code>' ); ?>
	</p>
</div>

<?php
