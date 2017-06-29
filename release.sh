#!/usr/bin/env bash

echo "=== Packaging Lightrail for WooCommerce for release ==="

echo "Changing config to PROD: overwriting includes/woocommerce-lightrail-configs.php with .config-files/woocommerce-lightrail-configs-prod.php"
cat ./.config-files/woocommerce-lightrail-configs-prod.php > ./includes/woocommerce-lightrail-configs.php
echo "done."

echo "=== Lightrail for WooCommerce ready for release ==="