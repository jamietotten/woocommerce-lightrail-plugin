# WooCommerce Lightrail Integration

A Wordpress plugin which enables WooCommerce merchants to integrate with the Lightrail ecosystem.

## Introduction

Lightrail is a modern account credit and gift card platform used by marketplaces and brands to acquire, retain, and reward customers. To learn more, visit [Lightrail.com](https://www.lightrail.com/).

This plugin enables the customers of your WooCommerce store to use Lightrail-powered gift codes at the checkout to pay for an order.

### Features

The following features are supported in the current version (1.0.0) of this plugin:

-  Pay for an order by a gift code.
-  Split payment on an order using a gift code and another payment method.
-  Pay for an order using more than one gift code.
-  Cancel an order by the customer after attempting to pay with a gift code, if the order balance exceeds the value of the gift code.
-  Issue full order refund by the store admin when an order is paid by a gift code.
-  Detailed log of all the transactions on an order for the store admin. 

The current version of this plugin has been tested to work seamlessly with [Stripe](https://en-ca.wordpress.org/plugins/woocommerce-gateway-stripe/) and [CardConnect](https://en-ca.wordpress.org/plugins/cardconnect-payment-module/) WooCommerce plugins.

## Installation

### Requirements

This requires the following dependencies.

- Wordpress 4.7, or later.
- [WooCommerce](https://en-ca.wordpress.org/plugins/woocommerce/) 3.0, or later.
- PHP 7.0 or later.

In order for this plugin to work, the WooComerce wordpress plugin must be installed and activated. 

### Upload and activate the plugin

#### Wordpress admin dashboard:

1. Go to `Plugins` > `Add new` > `Upload Plugin`.
2. Click `Choose File`, select the plugin `zip`, and click `Install Now`.
3. Click `Activate Plugin` when prompted.

####  SFTP:

1. Copy the  `woocommerce-lightrail` folder into the `wp-content/plugins` directory of your Wordpress instance.
2. Activate the plugin through the `Plugins` menu in WordPress.

### Set Up Lightrail Payment Gateway

In order to connect with the Lightrail API, the plugin needs valid Lightrail API keys. 

1. Find your API key in your  [Lightrail dashboard](https://www.lightrail.com/app/#/login): Go to `Account Settings`, click your user badge in the top right corner, and then click on `API`.
2. Enter your API key into the WooCommerce Lightrail settings page: from the WordPress dashboard go to `WooCommerce` > `Settings` > `Checkout` tab. Scroll down to the bottom of the page under `Payment Gateways ` and click on `Gift Code` by Lightrail. Paster the API Key in the text area.

### WooCommerce Coupons

To avoid confusion on the checkout page, if you are using Lightrail gift codes, we strongly recommend that you disable WooCommerce coupons. You can disable WooCommerce default coupons by going to `WooCommerce` >` Settings` > `Checkout` tab > `Checkout` options and unchecking the checkbox `Enable the use of coupons`.

## Screenshots

## Changelog

= 1.0.0-alpha =
* Initial Release