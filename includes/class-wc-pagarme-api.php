<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC Pagar.me API.
 */
class WC_Pagarme_API {

	/**
	 * API URL.
	 */
	const API_URL = 'https://api.pagar.me/1/';

	/**
	 * Gateway class.
	 *
	 * @var WC_Pagarme_Gateway
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
	protected $js_url = 'https://pagar.me/assets/pagarme-v2.min.js';

	/**
	 * Constructor.
	 *
	 * @param WC_PagSeguro_Gateway $gateway
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
	 * Only numbers.
	 *
	 * @param  string|int $string
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
			'method'    => $method,
			'sslverify' => false,
			'timeout'   => 60
		);

		if ( ! empty( $data ) ) {
			$params['body'] = $data;
		}

		if ( ! empty( $headers ) ) {
			$params['headers'] = $headers;
		}

		return wp_remote_post( $this->get_api_url() . $endpoint, $params );
	}

	/**
	 * Get the installments.
	 *
	 * @param  float $amount
	 *
	 * @return array
	 */
	public function get_installments( $amount ) {
		// Set the installment data.
		$data = http_build_query( array(
			'encryption_key'    => $this->gateway->encryption_key,
			'amount'            => $amount * 100,
			'interest_rate'     => $this->get_interest_rate(),
			'max_installments'  => $this->gateway->max_installment,
			'free_installments' => $this->gateway->free_installments
		) );

		// Get saved installment data.
		$_installments = get_transient( 'pgi_' . md5( $data ) );

		if ( false !== $_installments ) {
			return $_installments;
		}

		// Sets the post params.
		$params = array(
			'body'      => $data,
			'sslverify' => false,
			'timeout'   => 60
		);

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Getting the order installments...' );
		}

		$response = $this->do_request( 'transactions/calculate_installments_amount', 'GET', $data );

		if ( is_wp_error( $response ) ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'WP_Error in getting the installments: ' . $response->get_error_message() );
			}

			return array();
		} else {
			$_installments = json_decode( $response['body'], true );

			if ( isset( $_installments['installments'] ) ) {
				$installments = $_installments['installments'];

				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Installments generated successfully: ' . print_r( $_installments, true ) );
				}

				set_transient( 'pgi_' . md5( $data ), $installments, MINUTE_IN_SECONDS * 5 );

				return $installments;
			}
		}

		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Failed to get the installments: ' . print_r( $response, true ) );
		}

		return array();
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
		$wcbcf_settings = get_option( 'wcbcf_settings' );
		$phone          = $this->only_numbers( $order->billing_phone );
		$data           = array(
			'api_key'        => $this->gateway->api_key,
			'amount'         => $order->order_total * 100,
			'postback_url'   => WC()->api_request_url( 'WC_Pagarme_Gateway' ),
			'customer'       => array(
				'name'    => $order->billing_first_name . ' ' . $order->billing_last_name,
				'email'   => $order->billing_email,
				'address' => array(
					'street'        => $order->billing_address_1,
					'street_number' => $order->billing_number,
					'complementary' => $order->billing_address_2,
					'neighborhood'  => $order->billing_neighborhood,
					'zipcode'       => $this->only_numbers( $order->billing_postcode )
				),
				'phone' => array(
					'ddd'    => substr( $phone, 0, 2 ),
					'number' => substr( $phone, 2 )
				)
			)
		);

		// Set the document number.
		if ( 0 != $wcbcf_settings['person_type'] ) {
			if ( ( 1 == $wcbcf_settings['person_type'] && 1 == $order->billing_persontype ) || 2 == $wcbcf_settings['person_type'] ) {
				$data['customer']['document_number'] = $this->only_numbers( $order->billing_cpf );
			}

			if ( ( 1 == $wcbcf_settings['person_type'] && 2 == $order->billing_persontype ) || 3 == $wcbcf_settings['person_type'] ) {
				$data['customer']['name']            = $order->billing_company;
				$data['customer']['document_number'] = $this->only_numbers( $order->billing_cnpj );
			}
		}

		// Set the customer gender.
		if ( isset( $order->billing_sex ) && ! empty( $order->billing_sex ) ) {
			$data['customer']['sex'] = strtoupper( substr( $order->billing_sex, 0, 1 ) );
		}

		// Set the customer birthdate.
		if ( isset( $order->billing_birthdate ) && ! empty( $order->billing_birthdate ) ) {
			$birthdate = explode( '/', $order->billing_birthdate );

			$data['customer']['born_at'] = $birthdate[1] . '-' . $birthdate[0] . '-' . $birthdate[2];
		}

		if ( in_array( $this->gateway->methods, array( 'all', 'credit' ) ) && 'credit-card' == $posted[ 'pagarme_payment_method' ] ) {
			if ( isset( $posted[ 'pagarme_card_hash' ] ) ) {
				$data['payment_method'] = 'credit_card';
				$data['card_hash']      = $posted[ 'pagarme_card_hash' ];
			}

			// Validate the installments.
			if ( isset( $posted[ 'pagarme_installments' ] ) ) {
				$_installment = $posted[ 'pagarme_installments' ];

				// Get installments data.
				$installments = $this->get_installments( $order->order_total );
				if ( isset( $installments[ $_installment ] ) ) {
					$installment          = $installments[ $_installment ];
					$smallest_installment = $this->get_smallest_installment();

					if ( $installment['installment'] <= $this->gateway->max_installment && $smallest_installment <= $installment['installment_amount'] ) {
						$data['installments'] = $installment['installment'];
						$data['amount']       = $installment['amount'];
					}
				}
			}
		} elseif ( in_array( $this->gateway->methods, array( 'all', 'ticket' ) ) && 'banking-ticket' == $posted[ 'pagarme_payment_method' ] ) {
			$data['payment_method'] = 'boleto';
		}

		// Add filter for Third Party plugins.
		$data = apply_filters( 'wc_pagarme_transaction_data', $data );

		return $data;
	}

	/**
	 * Do the transaction.
	 *
	 * @param  WC_Order $order Order data.
	 * @param  array    $data  Transaction data.
	 *
	 * @return array           Response data.
	 */
	public function do_transaction( $order, $data ) {
		if ( 'yes' == $this->gateway->debug ) {
			$this->gateway->log->add( $this->gateway->id, 'Doing a transaction for order ' . $order->get_order_number() . '...' );
		}

		$response = $this->do_request( 'transactions', 'POST', http_build_query( $data ) );

		if ( is_wp_error( $response ) ) {
			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'WP_Error in doing the transaction: ' . $response->get_error_message() );
			}

			return array();
		} else {
			$transaction_data = json_decode( $response['body'], true );

			if ( isset( $transaction_data['errors'] ) ) {
				if ( 'yes' == $this->gateway->debug ) {
					$this->gateway->log->add( $this->gateway->id, 'Failed to make the transaction: ' . print_r( $response, true ) );
				}

				return $transaction_data;
			}

			if ( 'yes' == $this->gateway->debug ) {
				$this->gateway->log->add( $this->gateway->id, 'Transaction completed successfully! The transaction response is: ' . print_r( $transaction_data, true ) );
			}

			return $transaction_data;
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
}
