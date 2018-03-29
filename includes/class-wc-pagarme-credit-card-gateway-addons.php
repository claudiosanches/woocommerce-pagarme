<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Pagarme_Credit_Card_Gateway_Addons extends WC_Pagarme_Credit_Card_Gateway {

	public function __construct() {
		parent::__construct();

		if ( is_checkout() ) {
			if ( WC_Subscriptions_Cart::cart_contains_subscription() ) {
				add_filter( 'wc_pagarme_checkout', '__return_false' );
			}
		}

		add_filter( 'wc_pagarme_transaction_data' , array( $this, 'pagarme_subscription_transaction_data' ), 10, 2 );
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
		return $data['plan_id'] = get_post_meta( $order->get_id(), '_pagarme_plan_id', true );
	}

	/**
	 * Process subscription payment.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array Redirect data.
	 */
	public function process_subscription( $order_id ) {
		$order = wc_get_order( $order_id );

		// TODO: Checar se é possivel fazer o capture de uma subscription
		if ( isset( $this->gateway->checkout ) && 'yes' === $this->gateway->checkout ) {
			if ( ! empty( $_POST['pagarme_checkout_token'] ) ) {
				$token = sanitize_text_field( wp_unslash( $_POST['pagarme_checkout_token'] ) );
				$data  = $this->api->generate_checkout_data( $order, $token );

				// Cancel the payment is irregular.
				if ( isset( $data['error'] ) ) {
					$this->api->cancel_transaction( $order, $token );
					$order->update_status( 'failed', $data['error'] );

					return array(
						'result'   => 'success',
						'redirect' => $this->gateway->get_return_url( $order ),
					);
				}

				$transaction = $this->api->do_transaction( $order, $data, $token );
			} else {
				$transaction = array( 'errors' => array( array( 'message' => __( 'Missing credit card data, please review your data and try again or contact us for assistance.', 'woocommerce-pagarme' ) ) ) );
			}
		} else {
			$data        = $this->api->generate_transaction_data( $order, $_POST );
			$transaction = $this->api->do_transaction( $order, $data );
		}

		if ( isset( $transaction['errors'] ) ) {
			foreach ( $transaction['errors'] as $error ) {
				wc_add_notice( $error['message'], 'error' );
			}

			return array(
				'result' => 'fail',
			);
		// TODO se nao teve erro até aqui, deu tudo certo, apenas salva as informacoes da transacao
		} else {
			// Save transaction data.
			update_post_meta( $order->get_id(), '_wc_pagarme_transaction_id', intval( $transaction['id'] ) );
			$payment_data = array_map(
				'sanitize_text_field',
				array(
					'payment_method'  => $transaction['payment_method'],
					'installments'    => $transaction['installments'],
					'card_brand'      => $this->api->get_card_brand_name( $transaction['card_brand'] ),
					'antifraud_score' => $transaction['antifraud_score'],
					'boleto_url'      => $transaction['boleto_url'],
				)
			);
			update_post_meta( $order->get_id(), '_wc_pagarme_transaction_data', $payment_data );
			update_post_meta( $order->get_id(), '_transaction_id', intval( $transaction['id'] ) );
			$this->api->save_order_meta_fields( $order->get_id(), $transaction );

			// Change the order status.
			$this->api->process_order_status( $order, $transaction['status'] );

			// Empty the cart.
			WC()->cart->empty_cart();

			// Redirect to thanks page.
			return array(
				'result'   => 'success',
				'redirect' => $this->gateway->get_return_url( $order ),
			);
		}
	}
}
