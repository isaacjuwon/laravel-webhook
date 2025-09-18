<?php

namespace Isaacjuwon\LaravelWebhook\Examples;

use Isaacjuwon\LaravelWebhook\Abstracts\Webhook;
use Isaacjuwon\LaravelWebhook\Models\Webhook as WebhookModel;
use Isaacjuwon\LaravelWebhook\Support\WebhookBuilder;

class StripePaymentWebhook extends Webhook
{
    protected string $name = 'stripe.payment_intent.succeeded';
    protected string $signatureHeader = 'Stripe-Signature';
    
    protected array $storeHeaders = [
        'Stripe-Signature',
        'User-Agent'
    ];
    
    protected array $properties = [
        'id' => 'string',
        'type' => 'string',
        'data' => 'object',
        'created' => 'integer'
    ];

    public function __construct(string $signingSecret = null)
    {
        $this->signingSecret = $signingSecret ?? env('STRIPE_WEBHOOK_SECRET', '');
    }

    protected function configure(WebhookBuilder $builder): void
    {
        // Stripe-specific configuration
        $builder->stripeWebhook(); // This will override the basic config with Stripe presets
    }

    public function handle(WebhookModel $webhook): array
    {
        $eventId = $webhook->get('id');
        $eventType = $webhook->get('type');
        $eventData = $webhook->get('data');
        
        $paymentIntent = $eventData['object'] ?? [];
        
        // Process Stripe payment intent
        // Example: Update payment status, send notifications, etc.
        
        return [
            'stripe_event_processed' => true,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payment_intent_id' => $paymentIntent['id'] ?? null,
            'amount' => $paymentIntent['amount'] ?? 0,
            'currency' => $paymentIntent['currency'] ?? 'usd'
        ];
    }
}