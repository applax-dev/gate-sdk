<?php

declare(strict_types=1);

/**
 * Webhook Handling Examples for Appla-X Gate SDK
 *
 * This example demonstrates webhook setup and processing
 */

require_once __DIR__ . '/../vendor/autoload.php';

use ApplaxDev\GateSDK\GateSDK;
use ApplaxDev\GateSDK\Exceptions\GateException;

echo "=== Appla-X Gate SDK - Webhook Handling Examples ===\n\n";

// Initialize SDK
$sdk = new GateSDK(
    apiKey: 'your-api-key-here',
    sandbox: true,
    config: ['debug' => true]
);

echo "✓ SDK initialized for webhook management\n\n";

// ===== 1. CREATE WEBHOOKS =====
echo "1. Create Webhook Endpoints\n";
echo "---------------------------\n";

// Main webhook for order events
try {
    $orderWebhookData = [
        'url' => 'https://yourdomain.com/webhooks/applax/orders',
        'events' => [
            'order.created',
            'order.paid',
            'order.failed',
            'order.cancelled',
            'order.refunded',
            'order.partially_refunded',
            'order.captured',
            'order.authorized'
        ],
        'is_active' => true
    ];

    $orderWebhook = $sdk->createWebhook($orderWebhookData);

    echo "✓ Order webhook created:\n";
    echo "  - ID: " . $orderWebhook['id'] . "\n";
    echo "  - URL: " . $orderWebhook['url'] . "\n";
    echo "  - Secret: " . substr($orderWebhook['secret'], 0, 8) . "...(hidden)\n";
    echo "  - Events: " . implode(', ', $orderWebhook['events']) . "\n";

    $orderWebhookSecret = $orderWebhook['secret'];

} catch (GateException $e) {
    echo "✗ Failed to create order webhook: " . $e->getMessage() . "\n";
}

// Payment-specific webhook
try {
    $paymentWebhookData = [
        'url' => 'https://yourdomain.com/webhooks/applax/payments',
        'events' => [
            'payment.succeeded',
            'payment.failed',
            'payment.refunded',
            'payment.disputed'
        ],
        'is_active' => true
    ];

    $paymentWebhook = $sdk->createWebhook($paymentWebhookData);

    echo "\n✓ Payment webhook created:\n";
    echo "  - ID: " . $paymentWebhook['id'] . "\n";
    echo "  - URL: " . $paymentWebhook['url'] . "\n";
    echo "  - Events: " . implode(', ', $paymentWebhook['events']) . "\n";

    $paymentWebhookSecret = $paymentWebhook['secret'];

} catch (GateException $e) {
    echo "✗ Failed to create payment webhook: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 2. LIST AND MANAGE WEBHOOKS =====
echo "2. List and Manage Webhooks\n";
echo "---------------------------\n";

try {
    $webhooks = $sdk->getWebhooks();

    echo "✓ Total webhooks: " . count($webhooks['results'] ?? []) . "\n";

    foreach ($webhooks['results'] ?? [] as $webhook) {
        echo "  - " . $webhook['url'] . " (" . ($webhook['is_active'] ? 'Active' : 'Inactive') . ")\n";
        echo "    Events: " . implode(', ', $webhook['events']) . "\n";
    }

} catch (GateException $e) {
    echo "✗ Failed to list webhooks: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 3. WEBHOOK VALIDATION EXAMPLES =====
echo "3. Webhook Signature Validation\n";
echo "--------------------------------\n";

// Example webhook payloads
$validPayload = json_encode([
    'event' => 'order.paid',
    'object' => [
        'id' => 'order_123456789',
        'number' => 'DE1001',
        'status' => 'paid',
        'amount' => 29.99,
        'currency' => 'EUR',
        'client' => [
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]
    ],
    'created_at' => '2024-01-15T10:30:00Z'
]);

if (isset($orderWebhookSecret)) {
    // Generate valid signature
    $validSignature = hash_hmac('sha256', $validPayload, $orderWebhookSecret);

    // Test signature validation
    $isValid = $sdk->validateWebhookSignature($validPayload, $validSignature, $orderWebhookSecret);

    echo "✓ Valid signature test: " . ($isValid ? 'PASSED' : 'FAILED') . "\n";

    // Test with invalid signature
    $invalidSignature = 'invalid_signature_12345';
    $isInvalid = $sdk->validateWebhookSignature($validPayload, $invalidSignature, $orderWebhookSecret);

    echo "✓ Invalid signature test: " . (!$isInvalid ? 'PASSED' : 'FAILED') . "\n";

    // Test with tampered payload
    $tamperedPayload = str_replace('29.99', '99.99', $validPayload);
    $isTampered = $sdk->validateWebhookSignature($tamperedPayload, $validSignature, $orderWebhookSecret);

    echo "✓ Tampered payload test: " . (!$isTampered ? 'PASSED' : 'FAILED') . "\n";
}

echo "\n";

// ===== 4. WEBHOOK ENDPOINT IMPLEMENTATION =====
echo "4. Webhook Endpoint Implementation Example\n";
echo "------------------------------------------\n";

echo "Here's how to implement a webhook endpoint in your application:\n\n";

$webhookEndpointCode = <<<'PHP'
<?php
// webhook-endpoint.php

require_once 'vendor/autoload.php';

use ApplaxDev\GateSDK\GateSDK;

// Initialize SDK (for signature validation)
$sdk = new GateSDK('your-api-key', true);

// Get webhook data
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$webhookSecret = 'your-webhook-secret'; // Store securely!

// Validate webhook signature
if (!$sdk->validateWebhookSignature($payload, $signature, $webhookSecret)) {
    http_response_code(401);
    error_log('Invalid webhook signature');
    exit('Unauthorized');
}

// Parse webhook data
$data = json_decode($payload, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    exit('Invalid JSON');
}

$event = $data['event'] ?? '';
$object = $data['object'] ?? [];

// Process webhook events
switch ($event) {
    case 'order.paid':
        handleOrderPaid($object);
        break;

    case 'order.failed':
        handleOrderFailed($object);
        break;

    case 'order.refunded':
        handleOrderRefunded($object);
        break;

    case 'order.cancelled':
        handleOrderCancelled($object);
        break;

    default:
        error_log("Unknown webhook event: $event");
        break;
}

// Respond with success
http_response_code(200);
echo 'OK';

function handleOrderPaid($order) {
    $orderId = $order['id'];
    $amount = $order['amount'];

    // Update your database
    // Send confirmation emails
    // Fulfill the order
    // Generate receipt

    error_log("Order $orderId paid: $amount");
}

function handleOrderFailed($order) {
    $orderId = $order['id'];
    $errorMessage = $order['error_message'] ?? 'Unknown error';

    // Update order status
    // Notify customer
    // Log failure for analysis

    error_log("Order $orderId failed: $errorMessage");
}

function handleOrderRefunded($order) {
    $orderId = $order['id'];
    $refundAmount = $order['refunded_amount'];

    // Update order status
    // Process refund in your system
    // Notify customer

    error_log("Order $orderId refunded: $refundAmount");
}

function handleOrderCancelled($order) {
    $orderId = $order['id'];

    // Update order status
    // Release inventory
    // Notify customer

    error_log("Order $orderId cancelled");
}
PHP;

echo $webhookEndpointCode . "\n\n";

// ===== 5. WEBHOOK TESTING =====
echo "5. Webhook Testing\n";
echo "------------------\n";

echo "Test your webhook endpoints with these curl commands:\n\n";

if (isset($orderWebhookSecret)) {
    $testPayload = json_encode([
        'event' => 'order.paid',
        'object' => [
            'id' => 'test_order_123',
            'number' => 'DE1001',
            'status' => 'paid',
            'amount' => 25.00,
            'currency' => 'EUR'
        ]
    ]);

    $testSignature = hash_hmac('sha256', $testPayload, $orderWebhookSecret);

    echo "curl -X POST https://yourdomain.com/webhooks/applax/orders \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -H 'X-Webhook-Signature: $testSignature' \\\n";
    echo "  -d '$testPayload'\n\n";
}

// ===== 6. WEBHOOK SECURITY BEST PRACTICES =====
echo "6. Webhook Security Best Practices\n";
echo "-----------------------------------\n";

echo "Security checklist for webhook implementation:\n\n";

echo "✓ Signature Verification:\n";
echo "  - Always validate webhook signatures using HMAC-SHA256\n";
echo "  - Use constant-time comparison to prevent timing attacks\n";
echo "  - Reject webhooks with invalid signatures\n\n";

echo "✓ HTTPS Only:\n";
echo "  - Always use HTTPS endpoints for webhooks\n";
echo "  - Verify SSL certificates are valid\n\n";

echo "✓ Idempotency:\n";
echo "  - Handle duplicate webhooks gracefully\n";
echo "  - Use webhook IDs or timestamps to detect duplicates\n";
echo "  - Store processed webhook IDs to prevent reprocessing\n\n";

echo "✓ Error Handling:\n";
echo "  - Return HTTP 200 for successfully processed webhooks\n";
echo "  - Return appropriate error codes for failures\n";
echo "  - Implement retry logic for failed processing\n\n";

echo "✓ Logging and Monitoring:\n";
echo "  - Log all webhook events for debugging\n";
echo "  - Monitor webhook endpoint uptime and response times\n";
echo "  - Set up alerts for webhook failures\n\n";

// ===== 7. WEBHOOK DEBUGGING =====
echo "7. Webhook Debugging Tips\n";
echo "-------------------------\n";

echo "Common debugging scenarios:\n\n";

echo "Problem: Webhook signature validation fails\n";
echo "Solution: \n";
echo "  - Check webhook secret is correct\n";
echo "  - Verify payload is read correctly (use php://input)\n";
echo "  - Ensure no modifications to payload before validation\n";
echo "  - Check HTTP headers are properly received\n\n";

echo "Problem: Webhooks not being received\n";
echo "Solution:\n";
echo "  - Verify webhook URL is accessible from internet\n";
echo "  - Check firewall and security settings\n";
echo "  - Test endpoint manually with curl\n";
echo "  - Review webhook configuration in dashboard\n\n";

echo "Problem: Duplicate webhook processing\n";
echo "Solution:\n";
echo "  - Implement idempotency checks\n";
echo "  - Store processed webhook IDs in database\n";
echo "  - Use database transactions for webhook processing\n\n";

// ===== 8. WEBHOOK MONITORING =====
echo "8. Webhook Monitoring\n";
echo "---------------------\n";

echo "Implement monitoring for webhook health:\n\n";

$monitoringCode = <<<'PHP'
<?php
// webhook-monitor.php

class WebhookMonitor {
    private $db;

    public function logWebhook($event, $orderId, $status, $processingTime) {
        $stmt = $this->db->prepare("
            INSERT INTO webhook_logs
            (event_type, order_id, status, processing_time_ms, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->execute([$event, $orderId, $status, $processingTime]);
    }

    public function getWebhookStats($hours = 24) {
        $stmt = $this->db->prepare("
            SELECT
                event_type,
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'success' THEN 1 END) as successful,
                AVG(processing_time_ms) as avg_processing_time
            FROM webhook_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            GROUP BY event_type
        ");

        $stmt->execute([$hours]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkWebhookHealth() {
        // Check recent webhook success rate
        $stats = $this->getWebhookStats(1);

        foreach ($stats as $stat) {
            $successRate = ($stat['successful'] / $stat['total']) * 100;

            if ($successRate < 95) {
                $this->sendAlert("Low success rate for {$stat['event_type']}: {$successRate}%");
            }

            if ($stat['avg_processing_time'] > 5000) {
                $this->sendAlert("High processing time for {$stat['event_type']}: {$stat['avg_processing_time']}ms");
            }
        }
    }

    private function sendAlert($message) {
        // Send alert via email, Slack, etc.
        error_log("WEBHOOK ALERT: $message");
    }
}
PHP;

echo $monitoringCode . "\n\n";

echo "=== Webhook Handling Examples Complete ===\n\n";

/**
 * Webhook Implementation Summary:
 *
 * 1. ✓ Webhook Creation - Set up webhook endpoints for different events
 * 2. ✓ Signature Validation - Secure webhook verification using HMAC-SHA256
 * 3. ✓ Event Processing - Handle different webhook events appropriately
 * 4. ✓ Security Practices - Implement security best practices
 * 5. ✓ Error Handling - Proper error handling and response codes
 * 6. ✓ Monitoring - Track webhook health and performance
 * 7. ✓ Debugging - Common issues and solutions
 *
 * Next Steps for Production:
 *
 * 1. Set up webhook endpoints on your server
 * 2. Configure webhook URLs in Appla-X dashboard
 * 3. Implement proper database logging
 * 4. Set up monitoring and alerting
 * 5. Test webhook endpoints thoroughly
 * 6. Implement retry logic for failed webhooks
 * 7. Set up backup webhook endpoints for redundancy
 */

echo "ℹ Test your webhook endpoints thoroughly before going live!\n";
echo "ℹ Always implement proper logging and monitoring for webhooks\n";
echo "ℹ Keep your webhook secrets secure and rotate them regularly\n";