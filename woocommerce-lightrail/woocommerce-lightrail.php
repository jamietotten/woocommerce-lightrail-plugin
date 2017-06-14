<?php

/*
Plugin Name: Woocommerce Lightrail
Plugin URI: http://lightrail.com
Description: Enables WooCommerce merchants to integrate with the Lightrail ecosystem.
Version: 0.1
Author: Lightrail
Author URI: http://lightrail.com
License: TBD
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

define( 'WC_LIGHTRAIL_MIN_PHP_VER', '7.0.0' );
define( 'WC_LIGHTRAIL_MIN_WOOC_VER', '3.0.0' );

if ( ! function_exists( 'lightrail_compatibility_tests' ) ) {
	function lightrail_compatibility_tests() {
		return ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) //woocommerce is installed and active
		       && version_compare( phpversion(), WC_LIGHTRAIL_MIN_PHP_VER, '>=' )
		       && defined( 'WC_VERSION' )
		       && version_compare( WC_VERSION, WC_LIGHTRAIL_MIN_WOOC_VER, '>=' );
	}
}


if ( ! function_exists( 'lightrail_init_woo_gateway' ) ) {

	/**
	 * init gateway
	 */
	function lightrail_init_woo_gateway() {
		if ( !lightrail_compatibility_tests() ) {
			return;
		}
		// Include the core classes
		include_once 'includes/woocommerce-lightrail-constants.php';
		include_once 'includes/woocommerce-lightrail-configs.php';
		include_once 'includes/woocommerce-lightrail-core.php';
		include_once 'includes/woocommerce-lightrail-currency.php';
		include_once 'includes/woocommerce-lightrail-metadata.php';
		include_once 'includes/woocommerce-lightrail-transactions.php';
		include_once 'includes/woocommerce-lightrail-admin-view.php';
		include_once 'includes/woocommerce-lightrail-user-view.php';
		include_once 'includes/woocommerce-lightrail-payment-gateway.php';

		//Localisation
		load_plugin_textdomain( 'woocommerce_lightrail', false, dirname( plugin_basename( __FILE__ ) ) . '/' );
	}

	add_action( 'plugins_loaded', 'lightrail_init_woo_gateway' );
	add_action( 'init', 'WC_Lightrail_Currency::init' );
	add_action( 'init', 'WC_Lightrail_Admin::init' );
	add_action( 'init', 'WC_Lightrail_User::init' );
}

/**
 * Register Gateway
 */
if ( ! function_exists( 'lightrail_register_woo_gateway' ) ) {
	function lightrail_register_woo_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Lightrail';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'lightrail_register_woo_gateway' );
}
