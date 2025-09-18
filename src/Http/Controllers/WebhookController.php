<?php

namespace Isaacjuwon\LaravelWebhook\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Isaacjuwon\LaravelWebhook\LaravelWebhook;

class WebhookController extends Controller
{
    protected LaravelWebhook $webhookManager;

    public function __construct(LaravelWebhook $webhookManager)
    {
        $this->webhookManager = $webhookManager;
    }

    /**
     * Handle incoming webhook requests.
     *
     * @param Request $request
     * @param string $webhookName
     * @return JsonResponse
     */
    public function handle(Request $request, string $webhookName): JsonResponse
    {
        return $this->webhookManager->processWebhook($webhookName, $request);
    }
}