<?php

namespace Isaacjuwon\LaravelWebhook;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Isaacjuwon\LaravelWebhook\Models\Webhook;
use Isaacjuwon\LaravelWebhook\Exceptions\WebhookNotFoundException;
use Isaacjuwon\LaravelWebhook\Exceptions\WebhookValidationException;
use Isaacjuwon\LaravelWebhook\Support\WebhookBuilder;

class LaravelWebhook
{
    protected Collection $registeredWebhooks;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->registeredWebhooks = new Collection();
        $this->config = array_merge([
            'verify_signatures' => true,
            'default_signature_header' => 'X-Signature-256',
            'hash_algorithm' => 'sha256',
        ], $config);
    }

    /**
     * Register a webhook with the service.
     */
    public function registerWebhook(string $name, Webhook $webhook): static
    {
        $this->registeredWebhooks->put($name, $webhook);
        return $this;
    }

    /**
     * Get a registered webhook by name.
     */
    public function getWebhook(string $name): Webhook
    {
        if (!$this->registeredWebhooks->has($name)) {
            throw new WebhookNotFoundException("Webhook '{$name}' not found.");
        }

        return $this->registeredWebhooks->get($name);
    }

    /**
     * Get all registered webhooks.
     */
    public function getRegisteredWebhooks(): Collection
    {
        return $this->registeredWebhooks;
    }

    /**
     * Check if a webhook is registered.
     */
    public function hasWebhook(string $name): bool
    {
        return $this->registeredWebhooks->has($name);
    }

    /**
     * Create a new webhook using fluent API.
     */
    public function create(string $name, string $signingSecret = ''): WebhookBuilder
    {
        return new WebhookBuilder($name, $signingSecret, $this);
    }

    /**
     * Process a webhook request.
     */
    public function processWebhook(string $webhookName, Request $request): JsonResponse
    {
        try {
            // Get the webhook
            $webhook = $this->getWebhook($webhookName);

            // Extract payload data
            $payload = $this->extractPayload($request);
            $webhook->setPayload($payload);

            // Store configured headers
            $this->storeHeaders($webhook, $request);

            // Validate webhook signature if configured
            if ($this->shouldValidateSignature($webhook, $request)) {
                $this->validateSignature($webhook, $request);
            }

            // Execute the webhook
            $result = $webhook->execute();

            return response()->json([
                'success' => true,
                'webhook' => $webhookName,
                'result' => $result,
                'processed_at' => now()->toISOString(),
            ]);

        } catch (WebhookNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'webhook_not_found',
                'message' => $e->getMessage(),
            ], 404);

        } catch (WebhookValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'validation_failed',
                'message' => $e->getMessage(),
            ], 400);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_payload',
                'message' => $e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'internal_error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract payload from request.
     */
    protected function extractPayload(Request $request): array
    {
        if ($request->isJson()) {
            return $request->json()->all();
        }

        return $request->all();
    }

    /**
     * Store configured headers in the webhook.
     */
    protected function storeHeaders(Webhook $webhook, Request $request): void
    {
        $headersToStore = $webhook->getStoreHeaders();
        $storedHeaders = [];

        foreach ($headersToStore as $header) {
            if ($request->hasHeader($header)) {
                $storedHeaders[$header] = $request->header($header);
            }
        }

        $webhook->setHeaders($storedHeaders);
    }

    /**
     * Check if signature validation should be performed.
     */
    protected function shouldValidateSignature(Webhook $webhook, Request $request): bool
    {
        if (!$this->config['verify_signatures']) {
            return false;
        }

        $signingSecret = $webhook->getSigningSecret();
        $signatureHeader = $webhook->getSignatureHeaderName();
        
        return !empty($signingSecret) && $request->hasHeader($signatureHeader);
    }

    /**
     * Validate webhook signature.
     */
    protected function validateSignature(Webhook $webhook, Request $request): void
    {
        $signature = $request->header($webhook->getSignatureHeaderName());
        $payload = $request->getContent();
        $secret = $webhook->getSigningSecret();
        $algorithm = $this->config['hash_algorithm'];

        $expectedSignature = $algorithm . '=' . hash_hmac($algorithm, $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new WebhookValidationException('Invalid webhook signature');
        }
    }

    /**
     * Get configuration value.
     */
    public function getConfig(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->config[$key] ?? $default;
    }

    /**
     * Set configuration value.
     */
    public function setConfig(string $key, mixed $value): static
    {
        $this->config[$key] = $value;
        return $this;
    }
}