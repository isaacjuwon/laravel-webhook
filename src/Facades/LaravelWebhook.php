<?php

namespace Isaacjuwon\LaravelWebhook\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Isaacjuwon\LaravelWebhook\LaravelWebhook
 */
class LaravelWebhook extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Isaacjuwon\LaravelWebhook\LaravelWebhook::class;
    }
}
