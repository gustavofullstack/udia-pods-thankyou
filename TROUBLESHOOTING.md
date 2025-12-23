# Troubleshooting Guide - Woovi PIX Gateway

## Gateway não aparece em WooCommerce → Pagamentos

### Solução 1: Desativar e Reativar Plugin
```
WordPress Admin → Plugins → Udia Pods Pós-Checkout Experience
1. Clique em "Desativar"
2. Aguarde 3 segundos
3. Clique em "Ativar"
4. Recarregue página de Pagamentos
```

### Solução 2: Verificar WooCommerce Ativo
```
Plugins → Plugins Instalados
✅ WooCommerce deve estar ativo
```

### Solução 3: Rodar Script de Diagnóstico
```
Acesse: https://udiapods.com/wp-content/plugins/udia-pods-thankyou/diagnostic.php

Procure por:
✅ WooCommerce Active: YES
✅ WC_Gateway_Woovi_Pix exists: YES
✅ FOUND: woovi_pix - Woovi PIX
```

### Solução 4: Verificar Logs do WordPress
```php
// Em wp-config.php, adicione:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Depois, verifique:
wp-content/debug.log
```

---

## QR Code não aparece após checkout

### Causa 1: AppID Inválido
**Sintoma**: Mensagem "AppID inválido" no checkout

**Solução**:
1. Verifique se copiou AppID completo (sem espaços)
2. Confirme se está usando AppID correto (Teste vs Produção)
3. Regenere AppID no painel Woovi se necessário

### Causa 2: Erro na API
**Sintoma**: Checkout falha sem mensagem clara

**Solução**:
```
WooCommerce → Status → Logs → openpix-udia

Procure por:
- "API Response [401]" → AppID inválido
- "API Response [400]" → Dados inválidos no payload
- "API Error: cURL error" → Problema de conexão
```

### Causa 3: Firewall Bloqueando
**Sintoma**: Timeout ou erro de conexão

**Solução**:
```
Adicione ao firewall/proxy:
✅ Permitir: *.woovi.com
✅ Permitir: api.woovi.com
❌ NÃO armazenar em cache IPs
❌ NÃO armazenar certificados SSL
```

---

## Webhook não atualiza status do pedido

### Causa 1: Webhook não configurado
**Solução**:
```
Painel Woovi → Webhooks
URL: https://udiapods.com/wc-api/woovi_pix
Eventos: CHARGE_COMPLETED, CHARGE_EXPIRED
```

### Causa 2: URL incorreta
**Teste**:
```bash
curl -X POST https://udiapods.com/wc-api/woovi_pix \
  -H "Content-Type: application/json" \
  -d '{"test": "ping"}'

# Deve retornar:
HTTP/1.1 200 OK
```

### Causa 3: Firewall bloqueando IPs da Woovi
**Solução**:
```
Permitir no firewall:
Domínio: *.woovi.com (todos os IPs)
Porta: 443 (HTTPS)
```

### Causa 4: correlationID não corresponde
**Verificação**:
```
WooCommerce → Pedidos → Ver Pedido
Notas do Pedido → Procure:
"Cobrança PIX criada via Woovi. ID da transação: XXXXX"

Compare com webhook payload no log
```

---

## Timeout ou Latência Alta

### Causa: 3 chamadas API (fluxo incorreto)
**Sintoma**: Checkout demora mais de 10 segundos

**Verificação**: Você está usando fluxo de 3 fases?
```
❌ ERRADO:
1. POST /api/v1/account (criar conta)
2. POST /api/v1/application (criar app)
3. POST /api/v1/charge (criar cobrança)

✅ CORRETO (implementado):
1. POST /api/v1/charge (usar AppID configurado)
```

**Solução**: Já está implementado corretamente! Se ainda estiver lento, verifique:
- Latência do servidor
- Configuração de timeout PHP
- Cache de DNS

---

## Campos CPF/CNPJ Faltando

### Sintoma
```json
{
  "errors": [{
    "message": "customer.taxID is required"
  }]
}
```

### Solução: Instalar Plugin de Campos Brasileiros
```
Recomendações:
1. "Brazilian Market on WooCommerce" (oficial)
2. "WooCommerce Extra Checkout Fields for Brazil"

Após instalar:
- Campo CPF/CNPJ aparecerá no checkout
- Gateway detectará automaticamente via get_meta('_billing_cpf')
```

### Hotfix Manual (se necessário)
Se Woovi exigir customer.taxID, adicione no arquivo:
`includes/class-wc-gateway-woovi-pix.php`

Após linha 213 (método prepare_charge_data):

```php
// Get CPF/CNPJ
$tax_id = $order->get_meta('_billing_cpf') ?: $order->get_meta('_billing_cnpj');
$tax_id = preg_replace('/[^0-9]/', '', $tax_id);

if (!empty($tax_id)) {
    $data['customer'] = array(
        'name'  => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
        'email' => $order->get_billing_email(),
        'phone' => $order->get_billing_phone(),
        'taxID' => $tax_id,
    );
}
```

---

## Performance e Otimização

### Limpar Pedidos Expirados
Adicione cron job para cancelar pedidos on-hold após expiração:

```php
// Em functions.php do tema
add_action('init', function() {
    if (!wp_next_scheduled('cancel_expired_pix_orders')) {
        wp_schedule_event(time(), 'hourly', 'cancel_expired_pix_orders');
    }
});

add_action('cancel_expired_pix_orders', function() {
    $args = array(
        'status' => 'on-hold',
        'payment_method' => 'woovi_pix',
        'limit' => -1,
    );
    
    $orders = wc_get_orders($args);
    
    foreach ($orders as $order) {
        $expires = $order->get_meta('_woovi_expires_date');
        if ($expires && strtotime($expires) < time()) {
            $order->update_status('cancelled', 'PIX expirado automaticamente');
        }
    }
});
```

---

## Contatos de Suporte

### Woovi/OpenPix
- Documentação: https://developers.woovi.com
- Suporte: https://woovi.com/contato
- Status: https://status.woovi.com

### Plugin
- GitHub: https://github.com/gustavofullstack/udia-pods-thankyou
- Issues: https://github.com/gustavofullstack/udia-pods-thankyou/issues

---

## Checklist Pré-Produção

Antes de ir ao vivo:

- [ ] AppID de PRODUÇÃO configurado
- [ ] Modo de Teste DESATIVADO
- [ ] Webhook configurado e testado
- [ ] Teste com pagamento real de R$ 1,00
- [ ] Status atualiza automaticamente
- [ ] Email de confirmação sendo enviado
- [ ] QR Code renderiza corretamente
- [ ] Mobile responsivo testado
- [ ] WP_DEBUG desativado
- [ ] Backup do banco de dados feito
