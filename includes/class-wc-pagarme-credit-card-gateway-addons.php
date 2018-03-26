<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Pagarme_Credit_Card_Gateway_Addons extends WC_Pagarme_Credit_Card_Gateway {

	public function __construct() {
		parent::__construct();

		add_filter( 'wc_pagarme_transaction_data' , 'pagarme_subscription_transaction_data' );
	}

	public function process_payment( $order_id ) {
		if ( wcs_order_contains_subscription( $order_id ) ) {
			return $this->process_subscription( $order_id );
		} else {
			return parent::process_payment( $order_id );
		}
	}

	/**
	 * Adds the plan id to the transaction data.
	 *
	 * @param  array    $data  Transaction data.
	 * @param  WC_Order $order Order data.
	 *
	 * @return array            Transaction data.
	 */
	public function pagarme_subscription_transaction_data( $data, $order ) {
		return $data['plan_id'] = get_post_meta( $order, '_pagarme_plan_id', true );
	}

	/**
	 * Process subscription payment.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array Redirect data.
	 */
	public function process_subscription( $order_id ) {
		
	}
}
