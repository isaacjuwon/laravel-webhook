<?php

namespace Isaacjuwon\LaravelWebhook\Contracts;

use Illuminate\Support\Collection;

interface WebhookContract
{
    /**
     * Get the webhook name
     */
    public function getName(): string;

    /**
     * Get the signing secret
     */
    public function getSigningSecret(): string;

    /**
     * Set the signing secret
     */
    public function setSigningSecret(string $secret): static;

    /**
     * Add a property definition
     */
    public function addProperty(string $name, string|array $type, array $options = []): static;

    /**
     * Set headers to store
     */
    public function storeHeaders(array $headers): static;

    /**
     * Set the webhook handler
     */
    public function handler(callable|string $handler): static;

    /**
     * Get the webhook handler
     */
    public function getHandler(): mixed;

    /**
     * Set payload data
     */
    public function setPayload(array $payload): static;

    /**
     * Get payload data
     */
    public function getPayload(): Collection;

    /**
     * Get specific payload value
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if payload has a key
     */
    public function has(string $key): bool;

    /**
     * Set headers
     */
    public function setHeaders(array $headers): static;

    /**
     * Get stored headers
     */
    public function getHeaders(): Collection;

    /**
     * Validate the webhook payload
     */
    public function validate(): bool;

    /**
     * Execute the webhook handler
     */
    public function execute(): mixed;

    /**
     * Convert to array representation
     */
    public function toArray(): array;
}