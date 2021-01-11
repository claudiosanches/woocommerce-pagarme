<?php
/**
 * Notice: Pagar.me documentation link.
 *
 * @package WooCommerce_Pagarme/Admin/Notices
 */

?>

<div class="updated notice is-dismissible">
	<p><?php esc_html_e( 'We recommend checking out our documentation before starting using', 'woocommerce-pagarme' ); ?> <strong><?php esc_html_e( 'WooCommerce Pagar.me', 'woocommerce-pagarme' ); ?></strong>!</p>
	<p>
		<a href="https://docs.pagar.me/v2/docs/configurando-o-plugin-pagarme-woocommerce" class="button button-primary" target="_blank"><?php esc_html_e( 'Pagar.me documentation', 'woocommerce-pagarme' ); ?></a>		
	</p>
	<p><a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'woocommerce-pagarme-hide-notice', 'documentation_link' ) ) ); ?>" class="notice-dismiss" style="text-decoration:none;"></a></p>
</div>
