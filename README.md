# Appla-X Gate SDK for PHP / LARAVEL 11+

[![Latest Version](https://img.shields.io/packagist/v/applax-dev/gate-sdk.svg)](https://packagist.org/packages/applax-dev/gate-sdk)
[![PHP Version](https://img.shields.io/packagist/php-v/applax-dev/gate-sdk.svg)](https://packagist.org/packages/applax-dev/gate-sdk)
[![License](https://img.shields.io/packagist/l/applax-dev/gate-sdk.svg)](https://packagist.org/packages/applax-dev/gate-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/applax-dev/gate-sdk.svg)](https://packagist.org/packages/applax-dev/gate-sdk)

The official PHP SDK for Appla-X Gate API v0.6, providing secure payment processing, order management, and merchant services.

## ✨ Features

- 🔒 **Enterprise Security** - Secure authentication, input validation, SSL/TLS enforcement
- 🚀 **Production Ready** - Comprehensive error handling, retry logic, logging support
- 📦 **PSR Compatible** - PSR-3 logging, PSR-18 HTTP client support
- 🎯 **Type Safe** - Full PHP 7.4+ type declarations with rich IDE support
- 🔄 **Retry Logic** - Exponential backoff for failed requests
- 📊 **Rich Models** - Structured data objects for all API responses
- 🎛️ **Configurable** - Flexible configuration with environment support
- 📝 **Well Documented** - Comprehensive documentation and examples

## 🔧 Requirements

- PHP 7.4 or higher
- Guzzle HTTP Client 7.0+
- Valid Appla-X Gate API credentials

## 📦 Installation

Install via Composer:

```bash
composer require applax-dev/gate-sdk
```

## 🚀 Quick Start

### Basic Setup

```php
use ApplaxDev\GateSDK\GateSDK;

// Initialize with API key
$sdk = new GateSDK(
    apiKey: 'your-bearer-token-here',
    sandbox: true // Use sandbox for testing
);
```

### Environment-based Configuration

Set up your environment variables:

```bash
APPLAX_API_KEY=your-bearer-token
APPLAX_SANDBOX=true
APPLAX_DEBUG=false
```

Then use environment-based setup:

```php
use ApplaxDev\GateSDK\GateSDK;
use ApplaxDev\GateSDK\Config\GateConfig;

$config = GateConfig::fromEnvironment();
$sdk = GateSDK::fromConfig($config);
```

### Create Your First Order

```php
// Create an order
$orderData = [
    'client' => [
        'email' => 'customer@example.com',
        'phone' => '371-12345678',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ],
    'products' => [
        [
            'title' => 'Premium Subscription',
            'price' => 29.99,
            'quantity' => 1,
        ]
    ],
    'currency' => 'EUR',
    'language' => 'en',
];

use ApplaxDev\GateSDK\Exceptions\GateException;

try {
    $order = $sdk->createOrderModel($orderData);

    echo "Order created: " . $order->getNumber() . "\n";
    echo "Total: " . $order->getFormattedAmount() . "\n";
    echo "Payment URL: " . $order->getPaymentUrl() . "\n";

} catch (GateException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Process Card Payment

```php
// Execute card payment
$cardData = [
    'cardholder_name' => 'John Doe',
    'card_number' => '4111111111111111', // Test card
    'cvv' => '123',
    'exp_month' => 12,
    'exp_year' => 25,
];

$paymentResult = $sdk->executeCardPayment($order->getApiDoUrl(), $cardData);

if ($paymentResult['status'] === 'success') {
    echo "Payment successful! Transaction ID: " . $paymentResult['transaction_id'] . "\n";
}
```

## 🎯 API Coverage

### Orders Management
```php
// Create order
$order = $sdk->createOrder($orderData);

// Get order with rich model
$order = $sdk->getOrderModel($orderId);

// Payment operations
$sdk->capturePayment($orderId, ['amount' => 50.00]);
$sdk->refundPayment($orderId, ['amount' => 25.00, 'reason' => 'Customer request']);
$sdk->cancelOrder($orderId);
```

### Payment Methods

#### Card Payments
```php
$result = $sdk->executeCardPayment($order->getApiDoUrl(), $cardData);
```

#### Digital Wallets
```php
// Apple Pay
$result = $sdk->executeApplePayPayment($order->getApplePayUrl(), $applePayData);

// Google Pay
$result = $sdk->executeGooglePayPayment($order->getGooglePayUrl(), $googlePayData);
```

#### Alternative Payment Methods
```php
// PayPal
$result = $sdk->initPayPalPayment($order->getPayPalInitUrl());

// Klarna
$result = $sdk->initKlarnaPayment($order->getKlarnaInitUrl(), $klarnaData);
```

### Products & Clients
```php
// Product management
$product = $sdk->createProduct($productData);
$products = $sdk->getProducts(['filter_title' => 'Premium']);

// Client management
$client = $sdk->createClient($clientData);
$clientCards = $sdk->getClientCards($clientId);
```

## 📊 Rich Data Models

The SDK provides rich, type-safe models for API responses:

```php
use ApplaxDev\GateSDK\Models\Order;

$order = $sdk->getOrderModel($orderId);

// Rich model methods
echo $order->getNumber();
echo $order->getFormattedAmount();
echo $order->getClient()->getDisplayName();

// Status checks
if ($order->isPayable()) {
    echo "Order can be paid";
}

if ($order->isPaid()) {
    echo "Order is fully paid";
}

// Get available payment methods
$methods = $order->getAvailablePaymentMethods();
// ['card', 'apple_pay', 'paypal', 'klarna']
```

## 🚨 Error Handling

The SDK provides a comprehensive exception hierarchy:

```php
use ApplaxDev\GateSDK\Exceptions\{
    GateException,
    ValidationException,
    AuthenticationException,
    NotFoundException,
    RateLimitException,
    ServerException,
    NetworkException
};

try {
    $order = $sdk->createOrder($orderData);

} catch (ValidationException $e) {
    // Handle validation errors (400)
    echo "Validation error: " . $e->getMessage() . "\n";

    // Get field-specific errors
    if ($e->hasFieldErrors('email')) {
        print_r($e->getFieldErrors('email'));
    }

} catch (AuthenticationException $e) {
    // Handle authentication errors (401, 403)
    echo "Auth error: " . $e->getRecommendedAction() . "\n";

} catch (RateLimitException $e) {
    // Handle rate limiting (429)
    echo "Rate limited. Wait " . $e->getSuggestedWaitTime() . " seconds\n";

} catch (NetworkException $e) {
    // Handle network issues
    if ($e->isRetryable()) {
        echo "Network error, retrying in " . $e->getRecommendedRetryDelay() . "s\n";
    }

} catch (GateException $e) {
    // Handle any other API errors
    echo "API error: " . $e->getMessage() . "\n";
    print_r($e->getErrorDetails());
}
```

## 🔗 Webhook Support

### Setup Webhooks

```php
// Create webhook
$webhook = $sdk->createWebhook([
    'url' => 'https://yourdomain.com/webhooks/applax',
    'events' => ['order.paid', 'order.failed', 'order.refunded']
]);

$webhookSecret = $webhook['secret']; // Store securely
```

### Handle Webhooks

```php
// In your webhook endpoint
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

if (!$sdk->validateWebhookSignature($payload, $signature, $webhookSecret)) {
    http_response_code(401);
    exit('Invalid signature');
}

$data = json_decode($payload, true);

switch ($data['event']) {
    case 'order.paid':
        // Handle successful payment
        $orderId = $data['object']['id'];
        break;

    case 'order.failed':
        // Handle failed payment
        $orderId = $data['object']['id'];
        break;
}

http_response_code(200);
```

## ⚙️ Advanced Configuration

### Custom HTTP Client

```php
use GuzzleHttp\Client;

$customClient = new Client([
    'timeout' => 60,
    'verify' => '/path/to/cacert.pem'
]);

$sdk = new GateSDK(
    apiKey: 'your-api-key',
    sandbox: true,
    httpClient: $customClient
);
```

### Custom Logger

```php
use Monolog\Logger;
use Monolog\Handler\FileHandler;

$logger = new Logger('applax-sdk');
$logger->pushHandler(new FileHandler('applax-sdk.log', Logger::DEBUG));

$sdk = new GateSDK(
    apiKey: 'your-api-key',
    sandbox: true,
    config: ['debug' => true],
    logger: $logger
);
```

## 🧪 Testing

### Test Cards

Use these test cards in sandbox mode:

| Card Type | Number | CVV | Expiry |
|-----------|--------|-----|--------|
| Visa | 4111111111111111 | 123 | 12/25 |
| Mastercard | 5555555555554444 | 123 | 12/25 |
| Amex | 378282246310005 | 1234 | 12/25 |

### Running Tests

```bash
# Install dev dependencies
composer install --dev

# Run tests
composer test

# Run with coverage
composer test-coverage

# Code quality checks
composer quality
```

## 📚 Documentation

- [Installation Guide](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Payment Methods](docs/payment-methods.md)
- [Webhooks](docs/webhooks.md)
- [API Reference](https://docs.appla-x.com/)

## 🤝 Support

- 📖 [Official Documentation](https://docs.appla-x.com/)
- 🐛 [Issue Tracker](https://github.com/applax-dev/gate-sdk/issues)
- 💬 [Support Email](mailto:ike@appla-x.com)

## 📄 License

This SDK is released under the MIT License. See [LICENSE](LICENSE) file for details.

## 🙏 Contributing

Contributions are welcome! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

---

**Ready to start processing payments? Get your API credentials from the [Appla-X Dashboard](https://gate.appla-x.com/) and start building!** 🚀