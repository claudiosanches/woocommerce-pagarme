<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pagar.me my account frontend class.
 *
 * @package WooCommerce_Pagarme/Frontend
 */
class WC_Pagarme_My_Account {

	/**
	 * Initialize frontend actions.
	 */
	public function __construct() {
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'my_orders_banking_ticket_link' ), 10, 2 );
	}

	/**
	 * Add banking ticket link/button in My Orders section on My Accout page.
	 *
	 * @param array    $actions Actions.
	 * @param WC_Order $order   Order data.
	 *
	 * @return array
	 */
	public function my_orders_banking_ticket_link( $actions, $order ) {
		if ( 'pagarme-banking-ticket' !== $order->payment_method ) {
			return $actions;
		}

		if ( ! in_array( $order->get_status(), array( 'pending', 'on-hold' ) ) ) {
			return $actions;
		}

		$data = get_post_meta( $order->id, '_wc_pagarme_transaction_data', true );
		if ( ! empty( $data['boleto_url'] ) ) {
			$actions[] = array(
				'url'  => $data['boleto_url'],
				'name' => __( 'Print Banking Ticket', 'woocommerce-pagarme' ),
			);
		}

		return $actions;
	}
}

new WC_Pagarme_My_Account();
