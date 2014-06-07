<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( 'boleto' == $data['payment_method'] ) : ?>

<div class="woocommerce-message">
	<span><a class="button" href="<?php echo esc_url( $data['boleto_url'] ); ?>" target="_blank"><?php _e( 'Pay the Banking Ticket', 'woocommerce-pagarme' ); ?></a><strong><?php _e( 'Attention!', 'woocommerce-pagarme' ); ?></strong> <?php _e( 'You will not get the Banking Ticket by mail.', 'woocommerce-pagarme' ); ?><br /><?php _e( 'Please click in the following button to view your Banking Ticket', 'woocommerce-pagarme' ); ?><br /><?php _e( 'You can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-pagarme' ); ?></span>
</div>

<?php
endif;
