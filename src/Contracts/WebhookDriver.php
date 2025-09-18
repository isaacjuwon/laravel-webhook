<?php

namespace Isaacjuwon\LaravelWebhook\Contracts;

use Isaacjuwon\LaravelWebhook\Dto\WebhookDTO;
use Isaacjuwon\LaravelWebhook\Abstracts\Webhook as AbstractWebhook;

interface WebhookDriver
{
    /**
     * Register a webhook for this driver.
     *
     * @param Webhook $webhook The webhook to register
     * @return self
     */
    public function registerWebhook(AbstractWebhook $webhook): self;

    /**
     * Get all registered webhooks.
     *
     * @return array Array of registered webhooks keyed by their names.
     */
    public function getRegisteredWebhooks(): array;

    /**
     * Get a registered webhook by name.
     *
     * @param string $name The name of the webhook
     * @return Webhook The requested webhook
     */
    public function getWebhook(string $name): AbstractWebhook;



    /**
     * Set configuration parameters for the webhook driver.
     *
     * @param array $config Configuration options.
     * @return self
     */
    public function setConfig(array $config): self;

    /**
     * Get the current configuration parameters.
     *
     * @return array The current configuration options.
     */
    public function getConfig(): array;

    /**
     * Retrieve the last response from the webhook driver.
     *
     * @return array|null The last response or null if no response exists.
     */
    public function getLastResponse(): ?array;



    /**
     * Get the provider data merged with the model defined settings.
     * Some Model settings override provider settings.
     *
     * @return array The settings.
     */
    public function getSettings(): array;

    /**
     * Format the webhook for payload delivery.
     *
     * @param Webhook $webhook The webhook to format
     * @return WebhookDTO The formatted webhook data transfer object
     */
    public function formatWebhookForPayload(AbstractWebhook $webhook): WebhookDTO;

   
   }