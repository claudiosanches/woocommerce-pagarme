<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC Pagar.me Gateway addons for Subscriptions and Pre-orders.
 */
class WC_Pagarme_Gateway_Addons extends WC_Pagarme_Gateway {

	/**
	 * Init the gateway actions.
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
	 * Process the subscription.
	 *
	 * @param  int $order_id
	 *
	 * @return array
	 */
	protected function process_subscription( $order_id ) {
		try {
			$order = new WC_Order( $order_id );

			if ( ! $order->billing_neighborhood ) {
				throw new Exception( __( 'You must fill the neighborhood field.', 'woocommerce-pagarme' ) );
			}

			$card_id = '';

			if ( in_array( $this->methods, array( 'all', 'credit' ) ) && 'credit-card' == $_POST['pagarme_payment_method'] ) {
				$card_hash = isset( $_POST['pagarme_card_hash'] ) ? $_POST['pagarme_card_hash'] : '';
				$card_data = $this->api->save_card( $card_hash, $order );

				if ( isset( $card_data['errors'] ) ) {
					throw new Exception( __( 'There was an error to identify your credit card, please try again or contact us get help.', 'woocommerce-pagarme' ) );
				}

				$payment_method = 'credit_card';
				$card_id = sanitize_text_field( $card_data['id'] );

			} elseif ( in_array( $this->methods, array( 'all', 'ticket' ) ) && 'banking-ticket' == $_POST[ 'pagarme_payment_method' ] ) {
				$payment_method = 'boleto';
			}

			// Store the subscription data in the order
			update_post_meta( $order->id, '_pagarme_subscription_data', array(
				'payment_method' => $payment_method,
				'card_id'        => $card_id
			) );

			$initial_payment = WC_Subscriptions_Order::get_total_initial_payment( $order );

			if ( $initial_payment > 0 ) {
				$payment_response = $this->process_subscription_payment( $order, $initial_payment );
			}

			if ( isset( $payment_response ) && is_wp_error( $payment_response ) ) {
				throw new Exception( $payment_response->get_error_message() );
			} else {
				// Remove cart.
				WC()->cart->empty_cart();

				// Return thank you page redirect.
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
				);
			}
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );

			return array(
				'result'   => 'fail',
				'redirect' => ''
			);
		}
	}

	/**
	 * Process the pre-order.
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	protected function process_pre_order( $order_id ) {
		if ( WC_Pre_Orders_Order::order_requires_payment_tokenization( $order_id ) ) {
			$order = new WC_Order( $order_id );

			// @TODO
		} else {
			parent::process_payment( $order_id );
		}
	}

	/**
	 * Process the payment.
	 *
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		// Processing subscription.
		if ( $this->order_contains_subscription( $order_id ) ) {
			return $this->process_subscription( $order_id );

		// Processing pre-order.
		} elseif ( $this->order_contains_pre_order( $order_id ) ) {
			return $this->process_pre_order( $order_id );

		// Processing regular product.
		} else {
			return parent::process_payment( $order_id );
		}
	}

	/**
	 * Process subscription payment.
	 *
	 * @param WC_Order $order
	 * @param int      $amount
	 *
	 * @return bool|WP_Error
	 */
	public function process_subscription_payment( $order = '', $amount = 0 ) {
		$order_items       = $order->get_items();
		$order_item        = array_shift( $order_items );
		$subscription_name = sprintf( __( '%s - Subscription for "%s"', 'woocommerce-pagarme' ), esc_html( get_bloginfo( 'name' ) ), $order_item['name'] ) . ' ' . sprintf( __( '(Order #%s)', 'woocommerce-pagarme' ), $order->get_order_number() );
		$subscription_data = get_post_meta( $order->id, '_pagarme_subscription_data', true );

		if ( ! $subscription_data || ! is_array( $subscription_data ) ) {
			return new WP_Error( 'pagarme_error', __( 'Subscription data not found', 'woocommerce-pagarme' ) );
		}

		$transaciton_data = $this->api->generate_transaction_data( $order );
		$transaciton_data['payment_method'] = $subscription_data['payment_method'];
		if ( 'credit_card' == $subscription_data['payment_method'] ) {
			$transaciton_data['card_id'] = $subscription_data['card_id'];
		}
		$transaciton_data['amount'] = $amount * 100;

		$transaction = $this->api->do_transaction( $order, $transaciton_data );

		if ( isset( $transaction['errors'] ) ) {
			$messages = array();

			foreach ( $transaction['errors'] as $message ) {
				$messages[] = $message['message'];
			}

			return new WP_Error( 'pagarme_error', implode( ', ', $messages ) . '.' );
		}

		// Save transaction data.
		update_post_meta( $order->id, '_wc_pagarme_transaction_id', intval( $transaction['id'] ) );
		$payment_data = array_map(
			'sanitize_text_field',
			array(
				'payment_method'  => $transaction['payment_method'],
				'installments'    => $transaction['installments'],
				'card_brand'      => $transaction['card_brand'],
				'antifraud_score' => $transaction['antifraud_score'],
				'boleto_url'      => $transaction['boleto_url'],
				'subscription_id' => $transaction['subscription_id']
			)
		);
		update_post_meta( $order->id, '_wc_pagarme_transaction_data', $payment_data );

		// For WooCommerce 2.2 or later.
		update_post_meta( $order->id, '_transaction_id', intval( $transaction['id'] ) );

		// Save only in old versions.
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1.12', '<=' ) ) {
			update_post_meta( $order->id, __( 'Pagar.me Transaction details', 'woocommerce-pagarme' ), 'https://dashboard.pagar.me/#/transactions/' . intval( $transaction['id'] ) );
		}

		$this->process_order_status( $order, $transaction['status'] );

		if ( in_array( $transaction['status'], array( 'processing', 'paid', 'waiting_payment' ) ) ) {
			return true;
		} else {
			$order->add_order_note( __( 'Simplify payment declined', 'woocommerce-pagarme' ) );

			return new WP_Error( 'pagarme_payment_declined', __( 'Payment was declined - please try another card or payment method.', 'woocommerce-pagarme' ) );
		}
	}

	/**
	 * Scheduled subscription payment.
	 *
	 * @param float    $amount_to_charge The amount to charge.
	 * @param WC_Order $order            The WC_Order object of the order which the subscription was purchased in.
	 * @param int      $product_id       The ID of the subscription product for which this payment relates.
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
	 * @param int    $original_order_id Post ID of the order being used to purchased the subscription being renewed
	 * @param int    $renewal_order_id Post ID of the order created for renewing the subscription
	 * @param string $new_order_role The role the renewal order is taking, one of 'parent' or 'child'
	 *
	 * @return string
	 */
	public function remove_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {
		if ( 'parent' == $new_order_role ) {
			$order_meta_query .= " AND `meta_key` NOT LIKE '_pagarme_subscription_data' ";
		}

		return $order_meta_query;
	}

	/**
	 * Update the customer_id for a subscription after using Simplify to complete a payment to make up for
	 * an automatic renewal payment which previously failed.
	 *
	 * @param WC_Order $original_order   The original order in which the subscription was purchased.
	 * @param WC_Order $renewal_order    The order which recorded the successful payment (to make up for the failed automatic payment).
	 * @param string   $subscription_key A subscription key of the form created by @see WC_Subscriptions_Manager::get_subscription_key()
	 */
	public function update_failing_payment_method( $original_order, $renewal_order, $subscription_key ) {
		$new_customer_id = get_post_meta( $renewal_order->id, '_pagarme_subscription_data', true );

		update_post_meta( $original_order->id, '_pagarme_subscription_data', $new_customer_id );
	}

	/**
	 * Process a pre-order payment when the pre-order is released.
	 *
	 * @param  WC_Order $order
	 *
	 * @return WP_Error|null
	 */
	public function process_pre_order_release_payment( $order ) {

	}
}
