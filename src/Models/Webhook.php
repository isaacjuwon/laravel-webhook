<?php

namespace Isaacjuwon\LaravelWebhook\Models;

use Illuminate\Support\Collection;
use Isaacjuwon\LaravelWebhook\Contracts\WebhookContract;
use Isaacjuwon\LaravelWebhook\Exceptions\WebhookValidationException;

class Webhook implements WebhookContract
{
    protected string $name;
    protected string $signingSecret;
    protected string $signatureHeaderName = 'X-Signature-256';
    protected Collection $properties;
    protected Collection $storeHeaders;
    protected Collection $payload;
    protected Collection $headers;
    protected mixed $handler = null;
    protected array $metadata = [];

    public function __construct(string $name, string $signingSecret = '', array $storeHeaders = [])
    {
        $this->name = $name;
        $this->signingSecret = $signingSecret;
        $this->properties = new Collection();
        $this->storeHeaders = new Collection($storeHeaders);
        $this->payload = new Collection();
        $this->headers = new Collection();
    }

    /**
     * Static factory method for creating webhooks
     */
    public static function create(string $name, string $signingSecret = ''): static
    {
        return new static($name, $signingSecret);
    }

    /**
     * Get the webhook name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the signing secret
     */
    public function getSigningSecret(): string
    {
        return $this->signingSecret;
    }

    /**
     * Set the signing secret (fluent API)
     */
    public function signingSecret(string $secret): static
    {
        $this->signingSecret = $secret;
        return $this;
    }

    /**
     * Set the signing secret
     */
    public function setSigningSecret(string $secret): static
    {
        $this->signingSecret = $secret;
        return $this;
    }

    /**
     * Get the signature header name
     */
    public function getSignatureHeaderName(): string
    {
        return $this->signatureHeaderName;
    }

    /**
     * Set the signature header name (fluent API)
     */
    public function signatureHeader(string $headerName): static
    {
        $this->signatureHeaderName = $headerName;
        return $this;
    }

    /**
     * Set the signature header name
     */
    public function setSignatureHeaderName(string $headerName): static
    {
        $this->signatureHeaderName = $headerName;
        return $this;
    }

    /**
     * Check if signature validation is enabled
     */
    public function hasSignatureValidation(): bool
    {
        return !empty($this->signingSecret);
    }

    /**
     * Disable signature validation
     */
    public function withoutSignatureValidation(): static
    {
        $this->signingSecret = '';
        return $this;
    }

    /**
     * Add a property definition
     */
    public function addProperty(string $name, string|array $type, array $options = []): static
    {
        $property = [
            'type' => $type,
            'options' => $options
        ];

        $this->properties->put($name, $property);
        return $this;
    }

    /**
     * Set headers to store
     */
    public function storeHeaders(array $headers): static
    {
        $this->storeHeaders = new Collection($headers);
        return $this;
    }

    /**
     * Set the webhook handler (function or dispatchable class)
     */
    public function handler(callable|string $handler): static
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Get the webhook handler
     */
    public function getHandler(): mixed
    {
        return $this->handler;
    }

    /**
     * Set payload data
     */
    public function setPayload(array $payload): static
    {
        $this->payload = new Collection($payload);
        return $this;
    }

    /**
     * Get payload data
     */
    public function getPayload(): Collection
    {
        return $this->payload;
    }

    /**
     * Get specific payload value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->payload->get($key, $default);
    }

    /**
     * Check if payload has a key
     */
    public function has(string $key): bool
    {
        return $this->payload->has($key);
    }

    /**
     * Set headers
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = new Collection($headers);
        return $this;
    }

    /**
     * Get stored headers
     */
    public function getHeaders(): Collection
    {
        return $this->headers;
    }

    /**
     * Get specific header value
     */
    public function getHeader(string $name, mixed $default = null): mixed
    {
        return $this->headers->get($name, $default);
    }

    /**
     * Get properties
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    /**
     * Get headers to store
     */
    public function getStoreHeaders(): Collection
    {
        return $this->storeHeaders;
    }

    /**
     * Validate the webhook payload
     */
    public function validate(): bool
    {
        // Basic validation - can be extended by subclasses
        return true;
    }

    /**
     * Execute the webhook handler
     */
    public function execute(): mixed
    {
        if (!$this->handler) {
            throw new \BadMethodCallException("No handler defined for webhook '{$this->name}'");
        }

        $this->validate();

        // If handler is a dispatchable class
        if (is_string($this->handler) && class_exists($this->handler)) {
            $handlerInstance = new $this->handler();
            
            if (method_exists($handlerInstance, 'handle')) {
                return $handlerInstance->handle($this);
            }
            
            if (is_callable($handlerInstance)) {
                return $handlerInstance($this);
            }
            
            throw new \InvalidArgumentException("Handler class must have a handle method or be callable");
        }

        // If handler is a callable
        if (is_callable($this->handler)) {
            return call_user_func($this->handler, $this);
        }

        throw new \InvalidArgumentException("Handler must be callable or a dispatchable class");
    }

    /**
     * Set metadata
     */
    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Get metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'properties' => $this->properties->toArray(),
            'store_headers' => $this->storeHeaders->toArray(),
            'payload' => $this->payload->toArray(),
            'headers' => $this->headers->toArray(),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Magic getter for payload data
     */
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    /**
     * Magic isset for payload data
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }
}