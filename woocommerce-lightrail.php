<?php

/*
Plugin Name: Lightrail for WooCommerce
Plugin URI: https://wordpress.org/plugins/lightrail-for-woocommerce/
Description: Acquire and retain customers using account credits, gift cards, promotions, and points.
Version: 2.0.0
Author: Lightrail
Author URI: http://lightrail.com
License: GPL2

Lightrail for WooCommerce is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version.

Lightrail for WooCommerce is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with Lightrail for WooCommerce. If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.html.
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	function lightrail_init_woo_gateway() {
		if ( ! lightrail_compatibility_tests() ) {
			return;
		}
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

if ( ! function_exists( 'lightrail_plugin_add_settings_link' ) ) {
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'lightrail_plugin_add_settings_link' );

	function lightrail_plugin_add_settings_link( $existing_links ) {
		$settings_link = array( '<a href="admin.php?page=wc-settings&tab=checkout&section=lightrail">' . __( 'Settings' ) . '</a>', );
		return array_merge( $existing_links, $settings_link );
	}
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
