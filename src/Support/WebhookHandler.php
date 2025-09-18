<?php

namespace Isaacjuwon\LaravelWebhook\Support;

use Isaacjuwon\LaravelWebhook\Models\Webhook;

abstract class WebhookHandler
{
    /**
     * Handle the webhook execution.
     * 
     * @param Webhook $webhook The webhook model with payload data
     * @return mixed The result of the webhook processing
     */
    abstract public function handle(Webhook $webhook): mixed;

    /**
     * Determine if the webhook should be processed.
     * Override this method to add conditional processing logic.
     */
    public function shouldProcess(Webhook $webhook): bool
    {
        return true;
    }

    /**
     * Handle webhook processing failure.
     * Override this method to customize error handling.
     */
    public function handleFailure(Webhook $webhook, \Throwable $exception): void
    {
        // Default: re-throw the exception
        throw $exception;
    }

    /**
     * Get webhook payload data as an object.
     */
    protected function payload(Webhook $webhook): object
    {
        return (object) $webhook->getPayload()->toArray();
    }

    /**
     * Get specific payload field.
     */
    protected function get(Webhook $webhook, string $key, mixed $default = null): mixed
    {
        return $webhook->get($key, $default);
    }

    /**
     * Get webhook headers.
     */
    protected function headers(Webhook $webhook): object
    {
        return (object) $webhook->getHeaders()->toArray();
    }

    /**
     * Get specific header value.
     */
    protected function header(Webhook $webhook, string $name, mixed $default = null): mixed
    {
        return $webhook->getHeader($name, $default);
    }
}