<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC Pagar.me Gateway addons for Subscriptions and Pre-orders.
 *
 * Built the Pagar.me method.
 */
class WC_Pagarme_Gateway_Addons extends WC_Gateway_Simplify_Commerce {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		if ( class_exists( 'WC_Subscriptions_Order' ) ) {
			add_action( 'scheduled_subscription_payment_' . $this->id, array( $this, 'scheduled_subscription_payment' ), 10, 3 );
			add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'remove_renewal_order_meta' ), 10, 4 );
			add_action( 'woocommerce_subscriptions_changed_failing_payment_method_' . $this->id, array( $this, 'update_failing_payment_method' ), 10, 3 );
		}

		if ( class_exists( 'WC_Pre_Orders_Order' ) ) {
			add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array( $this, 'process_pre_order_release_payment' ) );
		}
	}

	/**
	 * Check if order contains subscriptions.
	 *
	 * @param  int $order_id
	 *
	 * @return bool
	 */
	protected function order_contains_subscription( $order_id ) {
		return class_exists( 'WC_Subscriptions_Order' ) && WC_Subscriptions_Order::order_contains_subscription( $order_id );
	}

	/**
	 * Check if order contains pre-orders.
	 *
	 * @param  int $order_id
	 *
	 * @return bool
	 */
	protected function order_contains_pre_order( $order_id ) {
		return class_exists( 'WC_Pre_Orders_Order' ) && WC_Pre_Orders_Order::order_contains_pre_order( $order_id );
	}

	/**
	 * Process the subscription
	 *
	 * @param WC_Order $order
	 * @param string   $cart_token
	 *
	 * @return array
	 */
	protected function process_subscription( $order, $cart_token = '' ) {

	}

	/**
	 * Process the pre-order
	 *
	 * @param WC_Order $order
	 * @param string   $cart_token
	 *
	 * @return array
	 */
	protected function process_pre_order( $order, $cart_token = '' ) {
		if ( WC_Pre_Orders_Order::order_requires_payment_tokenization( $order->id ) ) {

		} else {
			parent::process_payment( $order->id );
		}
	}

	/**
	 * Process the payment
	 *
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Processing subscription
		if ( $this->order_contains_subscription( $order->id ) ) {
			return $this->process_subscription( $order, $cart_token );

		// Processing pre-order
		} elseif ( $this->order_contains_pre_order( $order->id ) ) {
			return $this->process_pre_order( $order, $cart_token );

		// Processing regular product
		} else {
			return parent::process_payment( $order_id );
		}
	}

	/**
	 * process_subscription_payment function.
	 *
	 * @param WC_order $order
	 * @param integer $amount (default: 0)
	 * @return bool|WP_Error
	 */
	public function process_subscription_payment( $order = '', $amount = 0 ) {

	}

	/**
	 * scheduled_subscription_payment function.
	 *
	 * @param float $amount_to_charge The amount to charge.
	 * @param WC_Order $order The WC_Order object of the order which the subscription was purchased in.
	 * @param int $product_id The ID of the subscription product for which this payment relates.
	 * @return void
	 */
	public function scheduled_subscription_payment( $amount_to_charge, $order, $product_id ) {
		$result = $this->process_subscription_payment( $order, $amount_to_charge );

		if ( is_wp_error( $result ) ) {
			WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order, $product_id );
		} else {
			WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
		}
	}

	/**
	 * Don't transfer customer meta when creating a parent renewal order.
	 *
	 * @param string $order_meta_query MySQL query for pulling the metadata
	 * @param int $original_order_id Post ID of the order being used to purchased the subscription being renewed
	 * @param int $renewal_order_id Post ID of the order created for renewing the subscription
	 * @param string $new_order_role The role the renewal order is taking, one of 'parent' or 'child'
	 * @return string
	 */
	public function remove_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {
		if ( 'parent' == $new_order_role ) {
			$order_meta_query .= " AND `meta_key` NOT LIKE '_pagarme_card_token' ";
		}

		return $order_meta_query;
	}

	/**
	 * Update the customer_id for a subscription after using Simplify to complete a payment to make up for
	 * an automatic renewal payment which previously failed.
	 *
	 * @param WC_Order $original_order The original order in which the subscription was purchased.
	 * @param WC_Order $renewal_order The order which recorded the successful payment (to make up for the failed automatic payment).
	 * @param string $subscription_key A subscription key of the form created by @see WC_Subscriptions_Manager::get_subscription_key()
	 * @return void
	 */
	public function update_failing_payment_method( $original_order, $renewal_order, $subscription_key ) {
		$new_customer_id = get_post_meta( $renewal_order->id, '_pagarme_card_token', true );

		update_post_meta( $original_order->id, '_pagarme_card_token', $new_customer_id );
	}

	/**
	 * Process a pre-order payment when the pre-order is released
	 *
	 * @param WC_Order $order
	 * @return wp_error|null
	 */
	public function process_pre_order_release_payment( $order ) {

	}
}
