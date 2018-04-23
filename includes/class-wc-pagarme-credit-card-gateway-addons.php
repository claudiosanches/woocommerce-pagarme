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
			update_post_meta( $order->get_id(), '_transaction_id', intval( $transaction['id'] ) );

			// Change the order status.
			$this->api->process_order_status( $order, $transaction['status'] );

			// Empty the cart.
			WC()->cart->empty_cart();

			// Redirect to thanks page.
			return array(
				'result' => 'success',
			);
		}
	}

	public function add_payment_method() {
		$response = $this->api->do_request( 'transactions/card_hash_key','GET', array( 'encryption_key' => $this->encryption_key ) );
		if ( is_wp_error( $response ) ) {
			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'WP_Error in generating card token: ' . $response->get_error_message() );
			}

			return array();
		} else {
			$data = json_decode( $response['body'], true );

			$encode_data = array(
				'card_number'          => wc_clean( $_POST['pagarme-card-number'] ),
				'card_holder_name'     => wc_clean( $_POST['pagarme-card-holder-name'] ),
				'card_expiration_date' => substr( wc_clean( $_POST['pagarme-card-expiry'] ), 0, 2 ) . substr( wc_clean( $_POST['pagarme-card-expiry'] ), -2 ),
				'card_cvv'             => wc_clean( $_POST['pagarme-card-cvc'] ),
			);

			$query_string = http_build_query( $encode_data, null, '&', PHP_QUERY_RFC3986 );

			$encoded = '';
			if ( openssl_public_encrypt( $query_string, $encrypted, $data['public_key'] ) ) {
				$encoded = base64_encode( $encrypted );
			}

			$token_string = $data['id'] . '_' . $encoded;

			$token = new WC_Payment_Token_CC();

			$token->set_token( $token_string );
			$token->set_gateway_id( $this->id );
			$token->set_card_type( 'visa' );
			$token->set_last4( substr( $_POST['pagarme-card-number'], -4 ) );
			$token->set_expiry_month( substr( $_POST['pagarme-card-expiry'], 0, 2 ) );
			$token->set_expiry_year( substr( $_POST['pagarme-card-expiry'], -4 ) );
			$token->set_user_id( get_current_user_id() );
			$token->save();
		}
	}
}
