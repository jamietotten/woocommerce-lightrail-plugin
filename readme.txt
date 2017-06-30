=== Lightrail for WooCommerce ===
Contributors: lightrailintegrations
Tags: woocommerce, giftcard
Requires at least: 4.7
Tested up to: 4.7
Stable tag: trunk
License: GPL2
WC requires at least: 3.0
WC tested up to: 3.1

Acquire and retain customers using account credits, gift cards, promotions, and points.

== Description ==
A Wordpress plugin which enables WooCommerce merchants to integrate with the Lightrail ecosystem.
Lightrail is a modern account credit and gift card platform used by marketplaces and brands to acquire, retain, and reward customers.
To learn more, visit [Lightrail](https://www.lightrail.com/).

== Features ==
The following features are supported in the current version (1.0.0) of this plugin:

* Pay for an order by a gift code.
* Split payment on an order using a gift code and another payment method.
* Pay for an order using more than one gift code.
* Cancel an order by the customer after attempting to pay with a gift code, if the order balance exceeds the value of the gift code.
* Issue full order refund by the store admin when an order is paid by a gift code.
* Detailed log of all the transactions on an order for the store admin.

The current version of this plugin has been tested to work seamlessly with Stripe, PayPal Standard, and CardConnect WooCommerce plugins.

== Installation ==

= Dependencies =
This requires the following dependencies.

* PHP 7.0 or later.
* Wordpress 4.7, or later.
* WooCommerce 3.0, or later.

In order for this plugin to work, the WooComerce wordpress plugin must be installed and activated.

=== Upload and activate the plugin ===

= Wordpress admin dashboard: =

1. Go to `Plugins` > `Add new` > `Upload Plugin`.
2. Click `Choose File`, select the plugin `zip`, and click `Install Now`.
3. Click `Activate Plugin` when prompted.

= SFTP: =

1. Copy the `woocommerce-lightrail` folder into the `wp-content/plugins` directory of your Wordpress instance.
2. Activate the plugin through the `Plugins` menu in WordPress.

= Set Up Lightrail Payment Gateway =

In order to connect with the Lightrail API, the plugin needs valid Lightrail API keys. You can find your API key in your  [Lightrail dashboard](https://www.lightrail.com/app/#/login): Go to `Account Settings`, click your user badge in the top right corner, and then click on `API`.

Once you found your API key, enter it in the Lightrail for WooCommerce settings page. The settings are in `WooCommerce` > `Settings` > `Checkout` tab where you can scroll down to the bottom of the page under `Payment Gateways ` and find `Gift Code` by Lightrail.
Alternatively, you can directly go to the settings page by clicking on the `Settings` link under Lightrail for WooCommerce plugin name on Wordpress `Plugins` page.

== WooCommerce Coupons ==

To avoid confusion on the checkout page, if you are using Lightrail gift codes, we strongly recommend that you disable WooCommerce coupons. You can disable WooCommerce default coupons by going to `WooCommerce` >` Settings` > `Checkout` tab > `Checkout` options and unchecking the checkbox `Enable the use of coupons`.

== Screenshots ==

== Changelog ==

= 1.0.0-beta.1 =
* Tested with WooCommerce 3.1.0
* Test with Stripe, PaylPal Standard, and CardConnect

= 1.0.0-alpha =
* Initial Release