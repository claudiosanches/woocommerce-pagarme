<?php
/**
 * WC Pagar.me Gateway Class.
 *
 * Built the Pagar.me method.
 */
class WC_PagarMe_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 *
	 * @return void
	 */
	public function __construct() {
		global $woocommerce;

		$this->id                 = 'pagarme';
		$this->icon               = false;
		$this->has_fields         = true;
		$this->method_title       = __( 'Pagar.me', 'woocommerce-pagarme' );
		$this->method_description = __( 'Accept payments by Credit Card or Banking Ticket using Pagar.me.', 'woocommerce-pagarme' );

		// API URLs.
		$this->api_url = 'https://api.pagar.me/1/';

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->api_key     = $this->get_option( 'api_key' );
		$this->sandbox     = $this->get_option( 'sandbox' );
		$this->debug       = $this->get_option( 'debug' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Active logs.
		if ( 'yes' == $this->debug ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $woocommerce->logger();
			}
		}

		// Display admin notices.
		$this->admin_notices();
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return ( 'BRL' == get_woocommerce_currency() );
	}

	/**
	 * Displays notifications when the admin has something wrong with the configuration.
	 *
	 * @return void
	 */
	protected function admin_notices() {
		if ( is_admin() ) {
			// Checks if api_key is not empty.
			if ( empty( $this->api_key ) ) {
				add_action( 'admin_notices', array( $this, 'api_key_missing_message' ) );
			}

			// Checks that the currency is supported
			if ( ! $this->using_supported_currency() ) {
				add_action( 'admin_notices', array( $this, 'currency_not_supported_message' ) );
			}
		}
	}

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @return bool
	 */
	public function is_available() {
		// Test if is valid for use.
		$available = ( 'yes' == $this->get_option( 'enabled' ) ) && ! empty( $this->api_key ) && $this->using_supported_currency();

		return $available;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-pagarme' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Pagar.me standard', 'woocommerce-pagarme' ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-pagarme' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-pagarme' ),
				'desc_tip'    => true,
				'default'     => __( 'Pagar.me', 'woocommerce-pagarme' )
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-pagarme' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-pagarme' ),
				'default'     => __( 'Pay with Credit Card or Banking Ticket via Pagar.me', 'woocommerce-pagarme' )
			),
			'api_key' => array(
				'title'       => __( 'Pagar.me API Key', 'woocommerce-pagarme' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Please enter your Pagar.me API key. This is needed to process the payment and notifications. Is possible get your API Key in %s.', 'woocommerce-pagarme' ), '<a href="https://dashboard.pagar.me/">' . __( 'Pagar.me Dashboard > My Account page', 'woocommerce-pagarme' ) . '</a>' ),
				'default'     => ''
			),
			'testing' => array(
				'title'       => __( 'Gateway Testing', 'woocommerce-pagarme' ),
				'type'        => 'title',
				'description' => ''
			),
			'sandbox' => array(
				'title'       => __( 'Pagar.me Sandbox', 'woocommerce-pagarme' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable Pagar.me Sandbox', 'woocommerce-pagarme' ),
				'default'     => 'no',
				'description' => __( 'Pagar.me sandbox can be used to test the payments.', 'woocommerce-pagarme' )
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'woocommerce-pagarme' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce-pagarme' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Pagar.me events, such as API requests, inside %s', 'woocommerce-pagarme' ), '<code>woocommerce/logs/' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.txt</code>' )
			)
		);
	}

	/**
	 * Generate the transaction data.
	 *
	 * @param  WC_Order $order  Order data.
	 * @param  array    $posted Form posted data.
	 *
	 * @return array            Transaction data.
	 */
	protected function generate_transaction_data( $order, $posted ) {
		global $woocommerce;

		// Backwards compatibility with WooCommerce version prior to 2.1.
		if ( function_exists( 'WC' ) ) {
			$postback_url = WC()->api_request_url( 'WC_PagarMe_Gateway' );
		} else {
			$postback_url = $woocommerce->api_request_url( 'WC_PagarMe_Gateway' );
		}

		$phone = str_replace( array( '(', '-', ' ', ')' ), '', $order->billing_phone );

		// Set the request data.
		$data = array(
			'api_key'        => $this->api_key,
			'amount'         => number_format( $order->order_total, 2, '', '' ),
			'payment_method' => 'credit_card',
			'customer'       => array(
				'name'    => $order->billing_first_name . ' ' . $order->billing_last_name,
				'email'   => $order->billing_email,
				'address' => array(
					'street'        => $order->billing_address_1,
					'street_number' => $order->billing_number,
					'complementary' => $order->billing_address_2,
					'neighborhood'  => $order->billing_neighborhood,
					'zipcode'       => str_replace( array( '-', ' ' ), '', $order->billing_postcode )
				),
				'phone' => array(
					'ddd'    => substr( $phone, 0, 2 ),
					'number' => substr( $phone, 2 )
				)
			),
			'postback_url'    => $postback_url
		);

		// Set the document number.
		if ( isset( $order->billing_persontype ) && ! empty( $order->billing_persontype ) ) {
			if ( 1 == $order->billing_persontype ) {
				$data['customer']['document_number'] = str_replace( array( '-', '.' ), '', $order->billing_cpf );
			}

			if ( 2 == $order->billing_persontype ) {
				$data['customer']['name']            = $order->billing_company;
				$data['customer']['document_number'] = str_replace( array( '-', '.' ), '', $order->billing_cnpj );
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

		// Add filter for Third Party plugins.
		$data = apply_filters( 'wc_pagarme_transaction_data', $data );

		return $data;
	}

	/**
	 * Do the transaction.
	 *
	 * @param  WC_Order $order  Order data.
	 * @param  array    $posted Form posted data.
	 *
	 * @return array            Response data.
	 */
	protected function do_transaction( $order, $posted ) {
		$data = $this->generate_transaction_data( $order, $posted );

		// Sets the post params.
		$params = array(
			'body'      => http_build_query( $data ),
			'sslverify' => false,
			'timeout'   => 60
		);

		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Doing a transaction for order ' . $order->get_order_number() . ' with the following data: ' . print_r( $data, true ) );
		}

		$response = wp_remote_post( $this->api_url . 'transactions', $params );

		if ( is_wp_error( $response ) ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'WP_Error in doing the transaction: ' . $response->get_error_message() );
			}

			return array();
		} else {
			$transaction_data = json_decode( $response['body'], true );

			if ( isset( $transaction_data['errors'] ) ) {
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Failed to make the transaction: ' . print_r( $response, true ) );
				}

				return $transaction_data;
			}

			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Transaction completed successfully! The transaction response is: ' . print_r( $transaction_data, true ) );
			}

			return $transaction_data;
		}
	}

	/**
	 * Add error messages in checkout.
	 *
	 * @param string $messages Error message.
	 *
	 * @return string          Displays the error messages.
	 */
	protected function add_error( $messages ) {
		global $woocommerce;

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			foreach ( $messages as $message ) {
				wc_add_notice( $message['message'], 'error' );
			}
		} else {
			foreach ( $messages as $message ) {
				$woocommerce->add_error( $message['message'] );
			}
		}
	}

	/**
	 * Process the payment.
	 *
	 * @param int    $order_id Order ID.
	 *
	 * @return array           Redirect data.
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;

		$order       = new WC_Order( $order_id );
		$transaction = $this->do_transaction( $order, $_POST );

		if ( isset( $transaction['errors'] ) ) {
			$this->add_error( $transaction['errors'] );

			return array(
				'result' => 'fail'
			);
		} else {
			// Redirect to thanks page.
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
				);
			} else {
				$woocommerce->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order_id, get_permalink( woocommerce_get_page_id( 'thanks' ) ) ) )
				);
			}
		}
	}

	/**
	 * Gets the admin url.
	 *
	 * @return string
	 */
	protected function admin_url() {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_pagarme_gateway' );
		}

		return admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_PagarMe_Gateway' );
	}

	/**
	 * Adds error message when not configured the API Key.
	 *
	 * @return string Error Mensage.
	 */
	public function api_key_missing_message() {
		echo '<div class="error"><p><strong>' . __( 'Pagar.me Disabled', 'woocommerce-pagarme' ) . '</strong>: ' . sprintf( __( 'You should inform your API Key. %s', 'woocommerce-pagarme' ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', 'woocommerce-pagarme' ) . '</a>' ) . '</p></div>';
	}

	/**
	 * Adds error message when an unsupported currency is used.
	 *
	 * @return string
	 */
	public function currency_not_supported_message() {
		echo '<div class="error"><p><strong>' . __( 'Pagar.me Disabled', 'woocommerce-pagarme' ) . '</strong>: ' . sprintf( __( 'Currency <code>%s</code> is not supported. Works only with Brazilian Real.', 'woocommerce-pagarme' ), get_woocommerce_currency() ) . '</p></div>';
	}
}
