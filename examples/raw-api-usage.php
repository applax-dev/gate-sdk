<?php

declare(strict_types=1);

/**
 * Raw API Access Examples for Appla-X Gate SDK
 *
 * This example demonstrates how to use the raw API methods to access
 * any Appla-X Gate API endpoint, including Brands, Charges, Taxes,
 * Subscriptions, and future endpoints.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use ApplaxDev\GateSDK\GateSDK;
use ApplaxDev\GateSDK\Exceptions\GateException;
use ApplaxDev\GateSDK\Exceptions\ValidationException;
use ApplaxDev\GateSDK\Exceptions\NotFoundException;

echo "=== Appla-X Gate SDK - Raw API Access Examples ===\n\n";

// Initialize SDK
$sdk = new GateSDK(
    apiKey: 'your-api-key-here',
    sandbox: true,
    config: ['debug' => true]
);

// ===== 1. BRANDS MANAGEMENT =====
echo "1. Brands Management (using raw methods)\n";
echo "-----------------------------------------\n";

try {
    // Create a brand
    echo "Creating a brand...\n";
    $brandData = [
        'name' => 'My Brand',
        'description' => 'Brand description',
        'website' => 'https://mybrand.com',
        'logo_url' => 'https://mybrand.com/logo.png'
    ];

    $brand = $sdk->rawPost('/brands/', $brandData);
    echo "✓ Brand created: {$brand['name']} (ID: {$brand['id']})\n";
    $brandId = $brand['id'];

    // Get all brands
    echo "\nFetching all brands...\n";
    $brands = $sdk->rawGet('/brands/', ['limit' => 10]);
    echo "✓ Found " . count($brands['results'] ?? []) . " brands\n";

    // Get single brand
    if (isset($brandId)) {
        echo "\nFetching brand by ID...\n";
        $brand = $sdk->rawGet("/brands/{$brandId}/");
        echo "✓ Brand retrieved: {$brand['name']}\n";

        // Update brand (full update)
        echo "\nUpdating brand (PUT)...\n";
        $updatedBrand = $sdk->rawPut("/brands/{$brandId}/", [
            'name' => 'My Updated Brand',
            'description' => 'Updated description',
            'website' => 'https://mybrand.com'
        ]);
        echo "✓ Brand updated: {$updatedBrand['name']}\n";

        // Partial update brand
        echo "\nPartially updating brand (PATCH)...\n";
        $patchedBrand = $sdk->rawPatch("/brands/{$brandId}/", [
            'description' => 'Partially updated description'
        ]);
        echo "✓ Brand partially updated\n";

        // Delete brand
        echo "\nDeleting brand...\n";
        $sdk->rawDelete("/brands/{$brandId}/");
        echo "✓ Brand deleted\n";
    }

} catch (ValidationException $e) {
    echo "✗ Validation error: " . $e->getMessage() . "\n";
    if ($e->hasFieldErrors('name')) {
        print_r($e->getFieldErrors('name'));
    }
} catch (GateException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 2. SUBSCRIPTIONS MANAGEMENT =====
echo "2. Subscriptions Management (using raw methods)\n";
echo "------------------------------------------------\n";

try {
    // Create a subscription
    echo "Creating a subscription...\n";
    $subscriptionData = [
        'client' => [
            'email' => 'customer@example.com',
            'phone' => '371-12345678',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ],
        'plan' => 'monthly-premium',
        'amount' => 29.99,
        'currency' => 'EUR',
        'interval' => 'monthly',
        'start_date' => date('Y-m-d'),
        'payment_method' => 'card'
    ];

    $subscription = $sdk->rawPost('/subscriptions/', $subscriptionData);
    echo "✓ Subscription created: {$subscription['id']}\n";
    echo "  - Status: {$subscription['status']}\n";
    echo "  - Amount: {$subscription['amount']} {$subscription['currency']}\n";
    $subscriptionId = $subscription['id'];

    // Get subscriptions with filters
    echo "\nFetching active subscriptions...\n";
    $subscriptions = $sdk->rawGet('/subscriptions/', [
        'status' => 'active',
        'limit' => 10
    ]);
    echo "✓ Found " . count($subscriptions['results'] ?? []) . " active subscriptions\n";

    // Get single subscription
    if (isset($subscriptionId)) {
        echo "\nFetching subscription details...\n";
        $subscription = $sdk->rawGet("/subscriptions/{$subscriptionId}/");
        echo "✓ Subscription retrieved: {$subscription['id']}\n";

        // Update subscription
        echo "\nUpdating subscription...\n";
        $updatedSubscription = $sdk->rawPatch("/subscriptions/{$subscriptionId}/", [
            'amount' => 39.99
        ]);
        echo "✓ Subscription updated to: {$updatedSubscription['amount']} {$updatedSubscription['currency']}\n";

        // Cancel subscription
        echo "\nCancelling subscription...\n";
        $cancelledSubscription = $sdk->rawPost("/subscriptions/{$subscriptionId}/cancel/", []);
        echo "✓ Subscription cancelled: {$cancelledSubscription['status']}\n";
    }

} catch (GateException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 3. TAXES MANAGEMENT =====
echo "3. Taxes Management (using raw methods)\n";
echo "---------------------------------------\n";

try {
    // Create a tax
    echo "Creating a tax...\n";
    $taxData = [
        'name' => 'VAT',
        'rate' => 21.0,
        'type' => 'percentage',
        'country' => 'LV',
        'description' => 'Latvia VAT 21%'
    ];

    $tax = $sdk->rawPost('/taxes/', $taxData);
    echo "✓ Tax created: {$tax['name']} ({$tax['rate']}%)\n";
    $taxId = $tax['id'];

    // Get all taxes
    echo "\nFetching all taxes...\n";
    $taxes = $sdk->rawGet('/taxes/');
    echo "✓ Found " . count($taxes['results'] ?? []) . " taxes\n";

    // Get single tax
    if (isset($taxId)) {
        echo "\nFetching tax details...\n";
        $tax = $sdk->rawGet("/taxes/{$taxId}/");
        echo "✓ Tax retrieved: {$tax['name']} - {$tax['rate']}%\n";

        // Update tax rate
        echo "\nUpdating tax rate...\n";
        $updatedTax = $sdk->rawPatch("/taxes/{$taxId}/", [
            'rate' => 19.0
        ]);
        echo "✓ Tax rate updated to: {$updatedTax['rate']}%\n";

        // Delete tax
        echo "\nDeleting tax...\n";
        $sdk->rawDelete("/taxes/{$taxId}/");
        echo "✓ Tax deleted\n";
    }

} catch (GateException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 4. CHARGES MANAGEMENT =====
echo "4. Charges Management (using raw methods)\n";
echo "-----------------------------------------\n";

try {
    // Create a charge
    echo "Creating a charge...\n";
    $chargeData = [
        'amount' => 100.00,
        'currency' => 'EUR',
        'description' => 'One-time service charge',
        'client' => [
            'email' => 'customer@example.com'
        ]
    ];

    $charge = $sdk->rawPost('/charges/', $chargeData);
    echo "✓ Charge created: {$charge['id']}\n";
    echo "  - Amount: {$charge['amount']} {$charge['currency']}\n";
    echo "  - Status: {$charge['status']}\n";
    $chargeId = $charge['id'];

    // Get charges with filters
    echo "\nFetching charges...\n";
    $charges = $sdk->rawGet('/charges/', [
        'status' => 'pending',
        'limit' => 10
    ]);
    echo "✓ Found " . count($charges['results'] ?? []) . " charges\n";

    // Get single charge
    if (isset($chargeId)) {
        echo "\nFetching charge details...\n";
        $charge = $sdk->rawGet("/charges/{$chargeId}/");
        echo "✓ Charge retrieved: {$charge['id']}\n";

        // Capture charge
        echo "\nCapturing charge...\n";
        $capturedCharge = $sdk->rawPost("/charges/{$chargeId}/capture/", []);
        echo "✓ Charge captured: {$capturedCharge['status']}\n";

        // Refund charge
        echo "\nRefunding charge...\n";
        $refundedCharge = $sdk->rawPost("/charges/{$chargeId}/refund/", [
            'amount' => 50.00,
            'reason' => 'Customer request'
        ]);
        echo "✓ Charge refunded: {$refundedCharge['amount_refunded']} {$refundedCharge['currency']}\n";
    }

} catch (GateException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 5. GENERIC RAW METHOD USAGE =====
echo "5. Generic raw() Method Examples\n";
echo "---------------------------------\n";

try {
    // Using the main raw() method with all parameters
    echo "Using raw() method directly...\n";

    // GET with query parameters
    $result = $sdk->raw('GET', '/orders/', null, [
        'status' => 'paid',
        'limit' => 5
    ]);
    echo "✓ GET request successful: Found " . count($result['results'] ?? []) . " orders\n";

    // POST with payload
    $result = $sdk->raw('POST', '/products/', [
        'brand' => 'brand-uuid-here',
        'title' => 'Test Product',
        'price' => 49.99,
        'currency' => 'EUR'
    ]);
    echo "✓ POST request successful: Product created\n";

    // PATCH with payload
    if (isset($result['id'])) {
        $productId = $result['id'];
        $result = $sdk->raw('PATCH', "/products/{$productId}/", [
            'price' => 39.99
        ]);
        echo "✓ PATCH request successful: Product updated\n";
    }

} catch (GateException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 6. ERROR HANDLING WITH RAW METHODS =====
echo "6. Error Handling Examples\n";
echo "--------------------------\n";

try {
    // Invalid HTTP method
    echo "Testing invalid HTTP method...\n";
    $sdk->raw('INVALID', '/brands/', []);

} catch (ValidationException $e) {
    echo "✓ Caught expected ValidationException: " . $e->getMessage() . "\n";
}

try {
    // Empty endpoint
    echo "\nTesting empty endpoint...\n";
    $sdk->raw('GET', '', null);

} catch (ValidationException $e) {
    echo "✓ Caught expected ValidationException: " . $e->getMessage() . "\n";
}

try {
    // Non-existent resource
    echo "\nTesting non-existent resource...\n";
    $sdk->rawGet('/brands/00000000-0000-0000-0000-000000000000/');

} catch (NotFoundException $e) {
    echo "✓ Caught expected NotFoundException: " . $e->getMessage() . "\n";
}

echo "\n";

// ===== 7. COMBINING RAW AND SPECIFIC METHODS =====
echo "7. Combining Raw and Specific Methods\n";
echo "--------------------------------------\n";

try {
    // Create order using specific method
    echo "Creating order using specific method...\n";
    $order = $sdk->createOrderModel([
        'client' => [
            'email' => 'customer@example.com',
            'phone' => '371-12345678'
        ],
        'products' => [
            [
                'title' => 'Product',
                'price' => 29.99,
                'quantity' => 1
            ]
        ],
        'currency' => 'EUR'
    ]);
    echo "✓ Order created using specific method: {$order->getNumber()}\n";

    // Use raw method to access subscription for this order (if endpoint exists)
    echo "\nAccessing order subscription using raw method...\n";
    $orderId = $order->getId();

    // This is just an example - actual endpoint may vary
    // $subscription = $sdk->rawGet("/orders/{$orderId}/subscription/");
    // echo "✓ Order subscription retrieved using raw method\n";

    echo "ℹ Raw methods provide flexibility for custom endpoints\n";

} catch (GateException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Raw API Access Examples Complete ===\n";

/**
 * Key Takeaways:
 *
 * 1. raw() - Universal method for any HTTP method and endpoint
 * 2. rawGet() - Shortcut for GET requests with query parameters
 * 3. rawPost() - Shortcut for POST requests (create)
 * 4. rawPut() - Shortcut for PUT requests (full update)
 * 5. rawPatch() - Shortcut for PATCH requests (partial update)
 * 6. rawDelete() - Shortcut for DELETE requests
 *
 * 7. All methods return raw array responses from the API
 * 8. All methods throw appropriate exceptions (ValidationException, NotFoundException, etc.)
 * 9. Debug mode logs all raw API calls
 * 10. Works with all existing error handling and retry logic
 *
 * Use Cases:
 * - Access Brands, Charges, Taxes, Subscriptions before dedicated methods exist
 * - Test new API endpoints as they're released
 * - Build custom integrations with flexibility
 * - Combine with existing specific methods for complete coverage
 */
