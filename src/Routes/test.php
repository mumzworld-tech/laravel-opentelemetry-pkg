<?php

use Illuminate\Support\Facades\Route;
use Mumzworld\LaravelOpenTelemetry\Services\TracerService;

/*
|--------------------------------------------------------------------------
| OpenTelemetry Test Routes
|--------------------------------------------------------------------------
|
| These routes are for testing OpenTelemetry integration.
| Publish these routes only in development/testing environments.
|
*/

Route::prefix('api/opentelemetry')->group(function () {
    
    // Test basic tracing functionality
    Route::get('/test', function (TracerService $tracerService) {
        return $tracerService->trace('opentelemetry.test', function () use ($tracerService) {
            // Simulate some work
            usleep(100000); // 100ms
            
            // Add a nested span
            $tracerService->addSpan('opentelemetry.test.nested', [
                'test.nested' => true,
                'test.duration' => '100ms'
            ]);
            
            return [
                'message' => 'OpenTelemetry tracing is working!',
                'service' => config('opentelemetry.service.name'),
                'endpoint' => config('opentelemetry.exporter.otlp.endpoint'),
                'timestamp' => now()->toISOString(),
                'trace_enabled' => config('opentelemetry.enabled'),
            ];
        }, [
            'test.type' => 'integration',
            'test.endpoint' => '/api/opentelemetry/test',
            'http.method' => 'GET'
        ]);
    });

    // Test configuration display
    Route::get('/config', function () {
        return [
            'enabled' => config('opentelemetry.enabled'),
            'service' => config('opentelemetry.service'),
            'traces' => config('opentelemetry.traces'),
            'exporter' => config('opentelemetry.exporter.otlp'),
            'propagators' => config('opentelemetry.propagators'),
            'environment_variables' => [
                'OTEL_SERVICE_NAME' => $_ENV['OTEL_SERVICE_NAME'] ?? 'not set',
                'OTEL_TRACES_EXPORTER' => $_ENV['OTEL_TRACES_EXPORTER'] ?? 'not set',
                'OTEL_EXPORTER_OTLP_ENDPOINT' => $_ENV['OTEL_EXPORTER_OTLP_ENDPOINT'] ?? 'not set',
                'OTEL_PROPAGATORS' => $_ENV['OTEL_PROPAGATORS'] ?? 'not set',
            ]
        ];
    });

    // Test error handling and exception tracing
    Route::get('/error', function (TracerService $tracerService) {
        try {
            return $tracerService->trace('opentelemetry.error.test', function () {
                throw new \Exception('Test exception for OpenTelemetry error tracing');
            }, [
                'test.type' => 'error_handling',
                'test.expected_error' => true
            ]);
        } catch (\Exception $e) {
            return [
                'message' => 'Exception traced successfully',
                'error' => $e->getMessage(),
                'trace_recorded' => true
            ];
        }
    });

    // Test multiple nested spans
    Route::get('/nested', function (TracerService $tracerService) {
        return $tracerService->trace('opentelemetry.nested.parent', function () use ($tracerService) {
            
            // First nested operation
            $tracerService->trace('opentelemetry.nested.database', function () {
                usleep(50000); // Simulate DB query
            }, ['operation' => 'select', 'table' => 'users']);
            
            // Second nested operation
            $tracerService->trace('opentelemetry.nested.cache', function () {
                usleep(20000); // Simulate cache operation
            }, ['operation' => 'get', 'key' => 'user:123']);
            
            // Third nested operation
            $tracerService->trace('opentelemetry.nested.api', function () {
                usleep(80000); // Simulate external API call
            }, ['operation' => 'http_request', 'url' => 'https://api.example.com']);
            
            return [
                'message' => 'Nested spans created successfully',
                'operations' => ['database', 'cache', 'api'],
                'total_duration' => '~150ms'
            ];
        }, [
            'test.type' => 'nested_spans',
            'test.operations_count' => 3
        ]);
    });
});