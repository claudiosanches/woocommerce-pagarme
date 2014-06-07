<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( 'boleto' == $data['payment_method'] ) : ?>

<h2><?php _e( 'Payment', 'woocommerce-pagarme' ); ?></h2>

<p class="order_details"><?php _e( 'Please use the following link to view your Banking Ticket, you can print and pay in your internet banking or in a lottery retailer.', 'woocommerce-pagarme' ); ?><br /><a class="button" href="<?php echo esc_url( $data['boleto_url'] ); ?>" target="_blank"><?php _e( 'Pay the Banking Ticket', 'woocommerce-pagarme' ); ?></a></p>

<?php
endif;
