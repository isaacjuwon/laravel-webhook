<?php

namespace Isaacjuwon\LaravelWebhook\Examples;

use Isaacjuwon\LaravelWebhook\Abstracts\Webhook;
use Isaacjuwon\LaravelWebhook\Models\Webhook as WebhookModel;

class CallbackBasedWebhook extends Webhook
{
    protected string $name = 'user.created';
    protected string $signatureHeader = 'X-Signature-256';
    
    protected array $storeHeaders = [
        'X-Event-ID',
        'X-Timestamp'
    ];
    
    protected array $properties = [
        'user_id' => 'integer',
        'email' => 'string',
        'name' => 'string',
        'created_at' => 'string'
    ];

    public function __construct(string $signingSecret = null, callable $callback = null)
    {
        $this->signingSecret = $signingSecret ?? env('USER_WEBHOOK_SECRET', '');
        
        // If callback is provided, use it instead of the handle method
        if ($callback !== null) {
            $this->setCallback($callback);
        }
    }

    /**
     * Default handle method - only used if no callback is set
     */
    public function handle(WebhookModel $webhook): array
    {
        $userId = $webhook->get('user_id');
        $email = $webhook->get('email');
        $name = $webhook->get('name');
        
        // Access headers
        $eventId = $webhook->getHeader('X-Event-ID');
        
        // Default behavior
        return [
            'processed' => true,
            'user_id' => $userId,
            'event_id' => $eventId,
            'handler_type' => 'default_handle_method'
        ];
    }
}