<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenTelemetry Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for OpenTelemetry tracing in your
    | Laravel application. All values can be overridden using environment
    | variables for different deployment environments.
    |
    */

    'enabled' => env('OTEL_ENABLED', true),

    'service' => [
        'name' => env('OTEL_SERVICE_NAME', env('APP_NAME', 'laravel-app')),
        'version' => env('OTEL_SERVICE_VERSION', '1.0.0'),
        'environment' => env('OTEL_ENVIRONMENT', env('APP_ENV', 'production')),
    ],

    'traces' => [
        'exporter' => env('OTEL_TRACES_EXPORTER', 'otlp'),
        'sampler' => env('OTEL_TRACES_SAMPLER', 'parentbased_traceidratio'),
        'sampler_arg' => env('OTEL_TRACES_SAMPLER_ARG', 1.0),
    ],

    'exporter' => [
        'otlp' => [
            'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://otel-collector:4318'),
            'protocol' => env('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/protobuf'),
            'headers' => env('OTEL_EXPORTER_OTLP_HEADERS', ''),
            'timeout' => env('OTEL_EXPORTER_OTLP_TIMEOUT', 10),
        ],
        'console' => [
            'enabled' => env('OTEL_EXPORTER_CONSOLE_ENABLED', false),
        ],
    ],

    'propagators' => env('OTEL_PROPAGATORS', 'baggage,tracecontext'),

    'instrumentation' => [
        'php_autoload_enabled' => env('OTEL_PHP_AUTOLOAD_ENABLED', true),
        'enabled' => env('OTEL_INSTRUMENTATION_ENABLED', true),
        'attr_hooks_enabled' => env('OTEL_ATTR_HOOKS_ENABLED', true),
    ],

    'resource_attributes' => [
        'service.name' => env('OTEL_SERVICE_NAME', env('APP_NAME', 'laravel-app')),
        'service.version' => env('OTEL_SERVICE_VERSION', '1.0.0'),
        'deployment.environment' => env('OTEL_ENVIRONMENT', env('APP_ENV', 'production')),
    ],

    'docker' => [
        'collector' => [
            'image' => env('OTEL_COLLECTOR_IMAGE', 'otel/opentelemetry-collector:0.101.0'),
            'http_port' => env('OTEL_COLLECTOR_HTTP_PORT', 4318),
            'grpc_port' => env('OTEL_COLLECTOR_GRPC_PORT', 4317),
        ],
        'tempo' => [
            'image' => env('TEMPO_IMAGE', 'grafana/tempo:2.4.1'),
            'port' => env('TEMPO_PORT', 3200),
        ],
        'grafana' => [
            'image' => env('GRAFANA_IMAGE', 'grafana/grafana:10.4.6'),
            'port' => env('GRAFANA_PORT', 3000),
            'admin_password' => env('GRAFANA_ADMIN_PASSWORD', 'admin'),
        ],
    ],
];