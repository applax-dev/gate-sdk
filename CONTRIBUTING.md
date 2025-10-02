<div align="center">
  <img src="https://media.appla-x.com/img/applax.png" alt="Applax Logo" width="300"/>
</div>

# Contributing to Appla-X Gate SDK

Thank you for your interest in contributing to the Appla-X Gate SDK! This document provides guidelines and information for contributors.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Contributing Process](#contributing-process)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Documentation](#documentation)
- [Submitting Changes](#submitting-changes)
- [Release Process](#release-process)

## Code of Conduct

This project adheres to a code of conduct that promotes a welcoming and inclusive environment for all contributors. By participating, you agree to:

- Be respectful and considerate in all interactions
- Use welcoming and inclusive language
- Accept constructive feedback gracefully
- Focus on what is best for the community
- Show empathy towards other community members

## Getting Started

### Prerequisites

Before contributing, ensure you have:

- PHP 7.4 or higher
- Composer installed
- Git installed
- A GitHub account

### Fork and Clone

1. Fork the repository on GitHub
2. Clone your fork locally:

```bash
git clone https://github.com/your-username/gate-sdk.git
cd gate-sdk
```

3. Add the upstream repository:

```bash
git remote add upstream https://github.com/applax-dev/gate-sdk.git
```

## Development Setup

### Install Dependencies

```bash
# Install production dependencies
composer install

# Install development dependencies
composer install --dev
```

### Environment Setup

1. Copy the example environment file:

```bash
cp .env.example .env
```

2. Configure your test API credentials:

```bash
APPLAX_API_KEY=your-sandbox-api-key
APPLAX_SANDBOX=true
APPLAX_DEBUG=true
```

**⚠️ Important:** Never use production API keys for development or testing.

### Verify Setup

Run the test suite to verify your setup:

```bash
composer test
```

## Contributing Process

### 1. Choose an Issue

- Look for issues labeled `good first issue` for beginners
- Check issues labeled `help wanted` for areas needing contribution
- Create an issue for new features or bugs before starting work

### 2. Create a Branch

Create a descriptive branch name:

```bash
git checkout -b feature/add-webhook-validation
git checkout -b fix/card-payment-error-handling
git checkout -b docs/improve-installation-guide
```

### 3. Branch Naming Conventions

- `feature/` - New features
- `fix/` - Bug fixes
- `docs/` - Documentation updates
- `refactor/` - Code refactoring
- `test/` - Test improvements
- `chore/` - Maintenance tasks

## Coding Standards

### PHP Standards

We follow **PSR-12** coding standards with additional project-specific rules:

#### Code Style

```php
<?php

declare(strict_types=1);

namespace ApplaxDev\GateSDK\Example;

use ApplaxDev\GateSDK\GateSDK;
use ApplaxDev\GateSDK\Exceptions\GateException;

/**
 * Example class demonstrating coding standards
 */
class ExampleClass
{
    private string $apiKey;
    private bool $debug;

    public function __construct(string $apiKey, bool $debug = false)
    {
        $this->apiKey = $apiKey;
        $this->debug = $debug;
    }

    /**
     * Example method with proper documentation
     *
     * @param array $data Input data
     * @return array Processed result
     * @throws GateException When validation fails
     */
    public function processData(array $data): array
    {
        $this->validateData($data);

        try {
            return $this->performProcessing($data);
        } catch (Exception $e) {
            throw new GateException('Processing failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function validateData(array $data): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Data cannot be empty');
        }
    }
}
```

#### Key Rules

- Use `declare(strict_types=1);`
- Type hints for all parameters and return values
- Private/protected properties and methods where appropriate
- Descriptive variable and method names
- Proper PHPDoc comments for public methods
- Exception handling with meaningful messages

### Code Quality Tools

Run these commands before submitting:

```bash
# Check code style
composer cs-check

# Fix code style automatically
composer cs-fix

# Run static analysis
composer phpstan

# Run all quality checks
composer quality
```

## Testing

### Test Structure

```
tests/
├── Unit/           # Unit tests for individual classes
├── Integration/    # Integration tests with API
├── fixtures/       # Test data and mock responses
└── TestCase.php    # Base test case class
```

### Writing Tests

#### Unit Test Example

```php
<?php

namespace ApplaxDev\GateSDK\Tests\Unit;

use ApplaxDev\GateSDK\GateSDK;
use ApplaxDev\GateSDK\Tests\TestCase;
use ApplaxDev\GateSDK\Exceptions\ValidationException;

class GateSDKTest extends TestCase
{
    public function testValidApiKeyLength(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('API key appears to be invalid');

        new GateSDK('short-key', true);
    }

    public function testSuccessfulInitialization(): void
    {
        $sdk = new GateSDK('valid-api-key-32-characters-long', true);

        $this->assertInstanceOf(GateSDK::class, $sdk);
    }
}
```

#### Integration Test Example

```php
<?php

namespace ApplaxDev\GateSDK\Tests\Integration;

use ApplaxDev\GateSDK\GateSDK;
use ApplaxDev\GateSDK\Tests\TestCase;

class OrderIntegrationTest extends TestCase
{
    private GateSDK $sdk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sdk = new GateSDK(
            $_ENV['APPLAX_API_KEY'],
            true // Use sandbox
        );
    }

    public function testCreateOrder(): void
    {
        $orderData = [
            'client' => [
                'email' => 'test@example.com',
                'phone' => '371-12345678',
            ],
            'products' => [
                [
                    'title' => 'Test Product',
                    'price' => 10.00,
                    'quantity' => 1,
                ]
            ],
            'currency' => 'EUR',
        ];

        $order = $this->sdk->createOrderModel($orderData);

        $this->assertNotEmpty($order->getId());
        $this->assertEquals('EUR', $order->getCurrency());
        $this->assertEquals(10.00, $order->getAmount());
    }
}
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
./vendor/bin/phpunit tests/Unit/GateSDKTest.php

# Run tests with coverage
composer test-coverage

# Run only unit tests
./vendor/bin/phpunit --testsuite=unit

# Run only integration tests
./vendor/bin/phpunit --testsuite=integration
```

### Test Requirements

- All new code must have tests
- Tests must pass before submitting PR
- Aim for >90% code coverage
- Use meaningful test names
- Test both success and failure scenarios
- Mock external dependencies in unit tests

## Documentation

### Documentation Types

1. **API Documentation** - PHPDoc comments in code
2. **User Guides** - Markdown files in `docs/` folder
3. **Examples** - Working code examples in `examples/` folder
4. **README Updates** - Keep main README.md current

### PHPDoc Standards

```php
/**
 * Create a new order with payment information
 *
 * This method creates an order in the Appla-X system with the provided
 * client and product information. The order can then be used to process
 * payments through various payment methods.
 *
 * @param array $data Order data containing client and products
 * @return array Raw order data from API
 * @throws ValidationException When required fields are missing
 * @throws AuthenticationException When API key is invalid
 * @throws GateException For other API errors
 *
 * @example
 * $orderData = [
 *     'client' => ['email' => 'user@example.com'],
 *     'products' => [['title' => 'Item', 'price' => 10.00]]
 * ];
 * $order = $sdk->createOrder($orderData);
 */
public function createOrder(array $data): array
{
    // Implementation
}
```

### Example Code

When adding examples:

- Include complete, working code
- Add explanatory comments
- Show error handling
- Use realistic but safe test data
- Include expected output

## Submitting Changes

### Pull Request Process

1. **Sync with upstream:**

```bash
git fetch upstream
git checkout main
git merge upstream/main
```

2. **Create feature branch:**

```bash
git checkout -b feature/your-feature-name
```

3. **Make your changes:**
   - Write code following our standards
   - Add comprehensive tests
   - Update documentation as needed

4. **Run quality checks:**

```bash
composer quality
```

5. **Commit your changes:**

```bash
git add .
git commit -m "feat: add webhook signature validation

- Add validateWebhookSignature method to GateSDK
- Include HMAC-SHA256 signature verification
- Add comprehensive tests for signature validation
- Update webhook documentation with security examples

Closes #123"
```

### Commit Message Format

Use [Conventional Commits](https://www.conventionalcommits.org/) format:

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

#### Types

- `feat:` - New features
- `fix:` - Bug fixes
- `docs:` - Documentation changes
- `style:` - Code style changes (formatting, etc.)
- `refactor:` - Code refactoring
- `test:` - Adding or updating tests
- `chore:` - Maintenance tasks

#### Examples

```bash
# Feature
git commit -m "feat: add Apple Pay payment method support"

# Bug fix
git commit -m "fix: handle timeout errors in card payments"

# Documentation
git commit -m "docs: update installation guide for Laravel 10"

# Breaking change
git commit -m "feat!: change order creation API signature

BREAKING CHANGE: createOrder now requires currency parameter"
```

### Pull Request Template

When creating a PR, include:

```markdown
## Description
Brief description of changes made.

## Type of Change
- [ ] Bug fix (non-breaking change that fixes an issue)
- [ ] New feature (non-breaking change that adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] New tests added for new functionality
- [ ] Integration tests pass with sandbox API

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No breaking changes (or breaking changes documented)

## Related Issues
Closes #123
Related to #456
```

### Review Process

1. **Automated checks** - All CI checks must pass
2. **Code review** - At least one maintainer review required
3. **Testing** - Reviewers will test functionality
4. **Documentation** - Ensure docs are updated appropriately

## Release Process

### Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR** - Breaking changes
- **MINOR** - New features (backward compatible)
- **PATCH** - Bug fixes (backward compatible)

### Release Checklist

Before releasing:

- [ ] All tests pass
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] Version bumped in `composer.json`
- [ ] Examples tested
- [ ] Breaking changes documented

## Development Guidelines

### Performance Considerations

- Use appropriate timeouts for HTTP requests
- Implement retry logic for network errors
- Cache responses when appropriate
- Minimize API calls in examples

### Security Guidelines

- Never log sensitive data (API keys, card numbers)
- Validate all input parameters
- Use secure HTTP methods
- Implement proper error handling
- Follow OWASP security practices

### API Design Principles

- Consistent method naming
- Clear parameter validation
- Comprehensive error messages
- Backward compatibility when possible
- Intuitive class structure

### Error Handling

```php
// Good error handling
try {
    $result = $this->makeApiCall($data);
    return $this->processResult($result);
} catch (NetworkException $e) {
    $this->logger->error('Network error in API call', [
        'error' => $e->getMessage(),
        'endpoint' => $endpoint,
    ]);
    throw $e;
} catch (Exception $e) {
    throw new GateException(
        'Unexpected error: ' . $e->getMessage(),
        0,
        $e
    );
}
```

## Getting Help

### Communication Channels

- **GitHub Issues** - Bug reports and feature requests
- **GitHub Discussions** - General questions and community support
- **Email** - security@appla-x.com for security issues

### Development Support

- Check existing issues before creating new ones
- Provide minimal reproduction examples
- Include relevant environment information
- Use appropriate issue labels

### Maintainer Response Times

- **Security issues** - Within 24 hours
- **Bug reports** - Within 1 week
- **Feature requests** - Within 2 weeks
- **Pull requests** - Within 1 week

## Recognition

Contributors are recognized in:

- CHANGELOG.md for each release
- GitHub contributors section
- Annual contributor acknowledgments

Thank you for contributing to the Appla-X Gate SDK! Your contributions help make payment processing easier and more reliable for developers worldwide.