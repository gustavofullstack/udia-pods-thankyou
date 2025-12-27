# TriqHub: Thank You Page Plugin

## Introduction

The TriqHub Thank You Page plugin is a proprietary solution developed by Udia Pods for creating customized post-checkout thank you pages and tutorial experiences in WordPress, Elementor, and WooCommerce environments. This plugin replaces the standard WooCommerce order confirmation page with a branded, feature-rich interface that enhances customer experience, reduces support inquiries, and provides clear post-purchase guidance.

The plugin integrates seamlessly with WooCommerce payment gateways, including Woovi PIX, and offers a comprehensive set of features for displaying order information, tutorial content, and support options in a visually consistent manner aligned with Udia Pods' brand identity.

## Features

### Core Functionality
- **Custom Thank You Page**: Replaces the default WooCommerce order confirmation page with a branded interface
- **Woovi PIX Integration**: Native support for Woovi PIX payments with QR code display and countdown timers
- **Responsive Design**: Mobile-optimized layout that works across all devices
- **Shortcode Support**: Embed thank you pages anywhere using `[udia_pods_thankyou]`
- **Automatic Updates**: GitHub-based update system with release asset support

### Order Management
- **Order Summary Display**: Shows purchased items, quantities, prices, and totals
- **Order Status Tracking**: Visual indicators for payment and fulfillment status
- **Order Number Copy**: One-click copy functionality for order reference numbers
- **Customer Personalization**: Greets customers by first name from billing information

### Educational Components
- **Tutorial Timeline**: Step-by-step guidance for accessing purchased products/services
- **Knowledge Cards**: Highlighted call-to-action sections for accessing complete tutorials
- **Progress Indicators**: Visual progress tracking for multi-step processes
- **Filterable Steps**: Customizable tutorial steps via WordPress filters

### Support Integration
- **Support Block**: Dedicated section with support contact options
- **Chat Widget Integration**: Ready for live chat system integration
- **WhatsApp Links**: Direct links to WhatsApp support (configurable)
- **Support Actions**: Multiple contact methods in a unified interface

### Technical Features
- **Template Override Protection**: Ensures custom thank you page displays even with theme overrides
- **Asset Management**: Optimized CSS/JS loading with conditional enqueuing
- **Order Resolution**: Intelligent order detection from shortcode parameters or user sessions
- **PIX Payment Display**: Unified card layout for PIX payments with QR codes and copy functionality

## Installation & Usage

### Prerequisites
- WordPress 5.0 or higher
- WooCommerce 4.0 or higher (for full functionality)
- PHP 7.4 or higher

### Installation Methods

#### Method 1: WordPress Admin (Recommended)
1. Download the plugin ZIP file from GitHub Releases
2. Navigate to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the downloaded ZIP file
4. Click "Install Now" and then "Activate Plugin"

#### Method 2: Manual Installation
1. Download the plugin from GitHub: `https://github.com/gustavofullstack/triqhub-thank-you`
2. Extract the ZIP file to your WordPress plugins directory: `/wp-content/plugins/`
3. Rename the extracted folder to `triqhub-thank-you`
4. Navigate to WordPress Admin → Plugins
5. Find "TriqHub: Thank You Page" and click "Activate"

#### Method 3: Git Installation (Developers)
```bash
cd /wp-content/plugins/
git clone https://github.com/gustavofullstack/triqhub-thank-you.git
cd triqhub-thank-you
composer install  # If using update checker dependencies
```

### Basic Usage

#### Automatic Thank You Page
Once activated, the plugin automatically replaces the standard WooCommerce thank you page (`/checkout/order-received/`) with the custom Udia Pods interface. No additional configuration is required for basic functionality.

#### Shortcode Usage
Embed the thank you page anywhere using the shortcode:

```php
[udia_pods_thankyou]
```

Shortcode parameters:
- `order_id` (int): Specific order ID to display (default: 0 = auto-detect)
- `show_tutorial` (string): Display tutorial timeline ('yes' or 'no', default: 'yes')
- `highlight_cta` (string): URL for highlighted call-to-action button
- `show_progress` (string): Show progress indicators ('yes' or 'no', default: 'yes')
- `show_chat_widget` (string): Display chat widget section ('yes' or 'no', default: 'yes')

Examples:
```php
[udia_pods_thankyou order_id="1234" highlight_cta="https://example.com/tutorial"]
[udia_pods_thankyou show_tutorial="no" show_chat_widget="no"]
```

## Configuration & Architecture

### Plugin Structure
```
triqhub-thank-you/
├── assets/
│   ├── css/
│   │   ├── thankyou.css          # Frontend styles
│   │   └── triqhub-admin.css     # Admin styles
│   └── js/
│       └── thankyou.js           # Frontend JavaScript
├── includes/
│   ├── core/
│   │   └── class-triqhub-connector.php  # TriqHub integration
│   ├── class-wc-gateway-woovi-pix.php   # Woovi PIX gateway
│   └── class-woovi-webhook-handler.php  # Webhook processor
├── vendor/                       # Composer dependencies
├── triqhub-thank-you.php         # Main plugin file
└── diagnostic.php                # Diagnostic utility
```

### Payment Gateway Configuration

#### Woovi PIX Setup
1. Navigate to WooCommerce → Settings → Payments
2. Find "Woovi PIX" in the payment methods list
3. Click "Manage" or "Set up"
4. Configure the following settings:
   - **Enable/Disable**: Toggle gateway on/off
   - **Title**: Display name at checkout (e.g., "PIX via Woovi")
   - **Description**: Checkout description shown to customers
   - **Test Mode**: Enable for testing with sandbox credentials
   - **App ID**: Your Woovi API application ID
   - **Test App ID**: Sandbox App ID for testing

#### Obtaining Woovi Credentials
1. Log in to your Woovi dashboard: `https://app.woovi.com`
2. Navigate to API/Plugins section
3. Generate or copy your App ID
4. For testing, use the sandbox environment and corresponding test App ID

### Template System

The plugin uses a modular template rendering system with the following components:

1. **Header Section**: Welcome message, order confirmation, and order number
2. **Order Summary**: Detailed list of purchased items with pricing
3. **Knowledge Card**: Highlighted tutorial access point
4. **Tutorial Timeline**: Step-by-step guidance for product access
5. **Support Block**: Contact options and support resources
6. **Unified PIX Card**: Combined order and payment information for PIX transactions

### CSS Customization

The plugin includes a comprehensive CSS system with the following key classes:

```css
/* Layout */
.utp-wrapper          # Main container
.utp-grid             # Two-column layout container
.utp-card             # Card component base

/* Components */
.utp-hero             # Header section
.utp-order-card       # Order summary card
.utp-cta-card         # Call-to-action card
.utp-timeline         # Tutorial timeline
.utp-support          # Support block
.utp-unified-card     # PIX payment card

/* Elements */
.utp-order-code       # Order number display
.utp-copy             # Copy button
.utp-step-index       # Timeline step number
.utp-pix-timer        # PIX expiration countdown
```

Custom styles can be added via:
1. Child theme CSS files
2. Additional CSS in WordPress Customizer
3. Plugin filter hooks for template modification

## API Reference & Hooks

### WordPress Filters

#### `udia_pods_thankyou_steps`
Filter the tutorial steps displayed in the timeline section.

**Parameters:**
- `$steps` (array): Array of step arrays with 'title' and 'text' keys
- `$atts` (array): Shortcode attributes

**Example:**
```php
add_filter('udia_pods_thankyou_steps', function($steps, $atts) {
    // Add custom step
    $steps[] = [
        'title' => 'Custom Step',
        'text'  => 'Custom step description'
    ];
    
    // Modify existing steps
    $steps[0]['title'] = 'Updated First Step';
    
    return $steps;
}, 10, 2);
```

#### `puc_manual_check_message-{slug}`
Filter update checker messages for better user experience.

**Parameters:**
- `$message` (string): Update message text
- `$status` (string): Update status ('no_update', 'update_available', etc.)

**Example:**
```php
add_filter('puc_manual_check_message-triqhub-thank-you', function($message, $status) {
    if ('update_available' === $status) {
        return '<strong>⚠️ Nova versão disponível!</strong> Clique para atualizar.';
    }
    return $message;
}, 10, 2);
```

### JavaScript API

The plugin exposes a JavaScript object for frontend interactions:

```javascript
UdiaPodsThankyou = {
    orderId: '12345',           // Current order number
    status: 'processing',       // Order status
    billing: 'Customer Name',   // Customer billing name
    highlightCta: 'https://...' // CTA URL from shortcode
}
```

#### Available JavaScript Functions
- **Copy to Clipboard**: `data-copy-target` attribute on buttons
- **Countdown Timer**: Automatic PIX expiration countdown
- **Chat Widget Integration**: `data-open-chat` attribute support

### Woovi Webhook Endpoints

The plugin registers webhook endpoints for payment processing:

- **Endpoint**: `/wc-api/woovi_webhook/`
- **Method**: POST
- **Content-Type**: application/json

Webhook events processed:
- `payment_confirmed`: Updates order status to processing
- `payment_expired`: Updates order status to cancelled
- `payment_created`: Logs payment creation

## Troubleshooting

### Common Issues & Solutions

#### Issue: Thank You Page Not Displaying
**Symptoms:** Standard WooCommerce thank you page appears instead of custom page.

**Solutions:**
1. Verify plugin is activated in WordPress Admin → Plugins
2. Check for theme conflicts by switching to default theme (Storefront/Twenty Twenty)
3. Ensure WooCommerce is installed and activated
4. Check for JavaScript errors in browser console (F12)

#### Issue: Woovi PIX Gateway Not Appearing
**Symptoms:** Woovi PIX not listed in checkout payment options.

**Solutions:**
1. Run diagnostic script: Access `/wp-content/plugins/triqhub-thank-you/diagnostic.php`
2. Verify gateway is enabled in WooCommerce → Settings → Payments
3. Check PHP error logs for class loading issues
4. Ensure `class-wc-gateway-woovi-pix.php` exists in `/includes/` directory

#### Issue: PIX QR Code Not Showing
**Symptoms:** Order summary displays but PIX payment details are missing.

**Solutions:**
1. Verify Woovi API credentials are correctly configured
2. Test API connection using `test-woovi-api.php` utility
3. Check order meta data for `_woovi_qr_code_image` and `_woovi_br_code`
4. Ensure order payment method is `woovi_pix` and status is `on-hold`

#### Issue: Update Checker Not Working
**Symptoms:** Plugin doesn't show update notifications from GitHub.

**Solutions:**
1. Verify `vendor/autoload.php` exists (run `composer install` if missing)
2. Check GitHub repository accessibility from your server
3. Verify GitHub Releases have assets attached
4. Check PHP `allow_url_fopen` setting is enabled

### Diagnostic Tools

The plugin includes several diagnostic utilities:

1. **`diagnostic.php`**: Comprehensive system check for gateway registration
2. **`test-woovi-api.php`**: Direct API connection testing
3. **`quick-test.php`**: Quick functionality verification

**Usage:**
1. Upload desired diagnostic file to plugin directory
2. Access via browser: `https://yoursite.com/wp-content/plugins/triqhub-thank-you/FILENAME.php`
3. Review output for issues and suggested solutions

### Error Messages & Meanings

- **"Plugin Active: ❌ NO"**: Plugin not activated or wrong directory name
- **"WC_Gateway_Woovi_Pix exists: ❌ NO"**: Gateway class failed to load
- **"API Status: 401"**: Invalid or expired Woovi App ID
- **"API Status: 403"**: Insufficient API permissions
- **"API Status: 500"**: Woovi server error, try again later

### Performance Optimization

1. **Caching Considerations**: Exclude thank you pages from cache (`/checkout/order-received/`)
2. **Asset Loading**: CSS/JS loads only on thank you pages
3. **Database Queries**: Minimal queries with order object caching
4. **Image Optimization**: Product thumbnails use WooCommerce optimized sizes

### Support Resources

- **GitHub Issues**: `https://github.com/gustavofullstack/triqhub-thank-you/issues`
- **Woovi Support**: `https://app.woovi.com/support`
- **Udia Pods**: Contact via plugin admin interface

For persistent issues, provide the following information:
1. WordPress and WooCommerce versions
2. PHP version
3. Error logs (PHP and JavaScript)
4. Diagnostic script output
5. Steps to reproduce the issue