<?php
/**
 * Pagar.me Credit Card Addons for Subscriptions
 *
 * @package WooCommerce_Pagarme/Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Pagarme_Credit_Card_Gateway_Addons class.
 *
 * @extends WC_Pagarme_Credit_Card_Gateway
 */
class WC_Pagarme_Credit_Card_Gateway_Addons extends WC_Pagarme_Credit_Card_Gateway {

	/**
	 * Init gateway addon actions.
	 */
	public function __construct() {
		parent::__construct();

		if ( is_checkout() ) {
			if ( WC_Subscriptions_Cart::cart_contains_subscription() ) {
				add_filter( 'wc_pagarme_checkout', '__return_false' );
			}
		}

		add_filter( 'wc_pagarme_transaction_data' , array( $this, 'pagarme_subscription_transaction_data' ), 10, 2 );
		add_action( 'woocommerce_subscription_cancelled_' . $this->id, array( $this, 'pagarme_cancelled_subscription' ) );
	}

	/**
	 * Process the subscription payment.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array Redirect data.
	 */
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
		$order_items       = $order->get_items();
		$order_item_id     = array_shift( $order_items )->get_product_id();
		$data['plan_id']   = get_post_meta( $order_item_id, '_pagarme_plan_id', true );
		return $data;
	}

	/**
	 * Process subscription payment.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array Redirect data.
	 */
	public function process_subscription( $order_id ) {
		$order       = wc_get_order( $order_id );
		$data        = $this->api->generate_transaction_data( $order, $_POST );
		$transaction = $this->api->do_transaction( $order, $data );

		if ( isset( $transaction['errors'] ) ) {
			foreach ( $transaction['errors'] as $error ) {
				wc_add_notice( $error['message'], 'error' );
			}

			return array(
				'result' => 'fail',
			);
		} else {
			// Save transaction data.
			update_post_meta( $order->get_id(), '_wc_pagarme_transaction_id', intval( $transaction['current_transaction']['id'] ) );

			$payment_data = array_map(
				'sanitize_text_field',
				array(
					'payment_method'  => $transaction['payment_method'],
					'installments'    => $transaction['current_transaction']['installments'],
					'card_brand'      => $this->api->get_card_brand_name( $transaction['current_transaction']['card_brand'] ),
					'antifraud_score' => $transaction['current_transaction']['antifraud_score'],
				)
			);

			update_post_meta( $order->get_id(), '_wc_pagarme_transaction_data', $payment_data );
			update_post_meta( $order->get_id(), '_wc_pagarme_subscription_id', intval( $transaction['id'] ) );
			
			// Change the order status.
			$this->api->process_order_status( $order, $transaction['status'] );

			// Empty the cart.
			WC()->cart->empty_cart();

			// Redirect to thanks page.
			return array(
				'result'   => 'success',
			);
		}
	}

	/**
	 * Cancels the subscription when user chooses to do so.
	 *
	 * @param WC_Subscription $subscription Subscription object.
	 */
	public function pagarme_cancelled_subscription( $subscription ) {
		$endpoint = 'subscriptions/' . get_post_meta( $subscription->get_parent_id(), '_wc_pagarme_subscription_id', true ) . '/cancel';
		$this->api->do_request( $endpoint, 'POST', array( 'api_key' => $this->api_key ) );
	}
}
