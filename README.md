# 🪝 Laravel Webhook

<div align="center">

⚠️ **WORK IN PROGRESS** ⚠️

*This package is currently under active development. Features and APIs may change before the stable release.*

---

[![Latest Version on Packagist](https://img.shields.io/packagist/v/isaacjuwon/laravel-webhook.svg?style=for-the-badge)](https://packagist.org/packages/isaacjuwon/laravel-webhook)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/isaacjuwon/laravel-webhook/run-tests.yml?branch=main&label=tests&style=for-the-badge)](https://github.com/isaacjuwon/laravel-webhook/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/isaacjuwon/laravel-webhook/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=for-the-badge)](https://github.com/isaacjuwon/laravel-webhook/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/isaacjuwon/laravel-webhook.svg?style=for-the-badge)](https://packagist.org/packages/isaacjuwon/laravel-webhook)
[![License](https://img.shields.io/packagist/l/isaacjuwon/laravel-webhook.svg?style=for-the-badge)](https://packagist.org/packages/isaacjuwon/laravel-webhook)

**The most elegant and flexible Laravel webhook package with class-based architecture, fluent API, and powerful routing macros.**

*Transform complex webhook integrations into simple, readable code with Laravel conventions you already love.*

[Installation](#-installation) • [Quick Start](#-quick-start) • [Documentation](#-documentation) • [Examples](#-real-world-examples) • [Contributing](#-contributing)

</div>

---

## ✨ What Makes This Package Different?

### 🎯 **Class-Based Architecture** 
Define webhooks as elegant PHP classes instead of scattered configuration arrays. Clean, testable, and IDE-friendly.

```php
// ❌ Other packages: Messy config arrays
'webhooks' => [
    'stripe_payment' => [
        'url' => '/webhook/stripe',
        'secret' => env('STRIPE_SECRET'),
        'events' => ['payment_intent.succeeded'],
        'handler' => 'App\\Handlers\\StripeHandler@handle'
    ]
]

// ✅ Laravel Webhook: Beautiful class-based approach
class StripePaymentWebhook extends Webhook
{
    protected string $name = 'stripe.payment_intent.succeeded';
    protected string $signatureHeader = 'Stripe-Signature';
    
    public function handle(WebhookModel $webhook): array
    {
        $paymentIntent = $webhook->get('data.object');
        Payment::markAsPaid($paymentIntent['id']);
        return ['processed' => true];
    }
}
```

### 🚀 **Multiple Registration Patterns**
Choose the approach that fits your style - class-based, callback-based, or fluent builders.

```php
// 1. Super clean class-based (RECOMMENDED)
protected function webhooks(): array
{
    return [
        GitHubPushWebhook::class,
        StripePaymentWebhook::class,
        ShopifyOrderWebhook::class,
    ];
}

// 2. Inline callbacks for simple logic
WebhookBuilder::create('user.created', env('USER_SECRET'))
    ->handle(fn($webhook) => User::find($webhook->get('user_id'))->sendWelcome())

// 3. Mix both approaches in the same array!
return [
    GitHubPushWebhook::class,
    WebhookBuilder::create('simple.event')->handle($callback),
    new CustomWebhook()
];
```

### 🛣️ **Automatic Webhook Routing**
Single route handles all webhooks automatically. No need to define individual routes for each webhook!

```php
// ❌ Other packages: Define every webhook route manually
Route::post('/webhooks/github-push', 'GitHubController@handle');
Route::post('/webhooks/stripe-payment', 'StripeController@handle');
Route::post('/webhooks/shopify-order', 'ShopifyController@handle');
// ... dozens of routes

// ✅ Laravel Webhook: One route handles everything!
Route::webhooks('/webhooks');  // Handles /webhooks/{webhook-name} automatically

// Works with middleware and options
Route::webhooks('/api/webhooks', [
    'middleware' => ['api', 'throttle:webhook:100,1']
]);
```

### 🎭 **Zero-Config Platform Presets**
Pre-configured for major platforms with correct headers and signature validation out of the box.

```php
// Automatically sets X-Hub-Signature-256, proper validation
WebhookBuilder::create('github.push', env('GITHUB_SECRET'))->githubWebhook()

// Automatically sets Stripe-Signature, proper format
WebhookBuilder::create('stripe.event', env('STRIPE_SECRET'))->stripeWebhook()

// Automatically sets X-Shopify-Hmac-Sha256
WebhookBuilder::create('shopify.order', env('SHOPIFY_SECRET'))->shopifyWebhook()
```

### 🔗 **Laravel-First Design**
Built with Laravel conventions in mind. Feels native, not like a third-party add-on.

```php
// Uses familiar Laravel patterns
use RegistersWebhooks;  // Trait-based registration
Route::webhook('/github', 'github.push');  // Route macro
$webhook->get('user_id');  // Collection-like data access
```

### 📦 **Lightweight & Performant**
No bloated dependencies. Pure Laravel/PHP with smart lazy loading and minimal overhead.

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require isaacjuwon/laravel-webhook
```

**Optional:** Publish configuration and migrations:

```bash
# Publish config file
php artisan vendor:publish --tag="laravel-webhook-config"

# Publish migrations (if you need database logging)
php artisan vendor:publish --tag="laravel-webhook-migrations"
php artisan migrate
```

---

## ⚡ Quick Start

### 1. Create Your Webhook Class

```php
// app/Webhooks/UserCreatedWebhook.php
<?php

namespace App\Webhooks;

use Isaacjuwon\LaravelWebhook\Abstracts\Webhook;
use Isaacjuwon\LaravelWebhook\Models\Webhook as WebhookModel;
use App\Models\User;

class UserCreatedWebhook extends Webhook
{
    protected string $name = 'user.created';
    protected string $signatureHeader = 'X-Signature-256';
    
    protected array $storeHeaders = ['X-Event-ID', 'X-Timestamp'];
    
    protected array $properties = [
        'user_id' => 'integer',
        'email' => 'string',
        'name' => 'string',
        'created_at' => 'string'
    ];

    public function __construct()
    {
        $this->signingSecret = env('USER_WEBHOOK_SECRET');
    }

    public function handle(WebhookModel $webhook): array
    {
        $userId = $webhook->get('user_id');
        $email = $webhook->get('email');
        
        // Your business logic
        $user = User::find($userId);
        $user->sendWelcomeEmail();
        
        return [
            'processed' => true, 
            'user_id' => $userId,
            'event_id' => $webhook->getHeader('X-Event-ID')
        ];
    }
}
```

### 2. Register in Your Service Provider

```php
// app/Providers/AppServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Isaacjuwon\LaravelWebhook\Traits\RegistersWebhooks;
use App\Webhooks\UserCreatedWebhook;

class AppServiceProvider extends ServiceProvider
{
    use RegistersWebhooks;

    public function boot()
    {
        $this->registerWebhooks();
    }

    protected function webhooks(): array
    {
        return [
            UserCreatedWebhook::class,
            // Add more webhook classes here
        ];
    }
}
```

### 3. Define Routes Using Automatic Macro

```php
// routes/api.php
use Illuminate\Support\Facades\Route;

// Single route handles ALL webhooks automatically!
Route::webhooks('/webhooks');

// This creates: POST /webhooks/{webhookName}
// Examples:
// POST /webhooks/user.created -> processes 'user.created' webhook
// POST /webhooks/github.push -> processes 'github.push' webhook  
// POST /webhooks/stripe.payment -> processes 'stripe.payment' webhook

// With middleware
Route::webhooks('/api/webhooks', [
    'middleware' => ['api', 'throttle:webhook:100,1']
]);
```

### 4. Test It!

```bash
curl -X POST http://your-app.com/webhooks/user.created \
  -H "Content-Type: application/json" \
  -H "X-Signature-256: sha256=your-signature" \
  -d '{"user_id": 123, "email": "user@example.com", "name": "John Doe"}'
```

**🎉 That's it!** Your webhook is ready to receive and process requests.

---

## 📚 Documentation

### 🎯 Registration Methods

#### Class-Based (Recommended)

```php
protected function webhooks(): array
{
    return [
        GitHubPushWebhook::class,
        StripePaymentWebhook::class,
        UserCreatedWebhook::class,
    ];
}
```

**Benefits:**
- ✅ Clean and minimal - just class names
- ✅ Laravel-like - follows Laravel conventions
- ✅ Lazy loading - classes instantiated only when needed
- ✅ IDE-friendly - full autocomplete and refactoring support

#### Callback-Based

```php
protected function webhooks(): array
{
    return [
        WebhookBuilder::create('user.verified', env('USER_SECRET'))
            ->handle(function (Webhook $webhook) {
                $userId = $webhook->get('user_id');
                User::find($userId)->markEmailAsVerified();
                return ['verified' => true];
            }),
            
        // Arrow functions for simple cases
        WebhookBuilder::create('log.entry', env('LOG_SECRET'))
            ->handle(fn($webhook) => Log::info('Webhook received', $webhook->getPayload()->toArray())),
    ];
}
```

#### Mixed Approach

```php
protected function webhooks(): array
{
    return [
        // Class-based
        GitHubPushWebhook::class,
        
        // Callback-based
        WebhookBuilder::create('simple.event')
            ->handle(fn($webhook) => Cache::put('last_webhook', now())),
            
        // Instance-based
        new StripePaymentWebhook(env('STRIPE_SECRET')),
    ];
}
```

### 🛣️ Route Macros

Laravel Webhook provides powerful route macros that automatically handle all your webhooks through a single route:

#### Automatic Webhook Routing (Recommended)

```php
// Single route handles ALL webhooks!
Route::webhooks();  // Creates: POST /webhooks/{webhookName}

// Custom base path
Route::webhooks('/api/hooks');  // Creates: POST /api/hooks/{webhookName}

// With middleware
Route::webhooks('/webhooks', [
    'middleware' => ['api', 'throttle:webhook:100,1']
]);

// Named route
Route::webhooks('/webhooks', [
    'name' => 'api.webhooks'
]);
```

**How it works:**
- `POST /webhooks/user.created` → processes `user.created` webhook
- `POST /webhooks/github.push` → processes `github.push` webhook  
- `POST /webhooks/stripe.payment_intent.succeeded` → processes `stripe.payment_intent.succeeded` webhook
- `POST /webhooks/shopify.order_created` → processes `shopify.order_created` webhook

#### Advanced Routing Patterns

```php
// Different environments
Route::webhooks('/webhooks/staging', ['middleware' => 'staging.webhooks']);
Route::webhooks('/webhooks/production', ['middleware' => 'production.webhooks']);

// API versioning
Route::prefix('v1')->group(function () {
    Route::webhooks('/webhooks');  // /v1/webhooks/{webhookName}
});

Route::prefix('v2')->group(function () {
    Route::webhooks('/hooks');     // /v2/hooks/{webhookName}
});

// Domain-based routing
Route::domain('api.yourapp.com')->group(function () {
    Route::webhooks('/webhooks');
});

Route::domain('webhooks.yourapp.com')->group(function () {
    Route::webhooks();  // Just /{webhookName}
});
```

#### Individual Webhook Routes (Alternative)

For specific use cases, you can still define individual routes:

```php
// Single webhook route
Route::webhook('/webhooks/user-created', 'user.created');

// With middleware
Route::webhook('/webhooks/stripe', 'stripe.payment', [
    'middleware' => ['verify.stripe.signature']
]);

// Named route
Route::webhook('/github/push', 'github.push')->name('github.webhook');
```

#### Complete Example

```php
// routes/api.php
use Illuminate\Support\Facades\Route;

// Main webhook endpoint - handles all webhooks automatically
Route::webhooks('/api/webhooks', [
    'middleware' => ['api', 'log.webhooks', 'throttle:webhook:100,1']
]);

// This single line creates routes for:
// POST /api/webhooks/user.created
// POST /api/webhooks/github.push
// POST /api/webhooks/stripe.payment_intent.succeeded
// POST /api/webhooks/shopify.order_created
// POST /api/webhooks/mailgun.delivered
// ... and any other webhook you register!
```

### 🎭 Platform Presets

> 🚧 **Coming Soon** - Platform presets are currently in development and will be available in a future release.

Laravel Webhook will come with built-in presets for popular platforms:

```php
// GitHub - Auto-configures X-Hub-Signature-256
WebhookBuilder::create('github.push', env('GITHUB_SECRET'))
    ->githubWebhook()
    ->handle(ProcessGitHubPush::class)

// Stripe - Auto-configures Stripe-Signature
WebhookBuilder::create('stripe.payment', env('STRIPE_SECRET'))
    ->stripeWebhook()
    ->handle(ProcessStripePayment::class)

// Shopify - Auto-configures X-Shopify-Hmac-Sha256
WebhookBuilder::create('shopify.order', env('SHOPIFY_SECRET'))
    ->shopifyWebhook()
    ->handle(ProcessShopifyOrder::class)

// Discord - No signature validation (as per Discord's design)
WebhookBuilder::create('discord.message')
    ->discordWebhook()
    ->handle(ProcessDiscordMessage::class)

// Mailgun - Auto-configures X-Mailgun-Signature
WebhookBuilder::create('mailgun.delivered', env('MAILGUN_SECRET'))
    ->mailgunWebhook()
    ->handle(ProcessMailgunEvent::class)
```

### 🔐 Security Configuration

```php
// Basic signature validation
WebhookBuilder::create('custom.webhook')
    ->withSignature('your-secret', 'X-Custom-Signature')
    ->handle($handler)

// Disable validation for internal webhooks
WebhookBuilder::create('internal.event')
    ->withoutSignature()
    ->handle($handler)

// Custom signature validation
WebhookBuilder::create('advanced.webhook')
    ->signatureHeader('X-My-Signature')
    ->signingSecret(env('WEBHOOK_SECRET'))
    ->handle($handler)
```

### 📊 Header Management

```php
class MyWebhook extends Webhook
{
    protected array $storeHeaders = [
        'X-Event-ID',
        'X-Timestamp',
        'User-Agent',
        'X-Request-ID'
    ];
    
    public function handle(WebhookModel $webhook): array
    {
        $eventId = $webhook->getHeader('X-Event-ID');
        $timestamp = $webhook->getHeader('X-Timestamp');
        $userAgent = $webhook->getHeader('User-Agent');
        
        // Use headers in your logic
        return ['processed' => true, 'event_id' => $eventId];
    }
}
```

### 🔄 Processing Flow

When a webhook request is received, the package follows this processing flow:

1. **Route Resolution**: The webhook route macro maps the request to the `WebhookController`
2. **Webhook Lookup**: The controller finds the registered webhook by name
3. **Signature Validation**: If configured, validates the webhook signature
4. **Payload Extraction**: Extracts payload from JSON or form data
5. **Header Storage**: Stores configured headers for later use
6. **Execution**: Calls the webhook callback with validated data
7. **Response**: Returns a JSON response with success/error status

### 📤 Response Format

**Success Response:**
```json
{
    "success": true,
    "webhook": "user.created",
    "result": {
        "processed": true,
        "user_id": 123
    },
    "processed_at": "2024-01-15T10:30:00Z"
}
```

**Error Responses:**
```json
// Webhook not found (404)
{
    "success": false,
    "error": "Webhook not found",
    "message": "Webhook 'invalid.webhook' not found."
}

// Validation error (400)
{
    "success": false,
    "error": "Validation failed",
    "message": "Invalid payload format"
}

// Invalid signature (400)
{
    "success": false,
    "error": "Validation failed",
    "message": "Invalid webhook signature"
}
```

---

## 🌟 Real-World Examples

### GitHub Integration

```php
class GitHubPushWebhook extends Webhook
{
    protected string $name = 'github.push';
    protected string $signatureHeader = 'X-Hub-Signature-256';
    
    protected array $storeHeaders = [
        'X-GitHub-Event',
        'X-GitHub-Delivery',
        'X-Hub-Signature-256'
    ];

    public function __construct()
    {
        $this->signingSecret = env('GITHUB_WEBHOOK_SECRET');
    }

    public function handle(WebhookModel $webhook): array
    {
        $repository = $webhook->get('repository');
        $commits = $webhook->get('commits', []);
        $pusher = $webhook->get('pusher');
        
        // Trigger CI/CD pipeline
        if ($repository['name'] === 'production-app') {
            DeploymentJob::dispatch($repository, $commits);
        }
        
        // Log the push
        Log::info('GitHub push received', [
            'repository' => $repository['full_name'],
            'commits_count' => count($commits),
            'pusher' => $pusher['name']
        ]);
        
        return [
            'processed' => true,
            'repository' => $repository['full_name'],
            'commits_processed' => count($commits)
        ];
    }
}

// Route registration - ONE route handles all!
Route::webhooks('/webhooks', [
    'middleware' => ['api', 'verify.github.signature']
]);
// This handles: POST /webhooks/github.push
```

### Stripe Payment Processing

```php
class StripePaymentWebhook extends Webhook
{
    protected string $name = 'stripe.payment_intent.succeeded';
    protected string $signatureHeader = 'Stripe-Signature';

    public function __construct()
    {
        $this->signingSecret = env('STRIPE_WEBHOOK_SECRET');
    }

    public function handle(WebhookModel $webhook): array
    {
        $eventType = $webhook->get('type');
        $paymentIntent = $webhook->get('data.object');
        
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent['id'])->first();
        
        if ($payment) {
            $payment->update([
                'status' => 'completed',
                'stripe_event_id' => $webhook->get('id'),
                'completed_at' => now()
            ]);
            
            // Send confirmation email
            $payment->user->notify(new PaymentSuccessfulNotification($payment));
            
            // Update subscription if applicable
            if ($payment->subscription_id) {
                $payment->subscription->activate();
            }
        }
        
        return [
            'payment_processed' => true,
            'payment_intent_id' => $paymentIntent['id'],
            'amount' => $paymentIntent['amount'] / 100
        ];
    }
}

// Route with Stripe-specific middleware
Route::webhooks('/webhooks', [
    'middleware' => ['api', 'verify.stripe.signature', 'throttle:stripe:100,1']
]);
// This handles: POST /webhooks/stripe.payment_intent.succeeded
```

### E-commerce Order Webhook with Advanced Routing

```php
class OrderCreatedWebhook extends Webhook
{
    protected string $name = 'order.created';
    
    protected array $properties = [
        'order_id' => 'string',
        'customer_id' => 'string',
        'total' => 'number',
        'currency' => 'string',
        'items' => 'array'
    ];

    public function handle(WebhookModel $webhook): array
    {
        $orderId = $webhook->get('order_id');
        $customerId = $webhook->get('customer_id');
        $total = $webhook->get('total');
        $items = $webhook->get('items', []);
        
        // Create order record
        $order = Order::create([
            'external_id' => $orderId,
            'customer_id' => $customerId,
            'total' => $total,
            'status' => 'pending'
        ]);
        
        // Process order items
        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ]);
        }
        
        // Send order confirmation
        $customer = Customer::find($customerId);
        $customer->notify(new OrderConfirmationNotification($order));
        
        return [
            'order_created' => true,
            'local_order_id' => $order->id,
            'external_order_id' => $orderId,
            'items_count' => count($items)
        ];
    }
}

// Advanced routing with automatic webhook handling
Route::webhooks('/webhooks', [
    'middleware' => ['api', 'log.webhooks']
]);

// This single route handles all these endpoints:
// POST /webhooks/order.created
// POST /webhooks/order.updated  
// POST /webhooks/order.cancelled
// POST /webhooks/payment.completed
// POST /webhooks/payment.failed
```

---

## 🧪 Testing

Laravel Webhook is thoroughly tested with Pest:

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis
composer analyse

# Fix code style
composer format
```

**Test your webhooks:**

```php
// tests/Feature/WebhookTest.php
use Isaacjuwon\LaravelWebhook\LaravelWebhook;

it('processes user created webhook', function () {
    $payload = [
        'user_id' => 123,
        'email' => 'test@example.com',
        'name' => 'John Doe'
    ];
    
    // Test the automatic route: POST /webhooks/user.created
    $response = $this->postJson('/webhooks/user.created', $payload, [
        'X-Signature-256' => 'sha256=' . hash_hmac('sha256', json_encode($payload), 'test-secret')
    ]);
    
    $response->assertOk()
             ->assertJson(['success' => true]);
});

it('handles webhook routing automatically', function () {
    // Test that the automatic route works for different webhooks
    $this->postJson('/webhooks/github.push', ['repository' => 'test'])->assertOk();
    $this->postJson('/webhooks/stripe.payment', ['amount' => 1000])->assertOk();
    $this->postJson('/webhooks/user.created', ['user_id' => 123])->assertOk();
});
```

---

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/isaacjuwon/laravel-webhook.git
cd laravel-webhook

# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer format
```

---

## 🔒 Security

If you discover any security vulnerabilities, please send an email to [isaacjuwon@example.com](mailto:isaacjuwon@example.com) instead of using the issue tracker.

---

## 📝 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## 💖 Support

If this package helps you build amazing webhook integrations, consider supporting the development:

<div align="center">

[![GitHub Sponsors](https://img.shields.io/badge/GitHub%20Sponsors-Support%20Development-pink.svg?style=for-the-badge&logo=github-sponsors&logoColor=white)](https://github.com/sponsors/isaacjuwon)

<iframe src="https://github.com/sponsors/isaacjuwon/button" title="Sponsor isaacjuwon" height="32" width="114" style="border: 0; border-radius: 6px;"></iframe>

**Your support helps maintain and improve this package for the entire Laravel community! 🙏**

</div>

---

## 🏆 Credits

- [Isaac Juwon Gabriel](https://github.com/isaacjuwon) - Creator & Maintainer
- [All Contributors](../../contributors) - Thank you for your contributions!

---

<div align="center">

**Made with ❤️ for the Laravel community**

[⭐ Give us a star on GitHub](https://github.com/isaacjuwon/laravel-webhook) • [📦 View on Packagist](https://packagist.org/packages/isaacjuwon/laravel-webhook) • [📖 Read the Docs](#-documentation)

</div>