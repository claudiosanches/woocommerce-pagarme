<?php
/**
 * Plugin Name: WooCommerce Pagar.me
 * Plugin URI: http://github.com/claudiosmweb/woocommerce-pagarme
 * Description: Gateway de pagamento Pagar.me para WooCommerce.
 * Author: Pagar.me, Claudio Sanches
 * Author URI: https://pagar.me/
 * Version: 1.2.4
 * License: GPLv2 or later
 * Text Domain: woocommerce-pagarme
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Pagarme' ) ) :

/**
 * WooCommerce WC_Pagarme main class.
 */
class WC_Pagarme {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.2.4';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin public actions.
	 */
	private function __construct() {
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Checks with WooCommerce is installed.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			$this->includes();

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'my_orders_banking_ticket_link' ), 10, 2 );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Includes.
	 */
	private function includes() {
		include_once 'includes/class-wc-pagarme-api.php';
		include_once 'includes/class-wc-pagarme-credit-card-gateway.php';
		include_once 'includes/class-wc-pagarme-banking-ticket-gateway.php';
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-pagarme', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Get templates path.
	 *
	 * @return string
	 */
	public static function get_templates_path() {
		return plugin_dir_path( __FILE__ ) . 'templates/';
	}

	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 *
	 * @return array
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_Pagarme_Credit_Card_Gateway';
		$methods[] = 'WC_Pagarme_Banking_Ticket_Gateway';

		return $methods;
	}

	/**
	 * Add banking ticket link/button in My Orders section on My Accout page.
	 *
	 * @param array    $actions Actions.
	 * @param WC_Order $order   Order data.
	 *
	 * @return array
	 */
	public function my_orders_banking_ticket_link( $actions, $order ) {
		if ( 'pagarme' === $order->payment_method ) {
			$data = get_post_meta( $order->id, '_wc_pagarme_transaction_data', true );

			if ( ! empty( $data['boleto_url'] ) ) {
				$actions[] = array(
					'url'  => $data['boleto_url'],
					'name' => __( 'Print Banking Ticket', 'woocommerce-pagarme' ),
				);
			}
		}

		return $actions;
	}

	/**
	 * Action links.
	 *
	 * @param  array $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array();

		$credit_card    = 'wc_pagarme_credit_card_gateway';
		$banking_ticket = 'wc_pagarme_banking_ticket_gateway';

		$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $credit_card ) ) . '">' . __( 'Credit Card Settings', 'woocommerce-pagarme' ) . '</a>';

		$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $banking_ticket ) ) . '">' . __( 'Bank Slip Settings', 'woocommerce-pagarme' ) . '</a>';

		return array_merge( $plugin_links, $links );
	}

	/**
	 * WooCommerce fallback notice.
	 *
	 * @return string
	 */
	public function woocommerce_missing_notice() {
		include 'includes/admin/views/html-notice-missing-woocommerce.php';
	}
}

add_action( 'plugins_loaded', array( 'WC_Pagarme', 'get_instance' ) );

endif;
