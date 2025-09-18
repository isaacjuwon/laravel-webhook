<?php

namespace Isaacjuwon\LaravelWebhook\Contracts;

interface AbstractWebhook
{
    public function getName(): string;

    public function getSigningSecret(): string;

    public function getSignatureHeader(): string;

    public function getStoreHeaders(): array;

    public function getProperties(): array;

    public function setCallback(callable $callback): static;

    public function getCallback(): ?callable;

    public function handle(\Isaacjuwon\LaravelWebhook\Models\Webhook $webhook): mixed;

    public function toBuilder(): \Isaacjuwon\LaravelWebhook\Support\WebhookBuilder;
}