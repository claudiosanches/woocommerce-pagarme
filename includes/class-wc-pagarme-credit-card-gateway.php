<?php
/**
 * Pagar.me Credit Card gateway
 *
 * @package WooCommerce_Pagarme/Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Pagarme_Credit_Card_Gateway class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Pagarme_Credit_Card_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                   = 'pagarme-credit-card';
		$this->icon                 = apply_filters( 'wc_pagarme_credit_card_icon', false );
		$this->has_fields           = true;
		$this->method_title         = __( 'Pagar.me - Credit Card', 'woocommerce-pagarme' );
		$this->method_description   = __( 'Accept credit card payments using Pagar.me.', 'woocommerce-pagarme' );
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
		$this->checkout             = $this->get_option( 'checkout' );
		$this->max_installment      = $this->get_option( 'max_installment' );
		$this->smallest_installment = $this->get_option( 'smallest_installment' );
		$this->interest_rate        = $this->get_option( 'interest_rate', '0' );
		$this->free_installments    = $this->get_option( 'free_installments', '1' );
		$this->debug                = $this->get_option( 'debug' );

		// Active logs.
		if ( 'yes' === $this->debug ) {
			$this->log = new WC_Logger();
		}

		// Set the API.
		$this->api = new WC_Pagarme_API( $this );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'woocommerce_api_wc_pagarme_credit_card_gateway', array( $this, 'ipn_handler' ) );
	}

	/**
	 * Admin page.
	 */
	public function admin_options() {
		include dirname( __FILE__ ) . '/admin/views/html-admin-page.php';
	}

	/**
	 * Check if the gateway is available to take payments.
	 *
	 * @return bool
	 */
	public function is_available() {
		return parent::is_available() && ! empty( $this->api_key ) && ! empty( $this->encryption_key ) && $this->api->using_supported_currency();
	}

	/**
	 * Settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-pagarme' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Pagar.me Credit Card', 'woocommerce-pagarme' ),
				'default' => 'no',
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-pagarme' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-pagarme' ),
				'desc_tip'    => true,
				'default'     => __( 'Credit Card', 'woocommerce-pagarme' ),
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-pagarme' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-pagarme' ),
				'desc_tip'    => true,
				'default'     => __( 'Pay with Credit Card', 'woocommerce-pagarme' ),
			),
			'integration' => array(
				'title'       => __( 'Integration Settings', 'woocommerce-pagarme' ),
				'type'        => 'title',
				'description' => '',
			),
			'api_key' => array(
				'title'             => __( 'Pagar.me API Key', 'woocommerce-pagarme' ),
				'type'              => 'text',
				'description'       => sprintf( __( 'Please enter your Pagar.me API Key. This is needed to process the payment and notifications. Is possible get your API Key in %s.', 'woocommerce-pagarme' ), '<a href="https://dashboard.pagar.me/">' . __( 'Pagar.me Dashboard > My Account page', 'woocommerce-pagarme' ) . '</a>' ),
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required',
				),
			),
			'encryption_key' => array(
				'title'             => __( 'Pagar.me Encryption Key', 'woocommerce-pagarme' ),
				'type'              => 'text',
				'description'       => sprintf( __( 'Please enter your Pagar.me Encryption key. This is needed to process the payment. Is possible get your Encryption Key in %s.', 'woocommerce-pagarme' ), '<a href="https://dashboard.pagar.me/">' . __( 'Pagar.me Dashboard > My Account page', 'woocommerce-pagarme' ) . '</a>' ),
				'default'           => '',
				'custom_attributes' => array(
					'required' => 'required',
				),
			),
			'checkout' => array(
				'title'       => __( 'Checkout Pagar.me', 'woocommerce-pagarme' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable checkout Pagar.me', 'woocommerce-pagarme' ),
				'default'     => 'no',
				'desc_tip'    => true,
				'description' => __( "When enabled opens a Pagar.me modal window to receive the customer's credit card information.", 'woocommerce-pagarme' ),
			),
			'installments' => array(
				'title'       => __( 'Installments', 'woocommerce-pagarme' ),
				'type'        => 'title',
				'description' => '',
			),
			'max_installment' => array(
				'title'       => __( 'Number of Installment', 'woocommerce-pagarme' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
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
				),
			),
			'smallest_installment' => array(
				'title'       => __( 'Smallest Installment', 'woocommerce-pagarme' ),
				'type'        => 'text',
				'description' => __( 'Please enter with the value of smallest installment, Note: it not can be less than 5.', 'woocommerce-pagarme' ),
				'desc_tip'    => true,
				'default'     => '5',
			),
			'interest_rate' => array(
				'title'       => __( 'Interest rate', 'woocommerce-pagarme' ),
				'type'        => 'text',
				'description' => __( 'Please enter with the interest rate amount. Note: use 0 to not charge interest.', 'woocommerce-pagarme' ),
				'desc_tip'    => true,
				'default'     => '0',
			),
			'free_installments' => array(
				'title'       => __( 'Free Installments', 'woocommerce-pagarme' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'default'     => '1',
				'description' => __( 'Number of installments with interest free.', 'woocommerce-pagarme' ),
				'desc_tip'    => true,
				'options'     => array(
					'0'  => _x( 'None', 'no free installments', 'woocommerce-pagarme' ),
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
				),
			),
			'testing' => array(
				'title'       => __( 'Gateway Testing', 'woocommerce-pagarme' ),
				'type'        => 'title',
				'description' => '',
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'woocommerce-pagarme' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce-pagarme' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Pagar.me events, such as API requests. You can check the log in %s', 'woocommerce-pagarme' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'System Status &gt; Logs', 'woocommerce-pagarme' ) . '</a>' ),
			),
		);
	}

	/**
	 * Checkout scripts.
	 */
	public function checkout_scripts() {
		if ( is_checkout() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			if ( 'yes' === $this->checkout ) {
				$customer = array();

				wp_enqueue_script( 'pagarme-checkout-library', $this->api->get_checkout_js_url(), array( 'jquery' ), null );
				wp_enqueue_script( 'pagarme-checkout', plugins_url( 'assets/js/checkout' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', 'jquery-blockui', 'pagarme-checkout-library' ), WC_Pagarme::VERSION, true );

				if ( is_checkout_pay_page() ) {
					$customer = $this->api->get_customer_data_from_checkout_pay_page();
				}

				wp_localize_script(
					'pagarme-checkout',
					'wcPagarmeParams',
					array(
						'encryptionKey'    => $this->encryption_key,
						'interestRate'     => $this->api->get_interest_rate(),
						'freeInstallments' => $this->free_installments,
						'postbackUrl'      => WC()->api_request_url( get_class( $this ) ),
						'customerFields'   => $customer,
						'checkoutPayPage'  => ! empty( $customer ),
						'uiColor'          => apply_filters( 'wc_pagarme_checkout_ui_color', '#1a6ee1' ),
					)
				);
			} else {
				wp_enqueue_script( 'wc-credit-card-form' );
				wp_enqueue_script( 'pagarme-library', $this->api->get_js_url(), array( 'jquery' ), null );
				wp_enqueue_script( 'pagarme-credit-card', plugins_url( 'assets/js/credit-card' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', 'jquery-blockui', 'pagarme-library' ), WC_Pagarme::VERSION, true );

				wp_localize_script(
					'pagarme-credit-card',
					'wcPagarmeParams',
					array(
						'encryptionKey' => $this->encryption_key,
					)
				);
			}
		}
	}

	/**
	 * Payment fields.
	 */
	public function payment_fields() {
		if ( $description = $this->get_description() ) {
			echo wp_kses_post( wpautop( wptexturize( $description ) ) );
		}

		$cart_total = $this->get_order_total();

		if ( 'no' === $this->checkout ) {
			$installments = $this->api->get_installments( $cart_total );

			wc_get_template(
				'credit-card/payment-form.php',
				array(
					'cart_total'           => $cart_total,
					'max_installment'      => $this->max_installment,
					'smallest_installment' => $this->api->get_smallest_installment(),
					'installments'         => $installments,
				),
				'woocommerce/pagarme/',
				WC_Pagarme::get_templates_path()
			);
		} else {
			echo '<div id="pagarme-checkout-params" ';
			echo 'data-total="' . esc_attr( $cart_total * 100 ) . '" ';
			echo 'data-max_installment="' . esc_attr( apply_filters( 'wc_pagarme_checkout_credit_card_max_installments', $this->api->get_max_installment( $cart_total ) ) ) . '"';
			echo '></div>';
		}
	}

	/**
	 * Process the payment.
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array Redirect data.
	 */
	public function process_payment( $order_id ) {
		return $this->api->process_regular_payment( $order_id );
	}

	/**
	 * Thank You page message.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		$data  = get_post_meta( $order_id, '_wc_pagarme_transaction_data', true );

		if ( isset( $data['installments'] ) && in_array( $order->get_status(), array( 'processing', 'on-hold' ), true ) ) {
			wc_get_template(
				'credit-card/payment-instructions.php',
				array(
					'card_brand'   => $data['card_brand'],
					'installments' => $data['installments'],
				),
				'woocommerce/pagarme/',
				WC_Pagarme::get_templates_path()
			);
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param  object $order         Order object.
	 * @param  bool   $sent_to_admin Send to admin.
	 * @param  bool   $plain_text    Plain text or HTML.
	 *
	 * @return string                Payment instructions.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $sent_to_admin || ! in_array( $order->get_status(), array( 'processing', 'on-hold' ), true ) || $this->id !== $order->payment_method ) {
			return;
		}

		$data = get_post_meta( $order->id, '_wc_pagarme_transaction_data', true );

		if ( isset( $data['installments'] ) ) {
			$email_type = $plain_text ? 'plain' : 'html';

			wc_get_template(
				'credit-card/emails/' . $email_type . '-instructions.php',
				array(
					'card_brand'   => $data['card_brand'],
					'installments' => $data['installments'],
				),
				'woocommerce/pagarme/',
				WC_Pagarme::get_templates_path()
			);
		}
	}

	/**
	 * IPN handler.
	 */
	public function ipn_handler() {
		$this->api->ipn_handler();
	}
}
