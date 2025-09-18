<?php

namespace Isaacjuwon\LaravelWebhook\Abstracts;

use Isaacjuwon\LaravelWebhook\Support\WebhookBuilder;
use Isaacjuwon\LaravelWebhook\Models\Webhook as WebhookModel;

abstract class Webhook
{
    /**
     * The webhook name/identifier.
     */
    protected string $name;

    /**
     * The webhook signing secret.
     */
    protected string $signingSecret = '';

    /**
     * The signature header name.
     */
    protected string $signatureHeader = 'X-Signature-256';

    /**
     * Headers to store when processing the webhook.
     */
    protected array $storeHeaders = [];

    /**
     * Webhook properties configuration.
     */
    protected array $properties = [];

    /**
     * Optional callback handler (alternative to implementing handle method).
     */
    protected ?callable $callback = null;

    /**
     * Get the webhook configuration as a WebhookBuilder.
     */
    public function toBuilder(): WebhookBuilder
    {
        $builder = WebhookBuilder::create($this->name, $this->signingSecret)
            ->signatureHeader($this->signatureHeader)
            ->storeHeaders($this->storeHeaders)
            ->properties($this->properties);

        // Use callback if set, otherwise use the handle method
        if ($this->callback !== null) {
            $builder->handle($this->callback);
        } else {
            $builder->handle([$this, 'handle']);
        }

        // Allow subclasses to customize the builder
        $this->configure($builder);

        return $builder;
    }

    /**
     * Configure the webhook builder (override in subclass if needed).
     */
    protected function configure(WebhookBuilder $builder): void
    {
        // Override in subclass to customize builder
    }

    /**
     * Set a callback handler instead of implementing handle() method.
     */
    public function setCallback(callable $callback): static
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * Get the callback if set.
     */
    public function getCallback(): ?callable
    {
        return $this->callback ?? null;
    }

    /**
     * Handle the webhook execution.
     * 
     * @param WebhookModel $webhook The webhook model with payload data
     * @return mixed The result of the webhook processing
     */
    abstract public function handle(WebhookModel $webhook): mixed;

    /**
     * Get the webhook name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the signing secret.
     */
    public function getSigningSecret(): string
    {
        return $this->signingSecret;
    }

    /**
     * Get the signature header.
     */
    public function getSignatureHeader(): string
    {
        return $this->signatureHeader;
    }

    /**
     * Get headers to store.
     */
    public function getStoreHeaders(): array
    {
        return $this->storeHeaders;
    }

    /**
     * Get webhook properties.
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}