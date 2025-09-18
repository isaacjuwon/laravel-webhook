<?php

namespace Isaacjuwon\LaravelWebhook\Examples;

use Isaacjuwon\LaravelWebhook\Abstracts\Webhook;
use Isaacjuwon\LaravelWebhook\Models\Webhook as WebhookModel;

class GitHubPushWebhook extends Webhook
{
    protected string $name = 'github.push';
    protected string $signatureHeader = 'X-Hub-Signature-256';
    
    protected array $storeHeaders = [
        'X-GitHub-Event',
        'X-GitHub-Delivery',
        'X-Hub-Signature-256'
    ];
    
    protected array $properties = [
        'ref' => 'string',
        'repository' => 'object',
        'pusher' => 'object',
        'commits' => 'array',
        'head_commit' => 'object'
    ];

    public function __construct(string $signingSecret = null)
    {
        $this->signingSecret = $signingSecret ?? env('GITHUB_WEBHOOK_SECRET', '');
    }

    public function handle(WebhookModel $webhook): array
    {
        $ref = $webhook->get('ref');
        $repository = $webhook->get('repository');
        $commits = $webhook->get('commits', []);
        $headCommit = $webhook->get('head_commit');
        
        // Access GitHub-specific headers
        $githubEvent = $webhook->getHeader('X-GitHub-Event');
        $deliveryId = $webhook->getHeader('X-GitHub-Delivery');
        
        // Process GitHub push webhook
        // Example: Trigger CI/CD, update deployment status, etc.
        
        return [
            'github_push_processed' => true,
            'repository' => $repository['full_name'] ?? 'unknown',
            'branch' => str_replace('refs/heads/', '', $ref),
            'commits_count' => count($commits),
            'head_commit_id' => $headCommit['id'] ?? null,
            'delivery_id' => $deliveryId
        ];
    }
}