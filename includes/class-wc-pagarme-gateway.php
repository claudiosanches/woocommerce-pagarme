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
		$this->title                = $this->get_option( 'title' );
		$this->description          = $this->get_option( 'description' );
		$this->api_key              = $this->get_option( 'api_key' );
		$this->encryption_key       = $this->get_option( 'encryption_key' );
		$this->methods              = $this->get_option( 'methods' );
		$this->max_installment      = $this->get_option( 'max_installment' );
		$this->smallest_installment = $this->get_option( 'smallest_installment' );
		$this->debug                = $this->get_option( 'debug' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );
		add_action( 'woocommerce_api_wc_pagarme_gateway', array( $this, 'check_ipn_response' ) );
		add_action( 'wc_pagarme_valid_ipn_request', array( $this, 'ipn_successful_request' ) );

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
	 * Checkout scripts.
	 *
	 * @return void
	 */
	public function checkout_scripts() {
		if ( is_checkout() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( $this->id . '-library', 'https://pagar.me/assets/pagarme.js', array( 'jquery' ), null );
			wp_enqueue_script( $this->id . '-checkout', plugins_url( 'assets/js/checkout' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', $this->id . '-library' ), WC_PagarMe::VERSION, true );

			wp_localize_script(
				$this->id . '-checkout',
				'wc_' . $this->id . '_params',
				array(
					'encryption_key' => $this->encryption_key
				)
			);
		}
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
			$id = 'woocommerce_' . $this->id . '_';

			// Checks if api_key is not empty.
			if ( empty( $this->api_key ) || empty( $this->encryption_key ) ) {
				add_action( 'admin_notices', array( $this, 'plugin_not_configured_message' ) );
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
		$available = parent::is_available() && 'yes' == $this->get_option( 'enabled' ) && ! empty( $this->api_key ) && ! empty( $this->encryption_key ) && $this->using_supported_currency();

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
				'desc_tip'    => true,
				'default'     => __( 'Pay with Credit Card or Banking Ticket via Pagar.me', 'woocommerce-pagarme' )
			),
			'api_key' => array(
				'title'       => __( 'Pagar.me API Key', 'woocommerce-pagarme' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Please enter your Pagar.me API Key. This is needed to process the payment and notifications. Is possible get your API Key in %s.', 'woocommerce-pagarme' ), '<a href="https://dashboard.pagar.me/">' . __( 'Pagar.me Dashboard > My Account page', 'woocommerce-pagarme' ) . '</a>' ),
				'default'     => ''
			),
			'encryption_key' => array(
				'title'       => __( 'Pagar.me Encryption Key', 'woocommerce-pagarme' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Please enter your Pagar.me Encryption key. This is needed to process the payment. Is possible get your Encryption Key in %s.', 'woocommerce-pagarme' ), '<a href="https://dashboard.pagar.me/">' . __( 'Pagar.me Dashboard > My Account page', 'woocommerce-pagarme' ) . '</a>' ),
				'default'     => ''
			),
			'methods' => array(
				'title'   => __( 'Payment Methods', 'woocommerce-pagarme' ),
				'type'    => 'select',
				'default' => 'all',
				'options' => array(
					'all'    => __( 'Credit Card and Banking Ticket', 'woocommerce-pagarme' ),
					'credit' => __( ' Only Credit Card', 'woocommerce-pagarme' ),
					'ticket' => __( ' Only Banking Ticket', 'woocommerce-pagarme' ),
				)
			),
			'installments' => array(
				'title'       => __( 'Installments', 'woocommerce-pagarme' ),
				'type'        => 'title',
				'description' => ''
			),
			'max_installment' => array(
				'title'       => __( 'Maximum Installment', 'woocommerce-pagarme' ),
				'type'        => 'select',
				'default'     => '12',
				'description' => __( 'Credit Card maximum installment.', 'woocommerce-pagarme' ),
				'desc_tip'    => true,
				'options'     => array(
					'1'  => '1',
					'2'  => '2',
					'3'  => '3',
					'4'  => '4',
					'5'  => '5',
					'6'  => '6',
					'7'  => '7',
					'8'  => '8',
					'9'  => '9',
					'10' => '10',
					'11' => '11',
					'12' => '12',
				)
			),
			'smallest_installment' => array(
				'title'       => __( 'Smallest Installment', 'woocommerce-pagarme' ),
				'type'        => 'text',
				'description' => __( 'Please enter with the value of smallest installment, Note: it not can be less than 5', 'woocommerce-pagarme' ),
				'desc_tip'    => true,
				'default'     => '5'
			),
			'testing' => array(
				'title'       => __( 'Gateway Testing', 'woocommerce-pagarme' ),
				'type'        => 'title',
				'description' => ''
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
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			$postback_url = WC()->api_request_url( 'WC_PagarMe_Gateway' );
		} else {
			$postback_url = $woocommerce->api_request_url( 'WC_PagarMe_Gateway' );
		}

		$phone = $this->only_numbers( $order->billing_phone );

		// Set the request data.
		$data = array(
			'api_key'        => $this->api_key,
			'amount'         => number_format( $order->order_total, 2, '', '' ),
			'postback_url'   => $postback_url,
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
		if ( isset( $order->billing_persontype ) && ! empty( $order->billing_persontype ) ) {
			if ( 1 == $order->billing_persontype ) {
				$data['customer']['document_number'] = $this->only_numbers( $order->billing_cpf );
			}

			if ( 2 == $order->billing_persontype ) {
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

		if ( ( 'all' == $this->methods || 'credit' == $this->methods ) && 'credit-card' == $posted[ $this->id . '_payment_method' ] ) {
			if ( isset( $posted[ $this->id . '_card_hash' ] ) ) {
				$data['payment_method'] = 'credit_card';
				$data['card_hash']      = $posted[ $this->id . '_card_hash' ];
			}

			// Validate the installments.
			if ( isset( $posted[ $this->id . '_installments' ] ) ) {
				$installment          = $posted[ $this->id . '_installments' ];
				$smallest_installment = ( 5 > $this->smallest_installment ) ? 500 : number_format( $this->smallest_installment, 2, '', '' );
				$installment_total    = number_format( $order->order_total, 2, '', '' ) / $installment;

				if ( $installment <= $this->max_installment && $smallest_installment <= $installment_total ) {
					$data['installments'] = $installment;
				}
			}
		} elseif ( ( 'all' == $this->methods || 'ticket' == $this->methods ) && 'banking-ticket' == $posted[ $this->id . '_payment_method' ] ) {
			$data['payment_method'] = 'boleto';
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
			$this->log->add( $this->id, 'Doing a transaction for order ' . $order->get_order_number() . '...' );
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
	 * Send email notification.
	 *
	 * @param  string $subject Email subject.
	 * @param  string $title   Email title.
	 * @param  string $message Email message.
	 *
	 * @return void
	 */
	protected function send_email( $subject, $title, $message ) {
		global $woocommerce;

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			$mailer = WC()->mailer();
		} else {
			$mailer = $woocommerce->mailer();
		}

		$mailer->send( get_option( 'admin_email' ), $subject, $mailer->wrap_message( $title, $message ) );
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
			update_post_meta( $order->id, __( 'Pagar.me Transaction details', 'woocommerce-pagarme' ), 'https://dashboard.pagar.me/#/transactions/' . intval( $transaction['id'] ) );

			$this->process_order_status( $order, $transaction['status'] );

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
	 * Process the order status.
	 *
	 * @param  WC_Order $order  Order data.
	 * @param  string   $status Transaction status.
	 *
	 * @return void
	 */
	public function process_order_status( $order, $status ) {
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Payment status for order ' . $order->get_order_number() . ' is now: ' . $status );
		}

		switch ( $status ) {
			case 'processing':
				$order->update_status( 'on-hold', __( 'Pagar.me: The transaction is being processed.', 'woocommerce-pagarme' ) );

				break;
			case 'paid':
				$order->add_order_note( __( 'Pagar.me: Transaction paid.', 'woocommerce-pagarme' ) );

				// Changing the order for processing and reduces the stock.
				$order->payment_complete();

				break;
			case 'waiting_payment':
				$order->update_status( 'on-hold', __( 'Pagar.me: The banking ticket was issued but not paid yet.', 'woocommerce-pagarme' ) );

				break;
			case 'refused':
				$order->update_status( 'failed', __( 'Pagar.me: The transaction was rejected by the card company or by fraud.', 'woocommerce-pagarme' ) );

				$transaction_id  = get_post_meta( $order->id, '_wc_pagarme_transaction_id', true );
				$transaction_url = '<a href="https://dashboard.pagar.me/#/transactions/' . intval( $transaction_id ) . '">https://dashboard.pagar.me/#/transactions/' . intval( $transaction_id ) . '</a>';

				$this->send_email(
					sprintf( __( 'The transaction for order %s was rejected by the card company or by fraud', 'woocommerce-pagarme' ), $order->get_order_number() ),
					__( 'Transaction failed', 'woocommerce-pagarme' ),
					sprintf( __( 'Order %s has been marked as failed, because the transaction was rejected by the card company or by fraud, for more details, see %s.', 'woocommerce-pagarme' ), $order->get_order_number(), $transaction_url )
				);

				break;
			case 'refunded':
				$order->update_status( 'refunded', __( 'Pagar.me: The transaction was refunded/canceled.', 'woocommerce-pagarme' ) );

				$transaction_id  = get_post_meta( $order->id, '_wc_pagarme_transaction_id', true );
				$transaction_url = '<a href="https://dashboard.pagar.me/#/transactions/' . intval( $transaction_id ) . '">https://dashboard.pagar.me/#/transactions/' . intval( $transaction_id ) . '</a>';

				$this->send_email(
					sprintf( __( 'The transaction for order %s refunded', 'woocommerce-pagarme' ), $order->get_order_number() ),
					__( 'Transaction refunded', 'woocommerce-pagarme' ),
					sprintf( __( 'Order %s has been marked as refunded by Pagar.me, for more details, see %s.', 'woocommerce-pagarme' ), $order->get_order_number(), $transaction_url )
				);

				break;

			default:
				// No action xD.
				break;
		}
	}

	/**
	 * Payment fields.
	 *
	 * @return string
	 */
	public function payment_fields() {
		global $woocommerce;

		wp_enqueue_script( 'wc-credit-card-form' );

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			$cart_total = (float) WC()->cart->total;
		} else {
			$cart_total = (float) $woocommerce->cart->total;
		}

		if ( 'ticket' == $this->methods ) {
			echo '<input id="' . esc_attr( $this->id ) . '-payment-method-banking-ticket" type="hidden" name="' . $this->id . '_payment_method" value="banking-ticket" />';
		}

		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		if ( 'all' == $this->methods || 'credit' == $this->methods ) {
			include_once( 'views/html-payment-form.php' );
		}
	}

	/**
	 * Thank You page message.
	 *
	 * @param  int    $order_id Order ID.
	 *
	 * @return string
	 */
	public function thankyou_page( $order_id ) {
		$data = get_post_meta( $order_id, '_wc_pagarme_transaction_data', true );

		include_once( 'views/html-thankyou-page.php' );
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param  object $order         Order object.
	 * @param  bool   $sent_to_admin Send to admin.
	 * @param  bool   $plain_text    Plain text or HTML.
	 *
	 * @return string                Banking Ticket instructions.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text ) {
		if ( $sent_to_admin || 'on-hold' !== $order->status || $this->id !== $order->payment_method ) {
			return;
		}

		$data = get_post_meta( $order->id, '_wc_pagarme_transaction_data', true );

		if ( $plain_text ) {
			include_once( 'views/plain-email-instructions.php' );
		} else {
			include_once( 'views/html-email-instructions.php' );
		}
	}

	/**
	 * Check if Pagar.me response is validity.
	 *
	 * @param  array $ipn_response IPN response data.
	 *
	 * @return bool
	 */
	public function check_ipn_request_is_valid( $ipn_response ) {
		if ( isset( $ipn_response['id'] ) && isset( $ipn_response['current_status'] ) && isset( $ipn_response['fingerprint'] ) ) {
			$fingerprint = sha1( $ipn_response['id'] . '#' . $this->api_key );

			if ( $fingerprint === $ipn_response['fingerprint'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * IPN successful request.
	 * This method change the order status with the IPN.
	 *
	 * @return void
	 */
	public function ipn_successful_request( $posted ) {
		global $wpdb;

		$posted   = stripslashes_deep( $posted );
		$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wc_pagarme_transaction_id' AND meta_value = %d", $posted['id'] ) );
		$order    = new WC_Order( $order_id );
		$status   = sanitize_text_field( $posted['current_status'] );

		if ( $order->id === $order_id ) {
			$this->process_order_status( $order, $status );
		}

		exit;
	}

	/**
	 * Check API Response.
	 *
	 * @return void
	 */
	public function check_ipn_response() {
		@ob_clean();

		$ipn_response = ! empty( $_POST ) ? $_POST : false;

		if ( $ipn_response && $this->check_ipn_request_is_valid( $ipn_response ) ) {
			header( 'HTTP/1.1 200 OK' );

			do_action( 'wc_pagarme_valid_ipn_request', $ipn_response );
		} else {
			wp_die( __( 'Pagar.me Request Failure', 'woocommerce-pagseguro' ), '', array( 'response' => 401 ) );
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
	 * Adds error message when the plugin is not configured properly.
	 *
	 * @return string Error Mensage.
	 */
	public function plugin_not_configured_message() {
		$id = 'woocommerce_' . $this->id . '_';
		if (
			isset( $_POST[ $id . 'api_key' ] ) && ! empty( $_POST[ $id . 'api_key' ] )
			&& isset( $_POST[ $id . 'encryption_key' ] ) && ! empty( $_POST[ $id . 'encryption_key' ] )
		) {
			return;
		}

		echo '<div class="error"><p><strong>' . __( 'Pagar.me Disabled', 'woocommerce-pagarme' ) . '</strong>: ' . sprintf( __( 'You should inform your API Key and Encryption Key. %s', 'woocommerce-pagarme' ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', 'woocommerce-pagarme' ) . '</a>' ) . '</p></div>';
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
