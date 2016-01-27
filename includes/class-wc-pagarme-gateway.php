<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC Pagar.me Gateway Class.
 *
 * Built the Pagar.me method.
 */
class WC_Pagarme_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                   = 'pagarme';
		$this->icon                 = apply_filters( 'wc_pagarme_icon', false );
		$this->has_fields           = true;
		$this->method_title         = __( 'Pagar.me', 'woocommerce-pagarme' );
		$this->method_description   = __( 'Accept payments by Credit Card or Banking Ticket using Pagar.me.', 'woocommerce-pagarme' );
		$this->view_transaction_url = 'https://dashboard.pagar.me/#/transactions/%s';

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
		$this->interest_rate        = $this->get_option( 'interest_rate', '0' );
		$this->free_installments    = $this->get_option( 'free_installments', '1' );
		$this->debug                = $this->get_option( 'debug' );

		// Active logs.
		if ( 'yes' == $this->debug ) {
			$this->log = new WC_Logger();
		}

		// Set the API.
		$this->api = new WC_Pagarme_API( $this );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'woocommerce_api_wc_pagarme_gateway', array( $this, 'check_ipn_response' ) );
		add_action( 'wc_pagarme_valid_ipn_request', array( $this, 'ipn_successful_request' ) );

		// Display admin notices.
		$this->admin_notices();
	}

	/**
	 * Checkout scripts.
	 */
	public function checkout_scripts() {
		if ( is_checkout() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'pagarme-library', $this->api->get_js_url(), array( 'jquery' ), null );
			wp_enqueue_script( 'pagarme-checkout', plugins_url( 'assets/js/checkout' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', 'pagarme-library' ), WC_PagarMe::VERSION, true );

			wp_localize_script(
				'pagarme-checkout',
				'wc_pagarme_params',
				array(
					'encryption_key' => $this->encryption_key
				)
			);
		}
	}

	/**
	 * Admin scripts.
	 *
	 * @param string $hook Page slug.
	 */
	public function admin_scripts( $hook ) {
		if ( in_array( $hook, array( 'woocommerce_page_wc-settings', 'woocommerce_page_woocommerce_settings' ) ) && ( isset( $_GET['section'] ) && 'wc_pagarme_gateway' == strtolower( $_GET['section'] ) ) ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( $this->id . '-admin', plugins_url( 'assets/js/admin' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), WC_PagarMe::VERSION, true );
		}
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return 'BRL' == get_woocommerce_currency();
	}

	/**
	 * Displays notifications when the admin has something wrong with the configuration.
	 */
	protected function admin_notices() {
		if ( is_admin() ) {
			$id = 'woocommerce_pagarme_';

			// Checks if api_key is not empty.
			if ( empty( $this->api_key ) || empty( $this->encryption_key ) ) {
				add_action( 'admin_notices', array( $this, 'plugin_not_configured_message' ) );
			}

			// Checks that the currency is supported.
			if ( ! $this->using_supported_currency() && ! class_exists( 'woocommerce_wpml' ) ) {
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
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-pagarme' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Pagar.me', 'woocommerce-pagarme' ),
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
					'credit' => __( 'Only Credit Card', 'woocommerce-pagarme' ),
					'ticket' => __( 'Only Banking Ticket', 'woocommerce-pagarme' ),
				)
			),
			'installments' => array(
				'title'       => __( 'Installments', 'woocommerce-pagarme' ),
				'type'        => 'title',
				'description' => ''
			),
			'max_installment' => array(
				'title'       => __( 'Number of Installment', 'woocommerce-pagarme' ),
				'type'        => 'select',
				'default'     => '12',
				'description' => __( 'Maximum number of installments possible with payments by credit card.', 'woocommerce-pagarme' ),
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
				'description' => __( 'Please enter with the value of smallest installment, Note: it not can be less than 5.', 'woocommerce-pagarme' ),
				'desc_tip'    => true,
				'default'     => '5'
			),
			'interest_rate' => array(
				'title'       => __( 'Interest rate', 'woocommerce-pagarme' ),
				'type'        => 'text',
				'description' => __( 'Please enter with the interest rate amount. Note: use 0 to not charge interest.', 'woocommerce-pagarme' ),
				'desc_tip'    => true,
				'default'     => '0'
			),
			'free_installments' => array(
				'title'       => __( 'Free Installments', 'woocommerce-pagarme' ),
				'type'        => 'select',
				'default'     => '1',
				'description' => __( 'Number of installments with interest free.', 'woocommerce-pagarme' ),
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
	 * Add error messages in checkout.
	 *
	 * @param string $messages Error message.
	 *
	 * @return string          Displays the error messages.
	 */
	protected function add_error( $messages ) {
		foreach ( $messages as $message ) {
			wc_add_notice( $message['message'], 'error' );
		}
	}

	/**
	 * Send email notification.
	 *
	 * @param  string $subject Email subject.
	 * @param  string $title   Email title.
	 * @param  string $message Email message.
	 */
	protected function send_email( $subject, $title, $message ) {
		$mailer = WC()->mailer();
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
		$order            = new WC_Order( $order_id );
		$transaciton_data = $this->api->generate_transaction_data( $order, $_POST );
		$transaction      = $this->api->do_transaction( $order, $transaciton_data );

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

			// Save boleto URL as post meta too.
			if ( ! empty( $transaction['boleto_url'] ) ) {
				update_post_meta( $order->id, __( 'Link do Boleto', 'woocommerce-pagarme' ), sanitize_text_field( $transaction['boleto_url'] ) );
			}

			// For WooCommerce 2.2 or later.
			update_post_meta( $order->id, '_transaction_id', intval( $transaction['id'] ) );

			// Save only in old versions.
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1.12', '<=' ) ) {
				update_post_meta( $order->id, __( 'Pagar.me Transaction details', 'woocommerce-pagarme' ), 'https://dashboard.pagar.me/#/transactions/' . intval( $transaction['id'] ) );
			}

			// Change the order status.
			$this->process_order_status( $order, $transaction['status'] );

			// Empty the cart.
			WC()->cart->empty_cart();

			// Redirect to thanks page.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		}
	}

	/**
	 * Payment fields.
	 *
	 * @return string
	 */
	public function payment_fields() {
		wp_enqueue_script( 'wc-credit-card-form' );

		$cart_total = 0;
		$order_id = absint( get_query_var( 'order-pay' ) );

		// Gets order total from "pay for order" page.
		if ( 0 < $order_id ) {
			$order      = new WC_Order( $order_id );
			$cart_total = (float) $order->get_total();

		// Gets order total from cart/checkout.
		} elseif ( 0 < WC()->cart->total ) {
			$cart_total = (float) WC()->cart->total;
		}

		if ( 'ticket' == $this->methods ) {
			echo '<input id="pagarme-payment-method-banking-ticket" type="hidden" name="pagarme_payment_method" value="banking-ticket" />';
		}

		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		$installments = $this->api->get_installments( $cart_total );

		if ( in_array( $this->methods, array( 'all', 'credit' ) ) ) {
			include_once 'views/html-payment-form.php';
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

		include_once 'views/html-thankyou-page.php';
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
			include_once 'views/plain-email-instructions.php';
		} else {
			include_once 'views/html-email-instructions.php';
		}
	}

	/**
	 * Check API Response.
	 */
	public function check_ipn_response() {
		@ob_clean();

		$ipn_response = ! empty( $_POST ) ? $_POST : false;

		if ( $ipn_response && $this->api->check_fingerprint( $ipn_response ) ) {
			header( 'HTTP/1.1 200 OK' );

			do_action( 'wc_pagarme_valid_ipn_request', $ipn_response );
		} else {
			wp_die( __( 'Pagar.me Request Failure', 'woocommerce-pagarme' ), '', array( 'response' => 401 ) );
		}
	}

	/**
	 * IPN successful request.
	 * This method change the order status with the IPN.
	 */
	public function ipn_successful_request( $posted ) {
		global $wpdb;

		$posted   = wp_unslash( $posted );
		$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wc_pagarme_transaction_id' AND meta_value = %d", $posted['id'] ) );
		$order    = new WC_Order( $order_id );
		$status   = sanitize_text_field( $posted['current_status'] );

		if ( $order->id == $order_id ) {
			$this->process_order_status( $order, $status );
		}

		exit;
	}

	/**
	 * Process the order status.
	 *
	 * @param  WC_Order $order  Order data.
	 * @param  string   $status Transaction status.
	 */
	public function process_order_status( $order, $status ) {
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Payment status for order ' . $order->get_order_number() . ' is now: ' . $status );
		}

		switch ( $status ) {
			case 'processing' :
				$order->update_status( 'on-hold', __( 'Pagar.me: The transaction is being processed.', 'woocommerce-pagarme' ) );

				break;
			case 'paid' :
				$order->add_order_note( __( 'Pagar.me: Transaction paid.', 'woocommerce-pagarme' ) );

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
					sprintf( __( 'The transaction for order %s was rejected by the card company or by fraud', 'woocommerce-pagarme' ), $order->get_order_number() ),
					__( 'Transaction failed', 'woocommerce-pagarme' ),
					sprintf( __( 'Order %s has been marked as failed, because the transaction was rejected by the card company or by fraud, for more details, see %s.', 'woocommerce-pagarme' ), $order->get_order_number(), $transaction_url )
				);

				break;
			case 'refunded' :
				$order->update_status( 'refunded', __( 'Pagar.me: The transaction was refunded/canceled.', 'woocommerce-pagarme' ) );

				$transaction_id  = get_post_meta( $order->id, '_wc_pagarme_transaction_id', true );
				$transaction_url = '<a href="https://dashboard.pagar.me/#/transactions/' . intval( $transaction_id ) . '">https://dashboard.pagar.me/#/transactions/' . intval( $transaction_id ) . '</a>';

				$this->send_email(
					sprintf( __( 'The transaction for order %s refunded', 'woocommerce-pagarme' ), $order->get_order_number() ),
					__( 'Transaction refunded', 'woocommerce-pagarme' ),
					sprintf( __( 'Order %s has been marked as refunded by Pagar.me, for more details, see %s.', 'woocommerce-pagarme' ), $order->get_order_number(), $transaction_url )
				);

				break;

			default :
				// No action xD.
				break;
		}
	}

	/**
	 * Adds error message when the plugin is not configured properly.
	 *
	 * @return string Error Mensage.
	 */
	public function plugin_not_configured_message() {
		$id = 'woocommerce_pagarme_';
		if (
			isset( $_POST[ $id . 'api_key' ] ) && ! empty( $_POST[ $id . 'api_key' ] )
			&& isset( $_POST[ $id . 'encryption_key' ] ) && ! empty( $_POST[ $id . 'encryption_key' ] )
		) {
			return;
		}

		echo '<div class="error"><p><strong>' . __( 'Pagar.me Disabled', 'woocommerce-pagarme' ) . '</strong>: ' . sprintf( __( 'You should inform your API Key and Encryption Key. %s', 'woocommerce-pagarme' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_pagarme_gateway' ) . '">' . __( 'Click here to configure!', 'woocommerce-pagarme' ) . '</a>' ) . '</p></div>';
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
