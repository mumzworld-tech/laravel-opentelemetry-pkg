<?php

namespace Mumzworld\LaravelOpenTelemetry\Services;

use Closure;
use Throwable;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\Context\Context;

/**
 * TracerService - Custom OpenTelemetry tracing for business logic
 * 
 * Provides easy-to-use methods for creating custom spans and recording exceptions
 */
class TracerService
{
    protected TracerInterface $tracer;
    protected string $serviceName;

    public function __construct(TracerProviderInterface $provider, string $serviceName = null)
    {
        $this->serviceName = $serviceName ?? config('opentelemetry.service.name', 'laravel-app');
        $this->tracer = $provider->getTracer($this->serviceName);
    }

    /**
     * Trace a block of logic inside a named span.
     *
     * @param string $name Span name (e.g., 'database.query', 'external.api.call')
     * @param Closure $callback The code to execute within the span
     * @param array $attributes Additional span attributes
     * @return mixed The result of the callback
     * @throws Throwable
     */
    public function trace(string $name, Closure $callback, array $attributes = []): mixed
    {
        if (!config('opentelemetry.enabled', true)) {
            return $callback();
        }

        $span = $this->tracer->spanBuilder($name)->startSpan();
        $scope = Context::storage()->attach($span->storeInContext(Context::getCurrent()));

        // Add custom attributes to the span
        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }

        try {
            return $callback();
        } catch (Throwable $e) {
            // Record exception details in the span
            $span->recordException($e);
            $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->end();
            $scope->detach();
        }
    }

    /**
     * Create a simple span for marking important operations
     *
     * @param string $name Span name
     * @param array $attributes Span attributes
     * @return void
     */
    public function addSpan(string $name, array $attributes = []): void
    {
        if (!config('opentelemetry.enabled', true)) {
            return;
        }

        $span = $this->tracer->spanBuilder($name)->startSpan();
        
        foreach ($attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }
        
        $span->end();
    }

    /**
     * Get the service name used by this tracer
     *
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }
}