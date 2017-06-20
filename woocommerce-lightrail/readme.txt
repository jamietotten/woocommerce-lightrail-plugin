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

Requirements: Wordpress 4.7, WooCommerce 3.0, PHP7

= Upload and activate the plugin =

**Through the Wordpress admin dashboard:**

1. Go to **Plugins > Add new > Upload Plugin**
2. Click **Choose File**, select the .zip file of the plugin, click **Install Now** and then **Activate Plugin** when prompted
3. Add your Lightrail API key on the WooCommerce Lightrail settings page (details below)

**By FTP / SFTP / etc**

1. Upload the entire 'woocommerce-lightrail' folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add your Lightrail API key on the WooCommerce Lightrail settings page (details below)

= Add your Lightrail API key =

You'll need to enter your Lightrail API key to connect with your Lightrail account and process gift cards.

1. Get your Lightrail API key: go to your **[Lightrail dashboard](https://www.lightrail.com/app/#/login) > Account settings (click your user badge in the top right corner) > API** and copy the
2. Paste it into the WooCommerce Lightrail settings page: from the WordPress dashboard go to **WooCommerce > Settings > Checkout tab > Lightrail**

= A note on 'coupons' and user-friendliness =

By default, when WooCommerce is activated it enables the use of WooCommerce-powered coupons. The input field for these coupons appears in the customer's cart and in the first part of checkout. **Lightrail-powered gift codes will not work if they are entered in these input fields, which can be confusing for your customers.** To avoid confusion, we suggest disabling WooCommerce coupons: go to **WooCommerce > Settings > Checkout tab > Checkout options** and uncheck the box for "Enable the use of coupons".

== Screenshots ==

== Changelog ==

= 0.1.0 =
* Initial Release