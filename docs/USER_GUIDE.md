# TriqHub: Thank You Page - User Guide

## Table of Contents
1. [Overview](#overview)
2. [System Requirements](#system-requirements)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Usage](#usage)
6. [Troubleshooting](#troubleshooting)
7. [Changelog](#changelog)

## Overview

TriqHub: Thank You Page is a proprietary WordPress plugin developed by Udia Pods that replaces the standard WooCommerce thank you page with a branded, feature-rich post-purchase experience. The plugin integrates seamlessly with Elementor, WooCommerce, and includes a complete PIX payment gateway via Woovi/OpenPIX.

### Key Features
- **Custom Thank You Pages**: Replace default WooCommerce order confirmation with branded templates
- **Woovi PIX Gateway**: Complete Brazilian PIX payment processing with QR codes
- **Unified Order Summary**: Combined order details and payment information in a single view
- **Post-Purchase Tutorials**: Step-by-step guides for customers to access purchased products
- **Smart Shortcodes**: Flexible shortcode system for embedding in any page builder
- **Auto-Updates**: GitHub-based update system with release management
- **Responsive Design**: Mobile-optimized interface with modern CSS
- **Multi-Language Support**: Ready for translation with text domain `udia-pods-thankyou`

## System Requirements

### Minimum Requirements
- **WordPress**: 5.6 or higher
- **WooCommerce**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **Memory Limit**: 128MB minimum (256MB recommended)

### Recommended Environment
- **WordPress**: 6.0+
- **WooCommerce**: 7.0+
- **PHP**: 8.0+
- **Memory Limit**: 256MB+
- **SSL Certificate**: Required for production payments

### Compatible Plugins/Themes
- **Page Builders**: Elementor, Gutenberg, Divi, WPBakery
- **WooCommerce Extensions**: Most standard extensions
- **Themes**: Any WordPress theme (Twenty Twenty-Four, Astra, GeneratePress, etc.)
- **Translation**: WPML, Polylang compatible

## Installation

### Method 1: Standard WordPress Installation
1. Download the plugin ZIP file from GitHub Releases
2. Navigate to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin** and select the downloaded ZIP file
4. Click **Install Now** and then **Activate Plugin**
5. The plugin will automatically create necessary database tables and default settings

### Method 2: Manual Installation via FTP/SFTP
1. Download and extract the plugin ZIP file
2. Upload the `triqhub-thank-you` folder to `/wp-content/plugins/`
3. Navigate to **WordPress Admin → Plugins**
4. Locate "TriqHub: Thank You Page" and click **Activate**
5. Verify activation by checking for "Udia Pods" in the admin sidebar

### Method 3: Git Clone (Developers)
```bash
cd /path/to/wp-content/plugins/
git clone https://github.com/gustavofullstack/triqhub-thank-you.git
cd triqhub-thank-you
composer install  # Install dependencies if needed
```

### Post-Installation Checklist
1. **Verify Activation**: Check WordPress admin for any activation errors
2. **Check Dependencies**: Ensure WooCommerce is installed and active
3. **Clear Caches**: Clear any caching plugins (WP Rocket, W3 Total Cache, etc.)
4. **Test Shortcode**: Add `[udia_pods_thankyou]` to a test page to verify rendering
5. **Review Permalinks**: Go to **Settings → Permalinks** and click **Save Changes**

## Configuration

### 1. Plugin Settings

#### Global Settings
Navigate to **WordPress Admin → Udia Pods → Thank You Settings**

| Setting | Description | Default Value | Recommended |
|---------|-------------|---------------|-------------|
| **Enable Plugin** | Master toggle for all plugin functionality | Enabled | Always Enabled |
| **Default Template** | Choose between modern, classic, or minimal templates | Modern | Based on brand |
| **Auto-Override** | Automatically replace WooCommerce thank you pages | Enabled | Enabled for seamless UX |
| **Custom CSS** | Add custom CSS for advanced styling | Empty | Use for minor adjustments |
| **Debug Mode** | Enable detailed logging for troubleshooting | Disabled | Enable only when debugging |

#### Update Settings
The plugin uses GitHub for automatic updates:
- **Update Channel**: `main` (stable) or `develop` (beta)
- **Auto-Update**: Enabled by default
- **Update Notifications**: Shows in WordPress admin and plugin page
- **Manual Check**: Available via **Plugins → TriqHub → Check for Updates**

### 2. Woovi PIX Gateway Configuration

#### Access Gateway Settings
1. Navigate to **WooCommerce → Settings → Payments**
2. Find **Woovi PIX** in the payment methods list
3. Click **Manage** to configure settings

#### Detailed Settings Explanation

##### Basic Settings
| Setting | Type | Description | Critical | Example |
|---------|------|-------------|----------|---------|
| **Enable/Disable** | Checkbox | Master switch for PIX payments | ✅ Yes | Checked |
| **Title** | Text | Display name during checkout | ✅ Yes | "PIX (QR Code)" |
| **Description** | Textarea | Explanation shown to customers | ✅ Yes | "Pague instantaneamente com PIX" |
| **Test Mode** | Checkbox | Switch between test and production | ✅ Yes | Checked for staging |

##### API Credentials
| Setting | Type | Description | Format | Security |
|---------|------|-------------|--------|----------|
| **AppID (Production)** | Password | Production API key from Woovi | 64-character hex | 🔒 Encrypted |
| **AppID (Test)** | Password | Test API key for development | 64-character hex | 🔒 Encrypted |
| **Expiration Time** | Number | Seconds until PIX code expires | 1-86400 | 86400 (24h) |

##### Webhook Configuration
| Setting | Description | URL Format | Configuration |
|---------|-------------|------------|---------------|
| **Webhook URL** | Endpoint for payment notifications | `https://yoursite.com/wc-api/woovi_pix` | Copy to Woovi dashboard |
| **Webhook Secret** | Optional security token | Custom string | Set in both plugin and Woovi |
| **Retry Policy** | Failed webhook retries | 3 attempts, 5min intervals | Automatic |

#### Obtaining Woovi Credentials
1. **Create Woovi Account**: Visit [app.woovi.com](https://app.woovi.com)
2. **Access API Section**: Navigate to **API/Plugins → Credentials**
3. **Generate AppID**: 
   - Production: For live transactions
   - Test: For development and testing
4. **Copy AppID**: Click copy button (do not manually type)
5. **Configure Webhook**: 
   - URL: `https://yourdomain.com/wc-api/woovi_pix`
   - Events: Select all payment events
   - Secret: Optional but recommended

#### Testing Configuration
1. **Enable Test Mode**: Check "Test Mode" in settings
2. **Use Test AppID**: Paste test credentials
3. **Test Transaction**: Make a 1 BRL test purchase
4. **Verify Webhook**: Check WooCommerce logs for `[Woovi Webhook]` entries
5. **Switch to Production**: Only after successful testing

### 3. Shortcode Configuration

#### Basic Shortcode
```php
[udia_pods_thankyou]
```

#### Advanced Shortcode with All Parameters
```php
[udia_pods_thankyou 
    order_id="123" 
    show_tutorial="yes" 
    highlight_cta="https://example.com/tutorial"
    show_progress="yes" 
    show_chat_widget="yes"
]
```

#### Parameter Reference Table

| Parameter | Type | Default | Description | Use Case |
|-----------|------|---------|-------------|----------|
| **order_id** | Integer | 0 | Specific order ID to display | Manual order review pages |
| **show_tutorial** | yes/no | "yes" | Display step-by-step tutorial | First-time customer onboarding |
| **highlight_cta** | URL | "" | Primary call-to-action button URL | Link to video tutorial or download |
| **show_progress** | yes/no | "yes" | Show order progress indicator | Keep customers informed |
| **show_chat_widget** | yes/no | "yes" | Display support chat options | Reduce support tickets |

#### Implementation Examples

##### Elementor Implementation
1. Add **Shortcode** widget to page
2. Enter: `[udia_pods_thankyou show_tutorial="yes"]`
3. Style container with Elementor's design tools

##### Gutenberg Implementation
1. Add **Shortcode** block
2. Paste shortcode with desired parameters
3. Use Group block for additional styling

##### PHP Template Implementation
```php
<?php
if (function_exists('do_shortcode')) {
    echo do_shortcode('[udia_pods_thankyou order_id="' . $order_id . '"]');
}
?>
```

### 4. Template Customization

#### CSS Customization
The plugin provides CSS classes for easy styling:

```css
/* Main container */
.utp-wrapper {
    /* Your styles */
}

/* Hero section */
.utp-hero {
    /* Your styles */
}

/* Order cards */
.utp-card {
    /* Your styles */
}

/* PIX payment section */
.utp-unified-card {
    /* Your styles */
}
```

#### Template Override System
1. Create folder: `/wp-content/themes/your-theme/triqhub-templates/`
2. Copy templates from plugin's `templates/` directory
3. Modify copied files (WordPress will use theme versions)

#### Available Template Files
- `thankyou-basic.php` - Minimal order confirmation
- `thankyou-modern.php` - Default modern interface
- `thankyou-pix.php` - PIX-specific payment view
- `email-order-details.php` - Email template customization

#### Filter Hooks for Developers
```php
// Modify tutorial steps
add_filter('udia_pods_thankyou_steps', function($steps) {
    $steps[] = [
        'title' => 'Custom Step',
        'text'  => 'Custom instruction'
    ];
    return $steps;
});

// Modify order data before display
add_filter('udia_pods_thankyou_order_data', function($order_data, $order) {
    $order_data['custom_field'] = get_post_meta($order->get_id(), '_custom', true);
    return $order_data;
}, 10, 2);
```

### 5. Email Integration

#### Automatic Email Enhancements
The plugin automatically:
- Adds PIX payment instructions to order emails
- Includes QR code images in HTML emails
- Sends payment confirmation emails via Woovi webhooks
- Formats currency according to WooCommerce settings

#### Custom Email Templates
1. Navigate to **WooCommerce → Settings → Emails**
2. Find "Order Confirmation (PIX)" template
3. Click "Manage" to edit content
4. Use shortcode `[woovi_pix_details]` to insert payment info

## Usage

### 1. Standard WooCommerce Checkout Flow

#### Normal Product Purchase
1. Customer adds product to cart
2. Proceeds to checkout
3. Selects "PIX (QR Code)" as payment method
4. Completes order (no immediate payment)
5. **Plugin Action**: Redirects to custom thank you page
6. **Customer Sees**: 
   - Order confirmation with personalized greeting
   - PIX QR code for payment (if PIX selected)
   - Step-by-step tutorial for product access
   - Support contact options

#### PIX Payment Process
1. **QR Code Generation**: Plugin creates unique PIX code via Woovi API
2. **Display**: QR code shown in unified card layout
3. **Payment**: Customer scans with banking app
4. **Confirmation**: Woovi webhook notifies plugin
5. **Order Update**: Status changes from "On Hold" to "Processing"
6. **Notification**: Customer receives confirmation email

### 2. Shortcode Implementation Scenarios

#### Scenario A: Standalone Thank You Page
```php
// Create new WordPress page
Title: "Order Confirmation"
Content: [udia_pods_thankyou]

// Set as thank you page in WooCommerce
WooCommerce → Settings → Advanced → Page setup
```

#### Scenario B: Product-Specific Tutorial Page
```php
// Product A purchase confirmation
[udia_pods_thankyou highlight_cta="https://site.com/product-a-tutorial"]

// Product B purchase confirmation  
[udia_pods_thankyou highlight_cta="https://site.com/product-b-video"]
```

#### Scenario C: Membership/Subscription Flow
```php
// After subscription purchase
[udia_pods_thankyou 
    show_tutorial="yes"
    highlight_cta="https://site.com/member-portal"
    show_chat_widget="no"
]
```

### 3. Admin Management

#### Order Management
- **View PIX Status**: Check order details for PIX payment information
- **Manual Refresh**: Click "Refresh PIX Status" to update from Woovi
- **Resend Instructions**: "Resend PIX Email" button for customer requests

#### Customer Communication
- **Automated Follow-ups**: Plugin can trigger email sequences
- **Payment Reminders**: Optional reminders for pending PIX payments
- **Tutorial Access**: Track which customers viewed tutorials

#### Analytics Integration
- **Conversion Tracking**: Built-in hooks for Google Analytics
- **UTM Parameters**: Automatic tagging of tutorial links
- **Event Tracking**: Customer interactions with thank you page elements

### 4. Multi-Store Configuration

#### Single Plugin, Multiple Stores
```php
// Use WordPress multisite
// Each site gets independent:
// - Woovi API credentials
// - Template customizations  
// - Email settings
// - Shortcode configurations
```

#### Shared Configuration
```php
// Use filters to share settings across sites
add_filter('option_woocommerce_woovi_pix_settings', 'shared_woovi_settings');
```

## Troubleshooting

### FAQ - Frequently Asked Questions

#### Q1: Plugin not appearing in WordPress admin
**A**: 
1. Check PHP version (requires 7.4+)
2. Verify WooCommerce is installed and active
3. Check for plugin conflicts by disabling other plugins
4. Review WordPress debug log: `wp-content/debug.log`

#### Q2: Woovi PIX not showing as payment option
**A**:
1. Navigate to **WooCommerce → Settings → Payments**
2. Verify "Woovi PIX" is enabled (toggle switch)
3. Check that AppID is properly saved (no trailing spaces)
4. Test API connection using `test-woovi-api.php` diagnostic
5. Ensure currency is set to BRL (Brazilian Real)

#### Q3: QR code not generating
**A**:
1. **Check API Credentials**:
   - Test vs Production mode correct?
   - AppID copied exactly (64 characters)?
   - No extra spaces in credential field?
2. **Test API Connection**:
   ```bash
   # Upload test-woovi-api.php to plugin directory
   # Access via browser: domain.com/wp-content/plugins/udia-pods-thankyou/test-woovi-api.php
   ```
3. **Common Issues**:
   - AppID expired (regenerate in Woovi dashboard)
   - Test mode active but using production AppID
   - SSL certificate issues (requires HTTPS)

#### Q4: Webhooks not processing payments
**A**:
1. **Verify Webhook URL**:
   - Correct: `https://yourdomain.com/wc-api/woovi_pix`
   - Accessible from internet (no firewall blocking)
   - Configured in Woovi dashboard
2. **Check Webhook Logs**:
   ```php
   // Enable debug logging in wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
3. **Test Webhook Manually**:
   ```bash
   # Use curl to simulate webhook
   curl -X POST https://yourdomain.com/wc-api/woovi_pix \
     -H "Content-Type: application/json" \
     -d '{"event":"charge.completed","charge":{"correlationID":"123","status":"COMPLETED"}}'
   ```

#### Q5: Thank you page not overriding WooCommerce default
**A**:
1. **Check Theme Compatibility**:
   - Some themes override WooCommerce templates
   - Test with default theme (Twenty Twenty-Four)
2. **Plugin Loading Order**:
   - Ensure plugin loads after WooCommerce
   - Check for conflicting thank you page plugins
3. **Manual Override**:
   ```php
   // Add to theme's functions.php as last resort
   remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);
   add_action('woocommerce_thankyou', 'custom_thankyou_content', 5);
   ```

#### Q6: CSS/JS not loading on thank you page
**A**:
1. **Check Asset Registration**:
   - Assets registered on `init` hook (priority 10)
   - Version numbers match to prevent caching
2. **Caching Issues**:
   - Clear all caches (plugin, theme, server, CDN)
   - Disable minification for plugin assets
3. **Console Errors**:
   - Open browser Developer Tools (F12)
   - Check Console tab for 404 errors
   - Verify asset URLs are correct

### Diagnostic Tools

#### Built-in Diagnostic Scripts
1. **Quick Test** (`quick-test.php`):
   - Tests gateway registration
   - Verifies API connectivity
   - Checks WooCommerce integration

2. **Emergency Debug** (`test-woovi-api.php`):
   - Direct API testing bypassing WordPress
   - Validates AppID format and permissions
   - Tests webhook endpoints

3. **Comprehensive Diagnostic** (`diagnostic.php`):
