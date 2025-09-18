<?php

namespace Isaacjuwon\LaravelWebhook\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Isaacjuwon\LaravelWebhook\Models\Webhook;

abstract class WebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Webhook $webhook;

    /**
     * Create a new job instance.
     */
    public function __construct(Webhook $webhook)
    {
        $this->webhook = $webhook;
    }

    /**
     * Execute the job.
     */
    public function handle(): mixed
    {
        return $this->process($this->webhook);
    }

    /**
     * Process the webhook.
     * 
     * @param Webhook $webhook The webhook model with payload data
     * @return mixed The result of the webhook processing
     */
    abstract protected function process(Webhook $webhook): mixed;

    /**
     * Get webhook payload data as an object.
     */
    protected function payload(): object
    {
        return (object) $this->webhook->getPayload()->toArray();
    }

    /**
     * Get specific payload field.
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->webhook->get($key, $default);
    }

    /**
     * Get webhook headers.
     */
    protected function headers(): object
    {
        return (object) $this->webhook->getHeaders()->toArray();
    }

    /**
     * Get specific header value.
     */
    protected function header(string $name, mixed $default = null): mixed
    {
        return $this->webhook->getHeader($name, $default);
    }
}