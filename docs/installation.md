<div align="center">
  <img src="https://media.appla-x.com/img/applax.png" alt="Applax Logo" width="300"/>
</div>

# Installation Guide

This guide will help you install and set up the Appla-X Gate SDK for PHP.

## Quick Links

- [Configuration Guide](configuration.md)
- [Raw API Access](raw-api-access.md) ⭐ NEW - Access Brands, Charges, Taxes, Subscriptions
- [Payment Methods](payment-methods.md)
- [Webhooks](webhooks.md)

---

## Requirements

- **PHP**: 7.4 or higher
- **Extensions**:
  - `ext-json` - JSON support
  - `ext-curl` - HTTP client support
- **Composer**: Latest version recommended

## Installation

### Via Composer (Recommended)

Install the SDK using Composer:

```bash
composer require applax-dev/gate-sdk
```

### Manual Installation

If you prefer not to use Composer:

1. Download the latest release from [GitHub](https://github.com/applax-dev/gate-sdk/releases)
2. Extract the files to your project directory
3. Include the autoloader:

```php
require_once '/path/to/gate-sdk/src/GateSDK.php';
```

**Note**: Manual installation is not recommended as it doesn't handle dependencies automatically.

## Verification

Verify your installation by running this simple test:

```php
<?php

require_once 'vendor/autoload.php';

use ApplaxDev\GateSDK\GateSDK;

try {
    // This will validate your API key format (but not authenticate)
    $sdk = new GateSDK('test-api-key-32-characters-long-example', true);
    echo "✓ SDK installation successful!\n";
} catch (Exception $e) {
    echo "✗ Installation issue: " . $e->getMessage() . "\n";
}
```

## Quick Setup

### 1. Get Your API Credentials

1. Sign up or log in to [Appla-X Dashboard](https://gate.appla-x.com/)
2. Navigate to API Settings
3. Copy your API key (starts with your merchant ID)

### 2. Basic Initialization

```php
<?php

require_once 'vendor/autoload.php';

use ApplaxDev\GateSDK\GateSDK;

// Initialize for sandbox (testing)
$sdk = new GateSDK(
    apiKey: 'your-api-key-here',
    sandbox: true // Use true for testing, false for production
);
```

### 3. Environment Variables (Recommended)

Create a `.env` file in your project root:

```bash
APPLAX_API_KEY=your-api-key-here
APPLAX_SANDBOX=true
APPLAX_DEBUG=false
APPLAX_TIMEOUT=30
```

Then use environment-based configuration:

```php
<?php

require_once 'vendor/autoload.php';

use ApplaxDev\GateSDK\Config\GateConfig;
use ApplaxDev\GateSDK\GateSDK;

// Load from environment variables
$config = GateConfig::fromEnvironment();
$sdk = GateSDK::fromConfig($config);
```

## Framework Integration

### Laravel

#### 1. Install via Composer

```bash
composer require applax-dev/gate-sdk
```

#### 2. Add to Config

Add to `config/services.php`:

```php
'applax' => [
    'api_key' => env('APPLAX_API_KEY'),
    'sandbox' => env('APPLAX_SANDBOX', true),
    'timeout' => env('APPLAX_TIMEOUT', 30),
],
```

#### 3. Create Service Provider (Optional)

```bash
php artisan make:provider ApplaxServiceProvider
```

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ApplaxDev\GateSDK\GateSDK;
use ApplaxDev\GateSDK\Config\GateConfig;

class ApplaxServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(GateSDK::class, function ($app) {
            $config = new GateConfig(
                config('services.applax.api_key'),
                [
                    'sandbox' => config('services.applax.sandbox'),
                    'timeout' => config('services.applax.timeout'),
                    'debug' => config('app.debug'),
                ]
            );

            return GateSDK::fromConfig($config);
        });
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // Other providers...
    App\Providers\ApplaxServiceProvider::class,
],
```

#### 4. Usage in Laravel

```php
<?php

namespace App\Http\Controllers;

use ApplaxDev\GateSDK\GateSDK;

class PaymentController extends Controller
{
    public function __construct(private GateSDK $sdk)
    {
    }

    public function createOrder()
    {
        $orderData = [
            'client' => [
                'email' => 'customer@example.com',
                'phone' => '371-12345678',
            ],
            'products' => [
                [
                    'title' => 'Product Name',
                    'price' => 29.99,
                    'quantity' => 1,
                ]
            ],
            'currency' => 'EUR',
        ];

        $order = $this->sdk->createOrderModel($orderData);

        return response()->json([
            'order_id' => $order->getId(),
            'payment_url' => $order->getPaymentUrl(),
        ]);
    }
}
```

### Symfony

#### 1. Install via Composer

```bash
composer require applax-dev/gate-sdk
```

#### 2. Configure Service

Add to `config/services.yaml`:

```yaml
parameters:
    applax.api_key: '%env(APPLAX_API_KEY)%'
    applax.sandbox: '%env(bool:APPLAX_SANDBOX)%'

services:
    ApplaxDev\GateSDK\Config\GateConfig:
        arguments:
            $apiKey: '%applax.api_key%'
            $options:
                sandbox: '%applax.sandbox%'
                debug: '%kernel.debug%'

    ApplaxDev\GateSDK\GateSDK:
        factory: ['ApplaxDev\GateSDK\GateSDK', 'fromConfig']
        arguments:
            $config: '@ApplaxDev\GateSDK\Config\GateConfig'
```

#### 3. Usage in Symfony

```php
<?php

namespace App\Controller;

use ApplaxDev\GateSDK\GateSDK;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    public function __construct(private GateSDK $sdk)
    {
    }

    public function createOrder()
    {
        // Same usage as Laravel example
    }
}
```

## Development Tools

### 1. Install Development Dependencies

```bash
composer install --dev
```

### 2. Run Tests

```bash
# Run PHPUnit tests
composer test

# Run tests with coverage
composer test-coverage
```

### 3. Code Quality

```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix

# Run PHPStan analysis
composer phpstan

# Run all quality checks
composer quality
```

### 4. Example Files

The SDK includes example files to help you get started:

- `examples/basic-usage.php` - Basic SDK operations
- `examples/payment-processing.php` - Payment flow examples
- `examples/webhook-handling.php` - Webhook implementation

Run examples:

```bash
php examples/basic-usage.php
```

## Troubleshooting

### Common Issues

#### "Class not found" Error

```bash
# Regenerate Composer autoloader
composer dump-autoload
```

#### cURL SSL Issues

```php
// Disable SSL verification for testing only
$sdk = new GateSDK('your-api-key', true, [
    'verify_ssl' => false // NOT recommended for production
]);
```

#### Memory or Timeout Issues

```php
// Increase timeouts
$sdk = new GateSDK('your-api-key', true, [
    'timeout' => 60,
    'connect_timeout' => 30,
]);
```

### Error Logs

Enable debug mode to see detailed logs:

```php
$sdk = new GateSDK('your-api-key', true, [
    'debug' => true
]);
```

### Support

If you encounter issues:

1. Check the [documentation](https://docs.appla-x.com/)
2. Review [GitHub issues](https://github.com/applax-dev/gate-sdk/issues)
3. Contact support at support@appla-x.com

## Next Steps

After installation:

1. [Configure the SDK](configuration.md) for your environment
2. **NEW:** [Use Raw API Access](raw-api-access.md) to access Brands, Charges, Taxes, Subscriptions
3. Learn about [Payment Methods](payment-methods.md)
4. Set up [Webhooks](webhooks.md) for payment notifications
5. Review security best practices in the main documentation

## Security Note

- Never commit API keys to version control
- Use environment variables for sensitive configuration
- Always validate webhook signatures in production
- Keep the SDK updated to the latest version