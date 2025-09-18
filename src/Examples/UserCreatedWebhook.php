<?php

namespace Isaacjuwon\LaravelWebhook\Examples;

use Isaacjuwon\LaravelWebhook\Abstracts\Webhook;
use Isaacjuwon\LaravelWebhook\Models\Webhook as WebhookModel;
use Isaacjuwon\LaravelWebhook\Support\WebhookBuilder;

class UserCreatedWebhook extends Webhook
{
    protected string $name = 'user.created';
    protected string $signingSecret = ''; // Will be set from environment or config
    protected string $signatureHeader = 'X-Signature-256';
    
    protected array $storeHeaders = [
        'X-Event-ID',
        'X-Timestamp',
        'User-Agent'
    ];
    
    protected array $properties = [
        'user_id' => 'integer',
        'email' => 'string',
        'name' => 'string',
        'created_at' => 'string'
    ];

    public function __construct(string $signingSecret = null)
    {
        if ($signingSecret) {
            $this->signingSecret = $signingSecret;
        } else {
            $this->signingSecret = env('USER_WEBHOOK_SECRET', '');
        }
    }

    protected function configure(WebhookBuilder $builder): void
    {
        // Additional configuration can be done here
        // This method is called after the basic configuration
    }

    public function handle(WebhookModel $webhook): array
    {
        $userId = $webhook->get('user_id');
        $email = $webhook->get('email');
        $name = $webhook->get('name');
        
        // Access headers
        $eventId = $webhook->getHeader('X-Event-ID');
        
        // Your business logic here
        // Example: Send welcome email, update user status, etc.
        
        return [
            'processed' => true,
            'user_id' => $userId,
            'event_id' => $eventId,
            'timestamp' => now()->toISOString()
        ];
    }
}