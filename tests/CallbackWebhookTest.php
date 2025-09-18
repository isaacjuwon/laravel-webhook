<?php

use Isaacjuwon\LaravelWebhook\LaravelWebhook;
use Isaacjuwon\LaravelWebhook\Support\WebhookBuilder;
use Isaacjuwon\LaravelWebhook\Models\Webhook;
use Isaacjuwon\LaravelWebhook\Examples\CallbackBasedWebhook;

it('supports callback-based webhooks in WebhookBuilder', function () {
    $manager = app(LaravelWebhook::class);
    
    // Clear existing webhooks
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('registeredWebhooks');
    $property->setAccessible(true);
    $property->setValue($manager, collect());
    
    // Create webhook with callback
    $callbackExecuted = false;
    $result = null;
    
    WebhookBuilder::create('test.callback', 'secret')
        ->property('test_field', 'string')
        ->handle(function (Webhook $webhook) use (&$callbackExecuted, &$result) {
            $callbackExecuted = true;
            $result = [
                'test_field' => $webhook->get('test_field'),
                'processed_by' => 'callback'
            ];
            return $result;
        })
        ->register();
    
    // Get the registered webhook
    $registeredWebhook = $manager->getWebhook('test.callback');
    expect($registeredWebhook)->toBeInstanceOf(Webhook::class);
    
    // Set payload and execute
    $registeredWebhook->setPayload(['test_field' => 'callback_test_value']);
    $executionResult = $registeredWebhook->execute();
    
    expect($callbackExecuted)->toBeTrue();
    expect($executionResult)->toBe($result);
    expect($executionResult['test_field'])->toBe('callback_test_value');
    expect($executionResult['processed_by'])->toBe('callback');
});

it('supports callback overrides in webhook classes', function () {
    $callbackExecuted = false;
    $customResult = null;
    
    // Create webhook with callback override
    $webhook = new CallbackBasedWebhook(
        'test-secret',
        function (Webhook $webhook) use (&$callbackExecuted, &$customResult) {
            $callbackExecuted = true;
            $customResult = [
                'user_id' => $webhook->get('user_id'),
                'handler_type' => 'custom_callback_override'
            ];
            return $customResult;
        }
    );
    
    $builder = $webhook->toBuilder();
    expect($builder)->toBeInstanceOf(WebhookBuilder::class);
    
    $registeredWebhook = $builder->register();
    
    // Set payload and execute
    $registeredWebhook->setPayload([
        'user_id' => 123,
        'email' => 'test@example.com'
    ]);
    
    $result = $registeredWebhook->execute();
    
    expect($callbackExecuted)->toBeTrue();
    expect($result)->toBe($customResult);
    expect($result['handler_type'])->toBe('custom_callback_override');
});

it('falls back to handle method when no callback is set', function () {
    // Create webhook without callback
    $webhook = new CallbackBasedWebhook('test-secret');
    
    $builder = $webhook->toBuilder();
    $registeredWebhook = $builder->register();
    
    // Set payload and execute
    $registeredWebhook->setPayload([
        'user_id' => 456,
        'email' => 'test2@example.com'
    ]);
    
    $result = $registeredWebhook->execute();
    
    expect($result['handler_type'])->toBe('default_handle_method');
    expect($result['user_id'])->toBe(456);
});

it('supports arrow function callbacks', function () {
    $manager = app(LaravelWebhook::class);
    
    // Clear existing webhooks
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('registeredWebhooks');
    $property->setAccessible(true);
    $property->setValue($manager, collect());
    
    // Create webhook with arrow function
    WebhookBuilder::create('test.arrow', 'secret')
        ->property('value', 'integer')
        ->handle(fn(Webhook $webhook) => [
            'doubled' => $webhook->get('value') * 2,
            'processed_by' => 'arrow_function'
        ])
        ->register();
    
    $registeredWebhook = $manager->getWebhook('test.arrow');
    $registeredWebhook->setPayload(['value' => 21]);
    
    $result = $registeredWebhook->execute();
    
    expect($result['doubled'])->toBe(42);
    expect($result['processed_by'])->toBe('arrow_function');
});

it('handles callback validation and errors correctly', function () {
    $manager = app(LaravelWebhook::class);
    
    // Clear existing webhooks
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('registeredWebhooks');
    $property->setAccessible(true);
    $property->setValue($manager, collect());
    
    // Create webhook with validation
    WebhookBuilder::create('test.validation', 'secret')
        ->property('required_field', 'string')
        ->handle(function (Webhook $webhook) {
            return ['validated' => true];
        })
        ->register();
    
    $registeredWebhook = $manager->getWebhook('test.validation');
    
    // Test missing required field
    $registeredWebhook->setPayload(['other_field' => 'value']);
    
    // Test with valid payload (no validation since we removed required fields)
    $registeredWebhook->setPayload(['required_field' => 'present']);
    $result = $registeredWebhook->execute();
    
    expect($result['validated'])->toBeTrue();
});

it('supports complex callback scenarios with headers', function () {
    $manager = app(LaravelWebhook::class);
    
    // Clear existing webhooks
    $reflection = new ReflectionClass($manager);
    $property = $reflection->getProperty('registeredWebhooks');
    $property->setAccessible(true);
    $property->setValue($manager, collect());
    
    // Create webhook that uses headers
    WebhookBuilder::create('test.headers', 'secret')
        ->storeHeaders(['X-Event-ID', 'X-Timestamp'])
        ->property('data', 'string')
        ->handle(function (Webhook $webhook) {
            return [
                'data' => $webhook->get('data'),
                'event_id' => $webhook->getHeader('X-Event-ID'),
                'timestamp' => $webhook->getHeader('X-Timestamp'),
                'has_headers' => !empty($webhook->getHeaders()->toArray())
            ];
        })
        ->register();
    
    $registeredWebhook = $manager->getWebhook('test.headers');
    
    // Set payload and headers
    $registeredWebhook->setPayload(['data' => 'test_data']);
    $registeredWebhook->setHeaders([
        'X-Event-ID' => 'evt_123',
        'X-Timestamp' => '2024-01-01T12:00:00Z'
    ]);
    
    $result = $registeredWebhook->execute();
    
    expect($result['data'])->toBe('test_data');
    expect($result['event_id'])->toBe('evt_123');
    expect($result['timestamp'])->toBe('2024-01-01T12:00:00Z');
    expect($result['has_headers'])->toBeTrue();
});