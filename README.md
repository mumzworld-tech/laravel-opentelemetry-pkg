# Laravel OpenTelemetry Package

A comprehensive OpenTelemetry integration package for Laravel applications with complete observability stack including tracing, metrics, and visualization.

## 🚀 Features

- **Easy Integration**: Single composer require + config publish
- **Environment-Driven**: All settings configurable via .env variables
- **Complete Stack**: Includes OpenTelemetry Collector, Tempo, and Grafana
- **Custom Tracing**: Simple TracerService for business logic tracing
- **Docker Ready**: Complete observability stack with Docker Compose
- **Laravel Integration**: Automatic service provider registration
- **Production Ready**: Configurable sampling, batching, and resource limits

## 📋 Prerequisites

- **PHP 8.2+**
- **Laravel 11.0+**
- **Docker & Docker Compose** (for observability stack)
- **OpenTelemetry PHP Extension** (recommended for automatic instrumentation)

## 🛠️ Installation

### Step 1: Install the Package

#### Option A: Via Packagist (Recommended)
```bash
composer require mumzworld/laravel-opentelemetry
```

#### Option B: Via GitHub Repository
Add to your `composer.json`:
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/sahib-mmz/mumzworld-laravel-opentelemetry"
        }
    ],
    "require": {
        "mumzworld/laravel-opentelemetry": "^1.0"
    }
}
```

Then run:
```bash
composer install
```

### Step 2: Publish Configuration Files

```bash
# Publish all OpenTelemetry files
php artisan vendor:publish --provider="Mumzworld\LaravelOpenTelemetry\LaravelOpenTelemetryServiceProvider"

# Or publish specific components
php artisan vendor:publish --tag=opentelemetry-config
php artisan vendor:publish --tag=opentelemetry-docker
php artisan vendor:publish --tag=opentelemetry-bootstrap
php artisan vendor:publish --tag=opentelemetry-env
```

### Step 3: Configure Environment Variables

Add these variables to your `.env` file:

```env
# Core OpenTelemetry Settings
OTEL_ENABLED=true
OTEL_SERVICE_NAME=my-laravel-app
OTEL_SERVICE_VERSION=1.0.0
OTEL_ENVIRONMENT=production

# Trace Export Settings
OTEL_TRACES_EXPORTER=otlp
OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4318
OTEL_PROPAGATORS=baggage,tracecontext

# PHP Extension Settings (if using OpenTelemetry PHP extension)
OTEL_PHP_AUTOLOAD_ENABLED=true
OTEL_INSTRUMENTATION_ENABLED=true
```

### Step 4: Configure PHP Extension (Optional)

If using the OpenTelemetry PHP extension, add to your PHP INI:

```ini
; docker/php/20-otel.ini
[opentelemetry]
otel.php_autoload_enabled=1
otel.service_name="${OTEL_SERVICE_NAME}"
otel.traces_exporter="${OTEL_TRACES_EXPORTER}"
otel.exporter_otlp_endpoint="${OTEL_EXPORTER_OTLP_ENDPOINT}"
otel.propagators="${OTEL_PROPAGATORS}"
otel.instrumentation_enabled=true
```

### Step 5: Update Bootstrap (Optional but Recommended)

Add OpenTelemetry initialization to your `bootstrap/app.php`:

```php
<?php

// Initialize OpenTelemetry early for automatic tracing
if (extension_loaded('opentelemetry') && ($_ENV['OTEL_ENABLED'] ?? 'true') === 'true') {
    require_once __DIR__ . '/otel.php';
}

use Illuminate\Foundation\Application;
// ... rest of your bootstrap code
```

### Step 6: Setup Docker Observability Stack

Add the OpenTelemetry services to your `docker-compose.yml`:

```yaml
# Include the OpenTelemetry stack
include:
  - docker/opentelemetry/docker-compose.opentelemetry.yml

services:
  app:
    environment:
      # OpenTelemetry configuration for your Laravel app
      OTEL_SERVICE_NAME: my-laravel-app
      OTEL_TRACES_EXPORTER: otlp
      OTEL_EXPORTER_OTLP_ENDPOINT: http://otel-collector:4318
      OTEL_PROPAGATORS: baggage,tracecontext
    networks:
      - app-network
```

Or merge the services directly into your existing `docker-compose.yml`.

**Important**: Make sure your app service has the OpenTelemetry environment variables:
```yaml
services:
  app:
    environment:
      OTEL_SERVICE_NAME: ${OTEL_SERVICE_NAME:-my-laravel-app}
      OTEL_TRACES_EXPORTER: ${OTEL_TRACES_EXPORTER:-otlp}
      OTEL_EXPORTER_OTLP_ENDPOINT: ${OTEL_EXPORTER_OTLP_ENDPOINT:-http://otel-collector:4318}
      OTEL_PROPAGATORS: ${OTEL_PROPAGATORS:-baggage,tracecontext}
```

### Step 7: Start the Observability Stack

```bash
docker-compose up -d otel-collector tempo grafana
```

## 🎯 Usage Examples

### Basic Custom Tracing

```php
<?php

namespace App\Services;

use Mumzworld\LaravelOpenTelemetry\Services\TracerService;

class UserService
{
    public function __construct(
        private TracerService $tracerService
    ) {}

    public function createUser(array $userData): User
    {
        return $this->tracerService->trace('user.create', function () use ($userData) {
            // Your business logic here
            $user = User::create($userData);
            
            // Send welcome email
            $this->sendWelcomeEmail($user);
            
            return $user;
        }, [
            'user.email' => $userData['email'],
            'user.type' => $userData['type'] ?? 'regular'
        ]);
    }

    private function sendWelcomeEmail(User $user): void
    {
        $this->tracerService->trace('email.welcome.send', function () use ($user) {
            Mail::to($user)->send(new WelcomeEmail($user));
        }, [
            'email.recipient' => $user->email,
            'email.type' => 'welcome'
        ]);
    }
}
```

### Controller Integration

```php
<?php

namespace App\Http\Controllers;

use Mumzworld\LaravelOpenTelemetry\Services\TracerService;

class ProductController extends Controller
{
    public function __construct(
        private TracerService $tracerService
    ) {}

    public function store(StoreProductRequest $request)
    {
        return $this->tracerService->trace('product.create', function () use ($request) {
            $product = Product::create($request->validated());
            
            // Process images
            if ($request->hasFile('images')) {
                $this->processProductImages($product, $request->file('images'));
            }
            
            // Update search index
            $this->updateSearchIndex($product);
            
            return new ProductResource($product);
        }, [
            'product.name' => $request->name,
            'product.category' => $request->category,
            'product.has_images' => $request->hasFile('images')
        ]);
    }

    private function processProductImages(Product $product, array $images): void
    {
        $this->tracerService->trace('product.images.process', function () use ($product, $images) {
            foreach ($images as $image) {
                // Process each image
                $this->tracerService->addSpan('product.image.upload', [
                    'image.size' => $image->getSize(),
                    'image.type' => $image->getMimeType()
                ]);
            }
        }, [
            'product.id' => $product->id,
            'images.count' => count($images)
        ]);
    }
}
```

### Service Integration

```php
<?php

namespace App\Services;

use Mumzworld\LaravelOpenTelemetry\Services\TracerService;

class PaymentService
{
    public function __construct(
        private TracerService $tracerService,
        private PaymentGateway $gateway
    ) {}

    public function processPayment(Order $order, array $paymentData): PaymentResult
    {
        return $this->tracerService->trace('payment.process', function () use ($order, $paymentData) {
            
            // Validate payment data
            $this->tracerService->trace('payment.validate', function () use ($paymentData) {
                $this->validatePaymentData($paymentData);
            });
            
            // Call external payment gateway
            $result = $this->tracerService->trace('payment.gateway.charge', function () use ($order, $paymentData) {
                return $this->gateway->charge($order->total, $paymentData);
            }, [
                'payment.amount' => $order->total,
                'payment.currency' => $order->currency,
                'payment.method' => $paymentData['method']
            ]);
            
            // Update order status
            $this->tracerService->trace('order.status.update', function () use ($order, $result) {
                $order->update(['status' => $result->isSuccessful() ? 'paid' : 'failed']);
            });
            
            return $result;
            
        }, [
            'order.id' => $order->id,
            'order.total' => $order->total,
            'payment.method' => $paymentData['method']
        ]);
    }
}
```

### Job Tracing

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Mumzworld\LaravelOpenTelemetry\Services\TracerService;

class ProcessOrderJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $orderId
    ) {}

    public function handle(TracerService $tracerService): void
    {
        $tracerService->trace('job.process_order', function () use ($tracerService) {
            $order = Order::findOrFail($this->orderId);
            
            // Process inventory
            $tracerService->trace('order.inventory.reserve', function () use ($order) {
                $this->reserveInventory($order);
            });
            
            // Send confirmation email
            $tracerService->trace('order.email.confirmation', function () use ($order) {
                Mail::to($order->customer)->send(new OrderConfirmation($order));
            });
            
            // Update analytics
            $tracerService->addSpan('order.analytics.update', [
                'order.value' => $order->total,
                'customer.type' => $order->customer->type
            ]);
            
        }, [
            'job.name' => 'ProcessOrderJob',
            'order.id' => $this->orderId
        ]);
    }
}
```

## 🔧 Configuration Options

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `OTEL_ENABLED` | `true` | Enable/disable OpenTelemetry |
| `OTEL_SERVICE_NAME` | `APP_NAME` | Service name for traces |
| `OTEL_SERVICE_VERSION` | `1.0.0` | Service version |
| `OTEL_ENVIRONMENT` | `APP_ENV` | Deployment environment |
| `OTEL_TRACES_EXPORTER` | `otlp` | Trace exporter type |
| `OTEL_EXPORTER_OTLP_ENDPOINT` | `http://otel-collector:4318` | OTLP endpoint |
| `OTEL_PROPAGATORS` | `baggage,tracecontext` | Trace propagators |
| `OTEL_TRACES_SAMPLER` | `parentbased_traceidratio` | Sampling strategy |
| `OTEL_TRACES_SAMPLER_ARG` | `1.0` | Sampling ratio (0.0-1.0) |

### Configuration File

The package publishes a configuration file at `config/opentelemetry.php`:

```php
return [
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
    
    // ... more configuration options
];
```

## 🐳 Docker Integration

### Complete Stack

The package includes a complete observability stack:

```bash
# Start the observability stack
docker-compose up -d otel-collector tempo grafana

# Check service health
curl http://localhost:13133/  # Collector health check
curl http://localhost:3200/ready  # Tempo health check
curl http://localhost:3000/  # Grafana UI
```

### Service URLs

- **Grafana Dashboard**: http://localhost:3000 (admin/admin)
- **Tempo API**: http://localhost:3200
- **Collector Health**: http://localhost:13133

### Integration with Existing Docker Compose

Add to your existing `docker-compose.yml`:

```yaml
services:
  app:
    environment:
      OTEL_SERVICE_NAME: my-app
      OTEL_EXPORTER_OTLP_ENDPOINT: http://otel-collector:4318
    depends_on:
      - otel-collector

# Include OpenTelemetry stack
include:
  - docker/opentelemetry/docker-compose.opentelemetry.yml
```

## 📊 Viewing Traces

### Grafana Dashboard

1. Open Grafana at http://localhost:3000
2. Login with admin/admin
3. Go to "Explore" → Select "Tempo" datasource
4. Search for traces by:
   - Service name: `my-laravel-app`
   - Operation name: `user.create`
   - Tags: `http.method=POST`

### Sample Trace Queries

```
# Find all traces for a specific service
{service.name="my-laravel-app"}

# Find traces with errors
{service.name="my-laravel-app"} | select(status=error)

# Find slow traces (duration > 1s)
{service.name="my-laravel-app"} | select(duration>1s)

# Find traces for specific operations
{service.name="my-laravel-app" span.name="user.create"}
```

## 🔍 Troubleshooting

### Common Issues

**1. No traces appearing in Grafana**

Check if services are running:
```bash
docker-compose ps otel-collector tempo grafana
```

Check collector logs:
```bash
docker-compose logs otel-collector
```

**2. PHP Extension not loading**

Verify OpenTelemetry extension is installed:
```bash
php -m | grep opentelemetry
```

Install the extension:
```bash
# Using PECL
pecl install opentelemetry

# Or download from releases
# https://github.com/open-telemetry/opentelemetry-php-instrumentation/releases
```

**3. Traces not being exported**

Check environment variables:
```bash
php artisan tinker
>>> config('opentelemetry.enabled')
>>> config('opentelemetry.service.name')
>>> config('opentelemetry.exporter.otlp.endpoint')
```

**4. High memory usage**

Adjust sampling rate:
```env
OTEL_TRACES_SAMPLER_ARG=0.1  # Sample 10% of traces
```

Configure memory limits in collector:
```yaml
# docker/opentelemetry/otel-collector/config.yaml
processors:
  memory_limiter:
    limit_mib: 256  # Reduce memory limit
```

### Debug Mode

Enable debug logging:
```env
OTEL_DEBUG=true
APP_DEBUG=true
```

Check Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

## 🚀 Production Deployment

### Environment Configuration

```env
# Production settings
OTEL_ENABLED=true
OTEL_SERVICE_NAME=my-production-app
OTEL_ENVIRONMENT=production
OTEL_TRACES_SAMPLER_ARG=0.1  # Sample 10% of traces

# Use external collector
OTEL_EXPORTER_OTLP_ENDPOINT=https://otel-collector.example.com:4318
```

### Performance Considerations

1. **Sampling**: Use appropriate sampling rates for production
2. **Batching**: Configure batch processors in collector
3. **Resource Limits**: Set memory and CPU limits
4. **Network**: Use gRPC for better performance
5. **Storage**: Configure appropriate retention policies

### Security

1. **Authentication**: Secure Grafana with proper authentication
2. **Network**: Use private networks for internal communication
3. **TLS**: Enable TLS for external endpoints
4. **Access Control**: Restrict access to observability tools

## 📚 Advanced Usage

### Custom Exporters

Configure multiple exporters:

```yaml
# docker/opentelemetry/otel-collector/config.yaml
exporters:
  otlphttp/tempo:
    endpoint: http://tempo:4317
  jaeger:
    endpoint: jaeger:14250
  zipkin:
    endpoint: http://zipkin:9411/api/v2/spans

service:
  pipelines:
    traces:
      exporters: [otlphttp/tempo, jaeger, zipkin]
```

### Custom Attributes

Add global attributes:

```env
OTEL_RESOURCE_ATTRIBUTES=service.name=my-app,service.version=1.0.0,deployment.environment=production,team=backend
```

### Conditional Tracing

```php
// Only trace in specific environments
if (app()->environment(['production', 'staging'])) {
    $tracerService->trace('expensive.operation', $callback);
} else {
    $callback();
}
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## 📄 License

This package is licensed under the MIT License.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Check the troubleshooting section
- Review the Laravel OpenTelemetry documentation

---

**Built with ❤️ by Mumzworld Development Team**