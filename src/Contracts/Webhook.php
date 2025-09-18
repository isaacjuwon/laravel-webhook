<?php

namespace Isaacjuwon\LaravelWebhook\Contracts;

interface Webhook
{
    public function getName(): string;

    public function getSigningSecret(): string;

    public function addProperty(string $name, string|array $type, string $signing_secret = '', array $enum = []): self;

    public function setRequired(string $name): self;

    public function getProperties(): array;

    public function getRequired(): array;

    public function set(array $store_headers): self;

    public function getStoreHeaders(): array;

    public function toArray(): array;

    public function execute(array $input): mixed;
}
