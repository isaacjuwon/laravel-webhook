<?php

namespace Isaacjuwon\LaravelWebhook\Traits;

use Isaacjuwon\LaravelWebhook\LaravelWebhook;
use Isaacjuwon\LaravelWebhook\Support\WebhookBuilder;

trait RegistersWebhooks
{
    /**
     * Register webhooks in your application service provider.
     * 
     * Example usage in AppServiceProvider:
     * 
     * public function boot()
     * {
     *     $this->registerWebhooks();
     * }
     */
    protected function registerWebhooks(): void
    {
        $webhookManager = app(LaravelWebhook::class);
        
        // Get webhook builders from the method
        $webhookBuilders = $this->webhooks();
        
        // Register each webhook builder
        foreach ($webhookBuilders as $webhookBuilder) {
            // Support WebhookBuilder instances
            if ($webhookBuilder instanceof WebhookBuilder) {
                $webhookBuilder->register();
            }
            // Support webhook class names (strings)
            elseif (is_string($webhookBuilder) && class_exists($webhookBuilder)) {
                $webhookInstance = new $webhookBuilder();
                if (method_exists($webhookInstance, 'toBuilder')) {
                    $webhookInstance->toBuilder()->register();
                }
            }
            // Support webhook class instances
            elseif (is_object($webhookBuilder) && method_exists($webhookBuilder, 'toBuilder')) {
                $webhookBuilder->toBuilder()->register();
            }
        }
        
        // Also support the legacy configureWebhooks method
        if (method_exists($this, 'configureWebhooks')) {
            $this->configureWebhooks($webhookManager);
        }
    }

    /**
     * Define your webhooks by returning an array of webhook configurations.
     * This is the PRIMARY and RECOMMENDED way to define webhooks.
     * 
     * You can use multiple approaches:
     * 
     * 1. Class-based approach (RECOMMENDED):
     * 
     * protected function webhooks(): array
     * {
     *     return [
     *         GitHubPushWebhook::class,
     *         StripePaymentWebhook::class,
     *         UserCreatedWebhook::class,
     *     ];
     * }
     * 
     * 2. Instance-based approach:
     * 
     * protected function webhooks(): array
     * {
     *     return [
     *         new GitHubPushWebhook(),
     *         new StripePaymentWebhook(env('STRIPE_SECRET')),
     *         new UserCreatedWebhook(),
     *     ];
     * }
     * 
     * 3. Builder-based approach:
     * 
     * protected function webhooks(): array
     * {
     *     return [
     *         WebhookBuilder::create('user.created', env('USER_WEBHOOK_SECRET'))
     *             ->userWebhook()
     *             ->handle(ProcessUserCreated::class),
     * 
     *         WebhookBuilder::create('payment.completed', env('PAYMENT_SECRET'))
     *             ->paymentWebhook()
     *             ->handle(ProcessPaymentCompleted::class),
     * 
     *         // Using custom webhook classes
     *         (new UserCreatedWebhook())->toBuilder(),
     *         (new PaymentProcessedWebhook())->toBuilder(),
     *     ];
     * }
     * 
     * @return array Array of webhook definitions (class names, instances, or WebhookBuilder instances)
     */
    protected function webhooks(): array
    {
        return [];
    }

    /**
     * Legacy method - override this method for programmatic webhook configuration.
     * 
     * Example:
     * 
     * protected function configureWebhooks(LaravelWebhook $webhook): void
     * {
     *     // Using closures
     *     $webhook->create('user.created', 'secret')
     *         ->userWebhook()
     *         ->handle(function (Webhook $webhook) {
     *             $userId = $webhook->get('user_id');
     *             $email = $webhook->get('email');
     *             // Your webhook logic here
     *             return ['processed' => true, 'user_id' => $userId];
     *         })
     *         ->register();
     * 
     *     // Using dispatchable classes
     *     $webhook->create('payment.completed', 'payment-secret')
     *         ->paymentWebhook()
     *         ->handle(ProcessPaymentWebhook::class)
     *         ->register();
     * 
     *     // Using handler classes
     *     $webhook->create('order.created', 'order-secret')
     *         ->orderWebhook()
     *         ->handle(OrderWebhookHandler::class)
     *         ->register();
     * }
     * 
     * @param LaravelWebhook $webhook
     */
    protected function configureWebhooks(LaravelWebhook $webhook): void
    {
        // Override this method to define your webhooks (legacy approach)
    }
}