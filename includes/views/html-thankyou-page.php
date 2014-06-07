<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( 'boleto' == $data['payment_method'] ) : ?>

<div class="woocommerce-message">

	<a class="button" href="<?php echo esc_url( $data['boleto_url'] ); ?>" target="_blank"><?php _e( 'Pay the Banking Ticket', 'woocommerce-pagarme' ); ?></a>

	<strong><?php _e( 'Attention!', 'woocommerce-pagarme' ); ?></strong> <span><?php _e( 'You will not get the Banking Ticket by mail.', 'woocommerce-pagarme' ); ?></span><br />

	<span><?php _e( 'Please click the following button and pay the Banking Ticket in your Internet Banking.', 'woocommerce-pagarme' ); ?></span><br />

	<span><?php _e( 'If you prefer, print and pay at any bank branch or lottery retailer.', 'woocommerce-pagarme' ); ?></span><br />

</div>

<?php
endif;
