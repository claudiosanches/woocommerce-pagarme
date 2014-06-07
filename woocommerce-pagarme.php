<?php
/**
 * Plugin Name: WooCommerce Pagar.me
 * Plugin URI: http://github.com/claudiosmweb/woocommerce-pagarme
 * Description: Gateway de pagamento Pagar.me para WooCommerce.
 * Author: Pagar.me, claudiosanches
 * Author URI: https://pagar.me/
 * Version: 1.0.0
 * License: GPLv2 or later
 * Text Domain: woocommerce-pagarme
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_PagarMe' ) ) :

/**
 * WooCommerce WC_PagarMe main class.
 */
class WC_PagarMe {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

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
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Checks with WooCommerce and WooCommerce Extra Checkout Fields for Brazil is installed.
		if ( class_exists( 'WC_Payment_Gateway' ) && class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			// Include the WC_PagarMe_Gateway class.
			include_once 'includes/class-wc-pagarme-gateway.php';

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
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
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-pagarme' );

		load_textdomain( 'woocommerce-pagarme', trailingslashit( WP_LANG_DIR ) . 'woocommerce-pagarme/woocommerce-pagarme-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-pagarme', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @param   array $methods WooCommerce payment methods.
	 *
	 * @return  array          Payment methods with Pagar.me.
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_PagarMe_Gateway';

		return $methods;
	}

	/**
	 * WooCommerce fallback notice.
	 *
	 * @return  string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p>' . sprintf(
			__( 'WooCommerce Pagar.me depends on the last version of the %s and the %s to work!', 'bling-woocommerce' ),
			'<a href="http://wordpress.org/extend/plugins/woocommerce/">' . __( 'WooCommerce', 'bling-woocommerce' ) . '</a>',
			'<a href="http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/">' . __( 'WooCommerce Extra Checkout Fields for Brazil', 'bling-woocommerce' ) . '</a>'
		) . '</p></div>';
	}
}

add_action( 'plugins_loaded', array( 'WC_PagarMe', 'get_instance' ), 0 );

endif;
