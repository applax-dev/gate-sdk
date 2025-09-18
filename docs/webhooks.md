# Webhooks Guide

This guide covers webhook implementation, security, and best practices for the Appla-X Gate SDK.

## Overview

Webhooks are HTTP POST requests sent by the Appla-X Gate API to your server when specific events occur. They provide real-time notifications about payment status changes, order updates, and other important events.

### Why Use Webhooks?

- **Real-time Updates** - Get immediate notifications of payment status changes
- **Reliability** - Handle network issues and temporary downtime
- **Security** - Verify payment status server-side, not client-side
- **Automation** - Trigger business logic automatically
- **User Experience** - Update customers instantly

## Supported Events

### Order Events (v0.6)

| Event | Description | When Triggered |
|-------|-------------|----------------|
| `order.created` | Order created | When a new order is created |
| `order.paid` | Order fully paid | When payment is successfully completed |
| `order.failed` | Order payment failed | When payment fails permanently |
| `order.cancelled` | Order cancelled | When order is cancelled |
| `order.refunded` | Order fully refunded | When full refund is processed |
| `order.partially_refunded` | Order partially refunded | When partial refund is processed |
| `order.captured` | Payment captured | When authorized payment is captured |
| `order.authorized` | Payment authorized | When payment is authorized but not captured |
| `order.expired` | Order expired | When unpaid order expires |
| `order.reversed` | Payment reversed | When authorized payment is reversed |

### Payment Events (v0.6)

| Event | Description | When Triggered |
|-------|-------------|----------------|
| `payment.succeeded` | Payment successful | When individual payment succeeds |
| `payment.failed` | Payment failed | When individual payment fails |
| `payment.refunded` | Payment refunded | When payment is refunded |
| `payment.disputed` | Payment disputed | When chargeback is initiated |

### Client Events (v0.6)

| Event | Description | When Triggered |
|-------|-------------|----------------|
| `client.created` | Client created | When new client is registered |
| `client.updated` | Client updated | When client data is modified |
| `client.deleted` | Client deleted | When client is deleted |

## Webhook Setup

### 1. Create Webhook Endpoint

```php
<?php

use ApplaxDev\GateSDK\GateSDK;

$sdk = new GateSDK('your-api-key', true); // true for sandbox

// Create webhook for order events
$webhook = $sdk->createWebhook([
    'url' => 'https://yourdomain.com/webhooks/applax/orders',
    'events' => [
        'order.paid',
        'order.failed',
        'order.cancelled',
        'order.refunded',
    ],
    'is_active' => true,
]);

echo "Webhook created:\n";
echo "- ID: " . $webhook['id'] . "\n";
echo "- URL: " . $webhook['url'] . "\n";
echo "- Secret: " . $webhook['secret'] . "\n"; // Store this securely!
```

### 2. Manage Webhooks

```php
<?php

// List all webhooks
$webhooks = $sdk->getWebhooks();
foreach ($webhooks['results'] as $webhook) {
    echo "Webhook: " . $webhook['url'] . " (Active: " .
         ($webhook['is_active'] ? 'Yes' : 'No') . ")\n";
}

// Get specific webhook
$webhook = $sdk->getWebhook('webhook-id-here');

// Update webhook
$updated = $sdk->updateWebhook('webhook-id-here', [
    'url' => 'https://yourdomain.com/webhooks/new-endpoint',
    'is_active' => false,
]);

// Delete webhook
$sdk->deleteWebhook('webhook-id-here');
```

## Webhook Endpoint Implementation

### Basic Webhook Handler

```php
<?php
// webhook-endpoint.php

require_once 'vendor/autoload.php';

use ApplaxDev\GateSDK\GateSDK;

// Initialize SDK for signature validation
$sdk = new GateSDK('your-api-key', true);

// Webhook configuration
$webhookSecret = 'your-webhook-secret'; // From webhook creation response

// Get webhook data
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

// Validate webhook signature
if (!$sdk->validateWebhookSignature($payload, $signature, $webhookSecret)) {
    http_response_code(401);
    error_log('Invalid webhook signature from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    exit('Unauthorized');
}

// Parse webhook data
$data = json_decode($payload, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    error_log('Invalid JSON in webhook payload');
    exit('Invalid JSON');
}

// Process webhook
try {
    processWebhook($data);
    http_response_code(200);
    echo 'OK';
} catch (Exception $e) {
    http_response_code(500);
    error_log('Webhook processing failed: ' . $e->getMessage());
    echo 'Internal Server Error';
}

function processWebhook($data) {
    $event = $data['event'] ?? '';
    $object = $data['object'] ?? [];
    $webhookId = $data['id'] ?? '';
    $createdAt = $data['created_at'] ?? '';

    // Log webhook for debugging
    error_log("Processing webhook: {$event} for object: " . ($object['id'] ?? 'unknown'));

    // Handle different events
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
            error_log("Unhandled webhook event: {$event}");
            break;
    }
}

function handleOrderPaid($order) {
    $orderId = $order['id'];
    $orderNumber = $order['number'];
    $amount = $order['amount'];
    $currency = $order['currency'];

    // Update your database
    updateOrderStatus($orderId, 'paid');

    // Send confirmation email
    sendOrderConfirmationEmail($order);

    // Fulfill the order
    fulfillOrder($orderId);

    // Update inventory
    updateInventory($order['products']);

    // Generate receipt
    generateReceipt($orderId);

    error_log("Order {$orderNumber} marked as paid: {$amount} {$currency}");
}

function handleOrderFailed($order) {
    $orderId = $order['id'];
    $orderNumber = $order['number'];
    $errorMessage = $order['error_message'] ?? 'Payment failed';

    // Update order status
    updateOrderStatus($orderId, 'failed');

    // Send failure notification
    sendPaymentFailureEmail($order, $errorMessage);

    // Release reserved inventory
    releaseInventory($order['products']);

    error_log("Order {$orderNumber} failed: {$errorMessage}");
}

function handleOrderRefunded($order) {
    $orderId = $order['id'];
    $orderNumber = $order['number'];
    $refundedAmount = $order['refunded_amount'];

    // Update order status
    updateOrderStatus($orderId, 'refunded');

    // Process refund in your system
    processRefundInERP($orderId, $refundedAmount);

    // Send refund confirmation
    sendRefundConfirmationEmail($order);

    // Update inventory if needed
    if ($refundedAmount == $order['amount']) {
        restoreInventory($order['products']);
    }

    error_log("Order {$orderNumber} refunded: {$refundedAmount}");
}

function handleOrderCancelled($order) {
    $orderId = $order['id'];
    $orderNumber = $order['number'];

    // Update order status
    updateOrderStatus($orderId, 'cancelled');

    // Release inventory
    releaseInventory($order['products']);

    // Send cancellation email
    sendOrderCancellationEmail($order);

    error_log("Order {$orderNumber} cancelled");
}

// Helper functions (implement based on your system)
function updateOrderStatus($orderId, $status) {
    // Update in your database
}

function sendOrderConfirmationEmail($order) {
    // Send email to customer
}

function fulfillOrder($orderId) {
    // Trigger order fulfillment
}

function updateInventory($products) {
    // Update product inventory
}

function generateReceipt($orderId) {
    // Generate and store receipt
}
```

### Advanced Webhook Handler with Database Logging

```php
<?php
// advanced-webhook-handler.php

class WebhookHandler {
    private $pdo;
    private $sdk;
    private $webhookSecret;

    public function __construct(PDO $pdo, GateSDK $sdk, string $webhookSecret) {
        $this->pdo = $pdo;
        $this->sdk = $sdk;
        $this->webhookSecret = $webhookSecret;
    }

    public function handle() {
        try {
            // Get and validate webhook
            $payload = file_get_contents('php://input');
            $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

            if (!$this->validateSignature($payload, $signature)) {
                $this->logWebhook('INVALID_SIGNATURE', null, $payload);
                http_response_code(401);
                return false;
            }

            $data = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logWebhook('INVALID_JSON', null, $payload);
                http_response_code(400);
                return false;
            }

            // Check for duplicate webhook
            if ($this->isDuplicateWebhook($data['id'] ?? null)) {
                $this->logWebhook('DUPLICATE', $data['event'] ?? null, $payload);
                http_response_code(200); // Return 200 for duplicates
                return true;
            }

            // Process webhook
            $this->processWebhook($data);
            $this->logWebhook('SUCCESS', $data['event'] ?? null, $payload);

            http_response_code(200);
            return true;

        } catch (Exception $e) {
            $this->logWebhook('ERROR', $data['event'] ?? null, $payload ?? null, $e->getMessage());
            http_response_code(500);
            return false;
        }
    }

    private function validateSignature(string $payload, string $signature): bool {
        return $this->sdk->validateWebhookSignature($payload, $signature, $this->webhookSecret);
    }

    private function isDuplicateWebhook(?string $webhookId): bool {
        if (!$webhookId) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM webhook_logs WHERE webhook_id = ? AND status = 'SUCCESS'"
        );
        $stmt->execute([$webhookId]);

        return $stmt->fetchColumn() > 0;
    }

    private function processWebhook(array $data): void {
        $event = $data['event'] ?? '';
        $object = $data['object'] ?? [];

        // Start transaction
        $this->pdo->beginTransaction();

        try {
            switch ($event) {
                case 'order.paid':
                    $this->handleOrderPaid($object);
                    break;

                case 'order.failed':
                    $this->handleOrderFailed($object);
                    break;

                case 'order.refunded':
                    $this->handleOrderRefunded($object);
                    break;

                case 'order.cancelled':
                    $this->handleOrderCancelled($object);
                    break;

                default:
                    error_log("Unhandled webhook event: {$event}");
            }

            $this->pdo->commit();

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function handleOrderPaid(array $order): void {
        $orderId = $order['id'];

        // Update order in database
        $stmt = $this->pdo->prepare(
            "UPDATE orders SET status = 'paid', paid_at = NOW() WHERE external_id = ?"
        );
        $stmt->execute([$orderId]);

        // Create payment record
        $stmt = $this->pdo->prepare(
            "INSERT INTO payments (order_id, amount, currency, status, created_at)
             VALUES (?, ?, ?, 'completed', NOW())"
        );
        $stmt->execute([
            $order['internal_order_id'] ?? null,
            $order['amount'],
            $order['currency']
        ]);

        // Send notifications
        $this->sendOrderPaidNotifications($order);
    }

    private function logWebhook(string $status, ?string $event, ?string $payload, ?string $error = null): void {
        $stmt = $this->pdo->prepare(
            "INSERT INTO webhook_logs (webhook_id, event_type, status, payload, error_message, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );

        $data = $payload ? json_decode($payload, true) : null;
        $webhookId = $data['id'] ?? null;

        $stmt->execute([
            $webhookId,
            $event,
            $status,
            $payload,
            $error
        ]);
    }

    private function sendOrderPaidNotifications(array $order): void {
        // Queue email notifications
        // Update external systems
        // Trigger business logic
    }
}

// Usage
$pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
$sdk = new GateSDK('your-api-key', true);
$handler = new WebhookHandler($pdo, $sdk, 'your-webhook-secret');

$handler->handle();
```

## Webhook Security

### Signature Validation

The SDK automatically handles HMAC-SHA256 signature validation:

```php
<?php

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$secret = 'your-webhook-secret';

// The SDK handles this validation
$isValid = $sdk->validateWebhookSignature($payload, $signature, $secret);

if (!$isValid) {
    http_response_code(401);
    exit('Invalid signature');
}
```

### Manual Signature Validation

If needed, you can validate signatures manually:

```php
<?php

function validateWebhookSignature(string $payload, string $signature, string $secret): bool {
    $expectedSignature = hash_hmac('sha256', $payload, $secret);

    // Use hash_equals to prevent timing attacks
    return hash_equals($expectedSignature, $signature);
}
```

### Security Best Practices

1. **Always Validate Signatures** - Never process unsigned webhooks
2. **Use HTTPS** - Only accept webhooks over HTTPS
3. **Whitelist IPs** - Restrict webhook endpoints to Appla-X IPs
4. **Rate Limiting** - Implement rate limiting for webhook endpoints
5. **Idempotency** - Handle duplicate webhooks gracefully
6. **Secure Storage** - Store webhook secrets securely

## Testing Webhooks

### Local Development with ngrok

```bash
# Install ngrok
npm install -g ngrok

# Start your local server
php -S localhost:8000

# Expose to internet
ngrok http 8000

# Use the https URL for webhook configuration
# https://abc123.ngrok.io/webhook-endpoint.php
```

### Webhook Testing Script

```php
<?php
// test-webhook.php

require_once 'vendor/autoload.php';

use ApplaxDev\GateSDK\GateSDK;

$sdk = new GateSDK('your-api-key', true);

// Create test webhook
$webhook = $sdk->createWebhook([
    'url' => 'https://your-ngrok-url.ngrok.io/webhook-endpoint.php',
    'events' => ['order.paid'],
    'is_active' => true,
]);

echo "Test webhook created: " . $webhook['id'] . "\n";
echo "Secret: " . $webhook['secret'] . "\n";

// Create test order to trigger webhook
$order = $sdk->createOrderModel([
    'client' => [
        'email' => 'test@example.com',
        'phone' => '371-12345678',
    ],
    'products' => [
        [
            'title' => 'Test Product',
            'price' => 1.00,
            'quantity' => 1,
        ]
    ],
    'currency' => 'EUR',
]);

echo "Test order created: " . $order->getId() . "\n";
echo "Process payment to trigger webhook\n";
```

### Webhook Simulator

```php
<?php
// simulate-webhook.php

function simulateWebhook(string $url, string $secret, array $eventData): void {
    $payload = json_encode($eventData);
    $signature = hash_hmac('sha256', $payload, $secret);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'X-Webhook-Signature: ' . $signature,
                'User-Agent: ApplaX-Gate-Webhook/1.0',
            ],
            'content' => $payload,
        ],
    ]);

    $response = file_get_contents($url, false, $context);

    echo "Response: " . $response . "\n";
    echo "HTTP Code: " . $http_response_header[0] . "\n";
}

// Simulate order.paid webhook
$eventData = [
    'id' => 'webhook_' . uniqid(),
    'event' => 'order.paid',
    'created_at' => date('c'),
    'object' => [
        'id' => 'order_' . uniqid(),
        'number' => 'DE1001',
        'status' => 'paid',
        'amount' => 29.99,
        'currency' => 'EUR',
        'client' => [
            'id' => 'client_123',
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ],
        'products' => [
            [
                'id' => 'product_123',
                'title' => 'Test Product',
                'price' => 29.99,
                'quantity' => 1,
            ]
        ],
    ],
];

simulateWebhook(
    'http://localhost:8000/webhook-endpoint.php',
    'your-webhook-secret',
    $eventData
);
```

## Webhook Monitoring

### Database Schema

```sql
CREATE TABLE webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    webhook_id VARCHAR(255),
    event_type VARCHAR(100),
    status ENUM('SUCCESS', 'INVALID_SIGNATURE', 'INVALID_JSON', 'DUPLICATE', 'ERROR'),
    payload TEXT,
    error_message TEXT,
    processing_time_ms INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_webhook_id (webhook_id),
    INDEX idx_event_type (event_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

### Monitoring Dashboard

```php
<?php
// webhook-dashboard.php

class WebhookDashboard {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getStats(int $hours = 24): array {
        $stmt = $this->pdo->prepare("
            SELECT
                event_type,
                status,
                COUNT(*) as count,
                AVG(processing_time_ms) as avg_processing_time
            FROM webhook_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            GROUP BY event_type, status
            ORDER BY event_type, status
        ");

        $stmt->execute([$hours]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentErrors(int $limit = 50): array {
        $stmt = $this->pdo->prepare("
            SELECT webhook_id, event_type, error_message, created_at
            FROM webhook_logs
            WHERE status = 'ERROR'
            ORDER BY created_at DESC
            LIMIT ?
        ");

        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSuccessRate(int $hours = 24): float {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(CASE WHEN status = 'SUCCESS' THEN 1 END) * 100.0 / COUNT(*) as success_rate
            FROM webhook_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        ");

        $stmt->execute([$hours]);
        return $stmt->fetchColumn() ?? 0.0;
    }
}
```

### Alerting System

```php
<?php
// webhook-alerts.php

class WebhookAlerts {
    private $pdo;

    public function checkWebhookHealth(): void {
        $dashboard = new WebhookDashboard($this->pdo);

        // Check success rate
        $successRate = $dashboard->getSuccessRate(1); // Last hour
        if ($successRate < 95.0) {
            $this->sendAlert("Low webhook success rate: {$successRate}%");
        }

        // Check for recent errors
        $errors = $dashboard->getRecentErrors(10);
        $errorCount = count($errors);

        if ($errorCount > 5) {
            $this->sendAlert("High error rate: {$errorCount} errors in recent webhooks");
        }

        // Check processing time
        $stats = $dashboard->getStats(1);
        foreach ($stats as $stat) {
            if ($stat['avg_processing_time'] > 5000) { // 5 seconds
                $this->sendAlert("Slow webhook processing for {$stat['event_type']}: {$stat['avg_processing_time']}ms");
            }
        }
    }

    private function sendAlert(string $message): void {
        // Send to Slack, email, PagerDuty, etc.
        error_log("WEBHOOK ALERT: {$message}");

        // Example: Send to Slack
        $this->sendSlackAlert($message);
    }

    private function sendSlackAlert(string $message): void {
        $webhook_url = 'https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK';

        $payload = json_encode([
            'text' => "🚨 Webhook Alert: {$message}",
            'channel' => '#alerts',
            'username' => 'webhook-monitor',
        ]);

        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}

// Run health checks
$pdo = new PDO('mysql:host=localhost;dbname=yourdb', $username, $password);
$alerts = new WebhookAlerts($pdo);
$alerts->checkWebhookHealth();
```

## Webhook Retry Logic

The Appla-X Gate API automatically retries failed webhooks:

- **Initial Retry**: Immediate retry after failure
- **Subsequent Retries**: Exponential backoff (1min, 5min, 15min, 1hr, 6hr, 24hr)
- **Maximum Retries**: Up to 6 attempts over 48 hours
- **Retry Conditions**: HTTP 5xx errors, timeouts, connection failures

### Handling Retry Logic in Your Endpoint

```php
<?php

function handleWebhookWithRetrySupport($data) {
    $webhookId = $data['id'] ?? null;
    $attempt = $_SERVER['HTTP_X_WEBHOOK_ATTEMPT'] ?? '1';

    // Log retry attempts
    error_log("Processing webhook {$webhookId}, attempt {$attempt}");

    try {
        // Process webhook
        processWebhook($data);

        // Return 200 for success
        http_response_code(200);
        return true;

    } catch (TemporaryException $e) {
        // Temporary failure - return 5xx to trigger retry
        error_log("Temporary failure for webhook {$webhookId}: " . $e->getMessage());
        http_response_code(503);
        return false;

    } catch (PermanentException $e) {
        // Permanent failure - return 200 to prevent retries
        error_log("Permanent failure for webhook {$webhookId}: " . $e->getMessage());
        http_response_code(200);
        return false;
    }
}
```

## Troubleshooting

### Common Issues

#### Webhook Not Received

1. **Check URL accessibility**
   ```bash
   curl -X POST https://yourdomain.com/webhook-endpoint.php
   ```

2. **Verify SSL certificate**
   ```bash
   curl -I https://yourdomain.com/webhook-endpoint.php
   ```

3. **Check firewall settings**
4. **Verify webhook is active**
   ```php
   $webhook = $sdk->getWebhook('webhook-id');
   echo "Active: " . ($webhook['is_active'] ? 'Yes' : 'No') . "\n";
   ```

#### Signature Validation Fails

1. **Check webhook secret**
2. **Verify payload is not modified**
3. **Check HTTP headers**
4. **Debug signature generation**
   ```php
   error_log("Expected: " . hash_hmac('sha256', $payload, $secret));
   error_log("Received: " . $signature);
   ```

#### Duplicate Webhooks

1. **Implement idempotency checks**
2. **Store processed webhook IDs**
3. **Use database transactions**

### Debug Mode

Enable webhook debugging:

```php
<?php
// debug-webhook.php

$payload = file_get_contents('php://input');
$headers = getallheaders();

// Log everything for debugging
error_log("Webhook received:");
error_log("Headers: " . print_r($headers, true));
error_log("Payload: " . $payload);

// Validate signature
$signature = $headers['X-Webhook-Signature'] ?? '';
$secret = 'your-webhook-secret';

$expectedSignature = hash_hmac('sha256', $payload, $secret);
$isValid = hash_equals($expectedSignature, $signature);

error_log("Signature valid: " . ($isValid ? 'YES' : 'NO'));
error_log("Expected: " . $expectedSignature);
error_log("Received: " . $signature);
```

## Production Checklist

- [ ] Webhook endpoints use HTTPS
- [ ] Signature validation implemented
- [ ] Idempotency handling in place
- [ ] Database logging configured
- [ ] Error handling and alerting set up
- [ ] Rate limiting implemented
- [ ] Monitoring dashboard created
- [ ] Backup webhook endpoints configured
- [ ] Webhook secrets stored securely
- [ ] Tested webhook failure scenarios

## Next Steps

- Review [Payment Methods](PAYMENT_METHODS.md) for payment processing
- Check [Configuration](CONFIGURATION.md) for SDK setup
- Explore the examples in `examples/webhook-handling.php`
- Set up monitoring and alerting for production use