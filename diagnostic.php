<?php
/**
 * Plugin Diagnostic Script
 * Test if Woovi PIX Gateway is properly registered
 * 
 * Upload this file to: wp-content/plugins/udia-pods-thankyou/diagnostic.php
 * Access: https://yoursite.com/wp-content/plugins/udia-pods-thankyou/diagnostic.php
 */

// Load WordPress
require_once '../../../wp-load.php';

echo "<h1>Woovi PIX Gateway Diagnostic</h1>";
echo "<pre>";

// 1. Check if plugin is active
echo "=== Plugin Status ===\n";
echo "Plugin Active: " . (is_plugin_active('udia-pods-thankyou/udia-pods-thankyou.php') ? '✅ YES' : '❌ NO') . "\n";
echo "WordPress Version: " . get_bloginfo('version') . "\n\n";

// 2. Check if WooCommerce is active
echo "=== WooCommerce Status ===\n";
echo "WooCommerce Active: " . (class_exists('WooCommerce') ? '✅ YES' : '❌ NO') . "\n";
if (class_exists('WooCommerce')) {
    echo "WooCommerce Version: " . WC()->version . "\n";
}
echo "\n";

// 3. Check if gateway class exists
echo "=== Gateway Class Status ===\n";
echo "WC_Gateway_Woovi_Pix exists: " . (class_exists('WC_Gateway_Woovi_Pix') ? '✅ YES' : '❌ NO') . "\n\n";

// 4. Check registered gateways
echo "=== Registered Payment Gateways ===\n";
$gateways = WC()->payment_gateways->payment_gateways();
$woovi_found = false;

foreach ($gateways as $gateway_id => $gateway) {
    $is_woovi = ($gateway_id === 'woovi_pix');
    if ($is_woovi) {
        $woovi_found = true;
        echo "✅ FOUND: {$gateway_id} - {$gateway->method_title}\n";
        echo "   Enabled: " . ($gateway->enabled === 'yes' ? 'YES' : 'NO') . "\n";
        echo "   Class: " . get_class($gateway) . "\n";
    } else {
        echo "   {$gateway_id} - {$gateway->method_title}\n";
    }
}

if (!$woovi_found) {
    echo "\n❌ Woovi PIX Gateway NOT FOUND in registered gateways!\n";
}

echo "\n";

// 5. Check plugin files
echo "=== Plugin Files ===\n";
$plugin_dir = WP_PLUGIN_DIR . '/udia-pods-thankyou/';
$required_files = [
    'udia-pods-thankyou.php',
    'includes/class-wc-gateway-woovi-pix.php',
    'includes/class-woovi-webhook-handler.php',
];

foreach ($required_files as $file) {
    $exists = file_exists($plugin_dir . $file);
    echo ($exists ? '✅' : '❌') . " {$file}\n";
}

echo "\n";

// 6. Test gateway initialization
echo "=== Manual Gateway Test ===\n";
if (class_exists('WC_Gateway_Woovi_Pix')) {
    try {
        $test_gateway = new WC_Gateway_Woovi_Pix();
        echo "✅ Gateway instantiated successfully\n";
        echo "   ID: {$test_gateway->id}\n";
        echo "   Title: {$test_gateway->method_title}\n";
        echo "   Enabled: {$test_gateway->enabled}\n";
    } catch (Exception $e) {
        echo "❌ Error instantiating gateway: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Cannot test - class does not exist\n";
}

echo "</pre>";

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ul>";
if (!$woovi_found) {
    echo "<li>❌ Gateway not registered. Try deactivating and reactivating the plugin.</li>";
    echo "<li>Check WordPress debug.log for errors (enable WP_DEBUG in wp-config.php)</li>";
} else {
    echo "<li>✅ Gateway is registered! Go to WooCommerce → Settings → Payments to enable it.</li>";
}
echo "</ul>";
