<?php

namespace Isaacjuwon\LaravelWebhook;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Isaacjuwon\LaravelWebhook\Commands\LaravelWebhookCommand;
use Isaacjuwon\LaravelWebhook\Routing\WebhookRouteMacro;

class LaravelWebhookServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-webhook')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_webhook_table')
            ->hasCommand(LaravelWebhookCommand::class);
    }

    public function bootingPackage(): void
    {
        // Register route macro
        WebhookRouteMacro::register();
    }

    public function registeringPackage(): void
    {
        // Register the main webhook manager as singleton
        $this->app->singleton(LaravelWebhook::class, function ($app) {
            return new LaravelWebhook($app['config']['webhook'] ?? []);
        });

        // Register alias
        $this->app->alias(LaravelWebhook::class, 'laravel-webhook');
    }

    /**
     * Register webhooks method for application service providers.
     */
    public function provides(): array
    {
        return [
            LaravelWebhook::class,
            'laravel-webhook',
        ];
    }
}
