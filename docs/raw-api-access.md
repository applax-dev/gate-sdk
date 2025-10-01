# Raw API Access Guide

This guide explains how to use the raw API access methods to interact with any Appla-X Gate API endpoint, including those without dedicated SDK methods.

## Overview

The Appla-X Gate SDK provides raw API access methods that allow you to call any API endpoint directly. This is particularly useful for:

- **Accessing new features** - Brands, Charges, Taxes, Subscriptions
- **Testing beta endpoints** - Try new API features before dedicated methods exist
- **Custom integrations** - Build specialized workflows with flexibility
- **Future-proofing** - Access new endpoints as soon as they're released by Appla-X

## Available Methods

### Main Method: `raw()`

The universal method that accepts any HTTP method and endpoint.

```php
public function raw(
    string $method,        // HTTP method: GET, POST, PUT, PATCH, DELETE
    string $endpoint,      // API endpoint path (e.g., '/brands/')
    ?array $payload = null, // Request body data
    array $queryParams = [] // Query parameters for GET requests
): array
```

### Convenience Methods

Shortcut methods for common HTTP operations:

```php
// GET request
$sdk->rawGet(string $endpoint, array $queryParams = []): array

// POST request (create)
$sdk->rawPost(string $endpoint, array $payload): array

// PUT request (full update)
$sdk->rawPut(string $endpoint, array $payload): array

// PATCH request (partial update)
$sdk->rawPatch(string $endpoint, array $payload): array

// DELETE request
$sdk->rawDelete(string $endpoint): array
```

## Response Format

All raw methods return the raw array response from the API:

```php
[
    'id' => 'uuid',
    'type' => 'resource_type',
    'status' => 'active',
    // ... other fields from API
]
```

For paginated responses:

```php
[
    'count' => 100,
    'next' => 'cursor_token',
    'previous' => null,
    'results' => [
        // array of resource objects
    ]
]
```

## Exception Handling

Raw methods throw the same exceptions as dedicated methods:

- `ValidationException` - Invalid input (400)
- `AuthenticationException` - Authentication failed (401, 403)
- `NotFoundException` - Resource not found (404)
- `RateLimitException` - Rate limit exceeded (429)
- `ServerException` - Server error (5xx)
- `NetworkException` - Network connectivity issues

## Brands Management

### Create a Brand

```php
use ApplaxDev\GateSDK\GateSDK;

$sdk = new GateSDK('your-api-key', true);

// Create brand
$brand = $sdk->rawPost('/brands/', [
    'name' => 'My Brand',
    'description' => 'Brand description',
    'website' => 'https://mybrand.com',
    'logo_url' => 'https://mybrand.com/logo.png',
    'primary_color' => '#007bff',
    'secondary_color' => '#6c757d'
]);

echo "Brand created: {$brand['id']}\n";
```

### List Brands

```php
// Get all brands with pagination
$brands = $sdk->rawGet('/brands/', [
    'limit' => 20,
    'offset' => 0
]);

foreach ($brands['results'] as $brand) {
    echo "- {$brand['name']}\n";
}
```

### Get Single Brand

```php
$brandId = 'brand-uuid-here';
$brand = $sdk->rawGet("/brands/{$brandId}/");

echo "Brand: {$brand['name']}\n";
echo "Website: {$brand['website']}\n";
```

### Update Brand (Full Update)

```php
$brand = $sdk->rawPut("/brands/{$brandId}/", [
    'name' => 'Updated Brand Name',
    'description' => 'Updated description',
    'website' => 'https://newwebsite.com'
]);
```

### Update Brand (Partial Update)

```php
$brand = $sdk->rawPatch("/brands/{$brandId}/", [
    'description' => 'Only updating description'
]);
```

### Delete Brand

```php
$sdk->rawDelete("/brands/{$brandId}/");
echo "Brand deleted\n";
```

## Subscriptions Management

### Create a Subscription

```php
$subscription = $sdk->rawPost('/subscriptions/', [
    'client' => [
        'email' => 'customer@example.com',
        'phone' => '371-12345678',
        'first_name' => 'John',
        'last_name' => 'Doe'
    ],
    'plan_id' => 'plan-uuid',
    'amount' => 29.99,
    'currency' => 'EUR',
    'interval' => 'monthly', // monthly, yearly, weekly
    'interval_count' => 1,
    'trial_days' => 14,
    'start_date' => '2024-01-01',
    'payment_method' => 'card'
]);

echo "Subscription created: {$subscription['id']}\n";
echo "Status: {$subscription['status']}\n";
```

### List Subscriptions

```php
// Get active subscriptions
$subscriptions = $sdk->rawGet('/subscriptions/', [
    'status' => 'active',
    'limit' => 10
]);

// Get subscriptions for a specific client
$subscriptions = $sdk->rawGet('/subscriptions/', [
    'client_id' => 'client-uuid'
]);
```

### Get Subscription Details

```php
$subscriptionId = 'subscription-uuid';
$subscription = $sdk->rawGet("/subscriptions/{$subscriptionId}/");

echo "Plan: {$subscription['plan_id']}\n";
echo "Amount: {$subscription['amount']} {$subscription['currency']}\n";
echo "Next billing: {$subscription['next_billing_date']}\n";
```

### Update Subscription

```php
$subscription = $sdk->rawPatch("/subscriptions/{$subscriptionId}/", [
    'amount' => 39.99,
    'interval' => 'yearly'
]);
```

### Cancel Subscription

```php
$subscription = $sdk->rawPost("/subscriptions/{$subscriptionId}/cancel/", [
    'cancel_at_period_end' => true,
    'reason' => 'Customer request'
]);

echo "Subscription cancelled: {$subscription['status']}\n";
```

### Pause/Resume Subscription

```php
// Pause
$subscription = $sdk->rawPost("/subscriptions/{$subscriptionId}/pause/", []);

// Resume
$subscription = $sdk->rawPost("/subscriptions/{$subscriptionId}/resume/", []);
```

## Taxes Management

### Create a Tax

```php
$tax = $sdk->rawPost('/taxes/', [
    'name' => 'VAT',
    'rate' => 21.0,
    'type' => 'percentage', // percentage or fixed
    'country' => 'LV',
    'region' => 'Riga',
    'description' => 'Latvia VAT 21%',
    'is_active' => true
]);

echo "Tax created: {$tax['id']}\n";
```

### List Taxes

```php
// Get all taxes
$taxes = $sdk->rawGet('/taxes/');

// Get taxes for specific country
$taxes = $sdk->rawGet('/taxes/', [
    'country' => 'LV'
]);
```

### Get Tax Details

```php
$taxId = 'tax-uuid';
$tax = $sdk->rawGet("/taxes/{$taxId}/");

echo "Tax: {$tax['name']} - {$tax['rate']}%\n";
```

### Update Tax

```php
$tax = $sdk->rawPatch("/taxes/{$taxId}/", [
    'rate' => 19.0,
    'description' => 'Updated to 19%'
]);
```

### Delete Tax

```php
$sdk->rawDelete("/taxes/{$taxId}/");
```

## Charges Management

### Create a Charge

```php
$charge = $sdk->rawPost('/charges/', [
    'amount' => 100.00,
    'currency' => 'EUR',
    'description' => 'One-time service charge',
    'client' => [
        'email' => 'customer@example.com',
        'phone' => '371-12345678'
    ],
    'metadata' => [
        'order_id' => '12345',
        'internal_ref' => 'CHG-001'
    ]
]);

echo "Charge created: {$charge['id']}\n";
echo "Status: {$charge['status']}\n";
```

### List Charges

```php
// Get charges with filters
$charges = $sdk->rawGet('/charges/', [
    'status' => 'pending',
    'limit' => 20,
    'created_after' => '2024-01-01'
]);
```

### Get Charge Details

```php
$chargeId = 'charge-uuid';
$charge = $sdk->rawGet("/charges/{$chargeId}/");

echo "Amount: {$charge['amount']} {$charge['currency']}\n";
echo "Status: {$charge['status']}\n";
```

### Capture a Charge

```php
$charge = $sdk->rawPost("/charges/{$chargeId}/capture/", [
    'amount' => 100.00 // Optional: capture partial amount
]);
```

### Refund a Charge

```php
$charge = $sdk->rawPost("/charges/{$chargeId}/refund/", [
    'amount' => 50.00, // Optional: refund partial amount
    'reason' => 'Customer request'
]);

echo "Refunded: {$charge['amount_refunded']} {$charge['currency']}\n";
```

### Cancel a Charge

```php
$charge = $sdk->rawPost("/charges/{$chargeId}/cancel/", []);
```

## Advanced Usage

### Combining Raw and Specific Methods

```php
// Use specific method for orders
$order = $sdk->createOrderModel([
    'client' => ['email' => 'customer@example.com'],
    'products' => [/* products */],
    'currency' => 'EUR'
]);

// Use raw method for subscription
$subscription = $sdk->rawPost('/subscriptions/', [
    'order_id' => $order->getId(),
    'interval' => 'monthly'
]);
```

### Error Handling

```php
use ApplaxDev\GateSDK\Exceptions\{
    ValidationException,
    NotFoundException,
    RateLimitException,
    GateException
};

try {
    $brand = $sdk->rawPost('/brands/', $brandData);

} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";

    // Get field-specific errors
    foreach ($e->getValidationErrors() as $field => $errors) {
        echo "  - {$field}: " . implode(', ', $errors) . "\n";
    }

} catch (NotFoundException $e) {
    echo "Resource not found: " . $e->getMessage() . "\n";

} catch (RateLimitException $e) {
    echo "Rate limit exceeded. Wait: " . $e->getSuggestedWaitTime() . "s\n";

} catch (GateException $e) {
    echo "API error: " . $e->getMessage() . "\n";
    print_r($e->getErrorDetails());
}
```

### Debug Mode

Enable debug mode to see all raw API calls:

```php
$sdk = new GateSDK('your-api-key', true, [
    'debug' => true
]);

// All raw API calls will be logged
$brand = $sdk->rawPost('/brands/', $brandData);
```

### Custom Headers and Pagination

```php
// Pagination using query parameters
$page1 = $sdk->rawGet('/brands/', ['limit' => 10, 'offset' => 0]);
$page2 = $sdk->rawGet('/brands/', ['limit' => 10, 'offset' => 10]);

// Using cursor-based pagination
$firstPage = $sdk->rawGet('/subscriptions/', ['limit' => 20]);

if (isset($firstPage['next'])) {
    $nextPage = $sdk->rawGet('/subscriptions/', [
        'cursor' => $firstPage['next']
    ]);
}
```

## Best Practices

### 1. Validate Input Before Sending

```php
if (empty($brandData['name'])) {
    throw new InvalidArgumentException('Brand name is required');
}

$brand = $sdk->rawPost('/brands/', $brandData);
```

### 2. Use Try-Catch for Error Handling

Always wrap raw API calls in try-catch blocks to handle potential errors gracefully.

### 3. Store IDs for Future Operations

```php
$brand = $sdk->rawPost('/brands/', $brandData);
$brandId = $brand['id'];

// Store $brandId in your database
// Use it later for updates or deletes
```

### 4. Use Convenience Methods When Possible

```php
// Instead of:
$brands = $sdk->raw('GET', '/brands/', null, ['limit' => 10]);

// Use:
$brands = $sdk->rawGet('/brands/', ['limit' => 10]);
```

### 5. Check API Documentation

Refer to the [Appla-X Gate API documentation](https://docs.appla-x.com/) for:
- Exact endpoint paths
- Required and optional fields
- Response formats
- Rate limits and quotas

## Migrating to Dedicated Methods

When dedicated methods become available, migration is easy:

```php
// Before (raw method)
$brand = $sdk->rawPost('/brands/', $brandData);

// After (dedicated method - when available)
$brand = $sdk->createBrand($brandData);
```

Raw methods will continue to work alongside dedicated methods, giving you flexibility.

## Endpoint Reference

Based on standard REST conventions, here are common endpoint patterns:

### Resource Patterns

```
GET    /resource/              - List all resources
POST   /resource/              - Create new resource
GET    /resource/{id}/         - Get single resource
PUT    /resource/{id}/         - Full update resource
PATCH  /resource/{id}/         - Partial update resource
DELETE /resource/{id}/         - Delete resource
```

### Action Patterns

```
POST   /resource/{id}/action/  - Perform action on resource

Examples:
POST   /subscriptions/{id}/cancel/
POST   /subscriptions/{id}/pause/
POST   /charges/{id}/capture/
POST   /charges/{id}/refund/
```

## Support

For questions about raw API access:

1. Check [Appla-X API Documentation](https://docs.appla-x.com/)
2. Review [SDK Examples](../examples/raw-api-usage.php)
3. Contact [ike@appla-x.com](mailto:ike@appla-x.com)

## Next Steps

- Try the [raw API usage examples](../examples/raw-api-usage.php)
- Explore [payment methods](payment-methods.md)
- Set up [webhooks](webhooks.md)
- Read about [error handling](../README.md#error-handling)
