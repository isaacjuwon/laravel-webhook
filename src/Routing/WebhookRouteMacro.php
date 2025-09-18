<?php

namespace Isaacjuwon\LaravelWebhook\Routing;

use Illuminate\Support\Facades\Route;
use Isaacjuwon\LaravelWebhook\Http\Controllers\WebhookController;

class WebhookRouteMacro
{
    /**
     * Register the webhook route macros.
     */
    public static function register(): void
    {
        // Single route for specific webhook
        Route::macro('webhook', function (string $path, string $webhookName, array $options = []) {
            $middleware = $options['middleware'] ?? [];
            $name = $options['name'] ?? "webhook.{$webhookName}";
            
            return Route::post($path, [WebhookController::class, 'handle'])
                        ->defaults('webhookName', $webhookName)
                        ->middleware($middleware)
                        ->name($name);
        });

        // Auto-routing macro for all webhooks through a single route
        Route::macro('webhooks', function (string $basePath = '/webhooks', array $options = []) {
            $middleware = $options['middleware'] ?? [];
            $name = $options['name'] ?? 'webhooks';
            
            return Route::post($basePath . '/{webhookName}', [WebhookController::class, 'handle'])
                        ->middleware($middleware)
                        ->where('webhookName', '[a-zA-Z0-9._-]+')
                        ->name($name);
        });
    }
}