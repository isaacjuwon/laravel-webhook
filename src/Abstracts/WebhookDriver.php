<?php

namespace Isaacjuwon\LaravelWebhook\Abstracts;

use Isaacjuwon\LaravelWebhook\Contracts\Webhook as WebhookInterface;

use Isaacjuwon\LaravelWebhook\Dto\WebhookDTO;


abstract class WebhookDriver implements WebhookInterface
{
    protected array $config = [];

    protected ?array $responseSchema = null;

    protected mixed $lastResponse = null;

    protected array $webhooks = [];

    protected array $settings;

    public function registerWebhook(Webhook $webhook): self
    {
        $name = $webhook->getName();
        $this->webhooks[$name] = $webhook;

        return $this;
    }

    public function getRegisteredWebhooks(): array
    {
        return $this->webhooks;
    }

    public function getWebhook(string $name): Webhook
    {
        return $this->webhooks[$name];
    }

    public function setResponseSchema(array $schema): self
    {
        $this->responseSchema = $schema;

        return $this;
    }

    public function getResponseSchema(): ?array
    {
        return $this->responseSchema;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }

    protected function getRegisteredFunctions(): array
    {
        return array_map(fn (Webhook $webhook) => $this->formatWebhookForPayload($webhook), $this->webhooks);
    }

    public function structuredOutputEnabled(): bool
    {
        return ! empty($this->getResponseSchema());
    }

    /**
     * Get the provider data merged with the model defined settings.
     * Some Model settings override provider settings.
     *
     * @return array The settings.
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

     /**
     * Format the webhook for payload delivery.
     *
     * @param Webhook $webhook The webhook to format
     * @return WebhookDTO The formatted webhook data transfer object
     */
    public function formatWebhookForPayload(Webhook $webhook): WebhookDTO
    {
        // Extract basic webhook information
        $name = $webhook->getName();
        $properties = $webhook->getProperties();
        $required = $webhook->getRequired();
        $storeHeaders = $webhook->getStoreHeaders();
        
        // Create and return a new WebhookDTO
        return new WebhookDTO($name, $properties, $required, $storeHeaders);
    }



}