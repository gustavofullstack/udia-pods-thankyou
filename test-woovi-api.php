<?php
/**
 * EMERGENCY DEBUG SCRIPT - Test Woovi API Directly
 * 
 * Upload to: wp-content/plugins/udia-pods-thankyou/test-woovi-api.php
 * Access: https://udiapods.com/wp-content/plugins/udia-pods-thankyou/test-woovi-api.php
 */

// Prevent direct access in production - REMOVE THIS LINE to run:
die('REMOVA ESTA LINHA PARA EXECUTAR O TESTE');

require_once '../../../wp-load.php';

header('Content-Type: text/plain; charset=utf-8');

echo "═══════════════════════════════════════════\n";
echo "🔍 WOOVI API EMERGENCY DEBUG TEST\n";
echo "═══════════════════════════════════════════\n\n";

// Get gateway settings
$gateway_settings = get_option('woocommerce_woovi_pix_settings', array());

echo "📋 CONFIGURAÇÕES DO GATEWAY:\n";
echo "-------------------------------------------\n";
echo "Enabled: " . ($gateway_settings['enabled'] ?? 'N/A') . "\n";
echo "Test Mode: " . ($gateway_settings['testmode'] ?? 'N/A') . "\n";
echo "Title: " . ($gateway_settings['title'] ?? 'N/A') . "\n\n";

// Get AppID
$testmode = ($gateway_settings['testmode'] ?? 'no') === 'yes';
$app_id = $testmode ? ($gateway_settings['test_app_id'] ?? '') : ($gateway_settings['app_id'] ?? '');

echo "🔑 APPID DETECTADO:\n";
echo "-------------------------------------------\n";
echo "Modo: " . ($testmode ? 'TESTE' : 'PRODUÇÃO') . "\n";
echo "AppID Length: " . strlen($app_id) . " caracteres\n";
echo "Primeiro 10 chars: " . substr($app_id, 0, 10) . "...\n";
echo "Último 10 chars: ..." . substr($app_id, -10) . "\n";
echo "Tem espaços? " . (strlen($app_id) !== strlen(trim($app_id)) ? '⚠️ SIM!' : 'Não') . "\n";
echo "AppID (oculto): " . str_repeat('*', strlen($app_id)) . "\n\n";

if (empty($app_id)) {
    die("❌ ERRO: AppID não configurado!\nVá em: WooCommerce → Configurações → Pagamentos → Woovi PIX\n");
}

// Test 1: Simple API Call
echo "🧪 TESTE 1: CHAMADA SIMPLES À API\n";
echo "-------------------------------------------\n";

$test_payload = array(
    'correlationID' => 'test-' . time(),
    'value' => 100, // R$ 1,00
    'comment' => 'Teste de API - Debug'
);

echo "Endpoint: https://api.woovi.com/api/v1/charge\n";
echo "Payload: " . json_encode($test_payload, JSON_PRETTY_PRINT) . "\n\n";

$response = wp_remote_post(
    'https://api.woovi.com/api/v1/charge',
    array(
        'headers' => array(
            'Authorization' => trim($app_id),
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($test_payload),
        'timeout' => 15,
    )
);

if (is_wp_error($response)) {
    echo "❌ ERRO DE CONEXÃO:\n";
    echo $response->get_error_message() . "\n\n";
    die();
}

$status_code = wp_remote_retrieve_response_code($response);
$body = wp_remote_retrieve_body($response);
$headers = wp_remote_retrieve_headers($response);

echo "📡 RESPOSTA DA API:\n";
echo "-------------------------------------------\n";
echo "Status Code: " . $status_code . "\n";
echo "Response Body:\n" . $body . "\n\n";

if ($status_code === 401) {
    echo "❌ ERRO 401 - APPID INVÁLIDO!\n\n";
    echo "DIAGNÓSTICO:\n";
    echo "1. AppID está incorreto\n";
    echo "2. AppID é de teste mas modo está em produção (ou vice-versa)\n";
    echo "3. AppID expirou ou foi revogado\n\n";
    echo "SOLUÇÃO:\n";
    echo "1. Vá ao painel Woovi: https://app.woovi.com\n";
    echo "2. API/Plugins → Copie o AppID NOVAMENTE\n";
    echo "3. Cole em: WooCommerce → Pagamentos → Woovi PIX\n";
    echo "4. ATENÇÃO: Modo Teste = AppID de Teste | Produção = AppID de Produção\n\n";
}

if ($status_code === 200 || $status_code === 201) {
    echo "✅ SUCESSO! AppID está válido!\n\n";
    $data = json_decode($body, true);
    if (isset($data['charge']['brCode'])) {
        echo "🎉 QR Code gerado com sucesso!\n";
        echo "brCode: " . substr($data['charge']['brCode'], 0, 50) . "...\n";
    }
}

// Test 2: Check if WooCommerce class is loaded
echo "\n\n🧪 TESTE 2: GATEWAY CLASS\n";
echo "-------------------------------------------\n";
echo "WooCommerce ativo: " . (class_exists('WooCommerce') ? '✅ SIM' : '❌ NÃO') . "\n";
echo "Gateway class existe: " . (class_exists('WC_Gateway_Woovi_Pix') ? '✅ SIM' : '❌ NÃO') . "\n";

if (class_exists('WC_Gateway_Woovi_Pix')) {
    $gateway = new WC_Gateway_Woovi_Pix();
    echo "Gateway ID: " . $gateway->id . "\n";
    echo "Gateway Enabled: " . $gateway->enabled . "\n";
    echo "Gateway Title: " . $gateway->method_title . "\n";
}

echo "\n\n═══════════════════════════════════════════\n";
echo "FIM DO DEBUG\n";
echo "═══════════════════════════════════════════\n";
