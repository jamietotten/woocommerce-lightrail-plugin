=== WooCommerce Lightrail ===
Contributors: lightrailintegrations
Tags: woocommerce, giftcard
Requires at least: 4.7
Tested up to: 4.7
Stable tag: trunk
License: GPL2
WC requires at least: 3.0
WC tested up to: 3.0

Acquire and retain customers using account credits, gift cards, promotions, and points.

== Description ==

Lightrail is a modern account credit and gift card platform used by marketplaces and brands to acquire, retain, and reward customers.

To learn more, visit [Lightrail](https://www.lightrail.com/).

== Installation ==

1. Upload the entire 'woocommerce-lightrail' folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add your Lightrail API key:
    - Get your Lightrail API key: go to **https://www.lightrail.com/ > Account settings (click your user badge in the top right corner) > API
    - Paste it into the WooCommerce Lightrail settings page: from the WordPress dashboard go to **WooCommerce > Settings > Checkout tab > Lightrail**


**A note on user friendliness**

By default, when WooCommerce is activated it enables the use of WooCommerce-powered coupons. The input field for these coupons appears in the customer's cart and in the first part of checkout. **Lightrail-powered gift codes will not work if they are entered in these input fields, which can be confusing for your customers.** To avoid confusion, we suggest disabling WooCommerce coupons: go to **WooCommerce > Settings > Checkout tab > Checkout options** and uncheck the box for "Enable the use of coupons".

== Screenshots ==

== Changelog ==

= 0.1.0 =
* Initial Release