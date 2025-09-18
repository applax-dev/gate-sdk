<?php

declare(strict_types=1);

/**
 * Basic Usage Examples for Appla-X Gate SDK
 *
 * This example demonstrates basic SDK initialization and simple operations
 */

require_once __DIR__ . '/../vendor/autoload.php';

use ApplaxDev\GateSDK\GateSDK;
use ApplaxDev\GateSDK\Config\GateConfig;
use ApplaxDev\GateSDK\Exceptions\GateException;
use ApplaxDev\GateSDK\Models\Order;

echo "=== Appla-X Gate SDK - Basic Usage Examples ===\n\n";

// ===== 1. BASIC SDK INITIALIZATION =====
echo "1. Basic SDK Initialization\n";
echo "---------------------------\n";

try {
    // Method 1: Direct initialization
    $sdk = new GateSDK(
        apiKey: 'your-api-key-here',
        sandbox: true,
        config: [
            'timeout' => 30,
            'debug' => true
        ]
    );
    echo "✓ SDK initialized successfully\n";

    // Method 2: Configuration-based initialization
    $config = new GateConfig('your-api-key-here', [
        'sandbox' => true,
        'debug' => true
    ]);

    $sdk = GateSDK::fromConfig($config);
    echo "✓ SDK initialized from config\n";

} catch (Exception $e) {
    echo "✗ Initialization failed: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 2. CREATE A SIMPLE ORDER =====
echo "2. Create a Simple Order\n";
echo "------------------------\n";

try {
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
                'description' => 'Monthly premium subscription'
            ]
        ],
        'currency' => 'EUR',
        'language' => 'en',
        'notes' => 'Test order created via SDK'
    ];

    // Create order and get Order model
    $order = $sdk->createOrderModel($orderData);

    echo "✓ Order created successfully:\n";
    echo "  - Order ID: " . $order->getId() . "\n";
    echo "  - Order Number: " . $order->getNumber() . "\n";
    echo "  - Status: " . $order->getStatus() . "\n";
    echo "  - Total: " . $order->getFormattedAmount() . "\n";
    echo "  - Client: " . $order->getClient()->getDisplayName() . "\n";
    echo "  - Payment URL: " . $order->getPaymentUrl() . "\n";
    echo "  - Available Payment Methods: " . implode(', ', $order->getAvailablePaymentMethods()) . "\n";

    // Store order ID for later use
    $orderId = $order->getId();

} catch (GateException $e) {
    echo "✗ Order creation failed: " . $e->getMessage() . "\n";

    // Show validation errors if available
    if ($e instanceof \ApplaxDev\GateSDK\Exceptions\ValidationException) {
        echo "  Validation errors:\n";
        foreach ($e->getValidationErrors() as $field => $errors) {
            echo "    - {$field}: " . implode(', ', (array)$errors) . "\n";
        }
    }
}

echo "\n";

// ===== 3. RETRIEVE ORDER INFORMATION =====
echo "3. Retrieve Order Information\n";
echo "-----------------------------\n";

if (isset($orderId)) {
    try {
        $order = $sdk->getOrderModel($orderId);

        echo "✓ Order retrieved successfully:\n";
        echo "  - Status: " . $order->getStatus() . "\n";
        echo "  - Is Payable: " . ($order->isPayable() ? 'Yes' : 'No') . "\n";
        echo "  - Is Paid: " . ($order->isPaid() ? 'Yes' : 'No') . "\n";
        echo "  - Products Count: " . count($order->getProducts()) . "\n";

        // Show product details
        foreach ($order->getProducts() as $product) {
            echo "  - Product: " . $product->getTitle() . " - " . $product->getFormattedPrice() . "\n";
        }

    } catch (GateException $e) {
        echo "✗ Failed to retrieve order: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// ===== 4. PRODUCT MANAGEMENT =====
echo "4. Product Management\n";
echo "---------------------\n";

try {
    // Create a product
    $productData = [
        'brand' => 'b644318f-7ce0-43bd-81b8-3026d7a554d6', // Replace with your brand ID
        'title' => 'Digital Download',
        'currency' => 'EUR',
        'price' => 19.99,
        'description' => 'Premium digital content download'
    ];

    $product = $sdk->createProduct($productData);
    echo "✓ Product created: " . $product['title'] . " (ID: " . $product['id'] . ")\n";

    // Get products list
    $products = $sdk->getProducts(['filter_title' => 'Digital']);
    echo "✓ Found " . count($products['results'] ?? []) . " products matching 'Digital'\n";

} catch (GateException $e) {
    echo "✗ Product management failed: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 5. CLIENT MANAGEMENT =====
echo "5. Client Management\n";
echo "--------------------\n";

try {
    // Create a client
    $clientData = [
        'email' => 'jane.doe@example.com',
        'phone' => '371-87654321',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'birth_date' => '1985-03-15'
    ];

    $client = $sdk->createClient($clientData);
    echo "✓ Client created: " . $client['first_name'] . " " . $client['last_name'] . " (ID: " . $client['id'] . ")\n";

    // Get clients list
    $clients = $sdk->getClients();
    echo "✓ Total clients: " . count($clients['results'] ?? []) . "\n";

} catch (GateException $e) {
    echo "✗ Client management failed: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 6. WEBHOOK MANAGEMENT =====
echo "6. Webhook Management\n";
echo "---------------------\n";

try {
    // Create a webhook
    $webhookData = [
        'url' => 'https://yourdomain.com/webhooks/applax',
        'events' => [
            'order.paid',
            'order.failed',
            'order.refunded'
        ],
        'is_active' => true
    ];

    $webhook = $sdk->createWebhook($webhookData);
    echo "✓ Webhook created: " . $webhook['url'] . "\n";
    echo "  - Secret: " . substr($webhook['secret'], 0, 8) . "...\n";
    echo "  - Events: " . implode(', ', $webhook['events']) . "\n";

    // List webhooks
    $webhooks = $sdk->getWebhooks();
    echo "✓ Total webhooks: " . count($webhooks['results'] ?? []) . "\n";

} catch (GateException $e) {
    echo "✗ Webhook management failed: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 7. UTILITY FUNCTIONS =====
echo "7. Utility Functions\n";
echo "--------------------\n";

// Currency formatting
$formattedAmount = $sdk->formatCurrency(29.99, 'EUR');
echo "✓ Formatted currency: " . $formattedAmount . "\n";

// Currency validation
$validCurrency = $sdk->validateCurrency('EUR');
echo "✓ EUR is valid currency: " . ($validCurrency ? 'Yes' : 'No') . "\n";

$invalidCurrency = $sdk->validateCurrency('XXX');
echo "✓ XXX is valid currency: " . ($invalidCurrency ? 'Yes' : 'No') . "\n";

// Webhook signature validation (example)
$payload = '{"event":"order.paid","object":{"id":"test-order-id"}}';
$secret = 'webhook-secret-key';
$signature = hash_hmac('sha256', $payload, $secret);

$isValidSignature = $sdk->validateWebhookSignature($payload, $signature, $secret);
echo "✓ Webhook signature validation: " . ($isValidSignature ? 'Valid' : 'Invalid') . "\n";

echo "\n";

// ===== 8. ERROR HANDLING DEMONSTRATION =====
echo "8. Error Handling Demonstration\n";
echo "--------------------------------\n";

try {
    // This will trigger a validation error
    $invalidOrderData = [
        'client' => [
            'email' => 'invalid-email-format', // Invalid email
        ],
        'products' => [] // Empty products array
    ];

    $sdk->createOrder($invalidOrderData);

} catch (\ApplaxDev\GateSDK\Exceptions\ValidationException $e) {
    echo "✓ Caught ValidationException: " . $e->getMessage() . "\n";
    echo "  - Invalid fields: " . implode(', ', $e->getInvalidFields()) . "\n";

} catch (\ApplaxDev\GateSDK\Exceptions\AuthenticationException $e) {
    echo "✓ Caught AuthenticationException: " . $e->getRecommendedAction() . "\n";

} catch (GateException $e) {
    echo "✓ Caught GateException: " . $e->getMessage() . "\n";
}

echo "\n=== Basic Usage Examples Complete ===\n";

// ===== CONFIGURATION EXAMPLES =====
echo "\n=== Configuration Examples ===\n";

// Environment-based configuration (requires environment variables)
try {
    // Set these in your environment:
    // APPLAX_API_KEY=your-api-key
    // APPLAX_SANDBOX=true
    // APPLAX_DEBUG=false

    // Uncomment to test environment configuration:
    // $config = GateConfig::fromEnvironment();
    // $sdk = GateSDK::fromConfig($config);
    // echo "✓ Environment-based configuration loaded\n";

    echo "ℹ Environment configuration available (set APPLAX_* variables)\n";

} catch (Exception $e) {
    echo "ℹ Environment configuration not set: " . $e->getMessage() . "\n";
}

echo "\n=== All Examples Complete ===\n";

/**
 * Next Steps:
 *
 * 1. Replace 'your-api-key-here' with your actual API key
 * 2. Set up your webhook endpoint URL
 * 3. Configure environment variables for production
 * 4. Test with real payment methods (see payment-processing.php)
 * 5. Implement webhook handling (see webhook-handling.php)
 */