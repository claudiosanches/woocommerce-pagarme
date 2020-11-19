<?php
/**
 * Pagar.me My Account actions
 *
 * @package WooCommerce_Pagarme/Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Pagarme_My_Account class.
 */
class WC_Pagarme_My_Account {

	/**
	 * Initialize my account actions.
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
		if ( 'pagarme-banking-ticket' !== $order->get_payment_method() ) {
			return $actions;
		}

		if ( ! in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) ) {
			return $actions;
		}

		$data = $order->get_meta( '_wc_pagarme_transaction_data' );
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
