# Migration Guide: From Custom OpenTelemetry to Package

This guide will help you migrate your existing OpenTelemetry implementation to use the `mumzworld/laravel-opentelemetry` package.

## 🎯 Migration Steps

### Step 1: Install the Package

#### Method A: Via GitHub Repository (Recommended)

1. **Add repository to composer.json:**
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/mumzworld/laravel-opentelemetry"
        }
    ]
}
```

2. **Install the package:**
```bash
cd /Users/sahibmmz/Projects/mmz/backend/ratings-and-reviews
composer require mumzworld/laravel-opentelemetry:^1.0
```

#### Method B: Via Packagist (When Available)
```bash
composer require mumzworld/laravel-opentelemetry
```

### Step 2: Publish Package Files

```bash
php artisan vendor:publish --provider="Mumzworld\LaravelOpenTelemetry\LaravelOpenTelemetryServiceProvider"
```

### Step 3: Update Environment Variables

Add these to your `.env` file:

```env
# OpenTelemetry Configuration
OTEL_ENABLED=true
OTEL_SERVICE_NAME=laravel-ratings-and-reviews
OTEL_SERVICE_VERSION=1.0.0
OTEL_ENVIRONMENT=local

# Trace Export Settings
OTEL_TRACES_EXPORTER=otlp
OTEL_EXPORTER_OTLP_ENDPOINT=http://otel-collector:4318
OTEL_PROPAGATORS=baggage,tracecontext

# PHP Extension Settings
OTEL_PHP_AUTOLOAD_ENABLED=true
OTEL_INSTRUMENTATION_ENABLED=true
OTEL_ATTR_HOOKS_ENABLED=true
```

### Step 4: Update AppServiceProvider

Replace your current `app/Providers/AppServiceProvider.php`:

**Before:**
```php
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\API\Globals;

public function register(): void
{
    $this->app->register(RouteServiceProvider::class);

    $this->app->singleton(TracerProviderInterface::class, function () {
        return Globals::tracerProvider();
    });

    $this->app->singleton(\App\Services\TracerService::class, function ($app) {
        return new \App\Services\TracerService($app->make(TracerProviderInterface::class));
    });
}
```

**After:**
```php
public function register(): void
{
    $this->app->register(RouteServiceProvider::class);
    // TracerService is now automatically registered by the package
}
```

### Step 5: Update TracerService Usage

Update your service imports:

**Before:**
```php
use App\Services\TracerService;
```

**After:**
```php
use Mumzworld\LaravelOpenTelemetry\Services\TracerService;
```

### Step 6: Update Bootstrap Configuration

Replace your `bootstrap/otel.php` with the package version:

```bash
# Remove old file
rm bootstrap/otel.php

# Copy from package
cp vendor/mumzworld/laravel-opentelemetry/src/Bootstrap/otel.php bootstrap/otel.php
```

### Step 7: Update Docker Configuration

**Update docker-compose.yml:**

Replace the hardcoded OpenTelemetry environment variables:

**Before:**
```yaml
app:
  environment:
    OTEL_SERVICE_NAME: laravel-ratings-and-reviews
    OTEL_TRACES_EXPORTER: otlp
    OTEL_EXPORTER_OTLP_ENDPOINT: http://otel-collector:4318
    OTEL_PROPAGATORS: baggage,tracecontext
```

**After:**
```yaml
app:
  environment:
    OTEL_SERVICE_NAME: ${OTEL_SERVICE_NAME:-laravel-ratings-and-reviews}
    OTEL_TRACES_EXPORTER: ${OTEL_TRACES_EXPORTER:-otlp}
    OTEL_EXPORTER_OTLP_ENDPOINT: ${OTEL_EXPORTER_OTLP_ENDPOINT:-http://otel-collector:4318}
    OTEL_PROPAGATORS: ${OTEL_PROPAGATORS:-baggage,tracecontext}
```

**Update Horizon service:**
```yaml
horizon:
  environment:
    OTEL_SERVICE_NAME: ${OTEL_SERVICE_NAME:-laravel-ratings-and-reviews}-horizon
    OTEL_TRACES_EXPORTER: ${OTEL_TRACES_EXPORTER:-otlp}
    OTEL_EXPORTER_OTLP_ENDPOINT: ${OTEL_EXPORTER_OTLP_ENDPOINT:-http://otel-collector:4318}
    OTEL_PROPAGATORS: ${OTEL_PROPAGATORS:-baggage,tracecontext}
```

**Important**: Remove any local path volume mounts if you added them:
```yaml
# Remove this line if present:
# - ../laravel-opentelemetry:/var/www/laravel-opentelemetry
```

### Step 8: Update PHP INI Files

Replace your Docker PHP INI files:

**docker/application/20-otel.ini:**
```ini
; OpenTelemetry PHP extension configuration for automatic tracing
[opentelemetry]
otel.php_autoload_enabled=1
otel.service_name="${OTEL_SERVICE_NAME}"
otel.traces_exporter="${OTEL_TRACES_EXPORTER}"
otel.exporter_otlp_endpoint="${OTEL_EXPORTER_OTLP_ENDPOINT}"
otel.propagators="${OTEL_PROPAGATORS}"
otel.instrumentation_enabled=true
opentelemetry.attr_hooks_enabled=1
```

**docker/horizon/20-otel.ini:**
```ini
; OpenTelemetry PHP extension configuration for Horizon tracing
[opentelemetry]
otel.php_autoload_enabled=1
otel.service_name="${OTEL_SERVICE_NAME}-horizon"
otel.traces_exporter="${OTEL_TRACES_EXPORTER}"
otel.exporter_otlp_endpoint="${OTEL_EXPORTER_OTLP_ENDPOINT}"
otel.propagators="${OTEL_PROPAGATORS}"
otel.instrumentation_enabled=true
opentelemetry.attr_hooks_enabled=1
```

### Step 9: Remove Old Files

```bash
# Remove old TracerService (now provided by package)
rm app/Services/TracerService.php

# Keep your existing OpenTelemetry Docker configs or replace with package versions
# docker/otel-collector/config.yaml (optional - package provides updated version)
# docker/tempo/tempo.yaml (optional)
# docker/grafana/provisioning/ (optional)
```

### Step 10: Test the Migration

```bash
# Rebuild containers
docker-compose down
docker-compose up -d --build

# Test tracing is working
curl http://localhost:7001/api/products/12345678-1234-1234-1234-123456789012/reviews

# Check Grafana for traces
open http://localhost:3002
```

## 🔍 Verification Checklist

- [ ] Package installed via Composer
- [ ] Environment variables added to `.env`
- [ ] AppServiceProvider updated
- [ ] TracerService imports updated
- [ ] Bootstrap file replaced
- [ ] Docker configuration updated
- [ ] PHP INI files updated
- [ ] Application builds and runs
- [ ] Traces appear in Grafana
- [ ] No errors in logs

## 🚨 Breaking Changes

### TracerService Constructor

**Before:**
```php
public function __construct(TracerProviderInterface $provider)
{
    $this->tracer = $provider->getTracer('laravel-ratings-and-reviews');
}
```

**After:**
```php
public function __construct(TracerProviderInterface $provider, string $serviceName = null)
{
    $this->serviceName = $serviceName ?? config('opentelemetry.service.name', 'laravel-app');
    $this->tracer = $provider->getTracer($this->serviceName);
}
```

### Configuration Access

**Before:**
```php
// Hardcoded service name
$tracer = $provider->getTracer('laravel-ratings-and-reviews');
```

**After:**
```php
// Configurable service name
$serviceName = config('opentelemetry.service.name');
$tracer = $provider->getTracer($serviceName);
```

## 🔧 Rollback Plan

If you need to rollback:

1. **Remove package:**
   ```bash
   composer remove mumzworld/laravel-opentelemetry
   ```

2. **Restore old files:**
   ```bash
   git checkout app/Services/TracerService.php
   git checkout app/Providers/AppServiceProvider.php
   git checkout bootstrap/otel.php
   ```

3. **Restore Docker configuration:**
   ```bash
   git checkout docker-compose.yml
   git checkout docker/application/20-otel.ini
   git checkout docker/horizon/20-otel.ini
   ```

## 📞 Support

If you encounter issues during migration:

1. Check the main [README.md](README.md) for troubleshooting
2. Verify all environment variables are set correctly
3. Check Docker container logs: `docker-compose logs otel-collector`
4. Ensure OpenTelemetry PHP extension is installed: `php -m | grep opentelemetry`

## 🚀 GitHub Repository Setup

Before migration, ensure the package is available on GitHub:

1. **Create GitHub repository**: `mumzworld/laravel-opentelemetry`
2. **Initialize and push package code**:
   ```bash
   cd /Users/sahibmmz/Projects/mmz/backend/laravel-opentelemetry
   git init
   git add .
   git commit -m "Initial release v1.0.0"
   git branch -M main
   git remote add origin https://github.com/mumzworld/laravel-opentelemetry.git
   git push -u origin main
   ```

3. **Create release tag**:
   ```bash
   git tag -a v1.0.0 -m "Release version 1.0.0"
   git push origin v1.0.0
   ```

4. **Verify repository access** and ensure it's accessible to your team

## 🎉 Benefits After Migration

- ✅ **Centralized Configuration**: All OpenTelemetry settings in one place
- ✅ **Environment Flexibility**: Easy configuration per environment
- ✅ **Reusability**: Same package can be used across multiple projects
- ✅ **Maintainability**: Package updates provide new features and fixes
- ✅ **Documentation**: Comprehensive guides and examples
- ✅ **Best Practices**: Built-in performance optimizations and security