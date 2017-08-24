
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
	<span><a class="button" href="<?php echo esc_url( $order ); ?>" target="_blank"><?php esc_html_e( 'Clique aqui para ser redirecionado para seu pedido', 'woocommerce-pagarme' ); ?></a><?php esc_html_e( 'Seu boleto está sendo gerado, acesse seu pedido para visualizá-lo.', 'woocommerce-pagarme' ); ?><br />
 </span>
</div>
