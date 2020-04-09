<?php
/**
 * Pagar.me Banking Ticket gateway
 *
 * @package WooCommerce_Pagarme/Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Pagarme_Banking_Ticket_Gateway class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Pagarme_Banking_Ticket_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                   = 'pagarme-banking-ticket';
		$this->icon                 = apply_filters( 'wc_pagarme_banking_ticket_icon', false );
		$this->has_fields           = true;
		$this->method_title         = __( 'Pagar.me - Banking Ticket', 'woocommerce-pagarme' );
		$this->method_description   = __( 'Accept banking ticket payments using Pagar.me.', 'woocommerce-pagarme' );
		$this->view_transaction_url = 'https://dashboard.pagar.me/#/transactions/%s';

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->title          = $this->get_option( 'title' );
		$this->description    = $this->get_option( 'description' );
		$this->api_key        = $this->get_option( 'api_key' );
		$this->encryption_key = $this->get_option( 'encryption_key' );
		$this->debug          = $this->get_option( 'debug' );
		$this->async          = $this->get_option( 'async' );
		$this->feriados		  = $this->get_option( 'feriados' );
		$this->dias_vencimento = $this->get_option( 'dias_vencimento_boleto' );

		// Active logs.
		if ( 'yes' === $this->debug ) {
			$this->log = new WC_Logger();
		}

		// Set the API.
		$this->api = new WC_Pagarme_API( $this );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'woocommerce_api_wc_pagarme_banking_ticket_gateway', array( $this, 'ipn_handler' ) );
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
				'label'   => __( 'Enable Pagar.me Banking Ticket', 'woocommerce-pagarme' ),
				'default' => 'no',
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-pagarme' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-pagarme' ),
				'desc_tip'    => true,
				'default'     => __( 'Banking Ticket', 'woocommerce-pagarme' ),
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-pagarme' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-pagarme' ),
				'desc_tip'    => true,
				'default'     => __( 'Pay with Banking Ticket', 'woocommerce-pagarme' ),
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
			'feriados'      => array(
        		'type'              => 'multiselect',
        		'title'             => __('Feriados', 'woocommerce-pagarme'),
        		'desc_tip'          => __( 'Selecione os dias do ano que você deseja que o plugin evite gerar datas de vencimento para aumentar a conversão.', 'woocommerce-pagarme' ),
        		'options'           =>__( array('01/01' => '01/01',
                                        '02/01' => '02/01',
                                        '03/01' => '03/01',
                                        '04/01' => '04/01',
                                        '05/01' => '05/01',
                                        '06/01' => '06/01',
                                        '07/01' => '07/01',
                                        '08/01' => '08/01',
                                        '09/01' => '09/01',
                                        '10/01' => '10/01',
                                        '11/01' => '11/01',
                                        '12/01' => '12/01',
                                        '13/01' => '13/01',
                                        '14/01' => '14/01',
                                        '15/01' => '15/01',
                                        '16/01' => '16/01',
                                        '17/01' => '17/01',
                                        '18/01' => '18/01',
                                        '19/01' => '19/01',
                                        '20/01' => '20/01',
                                        '21/01' => '21/01',
                                        '22/01' => '22/01',
                                        '23/01' => '23/01',
                                        '24/01' => '24/01',
                                        '25/01' => '25/01',
                                        '26/01' => '26/01',
                                        '27/01' => '27/01',
                                        '28/01' => '28/01',
                                        '29/01' => '29/01',
                                        '30/01' => '30/01',
                                        '31/01' => '31/01',
                                        '01/02' => '01/02',
                                        '02/02' => '02/02',
                                        '03/02' => '03/02',
                                        '04/02' => '04/02',
                                        '05/02' => '05/02',
                                        '06/02' => '06/02',
                                        '07/02' => '07/02',
                                        '08/02' => '08/02',
                                        '09/02' => '09/02',
                                        '10/02' => '10/02',
                                        '11/02' => '11/02',
                                        '12/02' => '12/02',
                                        '13/02' => '13/02',
                                        '14/02' => '14/02',
                                        '15/02' => '15/02',
                                        '16/02' => '16/02',
                                        '17/02' => '17/02',
                                        '18/02' => '18/02',
                                        '19/02' => '19/02',
                                        '20/02' => '20/02',
                                        '21/02' => '21/02',
                                        '22/02' => '22/02',
                                        '23/02' => '23/02',
                                        '24/02' => '24/02',
                                        '25/02' => '25/02',
                                        '26/02' => '26/02',
                                        '27/02' => '27/02',
                                        '28/02' => '28/02',
                                        '29/02' => '29/02',
                                        '01/03' => '01/03',
                                        '02/03' => '02/03',
                                        '03/03' => '03/03',
                                        '04/03' => '04/03',
                                        '05/03' => '05/03',
                                        '06/03' => '06/03',
                                        '07/03' => '07/03',
                                        '08/03' => '08/03',
                                        '09/03' => '09/03',
                                        '10/03' => '10/03',
                                        '11/03' => '11/03',
                                        '12/03' => '12/03',
                                        '13/03' => '13/03',
                                        '14/03' => '14/03',
                                        '15/03' => '15/03',
                                        '16/03' => '16/03',
                                        '17/03' => '17/03',
                                        '18/03' => '18/03',
                                        '19/03' => '19/03',
                                        '20/03' => '20/03',
                                        '21/03' => '21/03',
                                        '22/03' => '22/03',
                                        '23/03' => '23/03',
                                        '24/03' => '24/03',
                                        '25/03' => '25/03',
                                        '26/03' => '26/03',
                                        '27/03' => '27/03',
                                        '28/03' => '28/03',
                                        '29/03' => '29/03',
                                        '30/03' => '30/03',
                                        '31/03' => '31/03',
                                        '01/04' => '01/04',
                                        '02/04' => '02/04',
                                        '03/04' => '03/04',
                                        '04/04' => '04/04',
                                        '05/04' => '05/04',
                                        '06/04' => '06/04',
                                        '07/04' => '07/04',
                                        '08/04' => '08/04',
                                        '09/04' => '09/04',
                                        '10/04' => '10/04',
                                        '11/04' => '11/04',
                                        '12/04' => '12/04',
                                        '13/04' => '13/04',
                                        '14/04' => '14/04',
                                        '15/04' => '15/04',
                                        '16/04' => '16/04',
                                        '17/04' => '17/04',
                                        '18/04' => '18/04',
                                        '19/04' => '19/04',
                                        '20/04' => '20/04',
                                        '21/04' => '21/04',
                                        '22/04' => '22/04',
                                        '23/04' => '23/04',
                                        '24/04' => '24/04',
                                        '25/04' => '25/04',
                                        '26/04' => '26/04',
                                        '27/04' => '27/04',
                                        '28/04' => '28/04',
                                        '29/04' => '29/04',
                                        '30/04' => '30/04',
                                        '01/05' => '01/05',
                                        '02/05' => '02/05',
                                        '03/05' => '03/05',
                                        '04/05' => '04/05',
                                        '05/05' => '05/05',
                                        '06/05' => '06/05',
                                        '07/05' => '07/05',
                                        '08/05' => '08/05',
                                        '09/05' => '09/05',
                                        '10/05' => '10/05',
                                        '11/05' => '11/05',
                                        '12/05' => '12/05',
                                        '13/05' => '13/05',
                                        '14/05' => '14/05',
                                        '15/05' => '15/05',
                                        '16/05' => '16/05',
                                        '17/05' => '17/05',
                                        '18/05' => '18/05',
                                        '19/05' => '19/05',
                                        '20/05' => '20/05',
                                        '21/05' => '21/05',
                                        '22/05' => '22/05',
                                        '23/05' => '23/05',
                                        '24/05' => '24/05',
                                        '25/05' => '25/05',
                                        '26/05' => '26/05',
                                        '27/05' => '27/05',
                                        '28/05' => '28/05',
                                        '29/05' => '29/05',
                                        '30/05' => '30/05',
                                        '31/05' => '31/05',
                                        '01/06' => '01/06',
                                        '02/06' => '02/06',
                                        '03/06' => '03/06',
                                        '04/06' => '04/06',
                                        '05/06' => '05/06',
                                        '06/06' => '06/06',
                                        '07/06' => '07/06',
                                        '08/06' => '08/06',
                                        '09/06' => '09/06',
                                        '10/06' => '10/06',
                                        '11/06' => '11/06',
                                        '12/06' => '12/06',
                                        '13/06' => '13/06',
                                        '14/06' => '14/06',
                                        '15/06' => '15/06',
                                        '16/06' => '16/06',
                                        '17/06' => '17/06',
                                        '18/06' => '18/06',
                                        '19/06' => '19/06',
                                        '20/06' => '20/06',
                                        '21/06' => '21/06',
                                        '22/06' => '22/06',
                                        '23/06' => '23/06',
                                        '24/06' => '24/06',
                                        '25/06' => '25/06',
                                        '26/06' => '26/06',
                                        '27/06' => '27/06',
                                        '28/06' => '28/06',
                                        '29/06' => '29/06',
                                        '30/06' => '30/06',
                                        '01/07' => '01/07',
                                        '02/07' => '02/07',
                                        '03/07' => '03/07',
                                        '04/07' => '04/07',
                                        '05/07' => '05/07',
                                        '06/07' => '06/07',
                                        '07/07' => '07/07',
                                        '08/07' => '08/07',
                                        '09/07' => '09/07',
                                        '10/07' => '10/07',
                                        '11/07' => '11/07',
                                        '12/07' => '12/07',
                                        '13/07' => '13/07',
                                        '14/07' => '14/07',
                                        '15/07' => '15/07',
                                        '16/07' => '16/07',
                                        '17/07' => '17/07',
                                        '18/07' => '18/07',
                                        '19/07' => '19/07',
                                        '20/07' => '20/07',
                                        '21/07' => '21/07',
                                        '22/07' => '22/07',
                                        '23/07' => '23/07',
                                        '24/07' => '24/07',
                                        '25/07' => '25/07',
                                        '26/07' => '26/07',
                                        '27/07' => '27/07',
                                        '28/07' => '28/07',
                                        '29/07' => '29/07',
                                        '30/07' => '30/07',
                                        '31/07' => '31/07',
                                        '01/08' => '01/08',
                                        '02/08' => '02/08',
                                        '03/08' => '03/08',
                                        '04/08' => '04/08',
                                        '05/08' => '05/08',
                                        '06/08' => '06/08',
                                        '07/08' => '07/08',
                                        '08/08' => '08/08',
                                        '09/08' => '09/08',
                                        '10/08' => '10/08',
                                        '11/08' => '11/08',
                                        '12/08' => '12/08',
                                        '13/08' => '13/08',
                                        '14/08' => '14/08',
                                        '15/08' => '15/08',
                                        '16/08' => '16/08',
                                        '17/08' => '17/08',
                                        '18/08' => '18/08',
                                        '19/08' => '19/08',
                                        '20/08' => '20/08',
                                        '21/08' => '21/08',
                                        '22/08' => '22/08',
                                        '23/08' => '23/08',
                                        '24/08' => '24/08',
                                        '25/08' => '25/08',
                                        '26/08' => '26/08',
                                        '27/08' => '27/08',
                                        '28/08' => '28/08',
                                        '29/08' => '29/08',
                                        '30/08' => '30/08',
                                        '31/08' => '31/08',
                                        '01/09' => '01/09',
                                        '02/09' => '02/09',
                                        '03/09' => '03/09',
                                        '04/09' => '04/09',
                                        '05/09' => '05/09',
                                        '06/09' => '06/09',
                                        '07/09' => '07/09',
                                        '08/09' => '08/09',
                                        '09/09' => '09/09',
                                        '10/09' => '10/09',
                                        '11/09' => '11/09',
                                        '12/09' => '12/09',
                                        '13/09' => '13/09',
                                        '14/09' => '14/09',
                                        '15/09' => '15/09',
                                        '16/09' => '16/09',
                                        '17/09' => '17/09',
                                        '18/09' => '18/09',
                                        '19/09' => '19/09',
                                        '20/09' => '20/09',
                                        '21/09' => '21/09',
                                        '22/09' => '22/09',
                                        '23/09' => '23/09',
                                        '24/09' => '24/09',
                                        '25/09' => '25/09',
                                        '26/09' => '26/09',
                                        '27/09' => '27/09',
                                        '28/09' => '28/09',
                                        '29/09' => '29/09',
                                        '30/09' => '30/09',
                                        '01/10' => '01/10',
                                        '02/10' => '02/10',
                                        '03/10' => '03/10',
                                        '04/10' => '04/10',
                                        '05/10' => '05/10',
                                        '06/10' => '06/10',
                                        '07/10' => '07/10',
                                        '08/10' => '08/10',
                                        '09/10' => '09/10',
                                        '10/10' => '10/10',
                                        '11/10' => '11/10',
                                        '12/10' => '12/10',
                                        '13/10' => '13/10',
                                        '14/10' => '14/10',
                                        '15/10' => '15/10',
                                        '16/10' => '16/10',
                                        '17/10' => '17/10',
                                        '18/10' => '18/10',
                                        '19/10' => '19/10',
                                        '20/10' => '20/10',
                                        '21/10' => '21/10',
                                        '22/10' => '22/10',
                                        '23/10' => '23/10',
                                        '24/10' => '24/10',
                                        '25/10' => '25/10',
                                        '26/10' => '26/10',
                                        '27/10' => '27/10',
                                        '28/10' => '28/10',
                                        '29/10' => '29/10',
                                        '30/10' => '30/10',
                                        '31/10' => '31/10',
                                        '01/11' => '01/11',
                                        '02/11' => '02/11',
                                        '03/11' => '03/11',
                                        '04/11' => '04/11',
                                        '05/11' => '05/11',
                                        '06/11' => '06/11',
                                        '07/11' => '07/11',
                                        '08/11' => '08/11',
                                        '09/11' => '09/11',
                                        '10/11' => '10/11',
                                        '11/11' => '11/11',
                                        '12/11' => '12/11',
                                        '13/11' => '13/11',
                                        '14/11' => '14/11',
                                        '15/11' => '15/11',
                                        '16/11' => '16/11',
                                        '17/11' => '17/11',
                                        '18/11' => '18/11',
                                        '19/11' => '19/11',
                                        '20/11' => '20/11',
                                        '21/11' => '21/11',
                                        '22/11' => '22/11',
                                        '23/11' => '23/11',
                                        '24/11' => '24/11',
                                        '25/11' => '25/11',
                                        '26/11' => '26/11',
                                        '27/11' => '27/11',
                                        '28/11' => '28/11',
                                        '29/11' => '29/11',
                                        '30/11' => '30/11',
                                        '01/12' => '01/12',
                                        '02/12' => '02/12',
                                        '03/12' => '03/12',
                                        '04/12' => '04/12',
                                        '05/12' => '05/12',
                                        '06/12' => '06/12',
                                        '07/12' => '07/12',
                                        '08/12' => '08/12',
                                        '09/12' => '09/12',
                                        '10/12' => '10/12',
                                        '11/12' => '11/12',
                                        '12/12' => '12/12',
                                        '13/12' => '13/12',
                                        '14/12' => '14/12',
                                        '15/12' => '15/12',
                                        '16/12' => '16/12',
                                        '17/12' => '17/12',
                                        '18/12' => '18/12',
                                        '19/12' => '19/12',
                                        '20/12' => '20/12',
                                        '21/12' => '21/12',
                                        '22/12' => '22/12',
                                        '23/12' => '23/12',
                                        '24/12' => '24/12',
                                        '25/12' => '25/12',
                                        '26/12' => '26/12',
                                        '27/12' => '27/12',
                                        '28/12' => '28/12',
                                        '29/12' => '29/12',
                                        '30/12' => '30/12',
                                        '31/12' => '31/12',), 'woocommerce-pagarme'),
                'class'             => __('wc-enhanced-select', 'woocommerce-pagarme'),
                'css'               => __('width: 400px;', 'woocommerce-pagarme'),
      		),
            'dias_vencimento_boleto' => array(
                'title'     => __( 'Dias para vencimento do boleto', 'woocommerce-pagarme' ),
                'type'      => 'text',
                'desc_tip'  => __( 'Insira quantidade de dias para vencimento do boleto.', 'woocommerce-pagarme' ),
                'required' => 'required',
                'default'   => '1',
            ),
			'async' => array(
				'title'       => __( 'Async', 'woocommerce-pagarme' ),
				'type'        => 'checkbox',
				'description' => sprintf( __( 'If enabled the banking ticket url will appear in the order page, if disabled it will appear after the checkout process.', 'woocommerce-pagarme' ) ),
				'default'     => 'no',
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
	 * Payment fields.
	 */
	public function payment_fields() {
		if ( $description = $this->get_description() ) {
			echo wp_kses_post( wpautop( wptexturize( $description ) ) );
		}

		wc_get_template(
			'banking-ticket/checkout-instructions.php',
			array(),
			'woocommerce/pagarme/',
			WC_Pagarme::get_templates_path()
		);
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

		if ( isset( $data['boleto_url'] ) && in_array( $order->get_status(), array( 'processing', 'on-hold' ), true ) ) {
			$template = 'no' === $this->async ? 'payment' : 'async';

			wc_get_template(
				'banking-ticket/' . $template . '-instructions.php',
				array(
					'url' => $data['boleto_url'],
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

		if ( isset( $data['boleto_url'] ) ) {
			$email_type = $plain_text ? 'plain' : 'html';

			wc_get_template(
				'banking-ticket/emails/' . $email_type . '-instructions.php',
				array(
					'url' => $data['boleto_url'],
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
