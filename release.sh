#!/usr/bin/env bash

echo "=== Packaging Lightrail for WooCommerce for release ==="
rm lightrail-for-woocommerce.zip
zip -r lightrail-for-woocommerce.zip . -x@exclude.lst
echo "=== Lightrail for WooCommerce ready for release ==="