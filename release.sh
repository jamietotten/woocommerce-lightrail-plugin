#!/usr/bin/env bash

echo "=== Packaging WooCommerce Lightrail for release ==="

echo "Changing config to PROD: overwriting woocommerce-lightrail/includes/woocommerce-lightrail-configs.php with woocommerce-lightrail-configs-prod.php"
cat woocommerce-lightrail-configs-prod.php > woocommerce-lightrail/includes/woocommerce-lightrail-configs.php
echo "done."

echo "=== WooCommerce Lightrail ready for release ==="