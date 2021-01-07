<?php
/**
 * Plugin Name: Pagar.me for WooCommerce
 * Plugin URI: http://github.com/claudiosmweb/woocommerce-pagarme
 * Description: Gateway de pagamento Pagar.me para WooCommerce.
 * Author: Pagar.me, Claudio Sanches
 * Author URI: https://pagar.me/
 * Version: 2.4.1
 * License: GPLv2 or later
 * Text Domain: woocommerce-pagarme
 * Domain Path: /languages/
 *
 * @package WooCommerce_Pagarme
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
		const VERSION = '2.4.1';

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

			// Checks if WooCommerce is installed.
			if ( class_exists( 'WC_Payment_Gateway' ) ) {
				$this->upgrade();
				$this->includes();

				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			}

			// Custom settings for the "Brazilian Market on WooCommerce" plugin billing fields.
			if ( class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
				add_action( 'wcbcf_billing_fields', array( $this, 'wcbcf_billing_fields_custom_settings' ) );
			}

			// Runs a specific method right after the plugin activation.
			add_action( 'admin_init', array( $this, 'after_activation' ) );

			// Dismissible notices.
			add_action( 'wp_loaded', array( $this, 'hide_notices' ) );
			add_action( 'admin_notices', array( $this, 'brazilian_market_missing_notice' ) );
			add_action( 'admin_notices', array( $this, 'pagarme_documentation_link_notice' ) );
		}

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null === self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Includes.
		 */
		private function includes() {
			include_once dirname( __FILE__ ) . '/includes/class-wc-pagarme-api.php';
			include_once dirname( __FILE__ ) . '/includes/class-wc-pagarme-my-account.php';
			include_once dirname( __FILE__ ) . '/includes/class-wc-pagarme-banking-ticket-gateway.php';
			include_once dirname( __FILE__ ) . '/includes/class-wc-pagarme-credit-card-gateway.php';
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
			$methods[] = 'WC_Pagarme_Banking_Ticket_Gateway';
			$methods[] = 'WC_Pagarme_Credit_Card_Gateway';

			return $methods;
		}

		/**
		 * Action links.
		 *
		 * @param  array $links Plugin links.
		 *
		 * @return array
		 */
		public function plugin_action_links( $links ) {
			$plugin_links = array();

			$banking_ticket = 'wc_pagarme_banking_ticket_gateway';
			$credit_card    = 'wc_pagarme_credit_card_gateway';

			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $banking_ticket ) ) . '">' . __( 'Bank Slip Settings', 'woocommerce-pagarme' ) . '</a>';

			$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $credit_card ) ) . '">' . __( 'Credit Card Settings', 'woocommerce-pagarme' ) . '</a>';

			return array_merge( $plugin_links, $links );
		}

		/**
		 * WooCommerce fallback notice.
		 */
		public function woocommerce_missing_notice() {
			include dirname( __FILE__ ) . '/includes/admin/views/html-notice-missing-woocommerce.php';
		}

		/**
		 * Brazilian Market plugin missing notice.
		 */
		public function brazilian_market_missing_notice() {
			if ( ( is_admin() && get_option( 'woocommerce_pagarme_admin_notice_missing_brazilian_market' ) === 'yes' ) ) {
				// Do not show the notice if the Brazilian Market plugin is installed.
				if ( class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
					delete_option( 'woocommerce_pagarme_admin_notice_missing_brazilian_market' );
					return;
				}

				include dirname( __FILE__ ) . '/includes/admin/views/html-notice-missing-brazilian-market.php';
			}
		}

		/**
		 * Pagar.me documentation notice.
		 */
		public function pagarme_documentation_link_notice() {
			if ( is_admin() && get_option( 'woocommerce_pagarme_admin_notice_documentation_link' ) === 'yes' ) {
				include dirname( __FILE__ ) . '/includes/admin/views/html-notice-pagarme-documentation-link.php';
			}
		}

		/**
		 * Upgrade.
		 *
		 * @since 2.0.0
		 */
		private function upgrade() {
			if ( is_admin() ) {
				if ( $old_options = get_option( 'woocommerce_pagarme_settings' ) ) {
					// Banking ticket options.
					$banking_ticket = array(
						'enabled'        => $old_options['enabled'],
						'title'          => 'Boleto bancário',
						'description'    => '',
						'api_key'        => $old_options['api_key'],
						'encryption_key' => $old_options['encryption_key'],
						'debug'          => $old_options['debug'],
					);

					// Credit card options.
					$credit_card = array(
						'enabled'              => $old_options['enabled'],
						'title'                => 'Cartão de crédito',
						'description'          => '',
						'api_key'              => $old_options['api_key'],
						'encryption_key'       => $old_options['encryption_key'],
						'checkout'             => 'no',
						'max_installment'      => $old_options['max_installment'],
						'smallest_installment' => $old_options['smallest_installment'],
						'interest_rate'        => $old_options['interest_rate'],
						'free_installments'    => $old_options['free_installments'],
						'debug'                => $old_options['debug'],
					);

					update_option( 'woocommerce_pagarme-banking-ticket_settings', $banking_ticket );
					update_option( 'woocommerce_pagarme-credit-card_settings', $credit_card );

					delete_option( 'woocommerce_pagarme_settings' );
				}
			}
		}

		/**
		 * Custom settings for the "Brazilian Market on WooCommerce" plugin billing fields.
		 *
		 * @param  array $wcbcf_billing_fields "Brazilian Market on WooCommerce" plugin billing fields.
		 *
		 * @return array
		 */
		public function wcbcf_billing_fields_custom_settings( $wcbcf_billing_fields ) {
			$wcbcf_billing_fields['billing_neighborhood']['required'] = true;

			return $wcbcf_billing_fields;
		}

		/**
		 * Hide a notice if the GET variable is set.
		 */
		public static function hide_notices() {
			if ( isset( $_GET['woocommerce-pagarme-hide-notice'] ) ) {
				$notice_to_hide = sanitize_text_field( wp_unslash( $_GET['woocommerce-pagarme-hide-notice'] ) );
				delete_option( 'woocommerce_pagarme_admin_notice_' . $notice_to_hide );
			}
		}

		/**
		 * Activate.
		 *
		 * Fired by `register_activation_hook` when the plugin is activated.
		 */
		public static function activation() {
			if ( is_multisite() ) {
				return;
			}

			add_option( 'woocommerce_pagarme_activated', 'yes' );
		}

		/**
		 * After activation.
		 */
		public function after_activation() {
			if ( is_admin() && get_option( 'woocommerce_pagarme_activated' ) === 'yes' ) {
				delete_option( 'woocommerce_pagarme_activated' );

				add_option( 'woocommerce_pagarme_admin_notice_documentation_link', 'yes' );
				add_option( 'woocommerce_pagarme_admin_notice_missing_brazilian_market', 'yes' );
			}
		}
	}

	add_action( 'plugins_loaded', array( 'WC_Pagarme', 'get_instance' ) );
	register_activation_hook( plugin_basename( __FILE__ ), array( 'WC_Pagarme', 'activation' ) );

endif;
