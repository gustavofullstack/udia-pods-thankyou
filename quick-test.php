<?php
/**
 * Quick Test - Run this in browser
 * URL: https://udiapods.com/wp-content/plugins/udia-pods-thankyou/quick-test.php
 */

require_once '../../../wp-load.php';

header('Content-Type: text/html; charset=utf-8');

echo '<h1>🧪 Teste Rápido Woovi PIX</h1>';
echo '<pre style="background:#222;color:#0f0;padding:20px;border-radius:8px;">';

// Test 1: Gateway habilitado?
$gateways = WC()->payment_gateways->get_available_payment_gateways();
echo "✓ Gateways disponíveis:\n";
foreach ($gateways as $id => $gateway) {
    echo "  - {$id}: {$gateway->title}\n";
}

if (isset($gateways['woovi_pix'])) {
    echo "\n✅ WOOVI PIX ENCONTRADO!\n\n";
    
    $woovi = $gateways['woovi_pix'];
    echo "Title: {$woovi->title}\n";
    echo "Enabled: {$woovi->enabled}\n";
    echo "Testmode: " . ($woovi->testmode ? 'SIM' : 'NÃO') . "\n";
    echo "AppID length: " . strlen($woovi->app_id) . " chars\n";
    echo "AppID start: " . substr($woovi->app_id, 0, 20) . "...\n";
    
    // Test API call
    echo "\n🔥 TESTANDO API CALL...\n";
    
    $test_data = array(
        'correlationID' => 'test-' . time(),
        'value' => 100,
        'comment' => 'Teste checkout'
    );
    
    $response = wp_remote_post(
        'https://api.woovi.com/api/v1/charge',
        array(
            'headers' => array(
                'Authorization' => trim($woovi->app_id),
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($test_data),
            'timeout' => 15,
        )
    );
    
    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    echo "Status: {$status}\n";
    
    if ($status === 200 || $status === 201) {
        echo "✅ API FUNCIONANDO!\n";
        $data = json_decode($body, true);
        echo "Transaction ID: " . ($data['charge']['transactionID'] ?? 'N/A') . "\n";
        echo "QR Code: " . (isset($data['charge']['brCode']) ? 'GERADO ✓' : 'ERRO') . "\n";
    } else {
        echo "❌ ERRO {$status}\n";
        echo "Response: " . substr($body, 0, 200) . "\n";
    }
    
} else {
    echo "\n❌ WOOVI PIX NÃO ESTÁ NA LISTA DE GATEWAYS!\n";
    echo "\nPOSSÍVEIS CAUSAS:\n";
    echo "1. Plugin não está ativado\n";
    echo "2. WooCommerce não está ativo\n";
    echo "3. Classe do gateway tem erro PHP\n";
}

echo '</pre>';

echo '<hr>';
echo '<h2>Next Steps:</h2>';
echo '<ul>';
echo '<li>Se gateway NÃO apareceu: Desative e reative o plugin</li>';
echo '<li>Se API deu erro 401: AppID está errado no campo</li>';
echo '<li>Se tudo OK: Problema está no checkout frontend</li>';
echo '</ul>';
