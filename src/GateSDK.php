<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK;

use ApplaxDev\GateSDK\Config\GateConfig;
use ApplaxDev\GateSDK\Exceptions\GateException;
use ApplaxDev\GateSDK\Exceptions\ValidationException;
use ApplaxDev\GateSDK\Exceptions\AuthenticationException;
use ApplaxDev\GateSDK\Exceptions\NotFoundException;
use ApplaxDev\GateSDK\Exceptions\RateLimitException;
use ApplaxDev\GateSDK\Exceptions\ServerException;
use ApplaxDev\GateSDK\Exceptions\NetworkException;
use ApplaxDev\GateSDK\Models\Order;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Appla-X Gate API PHP SDK
 *
 * A comprehensive, production-ready PHP SDK for the Appla-X Gate API v0.6
 * providing secure payment processing, order management, and merchant services.
 *
 * @package ApplaxDev\GateSDK
 * @version 1.0.0
 * @author  Appla-X Development Team
 * @see     https://docs.appla-x.com/
 */
class GateSDK
{
    private const API_VERSION = 'v0.6';
    private const SANDBOX_BASE_URL = 'https://gate.appla-x.com/api/' . self::API_VERSION;
    private const PRODUCTION_BASE_URL = 'https://gate.appla-x.com/api/' . self::API_VERSION;

    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_CONNECT_TIMEOUT = 10;
    private const MAX_RETRIES = 3;

    private string $apiKey;
    private string $baseUrl;
    private ClientInterface $httpClient;
    private LoggerInterface $logger;
    private array $defaultHeaders;
    private int $timeout;
    private int $connectTimeout;
    private bool $debugMode;
    private int $retryAttempts;

    /**
     * Initialize the Appla-X Gate SDK
     *
     * @param string $apiKey The Bearer token from your Appla-X dashboard
     * @param bool $sandbox Whether to use sandbox environment (default: true)
     * @param array $config Additional configuration options
     * @param ClientInterface|null $httpClient Optional custom HTTP client
     * @param LoggerInterface|null $logger Optional logger instance
     * @throws AuthenticationException
     */
    public function __construct(
        string $apiKey,
        bool $sandbox = true,
        array $config = [],
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null
    ) {
        $this->validateApiKey($apiKey);

        $this->apiKey = $apiKey;
        $this->baseUrl = $sandbox ? self::SANDBOX_BASE_URL : self::PRODUCTION_BASE_URL;
        $this->logger = $logger ?? new NullLogger();
        $this->timeout = $config['timeout'] ?? self::DEFAULT_TIMEOUT;
        $this->connectTimeout = $config['connect_timeout'] ?? self::DEFAULT_CONNECT_TIMEOUT;
        $this->debugMode = $config['debug'] ?? false;
        $this->retryAttempts = $config['max_retries'] ?? self::MAX_RETRIES;

        $this->defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json; charset=UTF-8',
            'User-Agent' => 'ApplaX-Gate-SDK-PHP/1.0.0',
        ];

        $this->httpClient = $httpClient ?? $this->createDefaultHttpClient();
    }

    /**
     * Create SDK instance from configuration object
     *
     * @param GateConfig $config Configuration instance
     * @param ClientInterface|null $httpClient Optional custom HTTP client
     * @param LoggerInterface|null $logger Optional logger instance
     * @return self
     */
    public static function fromConfig(
        GateConfig $config,
        ?ClientInterface $httpClient = null,
        ?LoggerInterface $logger = null
    ): self {
        return new self(
            $config->getApiKey(),
            $config->isSandbox(),
            $config->toArray(),
            $httpClient,
            $logger
        );
    }

    // ===== PRODUCTS MANAGEMENT =====

    /**
     * Create a new product
     *
     * @param array $data Product data
     * @return array
     * @throws GateException
     */
    public function createProduct(array $data): array
    {
        $this->validateRequired($data, ['brand', 'title', 'currency', 'price']);
        return $this->makeRequest('POST', '/products/', $data);
    }

    /**
     * Retrieve a list of all products
     *
     * @param array $filters Optional filters (search_query, filter_title, filter_price)
     * @return array
     * @throws GateException
     */
    public function getProducts(array $filters = []): array
    {
        return $this->makeRequest('GET', '/products/', null, $filters);
    }

    /**
     * Retrieve a single product
     *
     * @param string $productId Product UUID
     * @return array
     * @throws GateException
     */
    public function getProduct(string $productId): array
    {
        $this->validateUuid($productId, 'productId');
        return $this->makeRequest('GET', "/products/{$productId}/");
    }

    /**
     * Update all product parameters
     *
     * @param string $productId Product UUID
     * @param array $data Complete product data
     * @return array
     * @throws GateException
     */
    public function updateProduct(string $productId, array $data): array
    {
        $this->validateUuid($productId, 'productId');
        $this->validateRequired($data, ['brand', 'title', 'currency', 'price']);
        return $this->makeRequest('PUT', "/products/{$productId}/", $data);
    }

    /**
     * Partially update product parameters
     *
     * @param string $productId Product UUID
     * @param array $data Partial product data
     * @return array
     * @throws GateException
     */
    public function partialUpdateProduct(string $productId, array $data): array
    {
        $this->validateUuid($productId, 'productId');
        return $this->makeRequest('PATCH', "/products/{$productId}/", $data);
    }

    /**
     * Delete a product
     *
     * @param string $productId Product UUID
     * @return array
     * @throws GateException
     */
    public function deleteProduct(string $productId): array
    {
        $this->validateUuid($productId, 'productId');
        return $this->makeRequest('DELETE', "/products/{$productId}/");
    }

    // ===== CLIENTS MANAGEMENT =====

    /**
     * Create a new client
     *
     * @param array $data Client data
     * @return array
     * @throws GateException
     */
    public function createClient(array $data): array
    {
        $this->validateRequired($data, ['email', 'phone']);
        return $this->makeRequest('POST', '/clients/', $data);
    }

    /**
     * Retrieve a list of all clients
     *
     * @param array $filters Optional filters
     * @return array
     * @throws GateException
     */
    public function getClients(array $filters = []): array
    {
        return $this->makeRequest('GET', '/clients/', null, $filters);
    }

    /**
     * Retrieve a single client
     *
     * @param string $clientId Client UUID
     * @return array
     * @throws GateException
     */
    public function getClient(string $clientId): array
    {
        $this->validateUuid($clientId, 'clientId');
        return $this->makeRequest('GET', "/clients/{$clientId}/");
    }

    /**
     * Update all client parameters
     *
     * @param string $clientId Client UUID
     * @param array $data Complete client data
     * @return array
     * @throws GateException
     */
    public function updateClient(string $clientId, array $data): array
    {
        $this->validateUuid($clientId, 'clientId');
        $this->validateRequired($data, ['email', 'phone']);
        return $this->makeRequest('PUT', "/clients/{$clientId}/", $data);
    }

    /**
     * Partially update client parameters
     *
     * @param string $clientId Client UUID
     * @param array $data Partial client data
     * @return array
     * @throws GateException
     */
    public function partialUpdateClient(string $clientId, array $data): array
    {
        $this->validateUuid($clientId, 'clientId');
        return $this->makeRequest('PATCH', "/clients/{$clientId}/", $data);
    }

    /**
     * Delete a client
     *
     * @param string $clientId Client UUID
     * @return array
     * @throws GateException
     */
    public function deleteClient(string $clientId): array
    {
        $this->validateUuid($clientId, 'clientId');
        return $this->makeRequest('DELETE', "/clients/{$clientId}/");
    }

    // ===== ORDERS MANAGEMENT (CORE PAYMENT FUNCTIONALITY) =====

    /**
     * Create a new order
     *
     * @param array $data Order data with client and products
     * @return array
     * @throws GateException
     */
    public function createOrder(array $data): array
    {
        $this->validateRequired($data, ['client', 'products']);
        $this->validateOrderClient($data['client']);
        $this->validateOrderProducts($data['products']);
        return $this->makeRequest('POST', '/orders/', $data);
    }

    /**
     * Create order and return Order model
     *
     * @param array $data Order data with client and products
     * @return Order
     * @throws GateException
     */
    public function createOrderModel(array $data): Order
    {
        $orderData = $this->createOrder($data);
        return new Order($orderData);
    }

    /**
     * Retrieve a list of all orders
     *
     * @param array $filters Optional filters (field, quick_search, merchant_uid)
     * @return array
     * @throws GateException
     */
    public function getOrders(array $filters = []): array
    {
        return $this->makeRequest('GET', '/orders/', null, $filters);
    }

    /**
     * Retrieve a single order
     *
     * @param string $orderId Order UUID
     * @return array
     * @throws GateException
     */
    public function getOrder(string $orderId): array
    {
        $this->validateUuid($orderId, 'orderId');
        return $this->makeRequest('GET', "/orders/{$orderId}/");
    }

    /**
     * Retrieve order as Order model
     *
     * @param string $orderId Order UUID
     * @return Order
     * @throws GateException
     */
    public function getOrderModel(string $orderId): Order
    {
        $orderData = $this->getOrder($orderId);
        return new Order($orderData);
    }

    /**
     * Capture authorized payment
     *
     * @param string $orderId Order UUID
     * @param array $data Optional capture data (amount)
     * @return array
     * @throws GateException
     */
    public function capturePayment(string $orderId, array $data = []): array
    {
        $this->validateUuid($orderId, 'orderId');
        return $this->makeRequest('POST', "/orders/{$orderId}/capture/", $data);
    }

    /**
     * Refund payment
     *
     * @param string $orderId Order UUID
     * @param array $data Optional refund data (amount, reason)
     * @return array
     * @throws GateException
     */
    public function refundPayment(string $orderId, array $data = []): array
    {
        $this->validateUuid($orderId, 'orderId');
        return $this->makeRequest('POST', "/orders/{$orderId}/refund/", $data);
    }

    /**
     * Cancel an order
     *
     * @param string $orderId Order UUID
     * @return array
     * @throws GateException
     */
    public function cancelOrder(string $orderId): array
    {
        $this->validateUuid($orderId, 'orderId');
        return $this->makeRequest('POST', "/orders/{$orderId}/cancel/");
    }

    /**
     * Reverse payment (void authorization)
     *
     * @param string $orderId Order UUID
     * @return array
     * @throws GateException
     */
    public function reversePayment(string $orderId): array
    {
        $this->validateUuid($orderId, 'orderId');
        return $this->makeRequest('POST', "/orders/{$orderId}/reverse/");
    }

    // ===== PAYMENT PROCESSING METHODS =====

    /**
     * Execute card payment
     *
     * @param string $apiDoUrl The api_do_url from order creation response
     * @param array $cardData Card payment data
     * @return array
     * @throws GateException
     */
    public function executeCardPayment(string $apiDoUrl, array $cardData): array
    {
        $this->validateRequired($cardData, ['cardholder_name', 'card_number', 'cvv', 'exp_month', 'exp_year']);
        return $this->makeExternalRequest('POST', $apiDoUrl, $cardData);
    }

    /**
     * Execute Apple Pay payment
     *
     * @param string $apiDoUrl The api_do_applepay from order creation response
     * @param array $applePayData Apple Pay payment data
     * @return array
     * @throws GateException
     */
    public function executeApplePayPayment(string $apiDoUrl, array $applePayData): array
    {
        $this->validateRequired($applePayData, ['payment_data']);
        return $this->makeExternalRequest('POST', $apiDoUrl, $applePayData);
    }

    /**
     * Execute Google Pay payment
     *
     * @param string $apiDoUrl The api_do_googlepay from order creation response
     * @param array $googlePayData Google Pay payment data
     * @return array
     * @throws GateException
     */
    public function executeGooglePayPayment(string $apiDoUrl, array $googlePayData): array
    {
        $this->validateRequired($googlePayData, ['payment_data']);
        return $this->makeExternalRequest('POST', $apiDoUrl, $googlePayData);
    }

    /**
     * Initialize PayPal payment
     *
     * @param string $apiInitUrl The api_init_paypal from order creation response
     * @return array
     * @throws GateException
     */
    public function initPayPalPayment(string $apiInitUrl): array
    {
        return $this->makeExternalRequest('POST', $apiInitUrl);
    }

    /**
     * Initialize Klarna payment
     *
     * @param string $apiInitUrl The api_init_klarna from order creation response
     * @param array $data Klarna payment data
     * @return array
     * @throws GateException
     */
    public function initKlarnaPayment(string $apiInitUrl, array $data): array
    {
        return $this->makeExternalRequest('POST', $apiInitUrl, $data);
    }

    // ===== WEBHOOKS MANAGEMENT =====

    /**
     * Create webhook
     *
     * @param array $data Webhook data
     * @return array
     * @throws GateException
     */
    public function createWebhook(array $data): array
    {
        $this->validateRequired($data, ['url', 'events']);
        return $this->makeRequest('POST', '/webhooks/', $data);
    }

    /**
     * Get all webhooks
     *
     * @return array
     * @throws GateException
     */
    public function getWebhooks(): array
    {
        return $this->makeRequest('GET', '/webhooks/');
    }

    /**
     * Get webhook
     *
     * @param string $webhookId Webhook UUID
     * @return array
     * @throws GateException
     */
    public function getWebhook(string $webhookId): array
    {
        $this->validateUuid($webhookId, 'webhookId');
        return $this->makeRequest('GET', "/webhooks/{$webhookId}/");
    }

    /**
     * Update webhook
     *
     * @param string $webhookId Webhook UUID
     * @param array $data Webhook data
     * @return array
     * @throws GateException
     */
    public function updateWebhook(string $webhookId, array $data): array
    {
        $this->validateUuid($webhookId, 'webhookId');
        return $this->makeRequest('PUT', "/webhooks/{$webhookId}/", $data);
    }

    /**
     * Delete webhook
     *
     * @param string $webhookId Webhook UUID
     * @return array
     * @throws GateException
     */
    public function deleteWebhook(string $webhookId): array
    {
        $this->validateUuid($webhookId, 'webhookId');
        return $this->makeRequest('DELETE', "/webhooks/{$webhookId}/");
    }

    // ===== RAW API ACCESS METHODS =====

    /**
     * Make a raw API request to any endpoint
     *
     * This method provides direct access to the Appla-X Gate API, allowing you to call
     * any endpoint including those not yet implemented as dedicated methods (Brands, Charges,
     * Taxes, Subscriptions, etc.)
     *
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string $endpoint API endpoint path (e.g., '/brands/', '/subscriptions/{id}/')
     * @param array|null $payload Request payload/body data
     * @param array $queryParams Optional query parameters for GET requests
     * @return array API response data
     * @throws GateException When the API request fails
     * @throws ValidationException When input validation fails (400)
     * @throws AuthenticationException When authentication fails (401, 403)
     * @throws NotFoundException When resource is not found (404)
     * @throws RateLimitException When rate limit is exceeded (429)
     * @throws ServerException When server error occurs (5xx)
     * @throws NetworkException When network connectivity fails
     *
     * @example
     * // Create a brand
     * $brand = $sdk->raw('POST', '/brands/', [
     *     'name' => 'My Brand',
     *     'description' => 'Brand description'
     * ]);
     *
     * @example
     * // Get subscriptions with filters
     * $subscriptions = $sdk->raw('GET', '/subscriptions/', null, [
     *     'status' => 'active',
     *     'limit' => 10
     * ]);
     *
     * @example
     * // Update a tax
     * $tax = $sdk->raw('PATCH', '/taxes/{tax-id}/', [
     *     'rate' => 21.0
     * ]);
     */
    public function raw(string $method, string $endpoint, ?array $payload = null, array $queryParams = []): array
    {
        // Validate HTTP method
        $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
        $method = strtoupper($method);

        if (!in_array($method, $allowedMethods)) {
            throw new ValidationException("Invalid HTTP method: {$method}. Allowed: " . implode(', ', $allowedMethods));
        }

        // Validate endpoint format
        if (empty($endpoint)) {
            throw new ValidationException('API endpoint cannot be empty');
        }

        // Ensure endpoint starts with /
        if ($endpoint[0] !== '/') {
            $endpoint = '/' . $endpoint;
        }

        // Log raw API call if debug mode is enabled
        if ($this->debugMode) {
            $this->logger->info('Raw API Call', [
                'method' => $method,
                'endpoint' => $endpoint,
                'has_payload' => $payload !== null,
                'query_params' => $queryParams,
            ]);
        }

        // Make the request using existing internal method
        return $this->makeRequest($method, $endpoint, $payload, $queryParams);
    }

    /**
     * Make a raw GET request
     *
     * Convenience method for GET requests
     *
     * @param string $endpoint API endpoint path
     * @param array $queryParams Optional query parameters
     * @return array API response data
     * @throws GateException
     *
     * @example
     * $brands = $sdk->rawGet('/brands/', ['limit' => 20]);
     */
    public function rawGet(string $endpoint, array $queryParams = []): array
    {
        return $this->raw('GET', $endpoint, null, $queryParams);
    }

    /**
     * Make a raw POST request
     *
     * Convenience method for POST requests (create resources)
     *
     * @param string $endpoint API endpoint path
     * @param array $payload Request payload data
     * @return array API response data
     * @throws GateException
     *
     * @example
     * $brand = $sdk->rawPost('/brands/', ['name' => 'My Brand']);
     */
    public function rawPost(string $endpoint, array $payload): array
    {
        return $this->raw('POST', $endpoint, $payload);
    }

    /**
     * Make a raw PUT request
     *
     * Convenience method for PUT requests (full update)
     *
     * @param string $endpoint API endpoint path
     * @param array $payload Request payload data
     * @return array API response data
     * @throws GateException
     *
     * @example
     * $brand = $sdk->rawPut('/brands/{id}/', $fullBrandData);
     */
    public function rawPut(string $endpoint, array $payload): array
    {
        return $this->raw('PUT', $endpoint, $payload);
    }

    /**
     * Make a raw PATCH request
     *
     * Convenience method for PATCH requests (partial update)
     *
     * @param string $endpoint API endpoint path
     * @param array $payload Request payload data
     * @return array API response data
     * @throws GateException
     *
     * @example
     * $brand = $sdk->rawPatch('/brands/{id}/', ['name' => 'Updated Name']);
     */
    public function rawPatch(string $endpoint, array $payload): array
    {
        return $this->raw('PATCH', $endpoint, $payload);
    }

    /**
     * Make a raw DELETE request
     *
     * Convenience method for DELETE requests
     *
     * @param string $endpoint API endpoint path
     * @return array API response data
     * @throws GateException
     *
     * @example
     * $result = $sdk->rawDelete('/brands/{id}/');
     */
    public function rawDelete(string $endpoint): array
    {
        return $this->raw('DELETE', $endpoint);
    }

    // ===== UTILITY METHODS =====

    /**
     * Validate webhook signature
     *
     * @param string $payload Raw webhook payload
     * @param string $signature Webhook signature header
     * @param string $secret Webhook secret
     * @return bool
     */
    public function validateWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Format currency amount
     *
     * @param float $amount Amount to format
     * @param string $currency Currency code
     * @return string
     */
    public function formatCurrency(float $amount, string $currency): string
    {
        return number_format($amount, 2) . ' ' . strtoupper($currency);
    }

    /**
     * Validate currency code
     *
     * @param string $currency Currency code
     * @return bool
     */
    public function validateCurrency(string $currency): bool
    {
        return preg_match('/^[A-Z]{3}$/', $currency) === 1;
    }

    // ===== PRIVATE HELPER METHODS =====

    /**
     * Create default HTTP client
     *
     * @return ClientInterface
     */
    private function createDefaultHttpClient(): ClientInterface
    {
        return new Client([
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'verify' => true,
            'http_errors' => false,
        ]);
    }

    /**
     * Make HTTP request to the API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array|null $data Request data
     * @param array $queryParams Query parameters
     * @return array
     * @throws GateException
     */
    private function makeRequest(string $method, string $endpoint, ?array $data = null, array $queryParams = []): array
    {
        $url = $this->baseUrl . $endpoint;
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $headers = $this->defaultHeaders;
        $body = $data ? json_encode($data) : null;

        $request = new Request($method, $url, $headers, $body);

        return $this->executeRequest($request);
    }

    /**
     * Make HTTP request to external URL (for payment execution)
     *
     * @param string $method HTTP method
     * @param string $url Full URL
     * @param array|null $data Request data
     * @return array
     * @throws GateException
     */
    private function makeExternalRequest(string $method, string $url, ?array $data = null): array
    {
        $headers = $this->defaultHeaders;
        $body = $data ? json_encode($data) : null;

        $request = new Request($method, $url, $headers, $body);

        return $this->executeRequest($request);
    }

    /**
     * Execute HTTP request with retry logic
     *
     * @param RequestInterface $request
     * @return array
     * @throws GateException
     */
    private function executeRequest(RequestInterface $request): array
    {
        $attempt = 0;

        while ($attempt <= $this->retryAttempts) {
            try {
                if ($this->debugMode) {
                    $this->logger->debug('Appla-X Gate API Request', [
                        'method' => $request->getMethod(),
                        'url' => (string) $request->getUri(),
                        'headers' => $this->sanitizeHeaders($request->getHeaders()),
                    ]);
                }

                $response = $this->httpClient->sendRequest($request);

                if ($this->debugMode) {
                    $this->logger->debug('Appla-X Gate API Response', [
                        'status' => $response->getStatusCode(),
                        'headers' => $response->getHeaders(),
                    ]);
                }

                return $this->handleResponse($response);

            } catch (RequestException $e) {
                $attempt++;

                if ($attempt > $this->retryAttempts) {
                    $this->logger->error('Appla-X Gate API Request Failed', [
                        'error' => $e->getMessage(),
                        'attempts' => $attempt,
                    ]);

                    throw new NetworkException(
                        'Network error: ' . $e->getMessage(),
                        $e->getCode(),
                        $e
                    );
                }

                // Exponential backoff
                sleep(pow(2, $attempt - 1));
            }
        }

        throw new NetworkException('Maximum retry attempts exceeded');
    }

    /**
     * Handle HTTP response
     *
     * @param ResponseInterface $response
     * @return array
     * @throws GateException
     */
    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new GateException('Invalid JSON response: ' . json_last_error_msg());
        }

        switch ($statusCode) {
            case 200:
            case 201:
                return $data;

            case 400:
                throw new ValidationException(
                    $data['message'] ?? 'Invalid input',
                    $statusCode,
                    null,
                    $data
                );

            case 401:
            case 403:
                throw new AuthenticationException(
                    $data['message'] ?? 'Authentication failed',
                    $statusCode,
                    null,
                    $data
                );

            case 404:
                throw new NotFoundException(
                    $data['message'] ?? 'Resource not found',
                    $statusCode,
                    null,
                    $data
                );

            case 429:
                throw new RateLimitException(
                    $data['message'] ?? 'Rate limit exceeded',
                    $statusCode,
                    null,
                    $data
                );

            case 500:
            case 502:
            case 503:
            case 504:
                throw new ServerException(
                    $data['message'] ?? 'Server error',
                    $statusCode,
                    null,
                    $data
                );

            default:
                throw new GateException(
                    $data['message'] ?? 'Unknown error',
                    $statusCode,
                    null,
                    $data
                );
        }
    }

    /**
     * Sanitize headers for logging (remove sensitive data)
     *
     * @param array $headers
     * @return array
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sanitized = $headers;

        if (isset($sanitized['Authorization'])) {
            $sanitized['Authorization'] = ['Bearer ***REDACTED***'];
        }

        return $sanitized;
    }

    /**
     * Validate API key format
     *
     * @param string $apiKey
     * @throws AuthenticationException
     */
    private function validateApiKey(string $apiKey): void
    {
        if (empty($apiKey)) {
            throw new AuthenticationException('API key cannot be empty');
        }

        if (strlen($apiKey) < 32) {
            throw new AuthenticationException('API key appears to be invalid');
        }
    }

    /**
     * Validate UUID format
     *
     * @param string $uuid
     * @param string $fieldName
     * @throws ValidationException
     */
    private function validateUuid(string $uuid, string $fieldName): void
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) {
            throw new ValidationException("Invalid UUID format for {$fieldName}: {$uuid}");
        }
    }

    /**
     * Validate required fields
     *
     * @param array $data
     * @param array $required
     * @throws ValidationException
     */
    private function validateRequired(array $data, array $required): void
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                throw new ValidationException("Required field missing: {$field}");
            }
        }
    }

    /**
     * Validate order client data
     *
     * @param array $client
     * @throws ValidationException
     */
    private function validateOrderClient(array $client): void
    {
        if (!isset($client['email']) && !isset($client['phone'])) {
            throw new ValidationException('Client must have either email or phone');
        }

        if (isset($client['email']) && !filter_var($client['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }

        if (isset($client['phone']) && !preg_match('/^\d{1,4}-\d{1,15}$/', $client['phone'])) {
            throw new ValidationException('Invalid phone format. Use: country_code-phone_number');
        }
    }

    /**
     * Validate order products data
     *
     * @param array $products
     * @throws ValidationException
     */
    private function validateOrderProducts(array $products): void
    {
        if (empty($products) || !is_array($products)) {
            throw new ValidationException('Products array cannot be empty');
        }

        if (count($products) > 50) {
            throw new ValidationException('Maximum 50 products allowed per order');
        }

        foreach ($products as $index => $product) {
            if (!isset($product['title'])) {
                throw new ValidationException("Product at index {$index} missing required field: title");
            }

            if (!isset($product['price'])) {
                throw new ValidationException("Product at index {$index} missing required field: price");
            }

            if (!is_numeric($product['price']) || $product['price'] < 0) {
                throw new ValidationException("Product at index {$index} has invalid price");
            }
        }
    }
}