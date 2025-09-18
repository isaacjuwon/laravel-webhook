<?php

namespace Isaacjuwon\LaravelWebhook;

use Isaacjuwon\LaravelWebhook\Abstracts\Webhook as AbstractWebhook;
use Isaacjuwon\LaravelWebhook\Contracts\Webhook as WebhookInterface;

class Webhook extends AbstractWebhook implements WebhookInterface
{
    protected mixed $callback = null;

    protected array $enumTypes = [];

    public function __construct(?string $name = null, ?string $signing_secret = null)
    {
        $this->name = $name ?? $this->name;
        $this->signing_secret = $signing_secret ?? $this->signing_secret;
        parent::__construct($this->name, $this->signing_secret);
    }

    public function setCallback(?callable $callback): Webhook
    {
        $this->callback = $callback;

        return $this;
    }

    public function getCallback(): ?callable
    {
        return $this->callback;
    }

    public function execute(array $input): mixed
    {
        if ($this->callback === null) {
            throw new \BadMethodCallException('No callback defined for execution.');
        }

        // Validate required parameters
        foreach ($this->required as $param) {
            if (! array_key_exists($param, $input)) {
                throw new \InvalidArgumentException("Missing required parameter: {$param}");
            }
        }

        // Convert enum string values to actual enum instances
        $convertedInput = $this->convertEnumValues($input);

        // Execute the callback with input
        return call_user_func($this->callback, ...$convertedInput);
    }

    public static function create(string $name, string $signing_secret): Webhook
    {
        return new self($name, $signing_secret);
    }

 
}