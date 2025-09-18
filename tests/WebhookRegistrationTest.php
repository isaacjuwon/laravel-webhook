<?php

use Isaacjuwon\LaravelWebhook\LaravelWebhook;
use Isaacjuwon\LaravelWebhook\Traits\RegistersWebhooks;
use Isaacjuwon\LaravelWebhook\Support\WebhookBuilder;
use Isaacjuwon\LaravelWebhook\Examples\GitHubPushWebhook;
use Isaacjuwon\LaravelWebhook\Examples\StripePaymentWebhook;
use Isaacjuwon\LaravelWebhook\Examples\UserCreatedWebhook;

class MockServiceProvider
{
    use RegistersWebhooks;
}

class MockServiceProviderClassBased extends MockServiceProvider
{
    protected function webhooks(): array
    {
        return [
            GitHubPushWebhook::class,
            StripePaymentWebhook::class,
            UserCreatedWebhook::class,
        ];
    }
}

class MockServiceProviderInstanceBased extends MockServiceProvider
{
    protected function webhooks(): array
    {
        return [
            new GitHubPushWebhook(),
            new StripePaymentWebhook('stripe-secret'),
            new UserCreatedWebhook(),
        ];
    }
}

class MockServiceProviderBuilderBased extends MockServiceProvider
{
    protected function webhooks(): array
    {
        return [
            WebhookBuilder::create('test.webhook', 'secret')
                ->userWebhook()
                ->handle(fn($webhook) => ['processed' => true]),
            
            (new GitHubPushWebhook())->toBuilder(),
        ];
    }
}

it('can register webhooks using class names (class-based approach)', function () {
    $provider = new MockServiceProviderClassBased();
    $manager = app(LaravelWebhook::class);
    
    // Clear any existing webhooks
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('registeredWebhooks');
    $property->setAccessible(true);
    $property->setValue($manager, collect());
    
    $provider->registerWebhooks();
    
    // Verify webhooks were registered
    expect($manager->hasWebhook('github.push'))->toBeTrue();
    expect($manager->hasWebhook('stripe.payment_intent.succeeded'))->toBeTrue();
    expect($manager->hasWebhook('user.created'))->toBeTrue();
});

it('can register webhooks using instances (instance-based approach)', function () {
    $provider = new MockServiceProviderInstanceBased();
    $manager = app(LaravelWebhook::class);
    
    // Clear any existing webhooks
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('registeredWebhooks');
    $property->setAccessible(true);
    $property->setValue($manager, collect());
    
    $provider->registerWebhooks();
    
    // Verify webhooks were registered
    expect($manager->hasWebhook('github.push'))->toBeTrue();
    expect($manager->hasWebhook('stripe.payment_intent.succeeded'))->toBeTrue();
    expect($manager->hasWebhook('user.created'))->toBeTrue();
});

it('can register webhooks using builders (builder-based approach)', function () {
    $provider = new MockServiceProviderBuilderBased();
    $manager = app(LaravelWebhook::class);
    
    // Clear any existing webhooks
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('registeredWebhooks');
    $property->setAccessible(true);
    $property->setValue($manager, collect());
    
    $provider->registerWebhooks();
    
    // Verify webhooks were registered
    expect($manager->hasWebhook('test.webhook'))->toBeTrue();
    expect($manager->hasWebhook('github.push'))->toBeTrue();
});

it('handles mixed registration approaches in same array', function () {
    $provider = new class extends MockServiceProvider {
        protected function webhooks(): array
        {
            return [
                // Class name
                GitHubPushWebhook::class,
                
                // Instance
                new StripePaymentWebhook('stripe-secret'),
                
                // Builder
                WebhookBuilder::create('mixed.test', 'secret')
                    ->property('test_field', 'string')
                    ->handle(fn($webhook) => ['success' => true]),
                
                // Instance converted to builder
                (new UserCreatedWebhook())->toBuilder(),
            ];
        }
    };
    
    $manager = app(LaravelWebhook::class);
    
    // Clear any existing webhooks
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('registeredWebhooks');
    $property->setAccessible(true);
    $property->setValue($manager, collect());
    
    $provider->registerWebhooks();
    
    // Verify all approaches worked
    expect($manager->hasWebhook('github.push'))->toBeTrue();
    expect($manager->hasWebhook('stripe.payment_intent.succeeded'))->toBeTrue();
    expect($manager->hasWebhook('mixed.test'))->toBeTrue();
    expect($manager->hasWebhook('user.created'))->toBeTrue();
});

it('ignores invalid webhook definitions gracefully', function () {
    $provider = new class extends MockServiceProvider {
        protected function webhooks(): array
        {
            return [
                GitHubPushWebhook::class, // Valid
                'InvalidClass', // Invalid class name
                new StripePaymentWebhook(), // Valid instance
                'not-a-class-at-all', // Invalid
                WebhookBuilder::create('valid.builder', 'secret'), // Valid builder
            ];
        }
    };
    
    $manager = app(LaravelWebhook::class);
    
    // Clear any existing webhooks
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('registeredWebhooks');
    $property->setAccessible(true);
    $property->setValue($manager, collect());
    
    // Should not throw exception
    expect(fn() => $provider->registerWebhooks())->not->toThrow();
    
    // Valid webhooks should still be registered
    expect($manager->hasWebhook('github.push'))->toBeTrue();
    expect($manager->hasWebhook('stripe.payment_intent.succeeded'))->toBeTrue();
    expect($manager->hasWebhook('valid.builder'))->toBeTrue();
});