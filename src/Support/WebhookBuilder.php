<?php

namespace Isaacjuwon\LaravelWebhook\Support;

use Isaacjuwon\LaravelWebhook\Models\Webhook;
use Isaacjuwon\LaravelWebhook\LaravelWebhook;

class WebhookBuilder
{
    protected string $name;
    protected string $signingSecret;
    protected LaravelWebhook $manager;
    protected array $properties = [];
    protected array $storeHeaders = [];
    protected mixed $handler = null;
    protected string $signatureHeaderName = 'X-Signature-256';

    public function __construct(string $name, string $signingSecret = '', LaravelWebhook $manager = null)
    {
        $this->name = $name;
        $this->signingSecret = $signingSecret;
        $this->manager = $manager ?? app(LaravelWebhook::class);
    }

    /**
     * Static factory method for creating webhook builders.
     */
    public static function create(string $name, string $signingSecret = '', LaravelWebhook $manager = null): static
    {
        return new static($name, $signingSecret, $manager);
    }

    /**
     * Set the signing secret for the webhook.
     */
    public function signingSecret(string $secret): static
    {
        $this->signingSecret = $secret;
        return $this;
    }

    /**
     * Alias for signingSecret() for better expressiveness.
     */
    public function secret(string $secret): static
    {
        return $this->signingSecret($secret);
    }

    /**
     * Set the signature header name.
     */
    public function signatureHeader(string $headerName): static
    {
        $this->signatureHeaderName = $headerName;
        return $this;
    }

    /**
     * Alias for signatureHeader() for better expressiveness.
     */
    public function signedBy(string $headerName): static
    {
        return $this->signatureHeader($headerName);
    }

    /**
     * Disable signature validation.
     */
    public function withoutSignature(): static
    {
        $this->signingSecret = '';
        return $this;
    }

    /**
     * Enable signature validation with secret.
     */
    public function withSignature(string $secret, string $headerName = 'X-Signature-256'): static
    {
        $this->signingSecret = $secret;
        $this->signatureHeaderName = $headerName;
        return $this;
    }

    /**
     * Add a property to the webhook.
     */
    public function property(string $name, string|array $type, array $options = []): static
    {
        $this->properties[$name] = [
            'type' => $type,
            'options' => $options
        ];
        return $this;
    }

    /**
     * Add multiple properties at once.
     */
    public function properties(array $properties): static
    {
        foreach ($properties as $name => $config) {
            if (is_string($config)) {
                $this->property($name, $config);
            } elseif (is_array($config)) {
                $this->property($name, $config['type'] ?? 'string', $config['options'] ?? []);
            }
        }
        return $this;
    }

    /**
     * Set headers to store.
     */
    public function storeHeaders(array $headers): static
    {
        $this->storeHeaders = $headers;
        return $this;
    }

    /**
     * Set the handler for webhook execution (function or dispatchable class).
     */
    public function handle(callable|string $handler): static
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Build and register the webhook.
     */
    public function register(): Webhook
    {
        $webhook = Webhook::create($this->name, $this->signingSecret)
            ->signatureHeader($this->signatureHeaderName)
            ->storeHeaders($this->storeHeaders);
        
        // Add properties
        foreach ($this->properties as $name => $config) {
            $webhook->addProperty($name, $config['type'], $config['options']);
        }
        
        // Set handler
        if ($this->handler) {
            $webhook->handler($this->handler);
        }
        
        // Register with manager
        $this->manager->registerWebhook($this->name, $webhook);
        
        return $webhook;
    }

    // Preset webhook configurations

    /**
     * Configure as a user webhook with common fields.
     */
    public function userWebhook(): static
    {
        return $this->properties([
            'user_id' => 'integer',
            'email' => 'string',
            'name' => 'string',
            'created_at' => 'string'
        ]);
    }

    /**
     * Configure as a payment webhook with common fields.
     */
    public function paymentWebhook(): static
    {
        return $this->properties([
            'payment_id' => 'string',
            'amount' => 'number',
            'currency' => 'string',
            'status' => ['type' => 'string', 'options' => ['enum' => ['completed', 'failed', 'pending']]],
            'customer_id' => 'string'
        ]);
    }

    /**
     * Configure as an order webhook with common fields.
     */
    public function orderWebhook(): static
    {
        return $this->properties([
            'order_id' => 'string',
            'customer_id' => 'string',
            'total' => 'number',
            'status' => ['type' => 'string', 'options' => ['enum' => ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled']]],
            'items' => 'array'
        ]);
    }

    /**
     * Configure as a GitHub webhook.
     */
    public function githubWebhook(): static
    {
        return $this->properties([
            'action' => 'string',
            'repository' => 'object',
            'sender' => 'object',
            'ref' => 'string',
            'commits' => 'array'
        ])->storeHeaders(['X-GitHub-Event', 'X-GitHub-Delivery', 'X-Hub-Signature-256'])
          ->signatureHeader('X-Hub-Signature-256');
    }

    /**
     * Configure as a Stripe webhook.
     */
    public function stripeWebhook(): static
    {
        return $this->properties([
            'id' => 'string',
            'type' => 'string',
            'data' => 'object',
            'created' => 'integer'
        ])->storeHeaders(['Stripe-Signature', 'User-Agent'])
          ->signatureHeader('Stripe-Signature');
    }

    /**
     * Configure as a Discord webhook.
     */
    public function discordWebhook(): static
    {
        return $this->properties([
            'content' => 'string',
            'username' => 'string',
            'avatar_url' => 'string',
            'embeds' => 'array'
        ])->withoutSignature(); // Discord webhooks typically don't use signatures
    }

    /**
     * Configure as a Shopify webhook.
     */
    public function shopifyWebhook(): static
    {
        return $this->properties([
            'id' => 'integer',
            'name' => 'string',
            'email' => 'string',
            'created_at' => 'string',
            'updated_at' => 'string'
        ])->storeHeaders(['X-Shopify-Topic', 'X-Shopify-Shop-Domain', 'X-Shopify-Hmac-Sha256'])
          ->signatureHeader('X-Shopify-Hmac-Sha256');
    }

    /**
     * Configure as a Mailgun webhook.
     */
    public function mailgunWebhook(): static
    {
        return $this->properties([
            'event' => 'string',
            'timestamp' => 'integer',
            'token' => 'string',
            'signature' => 'string',
            'recipient' => 'string'
        ])->storeHeaders(['User-Agent'])
          ->signatureHeader('X-Mailgun-Signature');
    }
}