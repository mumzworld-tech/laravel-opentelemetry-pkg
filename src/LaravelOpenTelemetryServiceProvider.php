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
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/Config/opentelemetry.php' => config_path('opentelemetry.php'),
        ], 'opentelemetry-config');

        // Publish Docker configuration files
        $this->publishes([
            __DIR__ . '/../docker' => base_path('docker/opentelemetry'),
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
    }
}