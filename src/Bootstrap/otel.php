<?php

// OpenTelemetry configuration for automatic Laravel tracing
// Sets default environment variables for the OpenTelemetry PHP extension

if (!function_exists('setOtelEnvIfNotSet')) {
    function setOtelEnvIfNotSet(string $key, string $value): void
    {
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
        }
    }
}

// Only initialize if OpenTelemetry is enabled
if (($_ENV['OTEL_ENABLED'] ?? 'true') === 'true') {
    // Configure OpenTelemetry service name
    setOtelEnvIfNotSet('OTEL_SERVICE_NAME', $_ENV['APP_NAME'] ?? 'laravel-app');
    
    // Configure trace exporter
    setOtelEnvIfNotSet('OTEL_TRACES_EXPORTER', $_ENV['OTEL_TRACES_EXPORTER'] ?? 'otlp');
    
    // Configure OTLP endpoint
    setOtelEnvIfNotSet('OTEL_EXPORTER_OTLP_ENDPOINT', $_ENV['OTEL_EXPORTER_OTLP_ENDPOINT'] ?? 'http://otel-collector:4318');
    
    // Configure propagators
    setOtelEnvIfNotSet('OTEL_PROPAGATORS', $_ENV['OTEL_PROPAGATORS'] ?? 'baggage,tracecontext');
    
    // Configure resource attributes
    $serviceName = $_ENV['OTEL_SERVICE_NAME'] ?? $_ENV['APP_NAME'] ?? 'laravel-app';
    $serviceVersion = $_ENV['OTEL_SERVICE_VERSION'] ?? '1.0.0';
    $environment = $_ENV['OTEL_ENVIRONMENT'] ?? $_ENV['APP_ENV'] ?? 'production';
    
    $resourceAttributes = "service.name={$serviceName},service.version={$serviceVersion},deployment.environment={$environment}";
    setOtelEnvIfNotSet('OTEL_RESOURCE_ATTRIBUTES', $resourceAttributes);
    
    // Configure sampling
    setOtelEnvIfNotSet('OTEL_TRACES_SAMPLER', $_ENV['OTEL_TRACES_SAMPLER'] ?? 'parentbased_traceidratio');
    setOtelEnvIfNotSet('OTEL_TRACES_SAMPLER_ARG', $_ENV['OTEL_TRACES_SAMPLER_ARG'] ?? '1.0');

    // Log that OpenTelemetry is being initialized
    if (($_ENV['APP_DEBUG'] ?? false) && ($_ENV['OTEL_DEBUG'] ?? false)) {
        error_log('OpenTelemetry initialized with service: ' . ($_ENV['OTEL_SERVICE_NAME'] ?? 'unknown'));
    }
}