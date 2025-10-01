# Configuration Guide

This guide covers all configuration options available in the Appla-X Gate SDK.

## Quick Links

- [Installation Guide](installation.md)
- [Raw API Access](raw-api-access.md) ⭐ NEW - Access Brands, Charges, Taxes, Subscriptions
- [Payment Methods](payment-methods.md)
- [Webhooks](webhooks.md)

---

## Quick Start

### Basic Configuration

```php
<?php

use ApplaxDev\GateSDK\GateSDK;

$sdk = new GateSDK(
    apiKey: 'your-api-key-here',
    sandbox: true, // true for testing, false for production
    config: [
        'timeout' => 30,
        'debug' => false,
    ]
);
```

### Configuration Object

```php
<?php

use ApplaxDev\GateSDK\GateSDK;
use ApplaxDev\GateSDK\Config\GateConfig;

$config = new GateConfig('your-api-key', [
    'sandbox' => true,
    'timeout' => 60,
    'connect_timeout' => 10,
    'max_retries' => 3,
    'debug' => false,
]);

$sdk = GateSDK::fromConfig($config);
```

## Configuration Options

### Required Settings

#### API Key
Your unique API key from the Appla-X Dashboard.

```php
$apiKey = 'your-api-key-here'; // Required: 32+ character string
```

**Getting Your API Key:**
1. Log in to [Appla-X Dashboard](https://gate.appla-x.com/)
2. Navigate to Settings → API Keys
3. Copy your API key

**Important:** Keep your API key secure and never expose it in client-side code.

### Environment Settings

#### Sandbox Mode
Controls whether to use sandbox (testing) or production environment.

```php
'sandbox' => true, // Default: true
```

- `true`: Uses sandbox environment for testing
- `false`: Uses production environment for live payments

**Sandbox Features:**
- Test payments without real money
- Simulated payment responses
- Debug-friendly error messages
- No real card processing

### HTTP Configuration

#### Timeout Settings

```php
'timeout' => 30, // Default: 30 seconds
```

Request timeout in seconds. Maximum time to wait for API responses.

```php
'connect_timeout' => 10, // Default: 10 seconds
```

Connection timeout in seconds. Maximum time to wait for connection establishment.

**Recommendations:**
- Standard operations: 30 seconds
- Payment processing: 60+ seconds
- Webhook operations: 15 seconds

#### Retry Configuration

```php
'max_retries' => 3, // Default: 3
```

Number of automatic retry attempts for failed requests.

**Retry Logic:**
- Exponential backoff (1s, 2s, 4s delays)
- Only retries network errors and 5xx server errors
- Does not retry client errors (4xx)

### Debug and Logging

#### Debug Mode

```php
'debug' => false, // Default: false
```

When enabled:
- Logs all HTTP requests and responses
- Shows detailed error information
- Includes request/response headers (sanitized)

**⚠️ Warning:** Never enable debug mode in production as it may log sensitive data.

#### Custom Logger

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('applax-gate');
$logger->pushHandler(new StreamHandler('path/to/applax.log', Logger::DEBUG));

$sdk = new GateSDK('your-api-key', true, [], null, $logger);
```

### Advanced Configuration

#### User Agent

```php
'user_agent' => 'MyApp/1.0 ApplaX-Gate-SDK-PHP/1.0.0', // Custom user agent
```

#### Supported Currencies

```php
'allowed_currencies' => ['EUR', 'USD', 'GBP'], // Limit supported currencies
```

Default supported currencies:
- EUR, USD, GBP, SEK, NOK, DKK
- PLN, CZK, HUF, RON, BGN, HRK

#### Supported Languages

```php
'allowed_languages' => ['en', 'lv', 'de'], // Limit supported languages
```

Default supported languages:
- en, lv, lt, ee, ru, de, fr, es, it, pt, pl, cs, sk, hu, ro, bg, hr

## Environment-Based Configuration

### Using Environment Variables

Create `.env` file:

```bash
# Required
APPLAX_API_KEY=your-api-key-here

# Optional
APPLAX_SANDBOX=true
APPLAX_DEBUG=false
APPLAX_TIMEOUT=30
APPLAX_CONNECT_TIMEOUT=10
APPLAX_MAX_RETRIES=3
```

Load configuration:

```php
<?php

use ApplaxDev\GateSDK\Config\GateConfig;
use ApplaxDev\GateSDK\GateSDK;

$config = GateConfig::fromEnvironment();
$sdk = GateSDK::fromConfig($config);
```

### Laravel Configuration

Add to `config/services.php`:

```php
'applax' => [
    'api_key' => env('APPLAX_API_KEY'),
    'sandbox' => env('APPLAX_SANDBOX', true),
    'timeout' => env('APPLAX_TIMEOUT', 30),
    'connect_timeout' => env('APPLAX_CONNECT_TIMEOUT', 10),
    'max_retries' => env('APPLAX_MAX_RETRIES', 3),
    'debug' => env('APPLAX_DEBUG', false),
],
```

### Symfony Configuration

Add to `config/packages/applax.yaml`:

```yaml
parameters:
    applax.api_key: '%env(APPLAX_API_KEY)%'
    applax.sandbox: '%env(bool:APPLAX_SANDBOX)%'
    applax.timeout: '%env(int:APPLAX_TIMEOUT)%'
    applax.debug: '%env(bool:APPLAX_DEBUG)%'
```

## Custom HTTP Client

### Using Guzzle with Custom Configuration

```php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use ApplaxDev\GateSDK\GateSDK;

// Create custom handler stack
$stack = HandlerStack::create();

// Add retry middleware
$stack->push(Middleware::retry(function ($retries, $request, $response, $reason) {
    return $retries < 3 && ($response === null || $response->getStatusCode() >= 500);
}));

// Create custom HTTP client
$httpClient = new Client([
    'handler' => $stack,
    'timeout' => 60,
    'connect_timeout' => 15,
    'verify' => true,
    'headers' => [
        'X-Custom-Header' => 'MyApp/1.0',
    ],
]);

$sdk = new GateSDK('your-api-key', true, [], $httpClient);
```

### Using PSR-18 HTTP Client

```php
<?php

use Symfony\Component\HttpClient\Psr18Client;
use ApplaxDev\GateSDK\GateSDK;

$httpClient = new Psr18Client();
$sdk = new GateSDK('your-api-key', true, [], $httpClient);
```

## Configuration Validation

### Validate Configuration

```php
<?php

use ApplaxDev\GateSDK\Config\GateConfig;

try {
    $config = new GateConfig('your-api-key', [
        'sandbox' => true,
        'timeout' => 30,
    ]);

    // Configuration is valid
    echo "✓ Configuration valid\n";

} catch (InvalidArgumentException $e) {
    echo "✗ Configuration error: " . $e->getMessage() . "\n";
}
```

### Check Supported Features

```php
<?php

$config = new GateConfig('your-api-key');

// Check currency support
if ($config->isCurrencySupported('EUR')) {
    echo "EUR is supported\n";
}

// Check language support
if ($config->isLanguageSupported('en')) {
    echo "English is supported\n";
}

// Get all supported currencies
$currencies = $config->getAllowedCurrencies();
echo "Supported currencies: " . implode(', ', $currencies) . "\n";
```

## Production Configuration

### Security Best Practices

```php
<?php

// Production configuration example
$config = new GateConfig($_ENV['APPLAX_API_KEY'], [
    'sandbox' => false, // IMPORTANT: false for production
    'debug' => false,   // IMPORTANT: false for production
    'timeout' => 45,
    'connect_timeout' => 15,
    'max_retries' => 3,
    'user_agent' => 'YourApp/1.0',
]);
```

### Performance Optimization

```php
<?php

// Optimized for high-volume production
$config = [
    'timeout' => 30,           // Balance between speed and reliability
    'connect_timeout' => 10,   // Quick connection establishment
    'max_retries' => 2,        // Fewer retries for faster failure detection
    'debug' => false,          // No debug logging overhead
];
```

### Load Balancing

```php
<?php

// Configure for load-balanced environments
use GuzzleHttp\Client;

$httpClient = new Client([
    'timeout' => 30,
    'connect_timeout' => 10,
    'verify' => true,
    'pool_connections' => 10,    // Connection pooling
    'pool_maxsize' => 10,        // Max pool size
    'max_connections' => 100,    // Max concurrent connections
]);

$sdk = new GateSDK($_ENV['APPLAX_API_KEY'], false, [], $httpClient);
```

## Configuration Examples

### Development Environment

```php
<?php

$devConfig = [
    'sandbox' => true,
    'debug' => true,
    'timeout' => 60,        // Longer for debugging
    'max_retries' => 1,     // Fewer retries for faster development
];

$sdk = new GateSDK('dev-api-key', true, $devConfig);
```

### Testing Environment

```php
<?php

$testConfig = [
    'sandbox' => true,
    'debug' => false,       // Clean logs for testing
    'timeout' => 30,
    'max_retries' => 3,
];

$sdk = new GateSDK('test-api-key', true, $testConfig);
```

### Staging Environment

```php
<?php

$stagingConfig = [
    'sandbox' => false,     // Use production-like environment
    'debug' => false,
    'timeout' => 30,
    'max_retries' => 3,
];

$sdk = new GateSDK('staging-api-key', false, $stagingConfig);
```

### Production Environment

```php
<?php

$productionConfig = [
    'sandbox' => false,
    'debug' => false,
    'timeout' => 30,
    'connect_timeout' => 10,
    'max_retries' => 3,
];

$sdk = new GateSDK($_ENV['APPLAX_API_KEY'], false, $productionConfig);
```

## Troubleshooting Configuration

### Common Configuration Errors

#### Invalid API Key

```
InvalidArgumentException: API key cannot be empty
InvalidArgumentException: API key appears to be invalid (too short)
```

**Solution:** Check your API key format and length (must be 32+ characters).

#### Connection Timeouts

```
NetworkException: Network error: cURL error 28: Operation timed out
```

**Solution:** Increase timeout values:

```php
$config = [
    'timeout' => 60,
    'connect_timeout' => 30,
];
```

#### SSL/TLS Issues

```
NetworkException: cURL error 60: SSL certificate problem
```

**Solution:** Check SSL configuration:

```php
// For development only - NOT recommended for production
$httpClient = new Client(['verify' => false]);
$sdk = new GateSDK('api-key', true, [], $httpClient);
```

### Debug Configuration Issues

```php
<?php

use ApplaxDev\GateSDK\Config\GateConfig;

// Enable debug mode temporarily
$config = new GateConfig('your-api-key', ['debug' => true]);
$sdk = GateSDK::fromConfig($config);

// Test configuration
try {
    $sdk->getProducts(['limit' => 1]);
    echo "✓ Configuration working correctly\n";
} catch (Exception $e) {
    echo "✗ Configuration issue: " . $e->getMessage() . "\n";
}
```

## Configuration Reference

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `sandbox` | boolean | `true` | Enable sandbox mode |
| `timeout` | integer | `30` | Request timeout (seconds) |
| `connect_timeout` | integer | `10` | Connection timeout (seconds) |
| `max_retries` | integer | `3` | Maximum retry attempts |
| `debug` | boolean | `false` | Enable debug logging |
| `user_agent` | string | `ApplaX-Gate-SDK-PHP/1.0.0` | Custom user agent |
| `allowed_currencies` | array | All supported | Limit supported currencies |
| `allowed_languages` | array | All supported | Limit supported languages |

## Next Steps

After configuring the SDK:

1. Learn about [Payment Methods](PAYMENT_METHODS.md)
2. Set up [Webhooks](WEBHOOKS.md)
3. Review the main README for API usage examples
4. Check the examples folder for implementation patterns