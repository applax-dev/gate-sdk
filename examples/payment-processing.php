<?php

declare(strict_types=1);

/**
 * Payment Processing Examples for Appla-X Gate SDK
 *
 * This example demonstrates various payment processing scenarios
 */

require_once __DIR__ . '/../vendor/autoload.php';

use ApplaxDev\GateSDK\GateSDK;
use ApplaxDev\GateSDK\Exceptions\GateException;
use ApplaxDev\GateSDK\Models\Order;

echo "=== Appla-X Gate SDK - Payment Processing Examples ===\n\n";

// Initialize SDK
$sdk = new GateSDK(
    apiKey: 'your-api-key-here',
    sandbox: true, // Always use sandbox for testing
    config: ['debug' => true]
);

echo "✓ SDK initialized for payment processing\n\n";

// ===== 1. CREATE ORDER FOR PAYMENT =====
echo "1. Create Order for Payment Processing\n";
echo "--------------------------------------\n";

$orderData = [
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
            'description' => 'One-time premium service fee'
        ]
    ],
    'currency' => 'EUR',
    'language' => 'en',
    'save_card' => true, // Save card for future use
    'skip_capture' => false, // Capture immediately
    'notes' => 'Payment processing test order'
];

try {
    $order = $sdk->createOrderModel($orderData);

    echo "✓ Order created for payment processing:\n";
    echo "  - Order ID: " . $order->getId() . "\n";
    echo "  - Order Number: " . $order->getNumber() . "\n";
    echo "  - Amount: " . $order->getFormattedAmount() . "\n";
    echo "  - Status: " . $order->getStatus() . "\n";

    echo "\n  Available Payment Methods:\n";
    foreach ($order->getAvailablePaymentMethods() as $method) {
        echo "  - " . ucfirst(str_replace('_', ' ', $method)) . "\n";
    }

    echo "\n";

} catch (GateException $e) {
    echo "✗ Order creation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// ===== 2. CARD PAYMENT PROCESSING =====
echo "2. Credit Card Payment Processing\n";
echo "---------------------------------\n";

if ($order->getApiDoUrl()) {
    echo "Card payment URL available: " . substr($order->getApiDoUrl(), 0, 50) . "...\n";

    // Test card data (use only in sandbox)
    $cardData = [
        'cardholder_name' => 'John Doe',
        'card_number' => '4111111111111111', // Visa test card
        'cvv' => '123',
        'exp_month' => 12,
        'exp_year' => 25,
    ];

    try {
        echo "Processing card payment...\n";
        $paymentResult = $sdk->executeCardPayment($order->getApiDoUrl(), $cardData);

        echo "✓ Card payment result:\n";
        echo "  - Status: " . $paymentResult['status'] . "\n";

        if (isset($paymentResult['transaction_id'])) {
            echo "  - Transaction ID: " . $paymentResult['transaction_id'] . "\n";
        }

        if (isset($paymentResult['processing_id'])) {
            echo "  - Processing ID: " . $paymentResult['processing_id'] . "\n";
        }

        // Handle 3D Secure if required
        if (isset($paymentResult['3ds_url'])) {
            echo "  - 3D Secure required: " . $paymentResult['3ds_url'] . "\n";
            echo "  ℹ Redirect customer to 3DS URL to complete authentication\n";
        }

        // Handle redirect for additional authentication
        if (isset($paymentResult['redirect_url'])) {
            echo "  - Redirect required: " . $paymentResult['redirect_url'] . "\n";
        }

        if (isset($paymentResult['error_message'])) {
            echo "  - Error: " . $paymentResult['error_message'] . "\n";
        }

    } catch (GateException $e) {
        echo "✗ Card payment failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ Card payment not available for this order\n";
}

echo "\n";

// ===== 3. APPLE PAY PROCESSING =====
echo "3. Apple Pay Processing\n";
echo "-----------------------\n";

if ($order->getApplePayUrl()) {
    echo "Apple Pay URL available: " . substr($order->getApplePayUrl(), 0, 50) . "...\n";

    // Example Apple Pay payment token (this would come from Apple Pay JS)
    $applePayData = [
        'payment_data' => [
            'version' => 'EC_v1',
            'data' => 'encrypted_payment_data_from_apple_pay_js',
            'signature' => 'apple_pay_signature',
            'header' => [
                'ephemeralPublicKey' => 'ephemeral_public_key_from_apple',
                'publicKeyHash' => 'public_key_hash_from_apple',
                'transactionId' => 'apple_transaction_id'
            ]
        ]
    ];

    try {
        echo "Processing Apple Pay payment...\n";
        // Note: In real implementation, you would get this data from Apple Pay JS
        // $applePayResult = $sdk->executeApplePayPayment($order->getApplePayUrl(), $applePayData);

        echo "ℹ Apple Pay processing requires real Apple Pay token from frontend\n";
        echo "  Integration steps:\n";
        echo "  1. Implement Apple Pay JS in your frontend\n";
        echo "  2. Get payment token from Apple Pay\n";
        echo "  3. Send token to your backend\n";
        echo "  4. Process payment using SDK\n";

    } catch (GateException $e) {
        echo "✗ Apple Pay processing failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ Apple Pay not available for this order\n";
}

echo "\n";

// ===== 4. GOOGLE PAY PROCESSING =====
echo "4. Google Pay Processing\n";
echo "------------------------\n";

if ($order->getGooglePayUrl()) {
    echo "Google Pay URL available: " . substr($order->getGooglePayUrl(), 0, 50) . "...\n";

    // Example Google Pay payment token
    $googlePayData = [
        'payment_data' => [
            'signature' => 'google_pay_signature',
            'intermediateSigningKey' => [
                'signedKey' => 'signed_key_from_google',
                'signatures' => ['signature1']
            ],
            'signedMessage' => 'signed_message_from_google'
        ]
    ];

    try {
        echo "Processing Google Pay payment...\n";
        // Note: In real implementation, you would get this data from Google Pay JS
        // $googlePayResult = $sdk->executeGooglePayPayment($order->getGooglePayUrl(), $googlePayData);

        echo "ℹ Google Pay processing requires real Google Pay token from frontend\n";
        echo "  Integration steps:\n";
        echo "  1. Implement Google Pay JS in your frontend\n";
        echo "  2. Get payment token from Google Pay\n";
        echo "  3. Send token to your backend\n";
        echo "  4. Process payment using SDK\n";

    } catch (GateException $e) {
        echo "✗ Google Pay processing failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ Google Pay not available for this order\n";
}

echo "\n";

// ===== 5. PAYPAL PROCESSING =====
echo "5. PayPal Processing\n";
echo "--------------------\n";

if ($order->getPayPalInitUrl()) {
    echo "PayPal init URL available: " . substr($order->getPayPalInitUrl(), 0, 50) . "...\n";

    try {
        echo "Initializing PayPal payment...\n";
        $paypalResult = $sdk->initPayPalPayment($order->getPayPalInitUrl());

        echo "✓ PayPal initialization result:\n";
        echo "  - Status: " . ($paypalResult['status'] ?? 'unknown') . "\n";

        if (isset($paypalResult['redirect_url'])) {
            echo "  - PayPal URL: " . $paypalResult['redirect_url'] . "\n";
            echo "  ℹ Redirect customer to PayPal for payment\n";
        }

        if (isset($paypalResult['paypal_order_id'])) {
            echo "  - PayPal Order ID: " . $paypalResult['paypal_order_id'] . "\n";
        }

    } catch (GateException $e) {
        echo "✗ PayPal initialization failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ PayPal not available for this order\n";
}

echo "\n";

// ===== 6. KLARNA PROCESSING =====
echo "6. Klarna Processing\n";
echo "--------------------\n";

if ($order->getKlarnaInitUrl()) {
    echo "Klarna init URL available: " . substr($order->getKlarnaInitUrl(), 0, 50) . "...\n";

    $klarnaData = [
        'purchase_country' => 'SE',
        'purchase_currency' => 'EUR',
        'locale' => 'en-SE',
        'billing_address' => [
            'given_name' => 'John',
            'family_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'street_address' => 'Example Street 1',
            'postal_code' => '12345',
            'city' => 'Stockholm',
            'country' => 'SE'
        ]
    ];

    try {
        echo "Initializing Klarna payment...\n";
        $klarnaResult = $sdk->initKlarnaPayment($order->getKlarnaInitUrl(), $klarnaData);

        echo "✓ Klarna initialization result:\n";
        echo "  - Status: " . ($klarnaResult['status'] ?? 'unknown') . "\n";

        if (isset($klarnaResult['redirect_url'])) {
            echo "  - Klarna URL: " . $klarnaResult['redirect_url'] . "\n";
            echo "  ℹ Redirect customer to Klarna for payment\n";
        }

        if (isset($klarnaResult['session_id'])) {
            echo "  - Session ID: " . $klarnaResult['session_id'] . "\n";
        }

    } catch (GateException $e) {
        echo "✗ Klarna initialization failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ Klarna not available for this order\n";
}

echo "\n";

// ===== 7. PAYMENT MANAGEMENT =====
echo "7. Payment Management Operations\n";
echo "--------------------------------\n";

// Refresh order status
try {
    $updatedOrder = $sdk->getOrderModel($order->getId());
    echo "Current order status: " . $updatedOrder->getStatus() . "\n";

    if ($updatedOrder->isPaid()) {
        echo "✓ Order is fully paid\n";

        // Demonstrate refund
        echo "\nTesting refund operation...\n";
        try {
            $refundResult = $sdk->refundPayment($order->getId(), [
                'amount' => 10.00,
                'reason' => 'Partial refund for testing'
            ]);

            echo "✓ Refund processed:\n";
            echo "  - Status: " . ($refundResult['status'] ?? 'unknown') . "\n";
            echo "  - Amount: 10.00 EUR\n";

        } catch (GateException $e) {
            echo "ℹ Refund test (expected in sandbox): " . $e->getMessage() . "\n";
        }

    } elseif ($updatedOrder->shouldSkipCapture()) {
        echo "✓ Order is authorized, testing capture...\n";

        try {
            $captureResult = $sdk->capturePayment($order->getId());

            echo "✓ Capture processed:\n";
            echo "  - Status: " . ($captureResult['status'] ?? 'unknown') . "\n";

        } catch (GateException $e) {
            echo "ℹ Capture test: " . $e->getMessage() . "\n";
        }

    } elseif ($updatedOrder->isPayable()) {
        echo "ℹ Order is still payable - payment not completed yet\n";

        // Option to cancel order
        echo "\nTesting order cancellation...\n";
        try {
            $cancelResult = $sdk->cancelOrder($order->getId());

            echo "✓ Order cancelled:\n";
            echo "  - Status: " . ($cancelResult['status'] ?? 'unknown') . "\n";

        } catch (GateException $e) {
            echo "ℹ Cancel test: " . $e->getMessage() . "\n";
        }
    }

} catch (GateException $e) {
    echo "✗ Failed to check order status: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 8. TESTING DIFFERENT CARD SCENARIOS =====
echo "8. Testing Different Card Scenarios\n";
echo "-----------------------------------\n";

$testCards = [
    'visa_success' => [
        'name' => 'Visa Success',
        'number' => '4111111111111111',
        'cvv' => '123',
        'description' => 'Should process successfully'
    ],
    'visa_declined' => [
        'name' => 'Visa Declined',
        'number' => '4000000000000002',
        'cvv' => '123',
        'description' => 'Should be declined'
    ],
    'mastercard_success' => [
        'name' => 'Mastercard Success',
        'number' => '5555555555554444',
        'cvv' => '123',
        'description' => 'Should process successfully'
    ],
    'amex_success' => [
        'name' => 'Amex Success',
        'number' => '378282246310005',
        'cvv' => '1234',
        'description' => 'Should process successfully'
    ]
];

echo "Available test card scenarios:\n";
foreach ($testCards as $card) {
    echo "  - " . $card['name'] . ": " . $card['description'] . "\n";
}

echo "\n";

// ===== 9. SAVED CARD PROCESSING =====
echo "9. Saved Card Processing\n";
echo "------------------------\n";

// In a real scenario, you would:
// 1. Save card during first payment (save_card: true)
// 2. Get saved cards for client
// 3. Use saved card for subsequent payments

try {
    $clientId = $order->getClient()->getId();
    echo "Getting saved cards for client: " . $clientId . "\n";

    $savedCards = $sdk->getClientCards($clientId);
    echo "✓ Found " . count($savedCards['results'] ?? []) . " saved cards\n";

    if (!empty($savedCards['results'])) {
        echo "  Saved cards:\n";
        foreach ($savedCards['results'] as $card) {
            echo "    - " . ($card['masked_number'] ?? 'Hidden') .
                 " (expires: " . ($card['exp_month'] ?? '?') . "/" . ($card['exp_year'] ?? '?') . ")\n";
        }
    }

} catch (GateException $e) {
    echo "ℹ Saved cards check: " . $e->getMessage() . "\n";
}

echo "\n";

echo "=== Payment Processing Examples Complete ===\n\n";

/**
 * Payment Processing Summary:
 *
 * 1. ✓ Order Creation - Create orders with payment intent
 * 2. ✓ Card Processing - Handle credit/debit card payments
 * 3. ✓ Digital Wallets - Apple Pay and Google Pay integration
 * 4. ✓ Alternative Methods - PayPal, Klarna, etc.
 * 5. ✓ Payment Management - Capture, refund, cancel operations
 * 6. ✓ Test Scenarios - Different card test cases
 * 7. ✓ Saved Cards - Card tokenization and reuse
 *
 * Next Steps for Production:
 *
 * 1. Replace test API key with production key
 * 2. Implement frontend payment forms
 * 3. Set up webhook endpoints for payment notifications
 * 4. Implement proper error handling and user feedback
 * 5. Add payment confirmation and receipt generation
 * 6. Set up monitoring and logging for payment flows
 */

echo "ℹ Remember to test thoroughly in sandbox before going live!\n";
echo "ℹ Always validate payments via webhooks for security\n";