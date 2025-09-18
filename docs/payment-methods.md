# Payment Methods Guide

This guide covers all payment methods supported by the Appla-X Gate SDK and how to implement them.

## Overview

The Appla-X Gate API supports multiple payment methods:

- **Credit/Debit Cards** - Visa, Mastercard, American Express
- **Digital Wallets** - Apple Pay, Google Pay
- **Alternative Payment Methods** - PayPal, Klarna
- **Bank Transfers** - SEPA, local bank transfers
- **Buy Now, Pay Later** - Various BNPL providers

## Payment Flow

### Basic Payment Process

1. **Create Order** - Create an order with customer and product details
2. **Get Payment URLs** - Receive payment method-specific URLs from the API
3. **Process Payment** - Execute payment using the appropriate method
4. **Handle Response** - Process the payment result and handle redirects
5. **Webhook Notification** - Receive final payment status via webhook

```php
<?php

use ApplaxDev\GateSDK\GateSDK;

$sdk = new GateSDK('your-api-key', true); // true for sandbox

// 1. Create order
$order = $sdk->createOrderModel([
    'client' => [
        'email' => 'customer@example.com',
        'phone' => '371-12345678',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ],
    'products' => [
        [
            'title' => 'Premium Service',
            'price' => 99.99,
            'quantity' => 1,
        ]
    ],
    'currency' => 'EUR',
]);

// 2. Payment URLs are now available in the order object
echo "Payment URL: " . $order->getPaymentUrl() . "\n";
```

## Credit and Debit Cards

### Supported Cards

- **Visa** - All Visa cards globally
- **Mastercard** - All Mastercard products
- **American Express** - Amex cards in supported regions
- **Maestro** - Debit cards in Europe
- **Local Cards** - Regional card schemes as available

### Card Payment Implementation

```php
<?php

// Create order first (see basic example above)
$order = $sdk->createOrderModel($orderData);

// Check if card payment is available
if ($order->getApiDoUrl()) {
    $cardData = [
        'cardholder_name' => 'John Doe',
        'card_number' => '4111111111111111', // Test card for sandbox
        'cvv' => '123',
        'exp_month' => 12,
        'exp_year' => 25,
    ];

    try {
        $result = $sdk->executeCardPayment($order->getApiDoUrl(), $cardData);

        if ($result['status'] === 'success') {
            echo "Payment successful!\n";
        } elseif (isset($result['3ds_url'])) {
            echo "3D Secure authentication required\n";
            echo "Redirect to: " . $result['3ds_url'] . "\n";
        } elseif (isset($result['error_message'])) {
            echo "Payment failed: " . $result['error_message'] . "\n";
        }

    } catch (GateException $e) {
        echo "Payment error: " . $e->getMessage() . "\n";
    }
}
```

### Test Cards (Sandbox Only)

```php
// Successful test cards
$testCards = [
    'visa_success' => '4111111111111111',
    'mastercard_success' => '5555555555554444',
    'amex_success' => '378282246310005',
];

// Declined test cards
$declinedCards = [
    'visa_declined' => '4000000000000002',
    'insufficient_funds' => '4000000000009995',
    'expired_card' => '4000000000000069',
    'invalid_cvv' => '4000000000000127',
];
```

### 3D Secure Handling

```php
<?php

$result = $sdk->executeCardPayment($order->getApiDoUrl(), $cardData);

if (isset($result['3ds_url'])) {
    // Redirect customer to 3DS authentication
    header('Location: ' . $result['3ds_url']);
    exit;
}

// Handle 3DS return (in your return URL handler)
if (isset($_GET['3ds_return'])) {
    // Fetch updated order status
    $updatedOrder = $sdk->getOrderModel($orderId);

    if ($updatedOrder->isPaid()) {
        echo "Payment completed after 3DS authentication\n";
    } else {
        echo "Payment failed after 3DS authentication\n";
    }
}
```

### Saved Cards (Tokenization)

```php
<?php

// Save card during payment
$orderData = [
    // ... other order data
    'save_card' => true, // Enable card saving
];

$order = $sdk->createOrderModel($orderData);

// After successful payment, get saved cards
$clientId = $order->getClient()->getId();
$savedCards = $sdk->getClient($clientId);

if (!empty($savedCards['saved_payment_methods'])) {
    echo "Customer has saved cards:\n";
    foreach ($savedCards['saved_payment_methods'] as $card) {
        echo "- " . $card['masked_number'] . " expires " .
             $card['exp_month'] . "/" . $card['exp_year'] . "\n";
    }
}

// Use saved card for future payments
$futureOrderData = [
    // ... order details
    'recurring_payment_method' => $card['id'], // Use saved card
];
```

## Apple Pay

### Apple Pay Setup

Apple Pay requires frontend JavaScript integration and backend processing.

#### Frontend (JavaScript)

```javascript
// apple-pay.js
if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
    // Show Apple Pay button
    document.getElementById('apple-pay-button').style.display = 'block';
}

function startApplePay() {
    const request = {
        countryCode: 'US',
        currencyCode: 'EUR',
        supportedNetworks: ['visa', 'masterCard', 'amex'],
        merchantCapabilities: ['supports3DS'],
        total: {
            label: 'Your Store',
            amount: '99.99'
        }
    };

    const session = new ApplePaySession(3, request);

    session.onvalidatemerchant = (event) => {
        // Validate merchant with your server
        fetch('/apple-pay/validate-merchant', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                validationURL: event.validationURL
            })
        })
        .then(response => response.json())
        .then(merchantSession => {
            session.completeMerchantValidation(merchantSession);
        });
    };

    session.onpaymentauthorized = (event) => {
        // Process payment with your server
        fetch('/apple-pay/process', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                payment_data: event.payment.token,
                order_id: 'your_order_id'
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                session.completePayment(ApplePaySession.STATUS_SUCCESS);
            } else {
                session.completePayment(ApplePaySession.STATUS_FAILURE);
            }
        });
    };

    session.begin();
}
```

#### Backend Processing

```php
<?php

// Process Apple Pay payment
if ($order->getApplePayUrl()) {
    $applePayData = [
        'payment_data' => json_decode($_POST['payment_data'], true),
    ];

    try {
        $result = $sdk->executeApplePayPayment(
            $order->getApplePayUrl(),
            $applePayData
        );

        echo json_encode(['success' => $result['status'] === 'success']);

    } catch (GateException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
```

## Google Pay

### Google Pay Setup

Similar to Apple Pay, Google Pay requires frontend integration.

#### Frontend (JavaScript)

```javascript
// google-pay.js
const baseRequest = {
    apiVersion: 2,
    apiVersionMinor: 0
};

const allowedCardNetworks = ['AMEX', 'VISA', 'MASTERCARD'];
const allowedCardAuthMethods = ['PAN_ONLY', 'CRYPTOGRAM_3DS'];

const tokenizationSpecification = {
    type: 'PAYMENT_GATEWAY',
    parameters: {
        'gateway': 'applax',
        'gatewayMerchantId': 'your-merchant-id'
    }
};

const baseCardPaymentMethod = {
    type: 'CARD',
    parameters: {
        allowedAuthMethods: allowedCardAuthMethods,
        allowedCardNetworks: allowedCardNetworks
    }
};

const cardPaymentMethod = Object.assign(
    {tokenizationSpecification: tokenizationSpecification},
    baseCardPaymentMethod
);

function initializeGooglePay() {
    const paymentsClient = new google.payments.api.PaymentsClient({environment: 'TEST'});

    const paymentDataRequest = Object.assign({}, baseRequest);
    paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];
    paymentDataRequest.transactionInfo = {
        totalPriceStatus: 'FINAL',
        totalPrice: '99.99',
        currencyCode: 'EUR'
    };
    paymentDataRequest.merchantInfo = {
        merchantName: 'Your Store'
    };

    paymentsClient.loadPaymentData(paymentDataRequest).then(function(paymentData) {
        // Process payment data
        processGooglePayPayment(paymentData);
    });
}

function processGooglePayPayment(paymentData) {
    fetch('/google-pay/process', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            payment_data: paymentData,
            order_id: 'your_order_id'
        })
    });
}
```

#### Backend Processing

```php
<?php

if ($order->getGooglePayUrl()) {
    $googlePayData = [
        'payment_data' => $_POST['payment_data'],
    ];

    try {
        $result = $sdk->executeGooglePayPayment(
            $order->getGooglePayUrl(),
            $googlePayData
        );

        echo json_encode(['success' => $result['status'] === 'success']);

    } catch (GateException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
```

## PayPal

### PayPal Integration

PayPal payments are processed through redirect flow.

```php
<?php

if ($order->getPayPalInitUrl()) {
    try {
        // Initialize PayPal payment
        $paypalResult = $sdk->initPayPalPayment($order->getPayPalInitUrl());

        if (isset($paypalResult['redirect_url'])) {
            // Redirect customer to PayPal
            header('Location: ' . $paypalResult['redirect_url']);
            exit;
        }

    } catch (GateException $e) {
        echo "PayPal initialization failed: " . $e->getMessage() . "\n";
    }
}

// Handle PayPal return (in your return URL handler)
if (isset($_GET['paypal_return'])) {
    $orderId = $_GET['order_id'];
    $updatedOrder = $sdk->getOrderModel($orderId);

    if ($updatedOrder->isPaid()) {
        echo "PayPal payment completed successfully\n";
    } else {
        echo "PayPal payment was not completed\n";
    }
}
```

### PayPal Configuration

```php
<?php

// Order with PayPal-specific settings
$orderData = [
    'client' => [
        'email' => 'customer@example.com',
        // PayPal requires email
    ],
    'products' => [
        [
            'title' => 'Product Name',
            'price' => 29.99,
            'quantity' => 1,
        ]
    ],
    'currency' => 'EUR', // PayPal supported currency
    'return_url' => 'https://yourdomain.com/payment/success',
    'cancel_url' => 'https://yourdomain.com/payment/cancel',
    'paypal_cancel_redirect' => 'https://yourdomain.com/payment/paypal-cancel',
];
```

## Klarna (Buy Now, Pay Later)

### Klarna Integration

```php
<?php

if ($order->getKlarnaInitUrl()) {
    $klarnaData = [
        'purchase_country' => 'SE', // Customer country
        'purchase_currency' => 'EUR',
        'locale' => 'en-SE',
        'billing_address' => [
            'given_name' => 'John',
            'family_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'street_address' => 'Example Street 1',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'SE',
        ],
        'shipping_address' => [
            // Same format as billing_address
        ],
    ];

    try {
        $klarnaResult = $sdk->initKlarnaPayment(
            $order->getKlarnaInitUrl(),
            $klarnaData
        );

        if (isset($klarnaResult['redirect_url'])) {
            // Redirect to Klarna checkout
            header('Location: ' . $klarnaResult['redirect_url']);
            exit;
        }

    } catch (GateException $e) {
        echo "Klarna initialization failed: " . $e->getMessage() . "\n";
    }
}
```

### Klarna Requirements

- Valid billing address required
- Supported countries: SE, NO, DK, FI, DE, AT, NL, UK
- Age verification may be required
- Credit check performed by Klarna

## Bank Transfers

### SEPA Direct Debit

```php
<?php

// For SEPA, collect IBAN from customer
$orderData = [
    // ... standard order data
    'payment_method' => 'sepa_direct_debit',
    'sepa_data' => [
        'iban' => 'DE89370400440532013000',
        'bic' => 'COBADEFFXXX', // Optional for SEPA
        'account_holder_name' => 'John Doe',
        'mandate_reference' => 'MANDATE_REF_123',
    ],
];

$order = $sdk->createOrderModel($orderData);
```

### Local Bank Transfers

```php
<?php

// Country-specific bank transfer
$orderData = [
    // ... standard order data
    'payment_method' => 'bank_transfer',
    'bank_transfer_data' => [
        'country' => 'LV', // Latvia
        'bank_code' => 'RIKOLV2X',
        'account_number' => 'LV80BANK0000435195001',
    ],
];
```

## Advanced Payment Features

### Multi-Step Payments

```php
<?php

// Create order with authorization only
$orderData = [
    // ... order data
    'skip_capture' => true, // Authorize but don't capture
];

$order = $sdk->createOrderModel($orderData);

// Process card authorization
$cardResult = $sdk->executeCardPayment($order->getApiDoUrl(), $cardData);

if ($cardResult['status'] === 'authorized') {
    echo "Payment authorized, funds held\n";

    // Later, capture the payment
    $captureResult = $sdk->capturePayment($order->getId(), [
        'amount' => 50.00, // Partial capture optional
    ]);

    if ($captureResult['status'] === 'captured') {
        echo "Payment captured successfully\n";
    }
}
```

### Recurring Payments

```php
<?php

// Initial payment with card saving
$initialOrder = $sdk->createOrderModel([
    // ... order data
    'save_card' => true,
    'recurring' => true,
]);

// Process initial payment
$cardResult = $sdk->executeCardPayment($initialOrder->getApiDoUrl(), $cardData);

if ($cardResult['status'] === 'success') {
    // Get saved payment method
    $clientId = $initialOrder->getClient()->getId();
    $client = $sdk->getClient($clientId);
    $savedCard = $client['saved_payment_methods'][0];

    // Create recurring payment
    $recurringOrder = $sdk->createOrderModel([
        'client' => ['id' => $clientId],
        'products' => [/* subscription products */],
        'recurring_payment_method' => $savedCard['id'],
    ]);

    echo "Recurring payment set up with card: " . $savedCard['masked_number'] . "\n";
}
```

### Refunds

```php
<?php

// Full refund
$refundResult = $sdk->refundPayment($orderId);

// Partial refund
$partialRefund = $sdk->refundPayment($orderId, [
    'amount' => 25.00,
    'reason' => 'Partial refund requested by customer',
]);

if ($refundResult['status'] === 'refund_pending') {
    echo "Refund is being processed\n";
    // Final status will come via webhook
}
```

## Payment Security

### PCI DSS Compliance

The SDK handles PCI DSS compliance by:
- Never storing card data in your system
- Using tokenization for saved cards
- Encrypting all card data transmission
- Providing secure payment URLs

### Fraud Prevention

```php
<?php

// Enhanced fraud detection
$orderData = [
    // ... standard data
    'fraud_detection' => [
        'customer_ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'session_id' => session_id(),
        'customer_since' => '2023-01-15', // When customer registered
        'previous_orders' => 5, // Number of previous orders
    ],
];
```

### 3D Secure Configuration

```php
<?php

// Force 3DS for all transactions
$orderData = [
    // ... standard data
    'force_3ds' => true,
];

// Smart 3DS (based on risk analysis)
$orderData = [
    // ... standard data
    '3ds_preference' => 'smart', // Options: always, smart, never
];
```

## Error Handling

### Payment Errors

```php
<?php

try {
    $result = $sdk->executeCardPayment($order->getApiDoUrl(), $cardData);

} catch (\ApplaxDev\GateSDK\Exceptions\ValidationException $e) {
    // Invalid card data
    echo "Validation error: " . $e->getMessage() . "\n";
    $errors = $e->getValidationErrors();
    foreach ($errors as $field => $error) {
        echo "- {$field}: {$error}\n";
    }

} catch (\ApplaxDev\GateSDK\Exceptions\AuthenticationException $e) {
    // API authentication failed
    echo "Authentication error: " . $e->getMessage() . "\n";

} catch (\ApplaxDev\GateSDK\Exceptions\NetworkException $e) {
    // Network/connection issue
    echo "Network error: " . $e->getMessage() . "\n";

} catch (\ApplaxDev\GateSDK\Exceptions\GateException $e) {
    // General payment error
    echo "Payment error: " . $e->getMessage() . "\n";
}
```

### Common Error Codes

| Error Code | Description | Solution |
|------------|-------------|----------|
| `card_declined` | Card issuer declined | Try different card |
| `insufficient_funds` | Not enough money | Try different card |
| `expired_card` | Card is expired | Update card details |
| `invalid_cvv` | Wrong CVV code | Re-enter CVV |
| `3ds_failed` | 3DS authentication failed | Try again |
| `processing_error` | Bank processing error | Try again later |

## Testing

### Sandbox Testing

```php
<?php

// Always use sandbox for testing
$sdk = new GateSDK('your-api-key', true); // true = sandbox

// Test successful payment
$testOrder = $sdk->createOrderModel([
    'client' => [
        'email' => 'test@example.com',
        'phone' => '371-12345678',
    ],
    'products' => [
        [
            'title' => 'Test Product',
            'price' => 1.00, // Small amount for testing
            'quantity' => 1,
        ]
    ],
    'currency' => 'EUR',
]);

// Use test card
$testCard = [
    'cardholder_name' => 'Test User',
    'card_number' => '4111111111111111', // Visa test card
    'cvv' => '123',
    'exp_month' => 12,
    'exp_year' => 25,
];

$result = $sdk->executeCardPayment($testOrder->getApiDoUrl(), $testCard);
```

### Test Scenarios

```php
<?php

// Test different payment scenarios
$testScenarios = [
    'success' => '4111111111111111',
    'decline' => '4000000000000002',
    '3ds_required' => '4000000000003063',
    'insufficient_funds' => '4000000000009995',
    'expired_card' => '4000000000000069',
    'processing_error' => '4000000000000119',
];

foreach ($testScenarios as $scenario => $cardNumber) {
    echo "Testing scenario: {$scenario}\n";

    $testCard['card_number'] = $cardNumber;

    try {
        $result = $sdk->executeCardPayment($order->getApiDoUrl(), $testCard);
        echo "Result: " . $result['status'] . "\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

    echo "---\n";
}
```

## Best Practices

### Payment UX

1. **Show Available Methods** - Display all payment options clearly
2. **Secure Indicators** - Show security badges and SSL indicators
3. **Progress Indicators** - Show payment progress steps
4. **Error Messages** - Provide clear, actionable error messages
5. **Mobile Optimization** - Ensure mobile-friendly payment flow

### Performance

1. **Async Processing** - Use webhooks for payment status
2. **Timeout Handling** - Set appropriate timeouts
3. **Retry Logic** - Implement retry for network errors
4. **Caching** - Cache payment method availability

### Security

1. **HTTPS Only** - Always use HTTPS for payment pages
2. **Input Validation** - Validate all payment data
3. **Session Security** - Secure session management
4. **Webhook Validation** - Always verify webhook signatures

## Next Steps

- Set up [Webhooks](WEBHOOKS.md) for payment notifications
- Review the [Configuration](CONFIGURATION.md) guide
- Check the examples in the `examples/` folder
- Test thoroughly in sandbox before going live