# TriqHub Thank You Plugin - API Reference

## Overview

The TriqHub Thank You Plugin provides a comprehensive post-checkout experience for WooCommerce stores, featuring custom thank you pages, PIX payment integration via Woovi, and extensive customization options through hooks, filters, and shortcodes.

## Table of Contents

1. [Core Classes](#core-classes)
2. [Shortcodes](#shortcodes)
3. [Actions](#actions)
4. [Filters](#filters)
5. [Webhook API](#webhook-api)
6. [Payment Gateway API](#payment-gateway-api)
7. [JavaScript API](#javascript-api)
8. [Update Checker API](#update-checker-api)
9. [Diagnostic Tools](#diagnostic-tools)

---

## Core Classes

### Udia_Pods_Thankyou

Main plugin class implementing singleton pattern.

| Method | Visibility | Parameters | Return Type | Description |
|--------|------------|------------|-------------|-------------|
| `init()` | `public static` | None | `void` | Singleton bootstrap method |
| `__construct()` | `private` | None | `void` | Constructor - hooks all plugin functionality |
| `init_gateway()` | `public` | None | `void` | Initializes payment gateway after WooCommerce loads |
| `add_woovi_gateway()` | `public` | `array $gateways` | `array` | Adds Woovi gateway to WooCommerce payment methods |
| `register_assets()` | `public` | None | `void` | Registers CSS/JS assets for on-demand enqueuing |
| `render_shortcode()` | `public` | `array $atts = []` | `string` | Renders the main shortcode output |
| `thankyou_page()` | `public` | `int $order_id` | `void` | Outputs custom thank you page content |
| `maybe_override_thankyou_content()` | `public` | None | `void` | Forces custom layout even when theme overrides default template |
| `filter_thankyou_content()` | `public` | `string $content` | `string` | Completely replaces thank you page content |
| `resolve_order()` | `private` | `int $order_id = 0` | `WC_Order|null` | Attempts to recover an order for display |
| `enqueue_assets()` | `private` | `WC_Order|null $order`, `array $atts` | `void` | Enqueues assets and prepares JavaScript data |
| `render_template()` | `private` | `WC_Order|null $order`, `array $atts = []` | `string` | Shared renderer used by shortcode and action |
| `render_header()` | `private` | `WC_Order|null $order` | `void` | Renders hero section with main message |
| `render_order_summary()` | `private` | `WC_Order|null $order` | `void` | Renders order items and totals box |
| `render_knowledge_card()` | `private` | `array $atts` | `void` | Renders CTA/tutorial highlight card |
| `render_tutorial()` | `private` | `array $atts` | `void` | Renders tutorial timeline |
| `render_support_block()` | `private` | `array $atts` | `void` | Renders support and integrations block |
| `render_unified_pix_card()` | `private` | `WC_Order $order` | `void` | Renders unified PIX card with payment details |

### WC_Gateway_Woovi_Pix

Payment gateway class for Woovi PIX integration.

| Method | Visibility | Parameters | Return Type | Description |
|--------|------------|------------|-------------|-------------|
| `__construct()` | `public` | None | `void` | Constructor - initializes gateway |
| `init_form_fields()` | `public` | None | `void` | Initializes admin settings form fields |
| `process_payment()` | `public` | `int $order_id` | `array` | Processes payment and creates Woovi charge |
| `prepare_charge_data()` | `private` | `WC_Order $order` | `array` | Prepares charge data for API request |
| `create_charge()` | `private` | `array $charge_data` | `array|WP_Error` | Creates charge via Woovi API |
| `save_charge_metadata()` | `private` | `WC_Order $order`, `array $response` | `void` | Saves charge metadata to order |
| `thankyou_page()` | `public` | `int $order_id` | `void` | Displays payment instructions on thank you page |
| `email_instructions()` | `public` | `WC_Order $order`, `bool $sent_to_admin`, `bool $plain_text` | `void` | Adds payment instructions to emails |

### Woovi_Webhook_Handler

Handles webhook notifications from Woovi/OpenPix.

| Method | Visibility | Parameters | Return Type | Description |
|--------|------------|------------|-------------|-------------|
| `init()` | `public static` | None | `void` | Initializes webhook handler |
| `handle_webhook()` | `public static` | None | `void` | Handles incoming webhook requests |
| `process_webhook()` | `private static` | `array $payload` | `bool|WP_Error` | Processes webhook payload |
| `handle_payment_completed()` | `private static` | `WC_Order $order`, `array $charge` | `void` | Handles completed payment webhook |
| `handle_payment_active()` | `private static` | `WC_Order $order`, `array $charge` | `void` | Handles active payment webhook |
| `handle_payment_expired()` | `private static` | `WC_Order $order`, `array $charge` | `void` | Handles expired payment webhook |
| `log()` | `private static` | `string $message` | `void` | Logs webhook events |

---

## Shortcodes

### `[udia_pods_thankyou]`

Main shortcode for rendering custom thank you pages.

**Attributes:**

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `order_id` | `int` | `0` | Specific order ID to display. If 0, attempts to auto-detect |
| `show_tutorial` | `string` | `'yes'` | Whether to show tutorial timeline (`'yes'`/`'no'`) |
| `highlight_cta` | `string` | `''` | URL for highlighted CTA button in knowledge card |
| `show_progress` | `string` | `'yes'` | Whether to show progress indicators |
| `show_chat_widget` | `string` | `'yes'` | Whether to show support chat widget |

**Example:**
```php
// Basic usage
echo do_shortcode('[udia_pods_thankyou]');

// With custom attributes
echo do_shortcode('[udia_pods_thankyou order_id="123" show_tutorial="no" highlight_cta="https://example.com/tutorial"]');
```

---

## Actions

### WordPress Actions

| Hook | Callback | Priority | Description |
|------|----------|----------|-------------|
| `plugins_loaded` | `Udia_Pods_Thankyou::init_gateway()` | 11 | Initializes payment gateway after WooCommerce loads |
| `init` | `Udia_Pods_Thankyou::register_assets()` | 10 | Registers CSS/JS assets |
| `woocommerce_thankyou` | `Udia_Pods_Thankyou::thankyou_page()` | 5 | Outputs custom thank you page content |
| `wp` | `Udia_Pods_Thankyou::maybe_override_thankyou_content()` | 10 | Forces custom layout on thank you pages |
| `admin_enqueue_scripts` | `triqhub_enqueue_admin_udia_pods_thankyou()` | 10 | Enqueues admin styles |

### WooCommerce Actions

| Hook | Callback | Priority | Description |
|------|----------|----------|-------------|
| `woocommerce_thankyou_woovi_pix` | `WC_Gateway_Woovi_Pix::thankyou_page()` | 10 | Displays PIX payment instructions |
| `woocommerce_email_before_order_table` | `WC_Gateway_Woovi_Pix::email_instructions()` | 10 | Adds payment instructions to emails |
| `woocommerce_update_options_payment_gateways_woovi_pix` | `WC_Gateway_Woovi_Pix::process_admin_options()` | 10 | Processes gateway admin options |

### Webhook Action

| Hook | Callback | Priority | Description |
|------|----------|----------|-------------|
| `woocommerce_api_woovi_pix` | `Woovi_Webhook_Handler::handle_webhook()` | 10 | Handles Woovi webhook requests at `/wc-api/woovi_pix` |

---

## Filters

### Payment Gateway Filter

| Filter | Callback | Priority | Description |
|--------|----------|----------|-------------|
| `woocommerce_payment_gateways` | `Udia_Pods_Thankyou::add_woovi_gateway()` | 10 | Adds Woovi PIX gateway to available payment methods |

### Content Filter

| Filter | Callback | Priority | Description |
|--------|----------|----------|-------------|
| `the_content` | `Udia_Pods_Thankyou::filter_thankyou_content()` | 999 | Replaces thank you page content completely |

### Tutorial Steps Filter

| Filter | Callback | Parameters | Description |
|--------|----------|------------|-------------|
| `udia_pods_thankyou_steps` | N/A | `array $steps`, `array $atts` | Filters the tutorial steps displayed in the timeline |

**Default Steps Structure:**
```php
$steps = [
    [
        'title' => 'Confirme seu e-mail',
        'text'  => 'Enviamos um link de confirmação para liberar seu conteúdo...'
    ],
    [
        'title' => 'Acesse a área do cliente',
        'text'  => 'Use o e-mail e a senha cadastrados...'
    ],
    [
        'title' => 'Comece o tutorial guiado',
        'text'  => 'Clique no botão acima para assistir o passo a passo...'
    ]
];
```

**Example:**
```php
add_filter('udia_pods_thankyou_steps', function($steps, $atts) {
    // Add custom step
    $steps[] = [
        'title' => 'Step 4: Custom Action',
        'text'  => 'Perform this additional step for enhanced experience.'
    ];
    
    return $steps;
}, 10, 2);
```

### Update Checker Filter

| Filter | Callback | Parameters | Description |
|--------|----------|------------|-------------|
| `puc_manual_check_message-{slug}` | Custom closure | `string $message`, `string $status` | Customizes update checker message (slug: `triqhub-thank-you`) |

**Example Filter Usage:**
```php
// The plugin implements this internally:
add_filter(
    'puc_manual_check_message-triqhub-thank-you',
    function($message, $status) {
        if ('no_update' === $status) {
            return '<strong>🎉 Você está usando a versão mais recente!</strong>';
        }
        return $message;
    },
    10,
    2
);
```

---

## Webhook API

### Endpoint
```
POST /wc-api/woovi_pix
```

### Authentication
- No authentication required (public endpoint)
- Woovi validates via webhook signature in payload

### Request Headers
```
Content-Type: application/json
```

### Webhook Payload Structure

**Charge Completed Event:**
```json
{
    "event": "charge.completed",
    "charge": {
        "correlationID": "123",
        "transactionID": "txn_abc123",
        "status": "COMPLETED",
        "value": 10000,
        "paidAt": "2024-01-15T14:30:00Z",
        "payer": {
            "name": "John Doe",
            "taxID": "123.456.789-00"
        },
        "brCode": "00020126580014BR.GOV.BCB.PIX0136a1b2c3d4-e5f6-7890-abcd-ef1234567890520400005303986540510.005802BR5909MERCHANT6008SAO PAULO62070503***6304E2CA"
    }
}
```

**Charge Active Event:**
```json
{
    "event": "charge.created",
    "charge": {
        "correlationID": "123",
        "status": "ACTIVE",
        "value": 10000,
        "expiresIn": 86400,
        "brCode": "00020126580014BR.GOV.BCB.PIX0136a1b2c3d4-e5f6-7890-abcd-ef1234567890520400005303986540510.005802BR5909MERCHANT6008SAO PAULO62070503***6304E2CA"
    }
}
```

### Response Codes

| Code | Description |
|------|-------------|
| `200` | Webhook processed successfully |
| `400` | Invalid JSON or processing error |
| `404` | Order not found |
| `500` | Internal server error |

### Webhook Processing Flow

1. **Payload Validation** - Checks for valid JSON and required fields
2. **Order Lookup** - Finds order using `correlationID` (order ID)
3. **Payment Method Verification** - Confirms order uses `woovi_pix`
4. **Duplicate Check** - Prevents processing same transaction multiple times
5. **Status Processing** - Updates order based on charge status:
   - `COMPLETED` → Marks payment complete
   - `ACTIVE` → Updates metadata, keeps order on-hold
   - `EXPIRED` → Cancels order

---

## Payment Gateway API

### Woovi API Integration

**Base URL:** `https://api.woovi.com/api/v1`

#### Create Charge
```
POST /charge
```

**Headers:**
```
Authorization: {app_id}
Content-Type: application/json
```

**Request Body:**
```json
{
    "correlationID": "123",
    "value": 10000,
    "comment": "Pedido #123 - Store Name",
    "expiresIn": 86400,
    "additionalInfo": [
        {
            "key": "Nome",
            "value": "John Doe"
        },
        {
            "key": "Email",
            "value": "john@example.com"
        }
    ]
}
```

**Response (Success):**
```json
{
    "charge": {
        "correlationID": "123",
        "transactionID": "txn_abc123",
        "status": "ACTIVE",
        "value": 10000,
        "qrCodeImage": "https://api.woovi.com/qr/image/abc123",
        "brCode": "00020126580014BR.GOV.BCB.PIX0136a1b2c3d4-e5f6-7890-abcd-ef1234567890520400005303986540510.005802BR5909MERCHANT6008SAO PAULO62070503***6304E2CA",
        "expiresDate": "2024-01-16T14:30:00Z"
    }
}
```

#### Gateway Settings Structure

```php
$settings = [
    'enabled'     => 'yes',  // Gateway enabled
    'title'       => 'PIX (QR Code)',
    'description' => 'Pague instantaneamente com PIX escaneando o QR Code',
    'testmode'    => 'yes',  // Test mode enabled
    'app_id'      => 'prod_app_id_here',      // Production AppID
    'test_app_id' => 'test_app_id_here',      // Test AppID
    'expires_in'  => 86400,  // 24 hours in seconds
];
```

#### Order Metadata Saved

| Meta Key | Type | Description |
|----------|------|-------------|
| `_woovi_qr_code_image` | `string` | URL to QR code image |
| `_woovi_br_code` | `string` | PIX BR code for copy/paste |
| `_woovi_expires_date` | `string` | Expiration date/time |
| `_woovi_charge_status` | `string` | Current charge status |
| `_woovi_payer_info` | `string` | JSON-encoded payer information |
| `_woovi_paid_at` | `string` | Payment timestamp |
| `_transaction_id` | `string` | Woovi transaction ID |

---

## JavaScript API

### Global Object
```javascript
window.UdiaPodsThankyou
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `orderId` | `string|null` | Order number |
| `status` | `string|null` | Order status |
| `billing` | `string` | Formatted billing full name |
| `highlightCta` | `string` | Highlighted CTA URL |

### DOM Events

| Event | Target | Description |
|-------|--------|-------------|
| `click` | `[data-copy-target]` | Copies text from target element |
| `click` | `[data-open-chat]` | Opens chat widget (requires integration) |

### CSS Classes for Styling

| Class | Purpose |
|-------|---------|
| `.utp-wrapper` | Main container wrapper |
| `.utp-hero` | Hero header section |
| `.utp-card` | Card component base |
| `.utp-order-card` | Order summary card |
| `.utp-cta-card` | CTA/knowledge card |
| `.utp-timeline` | Tutorial timeline |
| `.utp