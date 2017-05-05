<?php

/*
Plugin Name: Woocommerce Lightrail
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: A brief description of the Plugin.
Version: 1.0
Author: mohammad
Author URI: http://URI_Of_The_Plugin_Author
License: A "Slug" license name e.g. GPL2
*/

error_log('Plugin activated', 0);


if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly


if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
	exit; // if woocommerce is not active never mind.



// Include the core class.
include_once 'includes/woocommerce-lightrail-core.php';



if ( !isset($GLOBALS["wc_lightrail"]) )
	exit; //if lightrail core is missing don't go any further.



if ( ! function_exists( 'lightrail_init_woo_gateway' ) ): {
	/**
	 * Construct Gateway
	 * @since 0.1
	 * @version 1.3
	 */
	add_action( 'after_setup_theme', 'lightrail_init_woo_gateway' );

	function lightrail_init_woo_gateway()
	{
		if (!class_exists('WC_Payment_Gateway')) return;

		//Localisation
		load_plugin_textdomain( 'woocommerce_lightrail', false, dirname( plugin_basename( __FILE__ ) ) . '/' );



		/**
		 * other useful things we need to get back to later:
		 *

		// indicates we are running the admin
		if ( is_admin() ) {
		// ...
		}

		// indicates we are being served over ssl
		if ( is_ssl() ) {
		// ...
		}
		 */



		/**
		 *
		 * Provides a Lightrail Payment Gateway
		 *
		 * @class        WC_Gateway_Lightrail
		 * @extends        WC_Payment_Gateway
		 * @version        0.0.1
		 * @author        Lightrail
		 */
		class WC_Gateway_Lightrail extends WC_Payment_Gateway
		{
			public $lightrail_engine;

			/**
			 * Constructor for the gateway.
			 */
			public function __construct()
			{
				$this->id = 'lightrail';
				$this->icon = 'img/lightrail.png';
				$this->has_fields = false;
				$this->method_title = _x('Lightrail', 'Lightrail gift code payment method', 'lightrail');
				$this->method_description = __('Pay with a Lightrail gift code.', 'lightrail');


				//load the lightrail engine singleton from global
				$this->lightrail_engine = $GLOBALS['wc_lightrail'];

				// Load the settings.
				$this->init_form_fields();
				$this->init_settings();

				// Define user set variables
				$this->title = $this->get_option('title');
				$this->description = $this->get_option('description');
				$this->instructions = $this->get_option('instructions');

				// Actions
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
				add_action('woocommerce_thankyou_lightrail', array($this, 'thankyou_page'));

				// Customer Emails
				add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
			}

			/**
			 * Initialise Gateway Settings Form Fields.
			 */
			public function init_form_fields()
			{

				$this->form_fields = array(
					'enabled' => array(
						'title' => __('Enable/Disable', 'lightrail'),
						'type' => 'checkbox',
						'label' => __('Enable Lightrail gift code payments', 'lightrail'),
						'default' => 'yes',
					),
					'title' => array(
						'title' => __('Title', 'lightrail'),
						'type' => 'text',
						'description' => __('This controls the title which the user sees during checkout.', 'lightrail'),
						'default' => _x('Lightrail', 'Lightrail gift code payment method', 'lightrail'),
						'desc_tip' => true,
					),
					'description' => array(
						'title' => __('Description', 'lightrail'),
						'type' => 'textarea',
						'description' => __('Payment method description that the customer will see on your checkout.', 'lightrail'),
						'default' => __('Pay using a Lightrail gift code.', 'lightrail'),
						'desc_tip' => true,
					),
					'instructions' => array(
						'title' => __('Instructions', 'lightrail'),
						'type' => 'textarea',
						'description' => __('Instructions that will be added to the thank you page and emails.', 'lightrail'),
						'default' => '',
						'desc_tip' => true,
					),
				);
			}

			/**
			 * Output for the order received page.
			 */
			public function thankyou_page()
			{
				if ($this->instructions) {
					echo wpautop(wptexturize($this->instructions));
				}
			}

			/**
			 * Add content to the WC emails.
			 *
			 * @access public
			 * @param WC_Order $order
			 * @param bool $sent_to_admin
			 * @param bool $plain_text
			 */
			/*
			public function email_instructions($order, $sent_to_admin, $plain_text = false)
			{
				if ($this->instructions && !$sent_to_admin && 'cheque' === $order->get_payment_method() && $order->has_status('on-hold')) {
					echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
				}
			}
			*/

			/**
			 * Process the payment and return the result.
			 *
			 * @param int $order_id
			 * @return array
			 */
			public function process_payment($order_id)
			{

				//grab the order
				$order = wc_get_order($order_id);

				//get order total
				$cost = $order->order_total;

				//get code
				$code = 'uvwxyz';

				// Check funds
				if ( $this->lightrail_engine->get_available_credit($code) < $cost ) {
					$message = __('Insufficient funds.', 'mycred');
					wc_add_notice($message, 'error');
					return;
				}

				// Mark as on-hold (we're awaiting the cheque)
				$order->update_status('on-hold', _x('Awaiting Lightrail payment', 'Lightrail gift code payment method', 'lightrail'));

				// Reduce stock levels
				wc_reduce_stock_levels($order_id);

				// Remove cart
				WC()->cart->empty_cart();

				// Return thankyou redirect
				return array(
					'result' => 'success',
					'redirect' => $this->get_return_url($order),
				);
			}
		}
	}
}
endif;

add_action( 'after_setup_theme', 'lightrail_init_woo_gateway' );

/**
 * Register Gateway
 * @since 0.1
 * @version 1.0
 */
if ( ! function_exists( 'lightrail_register_woo_gateway' ) ) :
	function lightrail_register_woo_gateway( $methods ) {

		$methods[] = 'WC_Gateway_Lightrail';
		return $methods;

	}
endif;
add_filter( 'woocommerce_payment_gateways', 'lightrail_register_woo_gateway' );


?>