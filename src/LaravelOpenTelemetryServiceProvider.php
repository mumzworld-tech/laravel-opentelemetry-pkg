<?php

namespace Mumzworld\LaravelOpenTelemetry;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\API\Globals;
use Mumzworld\LaravelOpenTelemetry\Services\TracerService;

/**
 * LaravelOpenTelemetryServiceProvider
 * 
 * Service provider for the OpenTelemetry Laravel package.
 * Handles registration of services and configuration publishing.
 */
class LaravelOpenTelemetryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge package configuration with app configuration
        $this->mergeConfigFrom(
            __DIR__ . '/Config/opentelemetry.php',
            'opentelemetry'
        );

        // Bind OpenTelemetry TracerProvider for manual tracing
        $this->app->singleton(TracerProviderInterface::class, function () {
            return Globals::tracerProvider();
        });

        // Register TracerService for custom business logic tracing
        $this->app->singleton(TracerService::class, function ($app) {
            $serviceName = config('opentelemetry.service.name');
            return new TracerService($app->make(TracerProviderInterface::class), $serviceName);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Auto-configure OpenTelemetry environment variables if enabled
        $this->configureOpenTelemetry();

        // Publish configuration file
        $this->publishes([
            __DIR__ . '/Config/opentelemetry.php' => config_path('opentelemetry.php'),
        ], 'opentelemetry-config');

        // Publish Docker configuration files
        $this->publishes([
            __DIR__ . '/../docker/docker-compose.opentelemetry.yml' => base_path('docker/opentelemetry/docker-compose.opentelemetry.yml'),
            __DIR__ . '/../docker/otel-collector/config.yaml' => base_path('docker/opentelemetry/otel-collector/config.yaml'),
            __DIR__ . '/../docker/tempo/tempo.yaml' => base_path('docker/opentelemetry/tempo/tempo.yaml'),
            __DIR__ . '/../docker/grafana' => base_path('docker/opentelemetry/grafana'),
            __DIR__ . '/../docker/php' => base_path('docker/opentelemetry/php'),
        ], 'opentelemetry-docker');

        // Publish OpenTelemetry bootstrap file
        $this->publishes([
            __DIR__ . '/Bootstrap/otel.php' => base_path('bootstrap/otel.php'),
        ], 'opentelemetry-bootstrap');

        // Publish environment example
        $this->publishes([
            __DIR__ . '/../.env.example' => base_path('.env.opentelemetry.example'),
        ], 'opentelemetry-env');

        // Publish documentation
        $this->publishes([
            __DIR__ . '/../README.md' => base_path('docs/opentelemetry.md'),
        ], 'opentelemetry-docs');

        // Publish test routes (optional, for development/testing)
        $this->publishes([
            __DIR__ . '/Routes/test.php' => base_path('routes/opentelemetry-test.php'),
        ], 'opentelemetry-test-routes');
    }

    /**
     * Auto-configure OpenTelemetry environment variables
     *
     * @return void
     */
    protected function configureOpenTelemetry(): void
    {
        if (!config('opentelemetry.enabled', true)) {
            return;
        }

        // Set OpenTelemetry environment variables for the PHP extension
        $this->setEnvIfNotSet('OTEL_SERVICE_NAME', config('opentelemetry.service.name'));
        $this->setEnvIfNotSet('OTEL_TRACES_EXPORTER', config('opentelemetry.traces.exporter'));
        $this->setEnvIfNotSet('OTEL_EXPORTER_OTLP_ENDPOINT', config('opentelemetry.exporter.otlp.endpoint'));
        $this->setEnvIfNotSet('OTEL_EXPORTER_OTLP_PROTOCOL', config('opentelemetry.exporter.otlp.protocol'));
        $this->setEnvIfNotSet('OTEL_PROPAGATORS', config('opentelemetry.propagators'));
        $this->setEnvIfNotSet('OTEL_TRACES_SAMPLER', config('opentelemetry.traces.sampler'));
        $this->setEnvIfNotSet('OTEL_TRACES_SAMPLER_ARG', (string) config('opentelemetry.traces.sampler_arg'));
        
        // Build resource attributes
        $resourceAttributes = [];
        foreach (config('opentelemetry.resource_attributes', []) as $key => $value) {
            $resourceAttributes[] = "{$key}={$value}";
        }
        if (!empty($resourceAttributes)) {
            $this->setEnvIfNotSet('OTEL_RESOURCE_ATTRIBUTES', implode(',', $resourceAttributes));
        }
    }

    /**
     * Set environment variable if not already set
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    protected function setEnvIfNotSet(string $key, string $value): void
    {
        if (!isset($_ENV[$key]) && $value !== null) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}