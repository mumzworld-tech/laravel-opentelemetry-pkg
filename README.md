# Laravel OpenTelemetry Package

A comprehensive OpenTelemetry integration package for Laravel applications that provides complete observability through distributed tracing, metrics collection, and visualization. This package enables you to monitor your application's performance, track requests across microservices, and identify bottlenecks in your Laravel application.

**Learn more about OpenTelemetry:**
- [OpenTelemetry Official Documentation](https://opentelemetry.io/docs/)
- [OpenTelemetry PHP Documentation](https://opentelemetry.io/docs/languages/php/)

## 🚀 Features

- **Easy Integration**: Single composer require + automated setup script
- **Environment-Driven**: All settings configurable via .env variables
- **Complete Observability Stack**: Includes OpenTelemetry Collector, Tempo, and Grafana
- **Custom Tracing**: Simple TracerService for business logic tracing
- **Docker Ready**: Complete observability stack with Docker Compose
- **Laravel Integration**: Automatic service provider registration
- **Production Ready**: Configurable sampling, batching, and resource limits
- **Automatic Instrumentation**: PHP extension support for zero-code tracing
- **Error Tracking**: Automatic exception recording in traces
- **Test Routes**: Built-in test endpoints to verify integration

## 📋 Prerequisites

- **PHP 8.2+**
- **Laravel 11.0+**
- **Docker & Docker Compose** (for observability stack)
- **OpenTelemetry PHP Extension** (required for automatic instrumentation)
- **Composer** (for package installation)

## 🛠️ Installation

### Quick Setup (Recommended)

Use our automated setup script for complete installation:

```bash
# Clone or download the package, then run:
curl -sSL https://raw.githubusercontent.com/mumzworld-tech/laravel-opentelemetry-pkg/main/setup-opentelemetry.sh | bash

# Or if you have the package locally:
./setup-opentelemetry.sh
```

### Manual Installation

#### Step 1: Install the Package

```bash
composer require mumzworld/laravel-opentelemetry
```

*Note: The service provider is automatically registered via Laravel's package auto-discovery.*

#### Step 2: Install OpenTelemetry PHP Extension

The OpenTelemetry PHP extension is **required** for automatic instrumentation.

**Option A: Using PECL**
```bash
# Install the extension
pecl install opentelemetry

# Add to your php.ini
echo "extension=opentelemetry" >> /path/to/php.ini

# Restart your web server
sudo systemctl restart apache2  # or nginx/php-fpm
```

**Option B: Docker Environment (Recommended)**
```dockerfile
# Add to your Dockerfile
RUN pecl install opentelemetry && docker-php-ext-enable opentelemetry

# Or copy the provided PHP configuration
COPY docker/php/20-otel.ini /usr/local/etc/php/conf.d/
```

**Verify Installation:**
```bash
php -m | grep opentelemetry
# Should output: opentelemetry
```

#### Step 3: Publish Configuration Files

```bash
# Publish all OpenTelemetry files
php artisan vendor:publish --provider="Mumzworld\LaravelOpenTelemetry\LaravelOpenTelemetryServiceProvider"

# Or publish specific components
php artisan vendor:publish --tag=opentelemetry-config
php artisan vendor:publish --tag=opentelemetry-docker
php artisan vendor:publish --tag=opentelemetry-bootstrap
php artisan vendor:publish --tag=opentelemetry-env
php artisan vendor:publish --tag=opentelemetry-test-routes
```

#### Step 4: Configure Environment Variables

Add these variables to your `.env` file:

```env
# Core OpenTelemetry Settings
OTEL_ENABLED=true
OTEL_SERVICE_NAME=${APP_NAME}
OTEL_SERVICE_VERSION=1.0.0
OTEL_ENVIRONMENT=${APP_ENV}

# Trace Export Settings
OTEL_TRACES_EXPORTER=otlp
OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4318
OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf
OTEL_PROPAGATORS=baggage,tracecontext

# Sampling Configuration (adjust for production)
OTEL_TRACES_SAMPLER=parentbased_traceidratio
OTEL_TRACES_SAMPLER_ARG=1.0

# PHP Extension Settings
OTEL_PHP_AUTOLOAD_ENABLED=true
OTEL_INSTRUMENTATION_ENABLED=true
OTEL_ATTR_HOOKS_ENABLED=true

# Debug Settings (disable in production)
OTEL_DEBUG=false
```

#### Step 5: Setup Docker Observability Stack

Add the OpenTelemetry services to your `docker-compose.yml`:

```yaml
# Include the OpenTelemetry stack
include:
  - docker/opentelemetry/docker-compose.opentelemetry.yml

services:
  app:
    environment:
      # OpenTelemetry configuration for your Laravel app
      OTEL_SERVICE_NAME: ${OTEL_SERVICE_NAME:-my-laravel-app}
      OTEL_TRACES_EXPORTER: ${OTEL_TRACES_EXPORTER:-otlp}
      OTEL_EXPORTER_OTLP_ENDPOINT: ${OTEL_EXPORTER_OTLP_ENDPOINT:-http://otel-collector:4318}
      OTEL_EXPORTER_OTLP_PROTOCOL: ${OTEL_EXPORTER_OTLP_PROTOCOL:-http/protobuf}
      OTEL_PROPAGATORS: ${OTEL_PROPAGATORS:-baggage,tracecontext}
      OTEL_PHP_AUTOLOAD_ENABLED: ${OTEL_PHP_AUTOLOAD_ENABLED:-true}
    networks:
      - app-network
    depends_on:
      - otel-collector

networks:
  app-network:
    external: true
```

#### Step 6: Start the Observability Stack

```bash
# Start the observability stack
docker-compose up -d otel-collector tempo grafana

# Start your application
docker-compose up -d app
```

#### Step 7: Verify Installation

```bash
# Check if services are running
docker-compose ps otel-collector tempo grafana

# Test the integration (if test routes are published)
curl http://your-host/api/opentelemetry/test

# Check Grafana is accessible
curl http://your-host:3000
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

### Database Query Tracing

```php
<?php

namespace App\Repositories;

use Mumzworld\LaravelOpenTelemetry\Services\TracerService;

class ProductRepository
{
    public function __construct(
        private TracerService $tracerService
    ) {}

    public function findWithFilters(array $filters): Collection
    {
        return $this->tracerService->trace('product.query.filtered', function () use ($filters) {
            $query = Product::query();
            
            if (isset($filters['category'])) {
                $query->where('category_id', $filters['category']);
            }
            
            if (isset($filters['price_range'])) {
                $query->whereBetween('price', $filters['price_range']);
            }
            
            return $query->get();
        }, [
            'query.filters' => json_encode($filters),
            'query.type' => 'product_search'
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

## 🧪 Testing the Integration

The package includes test routes to verify your OpenTelemetry setup:

### Test Routes

```bash
# Basic functionality test
curl http://your-host/api/opentelemetry/test

# Configuration verification
curl http://your-host/api/opentelemetry/config

# Error handling test
curl http://your-host/api/opentelemetry/error

# Nested spans test
curl http://your-host/api/opentelemetry/nested
```

### Enable Test Routes

Add to your `routes/api.php` (development only):

```php
// OpenTelemetry test routes (remove in production)
if (app()->environment(['local', 'testing'])) {
    require __DIR__ . '/opentelemetry-test.php';
}
```

### Expected Test Responses

**Basic Test (`/api/opentelemetry/test`):**
```json
{
    "message": "OpenTelemetry tracing is working!",
    "service": "my-laravel-app",
    "endpoint": "http://otel-collector:4318",
    "timestamp": "2024-03-15T10:30:00.000000Z",
    "trace_enabled": true
}
```

**Configuration Test (`/api/opentelemetry/config`):**
```json
{
    "enabled": true,
    "service": {
        "name": "my-laravel-app",
        "version": "1.0.0",
        "environment": "local"
    },
    "environment_variables": {
        "OTEL_SERVICE_NAME": "my-laravel-app",
        "OTEL_TRACES_EXPORTER": "otlp",
        "OTEL_EXPORTER_OTLP_ENDPOINT": "http://otel-collector:4318"
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
| `OTEL_PHP_AUTOLOAD_ENABLED` | `true` | Enable PHP auto-instrumentation |
| `OTEL_INSTRUMENTATION_ENABLED` | `true` | Enable instrumentation |

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
    
    'exporter' => [
        'otlp' => [
            'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://otel-collector:4318'),
            'protocol' => env('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/protobuf'),
        ],
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
curl http://your-host:13133/  # Collector health check
curl http://your-host:3200/ready  # Tempo health check
curl http://your-host:3000/  # Grafana UI
```

### Service URLs

- **Grafana Dashboard**: http://your-host:3000 (admin/admin)
- **Tempo API**: http://your-host:3200
- **Collector Health**: http://your-host:13133
- **Test Routes**: http://your-host/api/opentelemetry/test

## 📊 Viewing Traces

### Grafana Dashboard

1. Open Grafana at http://your-host:3000
2. Login with admin/admin
3. Go to "Explore" → Select "Tempo" datasource
4. Search for traces by:
   - Service name: `{service.name="your-service-name"}`
   - HTTP operations: `{service.name="your-service-name" && name=~".*GET.*"}`
   - Slow traces: `{service.name="your-service-name" && duration>100ms}`

### Sample Trace Queries

```
# Find all traces for a specific service
{service.name="your-service-name"}
# or
{resource.service.name="your-service-name"}

# Find traces with errors
{service.name="your-service-name" && status=error}
# or
{resource.service.name="your-service-name" && status=error}

# Find slow traces (duration > 100ms)
{service.name="your-service-name" && duration>100ms}
# or
{resource.service.name="your-service-name" && duration>100ms}

# Find traces for specific HTTP methods
{service.name="your-service-name" && name=~".*GET.*"}
# or
{resource.service.name="your-service-name" && name=~".*GET.*"}

# Find traces for specific endpoints
{service.name="your-service-name" && name=~".*api/users.*"}
# or
{resource.service.name="your-service-name" && name=~".*api/users.*"}
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

Check PHP configuration:
```bash
php --ini
php -i | grep opentelemetry
```

**3. Traces not being exported**

Check environment variables:
```bash
php artisan tinker
>>> config('opentelemetry.enabled')
>>> config('opentelemetry.service.name')
>>> config('opentelemetry.exporter.otlp.endpoint')
```

Test with debug mode:
```bash
# Add to .env
OTEL_DEBUG=true
APP_DEBUG=true

# Check logs
tail -f storage/logs/laravel.log
```

**4. High memory usage**

Adjust sampling rate:
```env
OTEL_TRACES_SAMPLER_ARG=0.1  # Sample 10% of traces
```

## 🚀 Production Deployment

### Environment Configuration

```env
# Production settings
OTEL_ENABLED=true
OTEL_SERVICE_NAME=my-production-app
OTEL_ENVIRONMENT=production
OTEL_TRACES_SAMPLER_ARG=0.1  # Sample 10% of traces

# Use production collector
OTEL_EXPORTER_OTLP_ENDPOINT=http://your-production-collector:4318
OTEL_EXPORTER_OTLP_PROTOCOL=http/protobuf

# Disable debug
OTEL_DEBUG=false
```

### Performance Considerations

1. **Sampling**: Use appropriate sampling rates for production (0.1 = 10%)
2. **Batching**: Configure batch processors in collector
3. **Resource Limits**: Set memory and CPU limits
4. **Network**: Use gRPC for better performance
5. **Storage**: Configure appropriate retention policies

## 🛠️ Automated Setup Script

Use the included setup script for quick installation:

```bash
# Make the script executable
chmod +x setup-opentelemetry.sh

# Run the setup script
./setup-opentelemetry.sh
```

The script will:
- ✅ Install the package via Composer
- ✅ Publish all configuration files
- ✅ Add environment variables to .env
- ✅ Update bootstrap/app.php
- ✅ Configure Docker Compose
- ✅ Add test routes (optional)
- ✅ Check PHP extension installation
- ✅ Start observability stack (optional)

### Script Features

- **Interactive**: Prompts for optional components
- **Safe**: Creates backups before modifying files
- **Comprehensive**: Handles all setup steps automatically
- **Colored Output**: Clear status indicators
- **Error Handling**: Stops on errors with helpful messages

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