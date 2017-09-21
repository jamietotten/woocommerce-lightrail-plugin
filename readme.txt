=== Lightrail for WooCommerce ===
Contributors: lightrailintegrations
Tags: woocommerce, giftcard
Requires at least: 4.7
Tested up to: 4.8.2
Stable tag: trunk
License: GPL2
WC requires at least: 3.0
WC tested up to: 3.1.2

Acquire and retain customers using Account Credits, Gift Cards, Promotions, and Points.

== Description ==
Lightrail for WooCommerce allows Lightrail’s Gift Cards to be redeemed in your WooCommerce checkout. You can view and track Gift Card redemptions and also issue Gift Card refunds from your WooCommerce dashboard.

Lightrail is a modern platform for digital account credits, Gift Cards, Promotions, and Points—made for customer acquisition and retention.

To learn more, visit [Lightrail](https://www.lightrail.com/).

== How to Get Started ==

* Get a Lightrail account [here](https://www.lightrail.com/)
* Install plugin
* Create and download Gift Card codes from Lightrail
* Distribute your WooCommerce-ready Gift Cards codes to customers

To connect the plugin with your Lightrail account to process Gift Cards, you will need to enter your Lightrail API key in the plugin settings as detailed below.

== Features ==
The following features are supported in the current version (2.0.0) of this plugin:

* Pay for an order by a Gift Card.
* Split payment on an order using a Gift Card and another payment method.
* Pay for an order using more than one Gift Card.
* Cancel an order by the customer after attempting to pay with a Gift Card, if the order balance exceeds the value of the Card.
* Issue full order refund by the store admin when an order is paid by a Gift Card.
* Detailed log of all the transactions on an order for the store admin.
* Use Gift Cards with Lightrail Attached Promotions including Promotions with Redemption Rules.

The current version of this plugin has been tested to work seamlessly with Stripe, PayPal Standard, and CardConnect WooCommerce plugins.

== Installation ==

= Dependencies =
This requires the following dependencies.

* PHP 7.0 or later.
* Wordpress 4.7, or later.
* WooCommerce 3.0, or later.

In order for this plugin to work, the WooComerce wordpress plugin must be installed and activated.

= Install and activate the plugin =

Option 1 - Install automatically through your WordPress dashboard:

1. Go to `Plugins` > `Add new` and use the search field to search for `Lightrail for WooCommerce`
2. Click `Install Now` and then `Activate`

Option 2 - Upload the plugin file:

1. In your WordPress dashboard, go to `Plugins` > `Add new` > `Upload Plugin`.
2. Click `Choose File`, select the plugin `zip`, and click `Install Now`.
3. Click `Activate Plugin` when prompted.

Option 3 - By SFTP:

1. Copy the `woocommerce-lightrail` folder into the `wp-content/plugins` directory of your Wordpress instance.
2. Activate the plugin through the `Plugins` menu in WordPress.

= Set Up Lightrail Payment Gateway =

In order to connect with the Lightrail API, the plugin needs valid Lightrail API keys. You can generate API keys in your  [Lightrail dashboard](https://www.lightrail.com/app/#/login): click your user badge in the top right corner, go to `Account Settings`, and then click on `API Keys`.

Once you have created an API key, enter it in the Lightrail for WooCommerce settings page. The settings are in the `WooCommerce` > `Settings` > `Checkout` tab where you can scroll down to the bottom of the page under `Payment Gateways ` and find `Gift Code` by Lightrail.
Alternatively, you can directly go to the settings page by clicking on the `Settings` link under Lightrail for WooCommerce plugin name on Wordpress `Plugins` page.

== WooCommerce Coupons ==

To avoid confusion on the checkout page, if you are using Lightrail Gift Cards, we strongly recommend that you disable WooCommerce coupons. You can disable WooCommerce default coupons by going to `WooCommerce` >` Settings` > `Checkout` tab > `Checkout` options and unchecking the checkbox `Enable the use of coupons`.

== Screenshots ==

1. Create and download Gift Card codes from Lightrail to send to your customers.
2. After installing Lightrail for WooCommerce, Lightrail codes are redeemable in your WooCommerce checkout.
3. View Lightrail Gift Card redemption details in your WordPress dashboard.
4. Lightrail for WooCommerce also supports refunds.
5. Track individual card details and transactions in Lightrail.

== Changelog ==

= 2.0.0 =
* Support for Lightrail conditional Promotions with redemption rules.

= 1.0.2 =
* Improvements to readme & assets for display in WordPress directory listing

= 1.0.1 =
* Bumping up the version to submit to WordPress.

= 1.0.0-beta.1 =
* Tested with WooCommerce 3.1.0
* Test with Stripe, PaylPal Standard, and CardConnect

= 1.0.0-alpha =
* Initial Release