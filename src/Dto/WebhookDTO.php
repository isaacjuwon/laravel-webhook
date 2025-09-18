<?php

namespace Isaacjuwon\LaravelWebhook\Dto;

/**
 * Data Transfer Object for webhook data.
 */
class WebhookDTO
{
    public function __construct(
        private string $name,
        private array $properties,
        private array $required,
        private array $storeHeaders
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getRequired(): array
    {
        return $this->required;
    }

    public function getStoreHeaders(): array
    {
        return $this->storeHeaders;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'properties' => $this->properties,
            'required' => $this->required,
            'store_headers' => $this->storeHeaders,
        ];
    }
}