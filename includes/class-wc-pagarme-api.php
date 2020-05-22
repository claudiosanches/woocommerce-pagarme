<?php
/**
 * Pagar.me API
 *
 * @package WooCommerce_Pagarme/API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Pagarme_API class.
 */
class WC_Pagarme_API {

	/**
	 * API URL.
	 */
	const API_URL = 'https://api.pagar.me/1/';

	/**
	 * Gateway class.
	 *
	 * @var WC_Payment_Gateway
	 */
	protected $gateway;

	/**
	 * API URL.
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.pagar.me/1/';

	/**
	 * JS Library URL.
	 *
	 * @var string
	 */
	protected $js_url = 'https://assets.pagar.me/js/pagarme.min.js';

	/**
	 * Checkout JS Library URL.
	 *
	 * @var string
	 */
	protected $checkout_js_url = 'https://assets.pagar.me/checkout/checkout.js';

	/**
	 * Constructor.
	 *
	 * @param WC_Payment_Gateway $gateway Gateway instance.
	 */
	public function __construct( $gateway = null ) {
		$this->gateway = $gateway;
	}

	/**
	 * Get API URL.
	 *
	 * @return string
	 */
	public function get_api_url() {
		return $this->api_url;
	}

	/**
	 * Get JS Library URL.
	 *
	 * @return string
	 */
	public function get_js_url() {
		return $this->js_url;
	}

	/**
	 * Get Checkout JS Library URL.
	 *
	 * @return string
	 */
	public function get_checkout_js_url() {
		return $this->checkout_js_url;
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return 'BRL' === get_woocommerce_currency();
	}

	/**
	 * Only numbers.
	 *
	 * @param  string|int $string String to convert.
	 *
	 * @return string|int
	 */
	protected function only_numbers( $string ) {
		return preg_replace( '([^0-9])', '', $string );
	}

	/**
	 * Get the smallest installment amount.
	 *
	 * @return int
	 */
	public function get_smallest_installment() {
		return ( 5 > $this->gateway->smallest_installment ) ? 500 : wc_format_decimal( $this->gateway->smallest_installment ) * 100;
	}

	/**
	 * Get the interest rate.
	 *
	 * @return float
	 */
	public function get_interest_rate() {
		return wc_format_decimal( $this->gateway->interest_rate );
	}

	/**
	 * Do requests in the Pagar.me API.
	 *
	 * @param  string $endpoint API Endpoint.
	 * @param  string $method   Request method.
	 * @param  array  $data     Request data.
	 * @param  array  $headers  Request headers.
	 *
	 * @return array            Request response.
	 */
	protected function do_request( $endpoint, $method = 'POST', $data = array(), $headers = array() ) {
		$params = array(
			'method'  => $method,
			'timeout' => 60,
		);

		if ( ! empty( $data ) ) {
			$params['body'] = $data;
		}

		if ( ! empty( $headers ) ) {
			$params['headers'] = $headers;
		}

		return wp_safe_remote_post( $this->get_api_url() . $endpoint, $params );
	}

	/**
	 * Get the installments.
	 *
	 * @param float $amount Order amount.
	 *
	 * @return array
	 */
	public function get_installments( $amount ) {
		// Set the installment data.
		$data = array(
			'encryption_key'    => $this->gateway->encryption_key,
			'amount'            => $amount * 100,
			'interest_rate'     => $this->get_interest_rate(),
			'max_installments'  => $this->gateway->max_installment,
			'free_installments' => $this->gateway->free_installments,
		);
		$transient_id = 'pgi_' . md5( http_build_query( $data ) );

		// Get saved installment data.
		$_installments = get_transient( $transient_id );

		if ( false !== $_installments ) {
			return $_installments;
		}

		if ( 'yes' === $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Getting the order installments...' );
		}

		$response = $this->do_request( 'transactions/calculate_installments_amount', 'GET', $data );

		if ( is_wp_error( $response ) ) {
			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'WP_Error in getting the installments: ' . $response->get_error_message() );
			}

			return array();
		} else {
			$_installments = json_decode( $response['body'], true );

			if ( isset( $_installments['installments'] ) ) {
				$installments = $_installments['installments'];

				if ( 'yes' === $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Installments generated successfully: ' . print_r( $_installments, true ) );
				}

				set_transient( $transient_id, $installments, MINUTE_IN_SECONDS * 5 );

				return $installments;
			}
		}

		if ( 'yes' === $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Failed to get the installments: ' . print_r( $response, true ) );
		}

		return array();
	}

	/**
	 * Get max installment.
	 *
	 * @param float $amount Order amount.
	 *
	 * @return int
	 */
	public function get_max_installment( $amount ) {
		$installments         = $this->get_installments( $amount );
		$smallest_installment = $this->get_smallest_installment();
		$max                  = 1;

		foreach ( $installments as $number => $installment ) {
			if ( $smallest_installment > $installment['installment_amount'] ) {
				break;
			}

			$max = $number;
		}

		return $max;
	}

	/**
	 * Generate the transaction data.
	 *
	 * @param  WC_Order $order  Order data.
	 * @param  array    $posted Form posted data.
	 *
	 * @return array            Transaction data.
	 */
	public function generate_transaction_data( $order, $posted ) {
		// Set the request data.
		$data = array(
			'api_key'      => $this->gateway->api_key,
			'amount'       => $order->get_total() * 100,
			'postback_url' => WC()->api_request_url( get_class( $this->gateway ) ),
			'customer'     => array(
				'name'  => trim( $order->billing_first_name . ' ' . $order->billing_last_name ),
				'email' => $order->billing_email,
			),
			'metadata'     => array(
				'order_number' => $order->get_order_number(),
			),
		);

		// Phone.
		if ( ! empty( $order->billing_phone ) ) {
			$phone = $this->only_numbers( $order->billing_phone );

			$data['customer']['phone'] = array(
				'ddd'    => substr( $phone, 0, 2 ),
				'number' => substr( $phone, 2 ),
			);
		}

		// Address.
		if ( ! empty( $order->billing_address_1 ) ) {
			$data['customer']['address'] = array(
				'street'        => $order->billing_address_1,
				'complementary' => $order->billing_address_2,
				'zipcode'       => $this->only_numbers( $order->billing_postcode ),
			);

			// Non-WooCommerce default address fields.
			if ( ! empty( $order->billing_number ) ) {
				$data['customer']['address']['street_number'] = $order->billing_number;
			}
			if ( ! empty( $order->billing_neighborhood ) ) {
				$data['customer']['address']['neighborhood'] = $order->billing_neighborhood;
			}
		}

		// Set the document number.
		if ( class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			$wcbcf_settings = get_option( 'wcbcf_settings' );
			if ( '0' !== $wcbcf_settings['person_type'] ) {
				// Avoid error if person type is set as an integer
				settype($wcbcf_settings['person_type'], 'string');
				
				if ( ( '1' === $wcbcf_settings['person_type'] && '1' === $order->billing_persontype ) || '2' === $wcbcf_settings['person_type'] ) {
					$data['customer']['document_number'] = $this->only_numbers( $order->billing_cpf );
				}

				if ( ( '1' === $wcbcf_settings['person_type'] && '2' === $order->billing_persontype ) || '3' === $wcbcf_settings['person_type'] ) {
					$data['customer']['name']            = $order->billing_company;
					$data['customer']['document_number'] = $this->only_numbers( $order->billing_cnpj );
				}
			}
		} else {
			if ( ! empty( $order->billing_cpf ) ) {
				$data['customer']['document_number'] = $this->only_numbers( $order->billing_cpf );
			}
			if ( ! empty( $order->billing_cnpj ) ) {
				$data['customer']['name']            = $order->billing_company;
				$data['customer']['document_number'] = $this->only_numbers( $order->billing_cnpj );
			}
		}

		// Set the customer gender.
		if ( ! empty( $order->billing_sex ) ) {
			$data['customer']['sex'] = strtoupper( substr( $order->billing_sex, 0, 1 ) );
		}

		// Set the customer birthdate.
		if ( ! empty( $order->billing_birthdate ) ) {
			$birthdate = explode( '/', $order->billing_birthdate );

			$data['customer']['born_at'] = $birthdate[1] . '-' . $birthdate[0] . '-' . $birthdate[2];
		}

		if ( 'pagarme-credit-card' === $this->gateway->id ) {
			if ( isset( $posted['pagarme_card_hash'] ) ) {
				$data['payment_method'] = 'credit_card';
				$data['card_hash']      = $posted['pagarme_card_hash'];
			}

			$is_checkout_pagarme = isset( $this->gateway->checkout ) && 'yes' === $this->gateway->checkout;
			$has_installment_data = ! empty( $posted['pagarme_installments'] ) && isset( $posted['pagarme_installments'] );

			$should_set_default_installment = $is_checkout_pagarme && ! $has_installment_data;

			$_installment = $should_set_default_installment ? 1 : $posted['pagarme_installments'];

			// Validate the installments.
			$data['installments'] = $_installment;
			// Get installments data.
			$installments = $this->get_installments( $order->get_total() );
			if ( isset( $installments[ $_installment ] ) ) {
				$installment          = $installments[ $_installment ];
				$smallest_installment = $this->get_smallest_installment();

				if ( $installment['installment'] <= $this->gateway->max_installment && $smallest_installment <= $installment['installment_amount'] ) {
					$data['amount'] = $installment['amount'];
				}
			}
		} elseif ( 'pagarme-banking-ticket' === $this->gateway->id ) {
			$data['payment_method'] = 'boleto';
			$data['async']          = 'yes' === $this->gateway->async;
		}

		// Add filter for Third Party plugins.
		return apply_filters( 'wc_pagarme_transaction_data', $data , $order );
	}

	/**
	 * Get customer data from checkout pay page.
	 *
	 * @return array
	 */
	public function get_customer_data_from_checkout_pay_page() {
		global $wp;

		$order    = wc_get_order( (int) $wp->query_vars['order-pay'] );
		$data     = $this->generate_transaction_data( $order, array() );
		$customer = array();

		if ( empty( $data['customer'] ) ) {
			return $customer;
		}

		$_customer = $data['customer'];
		$customer['customerName']  = $_customer['name'];
		$customer['customerEmail'] = $_customer['email'];

		if ( isset( $_customer['document_number'] ) ) {
			$customer['customerDocumentNumber'] = $_customer['document_number'];
		}

		if ( isset( $_customer['address'] ) ) {
			$customer['customerAddressStreet']        = $_customer['address']['street'];
			$customer['customerAddressComplementary'] = $_customer['address']['complementary'];
			$customer['customerAddressZipcode']       = $_customer['address']['zipcode'];

			if ( isset( $_customer['address']['street_number'] ) ) {
				$customer['customerAddressStreetNumber'] = $_customer['address']['street_number'];
			}
			if ( isset( $_customer['address']['neighborhood'] ) ) {
				$customer['customerAddressNeighborhood'] = $_customer['address']['neighborhood'];
			}
		}

		if ( isset( $_customer['phone'] ) ) {
			$customer['customerPhoneDdd']    = $_customer['phone']['ddd'];
			$customer['customerPhoneNumber'] = $_customer['phone']['number'];
		}

		return $customer;
	}

	/**
	 * Get transaction data.
	 *
	 * @param  WC_Order $order Order data.
	 * @param  string   $token Checkout token.
	 *
	 * @return array           Response data.
	 */
	public function get_transaction_data( $order, $token ) {
		if ( 'yes' === $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Getting transaction data for order ' . $order->get_order_number() . '...' );
		}

		$response = $this->do_request( 'transactions/' . $token, 'GET', array( 'api_key' => $this->gateway->api_key ) );

		if ( is_wp_error( $response ) ) {
			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'WP_Error in getting transaction data: ' . $response->get_error_message() );
			}

			return array();
		} else {
			$data = json_decode( $response['body'], true );

			if ( isset( $data['errors'] ) ) {
				if ( 'yes' === $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Failed to get transaction data: ' . print_r( $response, true ) );
				}

				return $data;
			}

			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Transaction data obtained successfully!' );
			}

			return $data;
		}
	}

	/**
	 * Generate checkout data.
	 *
	 * @param  WC_Order $order Order data.
	 * @param  string   $token Checkout token.
	 *
	 * @return array           Checkout data.
	 */
	public function generate_checkout_data( $order, $token ) {
		$transaction  = $this->get_transaction_data( $order, $token );
		$installments = $this->get_installments( $order->get_total() );

		// Valid transaction.
		if ( ! isset( $transaction['amount'] ) ) {
			return array( 'error' => __( 'Invalid transaction data.', 'woocommerce-pagarme' ) );
		}

		// Test if using more installments that allowed.
		if ( $this->gateway->max_installment < $transaction['installments'] || empty( $installments[ $transaction['installments'] ] ) ) {
			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Payment made with more installments than allowed for order ' . $order->get_order_number() );
			}

			return array( 'error' => __( 'Payment made with more installments than allowed.', 'woocommerce-pagarme' ) );
		}

		$installment = $installments[ $transaction['installments'] ];

		// Test smallest installment amount.
		if ( 1 !== intval( $transaction['installments'] ) && $this->get_smallest_installment() > $installment['installment_amount'] ) {
			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Payment divided into a lower amount than permitted for order ' . $order->get_order_number() );
			}

			return array( 'error' => __( 'Payment divided into a lower amount than permitted.', 'woocommerce-pagarme' ) );
		}

		// Check the transaction amount.
		if ( intval( $transaction['amount'] ) !== intval( $installment['amount'] ) ) {
			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Wrong payment amount total for order ' . $order->get_order_number() );
			}

			return array( 'error' => __( 'Wrong payment amount total.', 'woocommerce-pagarme' ) );
		}

		$data = array(
			'api_key'  => $this->gateway->api_key,
			'amount'   => $transaction['amount'],
			'metadata' => array(
				'order_number' => $order->get_order_number(),
			),
		);

		return apply_filters( 'wc_pagarme_checkout_data', $data );
	}

	/**
	 * Do the transaction.
	 *
	 * @param  WC_Order $order Order data.
	 * @param  array    $args  Transaction args.
	 * @param  string   $token Checkout token.
	 *
	 * @return array           Response data.
	 */
	public function do_transaction( $order, $args, $token = '' ) {
		if ( 'yes' === $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Doing a transaction for order ' . $order->get_order_number() . '...' );
		}

		$endpoint = 'transactions';
		if ( ! empty( $token ) ) {
			$endpoint .= '/' . $token . '/capture';
		}

		$response = $this->do_request( $endpoint, 'POST', $args );

		if ( is_wp_error( $response ) ) {
			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'WP_Error in doing the transaction: ' . $response->get_error_message() );
			}

			return array();
		} else {
			$data = json_decode( $response['body'], true );

			if ( isset( $data['errors'] ) ) {
				if ( 'yes' === $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Failed to make the transaction: ' . print_r( $response, true ) );
				}

				return $data;
			}

			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Transaction completed successfully! The transaction response is: ' . print_r( $data, true ) );
			}

			return $data;
		}
	}

	/**
	 * Do the transaction.
	 *
	 * @param  WC_Order $order Order data.
	 * @param  string   $token Checkout token.
	 *
	 * @return array           Response data.
	 */
	public function cancel_transaction( $order, $token ) {
		if ( 'yes' === $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Cancelling transaction for order ' . $order->get_order_number() . '...' );
		}

		$endpoint = 'transactions';
		if ( ! empty( $token ) ) {
			$endpoint .= '/' . $token . '/refund';
		}

		$response = $this->do_request( $endpoint, 'POST', array( 'api_key' => $this->gateway->api_key ) );

		if ( is_wp_error( $response ) ) {
			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'WP_Error in doing the transaction cancellation: ' . $response->get_error_message() );
			}

			return array();
		} else {
			$data = json_decode( $response['body'], true );

			if ( isset( $data['errors'] ) ) {
				if ( 'yes' === $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Failed to cancel the transaction: ' . print_r( $response, true ) );
				}

				return $data;
			}

			if ( 'yes' === $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Transaction canceled successfully! The response is: ' . print_r( $data, true ) );
			}

			return $data;
		}
	}

	/**
	 * Get card brand name.
	 *
	 * @param string $brand Card brand.
	 * @return string
	 */
	protected function get_card_brand_name( $brand ) {
		$names = array(
			'visa'       => __( 'Visa', 'woocommerce-pagarme' ),
			'mastercard' => __( 'MasterCard', 'woocommerce-pagarme' ),
			'amex'       => __( 'American Express', 'woocommerce-pagarme' ),
			'aura'       => __( 'Aura', 'woocommerce-pagarme' ),
			'jcb'        => __( 'JCB', 'woocommerce-pagarme' ),
			'diners'     => __( 'Diners', 'woocommerce-pagarme' ),
			'elo'        => __( 'Elo', 'woocommerce-pagarme' ),
			'hipercard'  => __( 'Hipercard', 'woocommerce-pagarme' ),
			'discover'   => __( 'Discover', 'woocommerce-pagarme' ),
		);

		return isset( $names[ $brand ] ) ? $names[ $brand ] : $brand;
	}

	/**
	 * Save order meta fields.
	 * Save fields as meta data to display on order's admin screen.
	 *
	 * @param int   $id Order ID.
	 * @param array $data Order data.
	 */
	protected function save_order_meta_fields( $id, $data ) {

		// Transaction data.
		$payment_data = array_map(
			'sanitize_text_field',
			array(
				'payment_method'  => $data['payment_method'],
				'installments'    => $data['installments'],
				'card_brand'      => $this->get_card_brand_name( $data['card_brand'] ),
				'antifraud_score' => $data['antifraud_score'],
				'boleto_url'      => $data['boleto_url'],
			)
		);

		// Meta data.
		$meta_data = array(
			__( 'Banking Ticket URL', 'woocommerce-pagarme' ) => sanitize_text_field( $data['boleto_url'] ),
			__( 'Credit Card', 'woocommerce-pagarme' )        => $this->get_card_brand_name( sanitize_text_field( $data['card_brand'] ) ),
			__( 'Installments', 'woocommerce-pagarme' )       => sanitize_text_field( $data['installments'] ),
			__( 'Total paid', 'woocommerce-pagarme' )         => number_format( intval( $data['amount'] ) / 100, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ),
			__( 'Anti Fraud Score', 'woocommerce-pagarme' )   => sanitize_text_field( $data['antifraud_score'] ),
			'_wc_pagarme_transaction_data'                    => $payment_data,
			'_wc_pagarme_transaction_id'                      => intval( $data['id'] ),
			'_transaction_id'                                 => intval( $data['id'] ),
		);

		$order = wc_get_order( $id );

		// WooCommerce 3.0 or later.
		if ( ! method_exists( $order, 'update_meta_data' ) ) {
			foreach ( $meta_data as $key => $value ) {
				update_post_meta( $id, $key, $value );
			}
		} else {
			foreach ( $meta_data as $key => $value ) {
				$order->update_meta_data( $key, $value );
			}

			$order->save();
		}
	}

	/**
	 * Process regular payment.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array Redirect data.
	 */
	public function process_regular_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		$is_checkout_pagarme    = isset( $this->gateway->checkout ) && 'yes' === $this->gateway->checkout;
		$register_refused_order = isset( $this->gateway->register_refused_order ) && 'yes' === $this->gateway->register_refused_order;

		if ( $is_checkout_pagarme && ! $register_refused_order ) {
			if ( ! empty( $_POST['pagarme_checkout_token'] ) ) {
				$token = sanitize_text_field( wp_unslash( $_POST['pagarme_checkout_token'] ) );
				$data  = $this->generate_checkout_data( $order, $token );

				// Cancel the payment is irregular.
				if ( isset( $data['error'] ) ) {
					$this->cancel_transaction( $order, $token );
					$order->update_status( 'failed', $data['error'] );

					return array(
						'result'   => 'success',
						'redirect' => $this->gateway->get_return_url( $order ),
					);
				}

				$transaction = $this->do_transaction( $order, $data, $token );
			} else {
				$transaction = array( 'errors' => array( array( 'message' => __( 'Missing credit card data, please review your data and try again or contact us for assistance.', 'woocommerce-pagarme' ) ) ) );
			}
		} else {
			$data        = $this->generate_transaction_data( $order, $_POST );
			$transaction = $this->do_transaction( $order, $data );
		}

		if ( isset( $transaction['errors'] ) ) {
			foreach ( $transaction['errors'] as $error ) {
				wc_add_notice( $error['message'], 'error' );
			}

			return array(
				'result' => 'fail',
			);
		} else {

			$this->save_order_meta_fields( $order_id, $transaction );

			$this->process_order_status( $order, $transaction['status'] );

			// Empty the cart.
			WC()->cart->empty_cart();

			// Redirect to thanks page.
			return array(
				'result'   => 'success',
				'redirect' => $this->gateway->get_return_url( $order ),
			);
		}
	}

	/**
	 * Check if Pagar.me response is validity.
	 *
	 * @param  array $ipn_response IPN response data.
	 *
	 * @return bool
	 */
	public function check_fingerprint( $ipn_response ) {
		if ( isset( $ipn_response['id'] ) && isset( $ipn_response['current_status'] ) && isset( $ipn_response['fingerprint'] ) ) {
			$fingerprint = sha1( $ipn_response['id'] . '#' . $this->gateway->api_key );

			if ( $fingerprint === $ipn_response['fingerprint'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Send email notification.
	 *
	 * @param string $subject Email subject.
	 * @param string $title   Email title.
	 * @param string $message Email message.
	 */
	protected function send_email( $subject, $title, $message ) {
		$mailer = WC()->mailer();
		$mailer->send( get_option( 'admin_email' ), $subject, $mailer->wrap_message( $title, $message ) );
	}

	/**
	 * IPN handler.
	 */
	public function ipn_handler() {
		@ob_clean();

		$ipn_response = ! empty( $_POST ) ? $_POST : false;

		if ( $ipn_response && $this->check_fingerprint( $ipn_response ) ) {
			header( 'HTTP/1.1 200 OK' );

			$this->process_successful_ipn( $ipn_response );

			// Deprecated action since 2.0.0.
			do_action( 'wc_pagarme_valid_ipn_request', $ipn_response );

			exit;
		} else {
			wp_die( esc_html__( 'Pagar.me Request Failure', 'woocommerce-pagarme' ), '', array( 'response' => 401 ) );
		}
	}

	/**
	 * Process successeful IPN requests.
	 *
	 * @param array $posted Posted data.
	 */
	public function process_successful_ipn( $posted ) {
		global $wpdb;

		$posted   = wp_unslash( $posted );
		$order_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wc_pagarme_transaction_id' AND meta_value = %d", $posted['id'] ) ) );
		$order    = wc_get_order( $order_id );
		$status   = sanitize_text_field( $posted['current_status'] );

		if ( $order && $order->id === $order_id ) {
			$this->process_order_status( $order, $status );
		}

		// Async transactions will only send the boleto_url on IPN.
		if ( ! empty( $posted['transaction']['boleto_url'] ) && 'pagarme-banking-ticket' === $order->payment_method ) {
			$post_data = get_post_meta( $order->id, '_wc_pagarme_transaction_data', true );
			$post_data['boleto_url'] = sanitize_text_field( $posted['transaction']['boleto_url'] );
			update_post_meta( $order->id, '_wc_pagarme_transaction_data', $post_data );
		}
	}

	/**
	 * Process the order status.
	 *
	 * @param WC_Order $order  Order data.
	 * @param string   $status Transaction status.
	 */
	public function process_order_status( $order, $status ) {
		if ( 'yes' === $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Payment status for order ' . $order->get_order_number() . ' is now: ' . $status );
		}

		switch ( $status ) {
			case 'authorized' :
				if ( ! in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
					$order->update_status( 'on-hold', __( 'Pagar.me: The transaction was authorized.', 'woocommerce-pagarme' ) );
				}

				break;
			case 'pending_review':
				$transaction_id  = get_post_meta( $order->id, '_wc_pagarme_transaction_id', true );
				$transaction_url = '<a href="https://dashboard.pagar.me/#/transactions/' . intval( $transaction_id ) . '">https://dashboard.pagar.me/#/transactions/' . intval( $transaction_id ) . '</a>';

				/* translators: %s transaction details url */
				$order->update_status( 'on-hold', __( 'Pagar.me: You should manually analyze this transaction to continue payment flow, access %s to do it!', 'woocommerce-pagarme'  ), $transaction_url  );

				break;
			case 'processing' :
				$order->update_status( 'on-hold', __( 'Pagar.me: The transaction is being processed.', 'woocommerce-pagarme' ) );

				break;
			case 'paid' :
				if ( ! in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
					$order->add_order_note( __( 'Pagar.me: Transaction paid.', 'woocommerce-pagarme' ) );
				}

				// Changing the order for processing and reduces the stock.
				$order->payment_complete();

				break;
			case 'waiting_payment' :
				$order->update_status( 'on-hold', __( 'Pagar.me: The banking ticket was issued but not paid yet.', 'woocommerce-pagarme' ) );

				break;
			case 'refused' :
				$order->update_status( 'failed', __( 'Pagar.me: The transaction was rejected by the card company or by fraud.', 'woocommerce-pagarme' ) );

				$transaction_id  = get_post_meta( $order->id, '_wc_pagarme_transaction_id', true );
				$transaction_url = '<a href="https://dashboard.pagar.me/#/transactions/' . intval( $transaction_id ) . '">https://dashboard.pagar.me/#/transactions/' . intval( $transaction_id ) . '</a>';

				$this->send_email(
					sprintf( esc_html__( 'The transaction for order %s was rejected by the card company or by fraud', 'woocommerce-pagarme' ), $order->get_order_number() ),
					esc_html__( 'Transaction failed', 'woocommerce-pagarme' ),
					sprintf( esc_html__( 'Order %1$s has been marked as failed, because the transaction was rejected by the card company or by fraud, for more details, see %2$s.', 'woocommerce-pagarme' ), $order->get_order_number(), $transaction_url )
				);

				break;
			case 'refunded' :
				$order->update_status( 'refunded', __( 'Pagar.me: The transaction was refunded/canceled.', 'woocommerce-pagarme' ) );

				$transaction_id  = get_post_meta( $order->id, '_wc_pagarme_transaction_id', true );
				$transaction_url = '<a href="https://dashboard.pagar.me/#/transactions/' . intval( $transaction_id ) . '">https://dashboard.pagar.me/#/transactions/' . intval( $transaction_id ) . '</a>';

				$this->send_email(
					sprintf( esc_html__( 'The transaction for order %s refunded', 'woocommerce-pagarme' ), $order->get_order_number() ),
					esc_html__( 'Transaction refunded', 'woocommerce-pagarme' ),
					sprintf( esc_html__( 'Order %1$s has been marked as refunded by Pagar.me, for more details, see %2$s.', 'woocommerce-pagarme' ), $order->get_order_number(), $transaction_url )
				);

				break;
			case 'analyzing' :
				$order->update_status( 'on-hold', __( 'Pagar.me: Transaction is waiting for antifraud analysis.', 'woocommerce-pagarme' ) );

				break;

			default :
				break;
		}
	}
}
